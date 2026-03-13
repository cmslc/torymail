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
            'site_name', 'site_url',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'max_domains_per_user', 'max_mailboxes_per_domain', 'default_quota',
            'max_attachment_size', 'allow_registration', 'mail_server_hostname',
        ];

        $saved = 0;
        foreach ($settingsData as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if (!in_array($key, $allowedKeys)) continue;

            $value = trim($value);

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

    default:
        error_response('Invalid action', 400);
        break;
}
