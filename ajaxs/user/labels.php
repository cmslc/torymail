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
    // LIST
    // -------------------------------------------------------
    case 'list':
        $labels = $ToryMail->get_list_safe(
            "SELECT el.*, COUNT(elm.email_id) as email_count
             FROM email_labels el
             LEFT JOIN email_label_map elm ON el.id = elm.label_id
             WHERE el.user_id = ?
             GROUP BY el.id
             ORDER BY el.name ASC",
            [$getUser['id']]
        );

        success_response('OK', ['labels' => $labels]);
        break;

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $name  = sanitize($_POST['name'] ?? '');
        $color = sanitize($_POST['color'] ?? '#6c757d');

        if (empty($name)) error_response('Label name is required');

        // Validate color format
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#6c757d';
        }

        // Check unique per user
        $exists = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM email_labels WHERE user_id = ? AND name = ?",
            [$getUser['id'], $name]
        );
        if ($exists > 0) error_response('A label with this name already exists');

        $labelId = $ToryMail->insert_safe('email_labels', [
            'user_id'    => $getUser['id'],
            'name'       => $name,
            'color'      => $color,
            'created_at' => gettime(),
        ]);

        if (!$labelId) error_response('Failed to create label');
        success_response('Label created', ['label_id' => $labelId]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $label_id = intval($_POST['label_id'] ?? 0);
        if ($label_id <= 0) error_response('Invalid label ID');

        $label = $ToryMail->get_row_safe(
            "SELECT * FROM email_labels WHERE id = ? AND user_id = ?",
            [$label_id, $getUser['id']]
        );
        if (!$label) error_response('Label not found or access denied', 403);

        $updateData = [];
        if (isset($_POST['name'])) {
            $name = sanitize($_POST['name']);
            if (empty($name)) error_response('Label name is required');

            // Check unique (exclude current)
            $exists = $ToryMail->get_value_safe(
                "SELECT COUNT(*) FROM email_labels WHERE user_id = ? AND name = ? AND id != ?",
                [$getUser['id'], $name, $label_id]
            );
            if ($exists > 0) error_response('A label with this name already exists');
            $updateData['name'] = $name;
        }
        if (isset($_POST['color'])) {
            $color = sanitize($_POST['color']);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $updateData['color'] = $color;
            }
        }

        if (empty($updateData)) error_response('No changes provided');

        $ToryMail->update_safe('email_labels', $updateData, 'id = ?', [$label_id]);
        success_response('Label updated');
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $label_id = intval($_POST['label_id'] ?? 0);
        if ($label_id <= 0) error_response('Invalid label ID');

        $label = $ToryMail->get_row_safe(
            "SELECT * FROM email_labels WHERE id = ? AND user_id = ?",
            [$label_id, $getUser['id']]
        );
        if (!$label) error_response('Label not found or access denied', 403);

        // Remove from email_label_map first (cascade should handle, but explicit)
        $ToryMail->remove_safe('email_label_map', 'label_id = ?', [$label_id]);
        $ToryMail->remove_safe('email_labels', 'id = ?', [$label_id]);

        success_response('Label deleted');
        break;

    // -------------------------------------------------------
    // ASSIGN (add label to emails)
    // -------------------------------------------------------
    case 'assign':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $label_id  = intval($_POST['label_id'] ?? 0);
        $email_ids = $_POST['email_ids'] ?? '';

        if ($label_id <= 0 || empty($email_ids)) error_response('Label ID and email IDs are required');

        // Verify label ownership
        $label = $ToryMail->get_row_safe(
            "SELECT * FROM email_labels WHERE id = ? AND user_id = ?",
            [$label_id, $getUser['id']]
        );
        if (!$label) error_response('Label not found or access denied', 403);

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $assigned = 0;
        foreach ($ids as $emailId) {
            // Verify email ownership
            $email = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$emailId, $getUser['id']]
            );
            if (!$email) continue;

            // Check if already assigned
            $exists = $ToryMail->get_value_safe(
                "SELECT COUNT(*) FROM email_label_map WHERE email_id = ? AND label_id = ?",
                [$emailId, $label_id]
            );
            if ($exists == 0) {
                $ToryMail->insert_safe('email_label_map', [
                    'email_id' => $emailId,
                    'label_id' => $label_id,
                ]);
                $assigned++;
            }
        }

        success_response("Label assigned to $assigned email(s)");
        break;

    // -------------------------------------------------------
    // REMOVE (remove label from emails)
    // -------------------------------------------------------
    case 'remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $label_id  = intval($_POST['label_id'] ?? 0);
        $email_ids = $_POST['email_ids'] ?? '';

        if ($label_id <= 0 || empty($email_ids)) error_response('Label ID and email IDs are required');

        // Verify label ownership
        $label = $ToryMail->get_row_safe(
            "SELECT * FROM email_labels WHERE id = ? AND user_id = ?",
            [$label_id, $getUser['id']]
        );
        if (!$label) error_response('Label not found or access denied', 403);

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $removed = 0;
        foreach ($ids as $emailId) {
            // Verify email ownership
            $email = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$emailId, $getUser['id']]
            );
            if (!$email) continue;

            $ToryMail->remove_safe('email_label_map', 'email_id = ? AND label_id = ?', [$emailId, $label_id]);
            $removed++;
        }

        success_response("Label removed from $removed email(s)");
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
