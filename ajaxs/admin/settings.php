<?php
require_once __DIR__ . "/../bootstrap.php";

// Admin auth check
$token = $_SESSION['admin_login'] ?? $_COOKIE['torymail_admin_token'] ?? null;
if (!$token) error_response('Admin authentication required', 401);

$getAdmin = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND role = 'admin' AND status = 'active'",
    [$token]
);
if (!$getAdmin) error_response('Admin authentication required', 401);

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // SAVE
    // -------------------------------------------------------
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $settingsData = $_POST['settings'] ?? [];

        if (empty($settingsData) || !is_array($settingsData)) {
            error_response('No settings provided');
        }

        // Allowed setting keys (whitelist for security)
        $allowedKeys = [
            'site_name', 'site_url', 'site_favicon', 'timezone',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'max_domains_per_user', 'max_mailboxes_per_domain', 'default_quota',
            'max_attachment_size', 'max_email_size', 'allow_registration',
            'require_email_verification', 'max_login_attempts', 'session_timeout',
            'mail_server_hostname', 'mx_record_value', 'mx_record_priority',
            'default_language',
        ];

        // MB-to-bytes mapping: form field name => actual setting key
        $mbToBytes = [
            'default_quota_mb'       => 'default_quota',
            'max_attachment_size_mb' => 'max_attachment_size',
            'max_email_size_mb'      => 'max_email_size',
        ];

        $saved = 0;
        foreach ($settingsData as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);

            // Convert MB fields to bytes and remap key
            if (isset($mbToBytes[$key])) {
                $key = $mbToBytes[$key];
                $value = intval($value) * 1048576;
            }

            if (!in_array($key, $allowedKeys)) continue;

            $value = is_int($value) ? (string)$value : trim($value);

            // Encrypt sensitive settings
            if ($key === 'smtp_password' && !empty($value)) {
                $value = encrypt_string($value);
            }

            set_setting($key, $value);
            $saved++;
        }

        if ($saved === 0) error_response('No valid settings were provided');

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_settings_save',
            'details'    => 'Admin updated ' . $saved . ' setting(s)',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response("$saved setting(s) saved successfully");
        break;

    // -------------------------------------------------------
    // UPLOAD LOGO
    // -------------------------------------------------------
    case 'upload_logo':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            error_response('No logo file uploaded');
        }

        $file = $_FILES['logo'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            error_response('Invalid file type. Allowed: PNG, JPG, SVG, WebP');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            error_response('Logo must be under 2MB');
        }

        $uploadDir = __DIR__ . '/../../storage/logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Remove old logo
        $oldLogo = get_setting('site_logo', '');
        if ($oldLogo) {
            $oldPath = __DIR__ . '/../../' . $oldLogo;
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $ext = match($mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            default => 'png',
        };
        $filename = 'logo_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            error_response('Failed to save logo file');
        }

        $relativePath = 'storage/logos/' . $filename;
        set_setting('site_logo', $relativePath);

        success_response('Logo uploaded successfully', ['data' => ['logo_url' => base_url($relativePath)]]);
        break;

    // -------------------------------------------------------
    // REMOVE LOGO
    // -------------------------------------------------------
    case 'remove_logo':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $oldLogo = get_setting('site_logo', '');
        if ($oldLogo) {
            $oldPath = __DIR__ . '/../../' . $oldLogo;
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        set_setting('site_logo', '');

        success_response('Logo removed');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
