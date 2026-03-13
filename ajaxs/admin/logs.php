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
        $page      = max(1, intval($_GET['page'] ?? 1));
        $per_page  = max(1, min(100, intval($_GET['per_page'] ?? 50)));
        $user_id   = intval($_GET['user_id'] ?? 0);
        $actionFilter = trim($_GET['action_filter'] ?? '');
        $date_from = trim($_GET['date_from'] ?? '');
        $date_to   = trim($_GET['date_to'] ?? '');
        $search    = trim($_GET['search'] ?? '');

        $where = "1=1";
        $params = [];

        if ($user_id > 0) {
            $where .= " AND al.user_id = ?";
            $params[] = $user_id;
        }
        if (!empty($actionFilter)) {
            $where .= " AND al.action LIKE ?";
            $params[] = '%' . $actionFilter . '%';
        }
        if (!empty($date_from)) {
            $where .= " AND al.created_at >= ?";
            $params[] = $date_from . ' 00:00:00';
        }
        if (!empty($date_to)) {
            $where .= " AND al.created_at <= ?";
            $params[] = $date_to . ' 23:59:59';
        }
        if (!empty($search)) {
            $where .= " AND (al.action LIKE ? OR al.details LIKE ? OR al.ip_address LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $total = $ToryMail->get_value_safe("SELECT COUNT(*) FROM activity_logs al WHERE $where", $params);
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $logs = $ToryMail->get_list_safe(
            "SELECT al.*, u.fullname as user_name, u.email as user_email
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE $where
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['logs' => $logs, 'pagination' => $pagination]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
