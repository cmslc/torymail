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
        $search   = trim($_GET['search'] ?? '');
        $status   = trim($_GET['status'] ?? '');

        $where = "1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (d.domain_name LIKE ? OR u.email LIKE ? OR u.fullname LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($status) && in_array($status, ['pending', 'active', 'suspended'])) {
            $where .= " AND d.status = ?";
            $params[] = $status;
        }

        $total = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM domains d JOIN users u ON d.user_id = u.id WHERE $where",
            $params
        );
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $domains = $ToryMail->get_list_safe(
            "SELECT d.*, u.fullname as owner_name, u.email as owner_email,
                    (SELECT COUNT(*) FROM mailboxes WHERE domain_id = d.id) as mailbox_count
             FROM domains d
             JOIN users u ON d.user_id = u.id
             WHERE $where
             ORDER BY d.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['domains' => $domains, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // VERIFY (admin force-verify)
    // -------------------------------------------------------
    case 'verify':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_id = intval($_POST['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe("SELECT * FROM domains WHERE id = ?", [$domain_id]);
        if (!$domain) error_response('Domain not found');

        $ToryMail->update_safe('domains', [
            'status'         => 'active',
            'verified_at'    => gettime(),
            'mx_verified'    => 1,
            'spf_verified'   => 1,
            'dkim_verified'  => 1,
            'dmarc_verified' => 1,
            'updated_at'     => gettime(),
        ], 'id = ?', [$domain_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_domain_verify',
            'details'    => 'Admin force-verified domain: ' . $domain['domain_name'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Domain verified successfully');
        break;

    // -------------------------------------------------------
    // TOGGLE STATUS
    // -------------------------------------------------------
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_id = intval($_POST['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe("SELECT * FROM domains WHERE id = ?", [$domain_id]);
        if (!$domain) error_response('Domain not found');

        $newStatus = ($domain['status'] === 'suspended') ? 'active' : 'suspended';

        // Only allow toggling between active and suspended
        if ($domain['status'] === 'pending' && $newStatus === 'active') {
            error_response('Cannot activate an unverified domain. Use force-verify instead.');
        }

        $ToryMail->update_safe('domains', ['status' => $newStatus, 'updated_at' => gettime()], 'id = ?', [$domain_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_domain_' . $newStatus,
            'details'    => 'Admin ' . $newStatus . ' domain: ' . $domain['domain_name'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Domain ' . $newStatus, ['status' => $newStatus]);
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_id = intval($_POST['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe("SELECT * FROM domains WHERE id = ?", [$domain_id]);
        if (!$domain) error_response('Domain not found');

        $ToryMail->beginTransaction();
        try {
            $ToryMail->remove_safe('mailboxes', 'domain_id = ?', [$domain_id]);
            $ToryMail->remove_safe('dns_records', 'domain_id = ?', [$domain_id]);
            $ToryMail->remove_safe('domains', 'id = ?', [$domain_id]);

            $ToryMail->commit();

            $ToryMail->insert_safe('activity_logs', [
                'user_id'    => $getAdmin['id'],
                'action'     => 'admin_domain_delete',
                'details'    => 'Admin deleted domain: ' . $domain['domain_name'],
                'ip_address' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => gettime(),
            ]);

            success_response('Domain and all associated data deleted');
        } catch (Exception $e) {
            $ToryMail->rollBack();
            error_response('Failed to delete domain');
        }
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
