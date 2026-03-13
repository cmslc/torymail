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
    // LIST
    // -------------------------------------------------------
    case 'list':
        $page     = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, min(100, intval($_GET['per_page'] ?? 20)));
        $status   = trim($_GET['status'] ?? '');
        $search   = trim($_GET['search'] ?? '');

        $where = "1=1";
        $params = [];

        if (!empty($status) && in_array($status, ['pending', 'sending', 'sent', 'failed'])) {
            $where .= " AND q.status = ?";
            $params[] = $status;
        }
        if (!empty($search)) {
            $where .= " AND (q.from_address LIKE ? OR q.subject LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $total = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue q WHERE $where", $params);
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $queue = $ToryMail->get_list_safe(
            "SELECT q.*, m.email_address as mailbox_email
             FROM email_queue q
             JOIN mailboxes m ON q.mailbox_id = m.id
             WHERE $where
             ORDER BY q.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['queue' => $queue, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // RETRY
    // -------------------------------------------------------
    case 'retry':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $queue_id = intval($_POST['queue_id'] ?? 0);
        if ($queue_id <= 0) error_response('Invalid queue ID');

        $entry = $ToryMail->get_row_safe("SELECT * FROM email_queue WHERE id = ?", [$queue_id]);
        if (!$entry) error_response('Queue entry not found');
        if ($entry['status'] !== 'failed') error_response('Can only retry failed entries');

        $ToryMail->update_safe('email_queue', [
            'status'        => 'pending',
            'attempts'      => 0,
            'error_message' => null,
        ], 'id = ?', [$queue_id]);

        success_response('Email queued for retry');
        break;

    // -------------------------------------------------------
    // RETRY ALL FAILED
    // -------------------------------------------------------
    case 'retry_all_failed':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $affected = $ToryMail->update_safe('email_queue', [
            'status'        => 'pending',
            'attempts'      => 0,
            'error_message' => null,
        ], "status = 'failed'", []);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_queue_retry_all',
            'details'    => 'Admin retried all failed emails (' . $affected . ' entries)',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response("$affected failed email(s) queued for retry");
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $queue_id = intval($_POST['queue_id'] ?? 0);
        if ($queue_id <= 0) error_response('Invalid queue ID');

        $entry = $ToryMail->get_row_safe("SELECT * FROM email_queue WHERE id = ?", [$queue_id]);
        if (!$entry) error_response('Queue entry not found');

        $ToryMail->remove_safe('email_queue', 'id = ?', [$queue_id]);
        success_response('Queue entry deleted');
        break;

    // -------------------------------------------------------
    // CLEAR SENT
    // -------------------------------------------------------
    case 'clear_sent':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $affected = $ToryMail->remove_safe('email_queue', "status = 'sent'", []);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_queue_clear_sent',
            'details'    => 'Admin cleared sent queue (' . $affected . ' entries)',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response("$affected sent queue entries cleared");
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
