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

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

// Helper: generate DNS records for a domain
function generateDnsRecords($domain_name, $verification_token, $dkim_selector, $dkim_public_key) {
    global $settings;
    $mailServer = get_setting('mail_server_hostname', 'mail.' . $domain_name);

    return [
        'verification' => [
            'type'  => 'TXT',
            'host'  => $domain_name,
            'value' => $verification_token,
            'note'  => 'Domain verification record',
        ],
        'mx' => [
            'type'     => 'MX',
            'host'     => $domain_name,
            'value'    => $mailServer,
            'priority' => 10,
            'note'     => 'Mail exchange record',
        ],
        'spf' => [
            'type'  => 'TXT',
            'host'  => $domain_name,
            'value' => 'v=spf1 mx a include:' . $mailServer . ' ~all',
            'note'  => 'SPF record for email authentication',
        ],
        'dkim' => [
            'type'  => 'TXT',
            'host'  => $dkim_selector . '._domainkey.' . $domain_name,
            'value' => 'v=DKIM1; k=rsa; p=' . $dkim_public_key,
            'note'  => 'DKIM record for email signing',
        ],
        'dmarc' => [
            'type'  => 'TXT',
            'host'  => '_dmarc.' . $domain_name,
            'value' => 'v=DMARC1; p=quarantine; rua=mailto:postmaster@' . $domain_name,
            'note'  => 'DMARC policy record',
        ],
    ];
}

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_name = strtolower(trim($_POST['domain_name'] ?? ''));

        if (empty($domain_name)) error_response('Domain name is required');
        if (!validate_domain($domain_name)) error_response('Invalid domain format');

        // Check domain limit
        $domainCount = $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM domains WHERE user_id = ?",
            [$getUser['id']]
        );
        if ($domainCount >= $getUser['max_domains']) {
            error_response('You have reached your maximum domain limit (' . $getUser['max_domains'] . ')');
        }

        // Check unique
        $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM domains WHERE domain_name = ?", [$domain_name]);
        if ($exists > 0) error_response('This domain is already registered');

        $verificationToken = generate_verification_token();
        $dkimSelector = generate_dkim_selector();

        // Placeholder DKIM keys
        $dkimPrivateKey = 'PLACEHOLDER_PRIVATE_KEY_' . bin2hex(random_bytes(32));
        $dkimPublicKey  = 'PLACEHOLDER_PUBLIC_KEY_' . bin2hex(random_bytes(32));

        $domainId = $ToryMail->insert_safe('domains', [
            'user_id'             => $getUser['id'],
            'domain_name'         => $domain_name,
            'status'              => 'pending',
            'verification_token'  => $verificationToken,
            'verification_method' => 'dns_txt',
            'mx_verified'         => 0,
            'spf_verified'        => 0,
            'dkim_verified'       => 0,
            'dmarc_verified'      => 0,
            'dkim_private_key'    => encrypt_string($dkimPrivateKey),
            'dkim_public_key'     => $dkimPublicKey,
            'dkim_selector'       => $dkimSelector,
            'created_at'          => gettime(),
            'updated_at'          => gettime(),
        ]);

        if (!$domainId) error_response('Failed to add domain');

        // Generate DNS records to return
        $dnsRecords = generateDnsRecords($domain_name, $verificationToken, $dkimSelector, $dkimPublicKey);

        // Save DNS records to db
        $mailServer = get_setting('mail_server_hostname', 'mail.' . $domain_name);
        $recordsToInsert = [
            ['domain_id' => $domainId, 'record_type' => 'TXT', 'hostname' => $domain_name, 'value' => $verificationToken, 'created_at' => gettime()],
            ['domain_id' => $domainId, 'record_type' => 'MX', 'hostname' => $domain_name, 'value' => $mailServer, 'priority' => 10, 'created_at' => gettime()],
            ['domain_id' => $domainId, 'record_type' => 'TXT', 'hostname' => $domain_name, 'value' => 'v=spf1 mx a include:' . $mailServer . ' ~all', 'created_at' => gettime()],
            ['domain_id' => $domainId, 'record_type' => 'TXT', 'hostname' => $dkimSelector . '._domainkey.' . $domain_name, 'value' => 'v=DKIM1; k=rsa; p=' . $dkimPublicKey, 'created_at' => gettime()],
            ['domain_id' => $domainId, 'record_type' => 'TXT', 'hostname' => '_dmarc.' . $domain_name, 'value' => 'v=DMARC1; p=quarantine; rua=mailto:postmaster@' . $domain_name, 'created_at' => gettime()],
        ];
        foreach ($recordsToInsert as $rec) {
            $ToryMail->insert_safe('dns_records', $rec);
        }

        // Log activity
        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $getUser['id'],
            'action'     => 'domain_add',
            'details'    => 'Added domain: ' . $domain_name,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Domain added successfully. Please configure the DNS records below.', [
            'domain_id'   => $domainId,
            'dns_records' => $dnsRecords,
        ]);
        break;

    // -------------------------------------------------------
    // VERIFY
    // -------------------------------------------------------
    case 'verify':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_id = intval($_POST['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND user_id = ?",
            [$domain_id, $getUser['id']]
        );
        if (!$domain) error_response('Domain not found or access denied', 403);

        $domainName    = $domain['domain_name'];
        $mailServer    = get_setting('mail_server_hostname', 'mail.' . $domainName);
        $dkimSelector  = $domain['dkim_selector'];
        $dkimPublicKey = $domain['dkim_public_key'];

        $results = [];

        // TXT verification
        $txtVerified = check_dns_record($domainName, 'TXT', $domain['verification_token']);
        $results['txt_verified'] = $txtVerified;

        // MX
        $mxVerified = check_dns_record($domainName, 'MX', $mailServer);
        $results['mx_verified'] = $mxVerified;

        // SPF
        $spfVerified = check_dns_record($domainName, 'TXT', 'v=spf1');
        $results['spf_verified'] = $spfVerified;

        // DKIM
        $dkimHost = $dkimSelector . '._domainkey.' . $domainName;
        $dkimVerified = check_dns_record($dkimHost, 'TXT', 'v=DKIM1');
        $results['dkim_verified'] = $dkimVerified;

        // DMARC
        $dmarcVerified = check_dns_record('_dmarc.' . $domainName, 'TXT', 'v=DMARC1');
        $results['dmarc_verified'] = $dmarcVerified;

        // Update domain
        $updateData = [
            'mx_verified'    => $mxVerified ? 1 : 0,
            'spf_verified'   => $spfVerified ? 1 : 0,
            'dkim_verified'  => $dkimVerified ? 1 : 0,
            'dmarc_verified' => $dmarcVerified ? 1 : 0,
            'updated_at'     => gettime(),
        ];

        if ($txtVerified && $domain['status'] === 'pending') {
            $updateData['status'] = 'active';
            $updateData['verified_at'] = gettime();
        }

        $ToryMail->update_safe('domains', $updateData, 'id = ?', [$domain_id]);

        // Update dns_records verification status
        $ToryMail->update_safe('dns_records', ['last_checked' => gettime()], 'domain_id = ?', [$domain_id]);

        $message = $txtVerified
            ? 'Domain verified successfully'
            : 'TXT verification record not found. Please add the DNS records and try again.';

        success_response($message, ['verification' => $results]);
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $domain_id = intval($_POST['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND user_id = ?",
            [$domain_id, $getUser['id']]
        );
        if (!$domain) error_response('Domain not found or access denied', 403);

        $ToryMail->beginTransaction();
        try {
            // Delete all mailboxes and their emails (cascade handles emails)
            $ToryMail->remove_safe('mailboxes', 'domain_id = ?', [$domain_id]);
            // Delete DNS records
            $ToryMail->remove_safe('dns_records', 'domain_id = ?', [$domain_id]);
            // Delete domain
            $ToryMail->remove_safe('domains', 'id = ?', [$domain_id]);

            $ToryMail->commit();

            $ToryMail->insert_safe('activity_logs', [
                'user_id'    => $getUser['id'],
                'action'     => 'domain_delete',
                'details'    => 'Deleted domain: ' . $domain['domain_name'],
                'ip_address' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => gettime(),
            ]);

            success_response('Domain deleted successfully');
        } catch (Exception $e) {
            $ToryMail->rollBack();
            error_response('Failed to delete domain');
        }
        break;

    // -------------------------------------------------------
    // DNS RECORDS
    // -------------------------------------------------------
    case 'dns_records':
        $domain_id = intval($_GET['domain_id'] ?? 0);
        if ($domain_id <= 0) error_response('Invalid domain ID');

        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND user_id = ?",
            [$domain_id, $getUser['id']]
        );
        if (!$domain) error_response('Domain not found or access denied', 403);

        $dnsRecords = generateDnsRecords(
            $domain['domain_name'],
            $domain['verification_token'],
            $domain['dkim_selector'],
            $domain['dkim_public_key']
        );

        success_response('OK', [
            'domain'      => $domain['domain_name'],
            'status'      => $domain['status'],
            'dns_records' => $dnsRecords,
            'verification_status' => [
                'mx_verified'    => (bool)$domain['mx_verified'],
                'spf_verified'   => (bool)$domain['spf_verified'],
                'dkim_verified'  => (bool)$domain['dkim_verified'],
                'dmarc_verified' => (bool)$domain['dmarc_verified'],
            ],
        ]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
