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
    // LIST
    // -------------------------------------------------------
    case 'list':
        $page     = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, min(100, intval($_GET['per_page'] ?? 20)));
        $search   = trim($_GET['search'] ?? '');
        $domain_id = intval($_GET['domain_id'] ?? 0);
        $status   = trim($_GET['status'] ?? '');

        $where = "1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (m.email_address LIKE ? OR m.display_name LIKE ? OR u.email LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if ($domain_id > 0) {
            $where .= " AND m.domain_id = ?";
            $params[] = $domain_id;
        }
        if (!empty($status) && in_array($status, ['active', 'disabled'])) {
            $where .= " AND m.status = ?";
            $params[] = $status;
        }

        $total = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM mailboxes m JOIN users u ON m.user_id = u.id WHERE $where",
            $params
        );
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $mailboxes = $ToryMail->get_list_safe(
            "SELECT m.*, u.fullname as owner_name, u.email as owner_email,
                    d.domain_name,
                    (SELECT COUNT(*) FROM emails WHERE mailbox_id = m.id) as email_count
             FROM mailboxes m
             JOIN users u ON m.user_id = u.id
             JOIN domains d ON m.domain_id = d.id
             WHERE $where
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['mailboxes' => $mailboxes, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // TOGGLE STATUS
    // -------------------------------------------------------
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ?", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox not found');

        $newStatus = ($mailbox['status'] === 'active') ? 'disabled' : 'active';
        $ToryMail->update_safe('mailboxes', ['status' => $newStatus, 'updated_at' => gettime()], 'id = ?', [$mailbox_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_mailbox_toggle',
            'details'    => 'Admin ' . $newStatus . ' mailbox: ' . $mailbox['email_address'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Mailbox ' . $newStatus, ['status' => $newStatus]);
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ?", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox not found');

        // Cascade delete handles emails
        $ToryMail->remove_safe('mailboxes', 'id = ?', [$mailbox_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_mailbox_delete',
            'details'    => 'Admin deleted mailbox: ' . $mailbox['email_address'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Mailbox and all associated emails deleted');
        break;

    // -------------------------------------------------------
    // RESET PASSWORD
    // -------------------------------------------------------
    case 'reset_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id   = intval($_POST['mailbox_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');
        if (mb_strlen($new_password) < 8) error_response('Password must be at least 8 characters');

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ?", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox not found');

        $ToryMail->update_safe('mailboxes', [
            'password_encrypted' => encrypt_string($new_password),
            'updated_at'         => gettime(),
        ], 'id = ?', [$mailbox_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_mailbox_password_reset',
            'details'    => 'Admin reset password for mailbox: ' . $mailbox['email_address'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Mailbox password reset successfully');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
