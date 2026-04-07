<?php
/**
 * Public mailbox creation — shared domains only, no auth required
 */
require_once __DIR__ . "/../bootstrap.php";

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // CREATE MAILBOX (public, shared domains only)
    // -------------------------------------------------------
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        // Rate limit: 5 mailbox creations per IP per 10 minutes
        rate_limit('public_mailbox_' . get_client_ip(), 5, 600);

        $local_part   = strtolower(trim($_POST['local_part'] ?? ''));
        $domain_id    = intval($_POST['domain_id'] ?? 0);
        $password     = $_POST['password'] ?? '';
        $display_name = sanitize($_POST['display_name'] ?? '');

        if (empty($local_part) || $domain_id <= 0 || empty($password)) {
            error_response(__('public_mailbox_required_fields'));
        }

        // Validate local part
        if (!preg_match('/^[a-z0-9._-]+$/', $local_part)) {
            error_response(__('public_mailbox_invalid_local'));
        }

        if (mb_strlen($local_part) < 3) {
            error_response(__('public_mailbox_local_min'));
        }

        if (mb_strlen($password) < 8) {
            error_response(__('password_min'));
        }

        // Only allow shared domains
        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND is_shared = 1 AND status = 'active'",
            [$domain_id]
        );
        if (!$domain) error_response(__('public_mailbox_domain_not_found'));

        // Check mailbox limit per domain (use global setting)
        $maxPerDomain = intval(get_setting('max_mailboxes_per_domain', 50));
        $mailboxCount = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM mailboxes WHERE domain_id = ?",
            [$domain_id]
        );
        if ($mailboxCount >= $maxPerDomain) {
            error_response(__('public_mailbox_domain_full'));
        }

        $emailAddress = $local_part . '@' . $domain['domain_name'];

        // Check unique
        $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE email_address = ?", [$emailAddress]);
        if ($exists > 0) error_response(__('public_mailbox_exists'));

        $defaultQuota = intval(get_setting('default_quota', '1073741824'));

        $mailboxId = $ToryMail->insert_safe('mailboxes', [
            'user_id'            => null,
            'domain_id'          => $domain_id,
            'email_address'      => $emailAddress,
            'display_name'       => $display_name ?: $local_part,
            'password_encrypted' => encrypt_string($password),
            'quota'              => $defaultQuota,
            'used_space'         => 0,
            'status'             => 'active',
            'created_at'         => gettime(),
            'updated_at'         => gettime(),
        ]);

        if (!$mailboxId) error_response(__('public_mailbox_create_failed'));

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => null,
            'action'     => 'public_mailbox_add',
            'details'    => 'Public mailbox created: ' . $emailAddress,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response(__('public_mailbox_created'), [
            'email_address' => $emailAddress,
        ]);
        break;

    // -------------------------------------------------------
    // LIST SHARED DOMAINS (public)
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
