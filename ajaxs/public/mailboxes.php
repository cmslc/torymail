<?php
/**
 * Public temp-mail API — shared domains only, no auth required
 */
require_once __DIR__ . "/../bootstrap.php";

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // GET EMAIL — create or access a mailbox instantly
    // -------------------------------------------------------
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        // Rate limit: 10 per IP per 10 minutes
        rate_limit('public_mailbox_' . get_client_ip(), 10, 600);

        $local_part = strtolower(trim($_POST['local_part'] ?? ''));
        $domain_id  = intval($_POST['domain_id'] ?? 0);

        if (empty($local_part) || $domain_id <= 0) {
            error_response(__('temp_mail_username_required'));
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $local_part)) {
            error_response(__('public_mailbox_invalid_local'));
        }

        if (mb_strlen($local_part) < 3) {
            error_response(__('public_mailbox_local_min'));
        }

        // Only shared domains
        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND is_shared = 1 AND status = 'active'",
            [$domain_id]
        );
        if (!$domain) error_response(__('public_mailbox_domain_not_found'));

        $emailAddress = $local_part . '@' . $domain['domain_name'];

        // Check if mailbox already exists
        $mailbox = $ToryMail->get_row_safe(
            "SELECT * FROM mailboxes WHERE email_address = ? AND status = 'active'",
            [$emailAddress]
        );

        if ($mailbox) {
            // Mailbox exists — open it
            $_SESSION['temp_mailbox_id'] = $mailbox['id'];
            $_SESSION['temp_mailbox_email'] = $mailbox['email_address'];
            success_response('OK', [
                'email_address' => $mailbox['email_address'],
                'mailbox_id'    => $mailbox['id'],
            ]);
            break;
        }

        // Check limit
        $maxPerDomain = intval(get_setting('max_mailboxes_per_domain', 50));
        $mailboxCount = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM mailboxes WHERE domain_id = ?",
            [$domain_id]
        );
        if ($mailboxCount >= $maxPerDomain) {
            error_response(__('public_mailbox_domain_full'));
        }

        // Create new mailbox (auto-generated password)
        $autoPassword = bin2hex(random_bytes(16));
        $defaultQuota = intval(get_setting('default_quota', '1073741824'));

        $mailboxId = $ToryMail->insert_safe('mailboxes', [
            'user_id'            => null,
            'domain_id'          => $domain_id,
            'email_address'      => $emailAddress,
            'display_name'       => $local_part,
            'password_encrypted' => encrypt_string($autoPassword),
            'password'           => hash_password($autoPassword),
            'quota'              => $defaultQuota,
            'used_space'         => 0,
            'status'             => 'active',
            'created_at'         => gettime(),
            'updated_at'         => gettime(),
        ]);

        if (!$mailboxId) error_response(__('public_mailbox_create_failed'));

        $_SESSION['temp_mailbox_id'] = $mailboxId;
        $_SESSION['temp_mailbox_email'] = $emailAddress;

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => null,
            'action'     => 'temp_mailbox_create',
            'details'    => 'Temp mailbox created: ' . $emailAddress,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response(__('public_mailbox_created'), [
            'email_address' => $emailAddress,
            'mailbox_id'    => $mailboxId,
        ]);
        break;

    // -------------------------------------------------------
    // INBOX — fetch emails for current temp mailbox
    // -------------------------------------------------------
    case 'inbox':
        $mailboxId = intval($_SESSION['temp_mailbox_id'] ?? 0);
        if ($mailboxId <= 0) error_response('No active mailbox', 401);

        $emails = $ToryMail->get_list_safe(
            "SELECT id, from_address, from_name, subject, is_read, has_attachments, received_at, created_at
             FROM emails
             WHERE mailbox_id = ? AND folder = 'inbox'
             ORDER BY created_at DESC
             LIMIT 50",
            [$mailboxId]
        );

        // Decode MIME encoded headers
        if ($emails) {
            foreach ($emails as &$e) {
                $e['from_name'] = decode_mime($e['from_name'] ?? '');
                $e['from_address'] = decode_mime($e['from_address'] ?? '');
                $e['subject'] = decode_mime($e['subject'] ?? '');
            }
            unset($e);
        }

        success_response('OK', ['emails' => $emails ?: []]);
        break;

    // -------------------------------------------------------
    // READ — get a specific email
    // -------------------------------------------------------
    case 'read':
        $mailboxId = intval($_SESSION['temp_mailbox_id'] ?? 0);
        if ($mailboxId <= 0) error_response('No active mailbox', 401);

        $emailId = intval($_GET['id'] ?? 0);
        if ($emailId <= 0) error_response('Invalid email ID');

        $email = $ToryMail->get_row_safe(
            "SELECT * FROM emails WHERE id = ? AND mailbox_id = ?",
            [$emailId, $mailboxId]
        );
        if (!$email) error_response('Email not found', 404);

        // Mark as read
        if (!$email['is_read']) {
            $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$emailId]);
        }

        // Get attachments
        $attachments = $ToryMail->get_list_safe(
            "SELECT id, original_filename, mime_type, size FROM email_attachments WHERE email_id = ?",
            [$emailId]
        );

        $email['attachments'] = $attachments ?: [];

        // Decode MIME encoded headers
        $email['from_name'] = decode_mime($email['from_name'] ?? '');
        $email['from_address'] = decode_mime($email['from_address'] ?? '');
        $email['subject'] = decode_mime($email['subject'] ?? '');

        // Sanitize HTML body
        if (!empty($email['body_html'])) {
            $email['body_html'] = sanitize_email_html($email['body_html']);
        }

        success_response('OK', ['email' => $email]);
        break;

    // -------------------------------------------------------
    // DELETE — delete a specific email
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailboxId = intval($_SESSION['temp_mailbox_id'] ?? 0);
        if ($mailboxId <= 0) error_response('No active mailbox', 401);

        $emailId = intval($_POST['email_id'] ?? 0);
        if ($emailId <= 0) error_response('Invalid email ID');

        $email = $ToryMail->get_row_safe(
            "SELECT id FROM emails WHERE id = ? AND mailbox_id = ?",
            [$emailId, $mailboxId]
        );
        if (!$email) error_response('Email not found', 404);

        $ToryMail->remove_safe('emails', 'id = ?', [$emailId]);
        success_response('Email deleted');
        break;

    // -------------------------------------------------------
    // LIST SHARED DOMAINS
    // -------------------------------------------------------
    case 'domains':
        $domains = $ToryMail->get_list_safe(
            "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC",
            []
        );
        success_response('OK', ['domains' => $domains ?: []]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
