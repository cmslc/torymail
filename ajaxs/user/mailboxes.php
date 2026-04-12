<?php
require_once __DIR__ . "/../bootstrap.php";

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
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $local_part   = strtolower(trim($_POST['local_part'] ?? ''));
        $domain_id    = intval($_POST['domain_id'] ?? 0);
        $display_name = sanitize($_POST['display_name'] ?? '');
        if (isset($_POST['quota_mb'])) {
            $quota = intval($_POST['quota_mb']) * 1048576;
        } else {
            $quota = intval($_POST['quota'] ?? get_setting('default_quota', '1073741824'));
        }

        $password     = $_POST['password'] ?? '';

        if (empty($local_part) || $domain_id <= 0 || empty($password)) {
            error_response('Email address, domain, and password are required');
        }

        // Validate local part (letters, numbers, dots, hyphens, underscores)
        if (!preg_match('/^[a-z0-9._-]+$/', $local_part)) {
            error_response('Invalid email address format. Use only letters, numbers, dots, hyphens, and underscores.');
        }

        if (mb_strlen($password) < 8) {
            error_response('Password must be at least 8 characters');
        }

        // Verify domain ownership (or shared) and status
        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND (user_id = ? OR is_shared = 1) AND status = 'active'",
            [$domain_id, $getUser['id']]
        );
        if (!$domain) error_response('Domain not found, not active, or access denied');

        // Check mailbox limit per domain
        $mailboxCount = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM mailboxes WHERE domain_id = ?",
            [$domain_id]
        );
        if ($mailboxCount >= $getUser['max_mailboxes_per_domain']) {
            error_response('You have reached the mailbox limit for this domain (' . $getUser['max_mailboxes_per_domain'] . ')');
        }

        $emailAddress = $local_part . '@' . $domain['domain_name'];

        // Check unique
        $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE email_address = ?", [$emailAddress]);
        if ($exists > 0) error_response('This email address already exists');

        $mailboxId = $ToryMail->insert_safe('mailboxes', [
            'user_id'            => $getUser['id'],
            'domain_id'          => $domain_id,
            'email_address'      => $emailAddress,
            'display_name'       => $display_name ?: $local_part,
            'password_encrypted' => encrypt_string($password),
            'password'           => hash_password($password),
            'quota'              => $quota,
            'used_space'         => 0,
            'status'             => 'active',
            'created_at'         => gettime(),
            'updated_at'         => gettime(),
        ]);

        if (!$mailboxId) error_response('Failed to create mailbox');

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getUser['id'],
            'action'     => 'mailbox_add',
            'details'    => 'Created mailbox: ' . $emailAddress,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Mailbox created successfully', ['mailbox_id' => $mailboxId, 'email_address' => $emailAddress]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id   = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        $updateData = ['updated_at' => gettime()];

        if (isset($_POST['display_name'])) {
            $updateData['display_name'] = sanitize($_POST['display_name']);
        }
        if (isset($_POST['quota'])) {
            $updateData['quota'] = max(0, intval($_POST['quota']));
        }
        if (isset($_POST['auto_reply_enabled'])) {
            $updateData['auto_reply_enabled'] = intval($_POST['auto_reply_enabled']) ? 1 : 0;
        }
        if (isset($_POST['auto_reply_subject'])) {
            $updateData['auto_reply_subject'] = sanitize($_POST['auto_reply_subject']);
        }
        if (isset($_POST['auto_reply_message'])) {
            $updateData['auto_reply_message'] = $_POST['auto_reply_message'];
        }
        if (isset($_POST['forwarding_enabled'])) {
            $updateData['forwarding_enabled'] = intval($_POST['forwarding_enabled']) ? 1 : 0;
        }
        if (isset($_POST['forwarding_address'])) {
            $addr = trim($_POST['forwarding_address']);
            if (!empty($addr) && !validate_email($addr)) {
                error_response('Invalid forwarding email address');
            }
            $updateData['forwarding_address'] = $addr ?: null;
        }

        $ToryMail->update_safe('mailboxes', $updateData, 'id = ?', [$mailbox_id]);
        success_response('Mailbox updated successfully');
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        // Cascade delete handles emails and attachments
        $ToryMail->remove_safe('mailboxes', 'id = ?', [$mailbox_id]);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getUser['id'],
            'action'     => 'mailbox_delete',
            'details'    => 'Deleted mailbox: ' . $mailbox['email_address'],
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Mailbox and all associated emails deleted');
        break;

    // -------------------------------------------------------
    // TOGGLE STATUS
    // -------------------------------------------------------
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        $newStatus = ($mailbox['status'] === 'active') ? 'disabled' : 'active';
        $ToryMail->update_safe('mailboxes', ['status' => $newStatus, 'updated_at' => gettime()], 'id = ?', [$mailbox_id]);

        success_response('Mailbox ' . $newStatus, ['mailbox_status' => $newStatus]);
        break;

    // -------------------------------------------------------
    // CHANGE PASSWORD
    // -------------------------------------------------------
    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id   = intval($_POST['mailbox_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');
        if (mb_strlen($new_password) < 8) error_response('Password must be at least 8 characters');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        $ToryMail->update_safe('mailboxes', [
            'password_encrypted' => encrypt_string($new_password),
            'password'           => hash_password($new_password),
            'updated_at'         => gettime(),
        ], 'id = ?', [$mailbox_id]);

        success_response('Mailbox password updated');
        break;

    // -------------------------------------------------------
    // SET AUTO REPLY
    // -------------------------------------------------------
    case 'set_auto_reply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        $enabled = intval($_POST['auto_reply_enabled'] ?? 0) ? 1 : 0;
        $subject = sanitize($_POST['auto_reply_subject'] ?? '');
        $message = $_POST['auto_reply_message'] ?? '';

        $ToryMail->update_safe('mailboxes', [
            'auto_reply_enabled' => $enabled,
            'auto_reply_subject' => $subject,
            'auto_reply_message' => $message,
            'updated_at'         => gettime(),
        ], 'id = ?', [$mailbox_id]);

        success_response('Auto-reply settings updated');
        break;

    // -------------------------------------------------------
    // SET FORWARDING
    // -------------------------------------------------------
    case 'set_forwarding':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        $enabled = intval($_POST['forwarding_enabled'] ?? 0) ? 1 : 0;
        $address = trim($_POST['forwarding_address'] ?? '');

        if ($enabled && !empty($address) && !validate_email($address)) {
            error_response('Invalid forwarding email address');
        }

        $ToryMail->update_safe('mailboxes', [
            'forwarding_enabled' => $enabled,
            'forwarding_address' => $address ?: null,
            'updated_at'         => gettime(),
        ], 'id = ?', [$mailbox_id]);

        success_response('Forwarding settings updated');
        break;

    // -------------------------------------------------------
    // GET API TOKEN
    // -------------------------------------------------------
    case 'get_api_token':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id = intval($_POST['mailbox_id'] ?? 0);
        if ($mailbox_id <= 0) error_response('Invalid mailbox ID');

        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE id = ? AND user_id = ?",
            [$mailbox_id, $getUser['id']]
        );
        if (!$mailbox) error_response('Mailbox not found or access denied', 403);

        // The password_encrypted field is used as API token
        $token = $mailbox['password_encrypted'];

        success_response('OK', ['token' => $token, 'email' => $mailbox['email_address']]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
