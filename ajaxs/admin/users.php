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
        $role     = trim($_GET['role'] ?? '');
        $status   = trim($_GET['status'] ?? '');

        $where = "1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (u.fullname LIKE ? OR u.email LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($role) && in_array($role, ['user', 'admin'])) {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }
        if (!empty($status) && in_array($status, ['active', 'banned', 'inactive'])) {
            $where .= " AND u.status = ?";
            $params[] = $status;
        }

        $total = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users u WHERE $where", $params);
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $users = $ToryMail->get_list_safe(
            "SELECT u.id, u.fullname, u.email, u.role, u.status, u.timezone,
                    u.max_domains, u.max_mailboxes_per_domain, u.storage_quota, u.storage_used,
                    u.last_activity, u.created_at,
                    (SELECT COUNT(*) FROM domains WHERE user_id = u.id) as domain_count,
                    (SELECT COUNT(*) FROM mailboxes WHERE user_id = u.id) as mailbox_count
             FROM users u
             WHERE $where
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['users' => $users, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $fullname = sanitize($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? 'user', ['user', 'admin']) ? $_POST['role'] : 'user';
        $status   = in_array($_POST['status'] ?? 'active', ['active', 'banned', 'inactive']) ? $_POST['status'] : 'active';
        $max_domains = intval($_POST['max_domains'] ?? get_setting('max_domains_per_user', '5'));
        $max_mailboxes = intval($_POST['max_mailboxes_per_domain'] ?? get_setting('max_mailboxes_per_domain', '50'));
        // Form sends storage_quota_mb in MB, convert to bytes
        if (isset($_POST['storage_quota_mb'])) {
            $storage_quota = intval($_POST['storage_quota_mb']) * 1048576;
        } else {
            $storage_quota = intval($_POST['storage_quota'] ?? get_setting('default_quota', '1073741824'));
        }

        if (empty($fullname) || empty($email) || empty($password)) {
            error_response('Full name, email, and password are required');
        }
        if (!validate_email($email)) error_response('Invalid email format');
        if (mb_strlen($password) < 8) error_response('Password must be at least 8 characters');

        $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
        if ($exists > 0) error_response('Email address is already registered');

        $userId = $ToryMail->insert_safe('users', [
            'fullname'                 => $fullname,
            'email'                    => $email,
            'password'                 => hash_password($password),
            'role'                     => $role,
            'status'                   => $status,
            'timezone'                 => 'UTC',
            'max_domains'              => $max_domains,
            'max_mailboxes_per_domain' => $max_mailboxes,
            'storage_quota'            => $storage_quota,
            'created_at'               => gettime(),
            'updated_at'               => gettime(),
        ]);

        if (!$userId) error_response('Failed to create user');

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_user_add',
            'details'    => 'Admin created user: ' . $email,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('User created successfully', ['user_id' => $userId]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) error_response('Invalid user ID');

        $user = $ToryMail->get_row_safe("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) error_response('User not found');

        $updateData = ['updated_at' => gettime()];

        if (isset($_POST['fullname'])) {
            $updateData['fullname'] = sanitize($_POST['fullname']);
        }
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if (!validate_email($email)) error_response('Invalid email format');
            // Check unique (exclude current)
            $exists = $ToryMail->get_value_safe(
                "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?",
                [$email, $user_id]
            );
            if ($exists > 0) error_response('Email already in use');
            $updateData['email'] = $email;
        }
        if (isset($_POST['role']) && in_array($_POST['role'], ['user', 'admin'])) {
            $updateData['role'] = $_POST['role'];
        }
        if (isset($_POST['status']) && in_array($_POST['status'], ['active', 'banned', 'inactive'])) {
            $updateData['status'] = $_POST['status'];
        }
        if (isset($_POST['max_domains'])) {
            $updateData['max_domains'] = max(0, intval($_POST['max_domains']));
        }
        if (isset($_POST['max_mailboxes_per_domain'])) {
            $updateData['max_mailboxes_per_domain'] = max(0, intval($_POST['max_mailboxes_per_domain']));
        }
        if (isset($_POST['storage_quota_mb'])) {
            $updateData['storage_quota'] = max(0, intval($_POST['storage_quota_mb'])) * 1048576;
        } elseif (isset($_POST['storage_quota'])) {
            $updateData['storage_quota'] = max(0, intval($_POST['storage_quota']));
        }

        $ToryMail->update_safe('users', $updateData, 'id = ?', [$user_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_user_edit',
            'details'    => 'Admin edited user ID: ' . $user_id,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('User updated successfully');
        break;

    // -------------------------------------------------------
    // TOGGLE STATUS
    // -------------------------------------------------------
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) error_response('Invalid user ID');
        if ($user_id === $getAdmin['id']) error_response('Cannot change your own status');

        $user = $ToryMail->get_row_safe("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) error_response('User not found');

        $newStatus = ($user['status'] === 'active') ? 'banned' : 'active';
        $ToryMail->update_safe('users', ['status' => $newStatus, 'updated_at' => gettime()], 'id = ?', [$user_id]);

        // If banning, clear their token to force logout
        if ($newStatus === 'banned') {
            $ToryMail->update_safe('users', ['token' => null], 'id = ?', [$user_id]);
        }

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_user_' . ($newStatus === 'banned' ? 'ban' : 'unban'),
            'details'    => 'Admin ' . ($newStatus === 'banned' ? 'banned' : 'unbanned') . ' user: ' . $user['email'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('User ' . ($newStatus === 'banned' ? 'banned' : 'activated'), ['user_status' => $newStatus]);
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) error_response('Invalid user ID');
        if ($user_id === $getAdmin['id']) error_response('Cannot delete your own account');

        $user = $ToryMail->get_row_safe("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) error_response('User not found');

        // Cascade delete handles domains, mailboxes, emails, contacts, etc.
        $ToryMail->remove_safe('users', 'id = ?', [$user_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_user_delete',
            'details'    => 'Admin deleted user: ' . $user['email'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('User and all associated data deleted');
        break;

    // -------------------------------------------------------
    // RESET PASSWORD
    // -------------------------------------------------------
    case 'reset_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $user_id      = intval($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($user_id <= 0) error_response('Invalid user ID');
        if (mb_strlen($new_password) < 8) error_response('Password must be at least 8 characters');

        $user = $ToryMail->get_row_safe("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) error_response('User not found');

        $ToryMail->update_safe('users', [
            'password'   => hash_password($new_password),
            'updated_at' => gettime(),
        ], 'id = ?', [$user_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getAdmin['id'],
            'action'     => 'admin_password_reset',
            'details'    => 'Admin reset password for user: ' . $user['email'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Password reset successfully');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
