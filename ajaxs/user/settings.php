<?php
session_start();

// Load environment
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System not configured']);
    exit;
}
$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
    putenv(trim($key) . '=' . trim($value));
}

require_once __DIR__ . '/../../libs/db.php';
require_once __DIR__ . '/../../libs/helper.php';

$ToryMail = new DB();

$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
if ($settingsRows) {
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // UPDATE PROFILE
    // -------------------------------------------------------
    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $fullname = sanitize($_POST['fullname'] ?? '');
        $timezone = sanitize($_POST['timezone'] ?? '');

        if (empty($fullname)) error_response('Full name is required');

        $updateData = [
            'fullname'   => $fullname,
            'updated_at' => gettime(),
        ];

        if (!empty($timezone)) {
            // Validate timezone
            $validTimezones = timezone_identifiers_list();
            if (in_array($timezone, $validTimezones)) {
                $updateData['timezone'] = $timezone;
            }
        }

        $ToryMail->update_safe('users', $updateData, 'id = ?', [$getUser['id']]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getUser['id'],
            'action'     => 'profile_update',
            'details'    => 'Updated profile information',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Profile updated successfully');
        break;

    // -------------------------------------------------------
    // CHANGE PASSWORD
    // -------------------------------------------------------
    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password)) {
            error_response('Old and new passwords are required');
        }
        if (mb_strlen($new_password) < 8) {
            error_response('New password must be at least 8 characters');
        }
        if ($new_password !== $confirm_password) {
            error_response('New passwords do not match');
        }

        // Verify old password
        if (!verify_password($old_password, $getUser['password'])) {
            error_response('Current password is incorrect');
        }

        $ToryMail->update_safe('users', [
            'password'   => hash_password($new_password),
            'updated_at' => gettime(),
        ], 'id = ?', [$getUser['id']]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getUser['id'],
            'action'     => 'password_change',
            'details'    => 'Changed account password',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Password changed successfully');
        break;

    // -------------------------------------------------------
    // UPDATE SIGNATURE
    // -------------------------------------------------------
    case 'update_signature':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        $signature  = $_POST['signature'] ?? '';

        if ($mailbox_id > 0) {
            // Update signature for a specific mailbox
            // We store per-mailbox signature in the mailbox's display area
            // Since mailboxes table doesn't have a signature column, we use the user's signature field
            // with a mailbox-specific approach. For simplicity, store as user-level signature.
            // If mailbox_id is given, we verify ownership but still update the user signature.
            $mailbox = $ToryMail->get_row_safe(
                "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
                [$mailbox_id, $getUser['id']]
            );
            if (!$mailbox) error_response('Mailbox not found or access denied', 403);
        }

        $ToryMail->update_safe('users', [
            'signature'  => $signature,
            'updated_at' => gettime(),
        ], 'id = ?', [$getUser['id']]);

        success_response('Signature updated');
        break;

    // -------------------------------------------------------
    // UPLOAD AVATAR
    // -------------------------------------------------------
    case 'upload_avatar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            error_response('No avatar file uploaded or upload error');
        }

        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            error_response('Invalid file type. Allowed: JPEG, PNG, GIF, WebP');
        }
        if ($file['size'] > $maxSize) {
            error_response('File too large. Maximum size is 2MB');
        }

        $uploadDir = __DIR__ . '/../../storage/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Delete old avatar if exists
        if (!empty($getUser['avatar'])) {
            $oldPath = __DIR__ . '/../../' . $getUser['avatar'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $getUser['id'] . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_response('Failed to save avatar');
        }

        $avatarPath = 'storage/avatars/' . $filename;
        $ToryMail->update_safe('users', [
            'avatar'     => $avatarPath,
            'updated_at' => gettime(),
        ], 'id = ?', [$getUser['id']]);

        success_response('Avatar uploaded', ['avatar_url' => base_url($avatarPath)]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
