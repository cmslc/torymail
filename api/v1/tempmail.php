<?php
/**
 * Temporary Mail Public API v1
 *
 * ── No Auth Required (public) ──
 *   GET    /api/v1/domains                     — List available domains
 *   POST   /api/v1/create                      — Create temp mailbox
 *   POST   /api/v1/random                      — Create with random username
 *   GET    /api/v1/inbox/{email}               — List inbox by email address
 *   GET    /api/v1/read/{email}/{id}           — Read email by address + id
 *   DELETE /api/v1/delete/{email}/{id}         — Delete email by address + id
 *
 * ── Token Auth (for programmatic use) ──
 *   GET    /api/v1/inbox?token=xxx             — List inbox by token
 *   GET    /api/v1/read/{id}?token=xxx         — Read email by token + id
 *   POST   /api/v1/send?token=xxx              — Send email from mailbox
 *   GET    /api/v1/mailbox/inbox?token=xxx     — Alias for inbox
 *   GET    /api/v1/mailbox/read/{id}?token=xxx — Alias for read
 *   DELETE /api/v1/delete/{id}?token=xxx       — Delete email by token + id
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Load env
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) { api_error('System not configured', 500); }
$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
    putenv(trim($key) . '=' . trim($value));
}

require_once __DIR__ . '/../../version.php';
require_once __DIR__ . '/../../libs/db.php';
require_once __DIR__ . '/../../libs/helper.php';

$ToryMail = new DB();

$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
if ($settingsRows) foreach ($settingsRows as $row) $settings[$row['setting_key']] = $row['setting_value'];

// ─── Helpers ───────────────────────────────────────────

function api_success($data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function get_api_token() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) return trim($m[1]);
    return trim($_GET['token'] ?? $_POST['token'] ?? '');
}

function get_mailbox_by_token($db, $token) {
    if (empty($token)) return null;
    return $db->get_row_safe(
        "SELECT * FROM mailboxes WHERE password_encrypted = ? AND status = 'active'",
        [$token]
    );
}

function get_mailbox_by_email($db, $email) {
    if (empty($email)) return null;
    return $db->get_row_safe(
        "SELECT m.* FROM mailboxes m JOIN domains d ON m.domain_id = d.id
         WHERE m.email_address = ? AND m.status = 'active'",
        [$email]
    );
}

function resolve_mailbox($db) {
    // Try token first
    $token = get_api_token();
    if ($token) {
        $mb = get_mailbox_by_token($db, $token);
        if ($mb) return $mb;
        api_error('Invalid token', 401);
    }
    api_error('Authentication required. Provide token or use /inbox/{email} endpoints.', 401);
}

function format_email_item($e) {
    return [
        'id'              => (int)$e['id'],
        'from_name'       => decode_mime($e['from_name'] ?? ''),
        'from_address'    => decode_mime($e['from_address'] ?? ''),
        'subject'         => decode_mime($e['subject'] ?? ''),
        'is_read'         => (bool)$e['is_read'],
        'has_attachments' => (bool)$e['has_attachments'],
        'received_at'     => $e['received_at'],
        'created_at'      => $e['created_at'],
    ];
}

function format_email_full($e, $attachments) {
    return [
        'id'              => (int)$e['id'],
        'from_name'       => decode_mime($e['from_name'] ?? ''),
        'from_address'    => decode_mime($e['from_address'] ?? ''),
        'to'              => $e['to_addresses'],
        'subject'         => decode_mime($e['subject'] ?? ''),
        'body_text'       => $e['body_text'],
        'body_html'       => $e['body_html'] ? sanitize_email_html($e['body_html']) : null,
        'is_read'         => true,
        'has_attachments' => (bool)$e['has_attachments'],
        'attachments'     => array_map(function($a) {
            return [
                'id'       => (int)$a['id'],
                'filename' => $a['original_filename'],
                'mime'     => $a['mime_type'],
                'size'     => (int)$a['size'],
            ];
        }, $attachments ?: []),
        'received_at'     => $e['received_at'],
        'created_at'      => $e['created_at'],
    ];
}

// ─── Routing ───────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = trim($_GET['endpoint'] ?? '', '/');
$parts = explode('/', $endpoint);
$action = $parts[0] ?? '';
$param1 = $parts[1] ?? '';
$param2 = $parts[2] ?? '';

switch ($action) {

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/domains
    // ═══════════════════════════════════════════════════════
    case 'domains':
        if ($method !== 'GET') api_error('Method not allowed', 405);
        $domains = $ToryMail->get_list_safe(
            "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC", []
        );
        api_success([
            'domains' => array_map(function($d) {
                return ['id' => (int)$d['id'], 'domain' => $d['domain_name']];
            }, $domains ?: [])
        ]);
        break;

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/create — Create with custom name
    // ═══════════════════════════════════════════════════════
    case 'create':
        if ($method !== 'POST') api_error('Method not allowed', 405);
        rate_limit('api_create_' . get_client_ip(), 10, 600);

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = strtolower(trim($input['name'] ?? $input['local_part'] ?? ''));
        $domainId = intval($input['domain_id'] ?? 0);
        $domainName = trim($input['domain'] ?? '');

        if ($domainId <= 0 && !empty($domainName)) {
            $dl = $ToryMail->get_row_safe("SELECT id FROM domains WHERE domain_name = ? AND is_shared = 1 AND status = 'active'", [$domainName]);
            if ($dl) $domainId = (int)$dl['id'];
        }

        if (empty($name)) api_error('Parameter "name" is required');
        if ($domainId <= 0) api_error('Parameter "domain_id" or "domain" is required');
        if (!preg_match('/^[a-z0-9._-]+$/', $name)) api_error('Invalid name. Use only a-z, 0-9, dots, hyphens, underscores.');
        if (mb_strlen($name) < 3) api_error('Name must be at least 3 characters');

        $domain = $ToryMail->get_row_safe("SELECT * FROM domains WHERE id = ? AND is_shared = 1 AND status = 'active'", [$domainId]);
        if (!$domain) api_error('Domain not found or not available');

        $emailAddress = $name . '@' . $domain['domain_name'];

        // Existing
        $existing = $ToryMail->get_row_safe("SELECT id, email_address, password_encrypted, created_at FROM mailboxes WHERE email_address = ? AND status = 'active'", [$emailAddress]);
        if ($existing) {
            api_success(['email' => $existing['email_address'], 'token' => $existing['password_encrypted'], 'created_at' => $existing['created_at']]);
            break;
        }

        // Limit
        $maxPD = intval(get_setting('max_mailboxes_per_domain', 50));
        $cnt = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE domain_id = ?", [$domainId]);
        if ($cnt >= $maxPD) api_error('Domain has reached the mailbox limit');

        $token = bin2hex(random_bytes(32));
        $mbId = $ToryMail->insert_safe('mailboxes', [
            'user_id' => null, 'domain_id' => $domainId, 'email_address' => $emailAddress,
            'display_name' => $name, 'password_encrypted' => $token,
            'password' => hash_password($token),
            'quota' => intval(get_setting('default_quota', '1073741824')),
            'used_space' => 0, 'status' => 'active', 'created_at' => gettime(), 'updated_at' => gettime(),
        ]);
        if (!$mbId) api_error('Failed to create mailbox', 500);

        $ToryMail->insert_safe('activity_logs', [
            'user_id' => null, 'action' => 'api_mailbox_create',
            'details' => 'API: ' . $emailAddress, 'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'created_at' => gettime(),
        ]);

        api_success(['email' => $emailAddress, 'token' => $token, 'created_at' => gettime()], 201);
        break;

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/random — Create with random username
    // ═══════════════════════════════════════════════════════
    case 'random':
        if ($method !== 'POST' && $method !== 'GET') api_error('Method not allowed', 405);
        rate_limit('api_random_' . get_client_ip(), 10, 600);

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $domainId = intval($input['domain_id'] ?? $_GET['domain_id'] ?? 0);
        $domainName = trim($input['domain'] ?? $_GET['domain'] ?? '');

        // If no domain specified, pick first available
        if ($domainId <= 0 && empty($domainName)) {
            $first = $ToryMail->get_row_safe("SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC LIMIT 1", []);
            if ($first) { $domainId = (int)$first['id']; $domainName = $first['domain_name']; }
        }
        if ($domainId <= 0 && !empty($domainName)) {
            $dl = $ToryMail->get_row_safe("SELECT id FROM domains WHERE domain_name = ? AND is_shared = 1 AND status = 'active'", [$domainName]);
            if ($dl) $domainId = (int)$dl['id'];
        }

        $domain = $ToryMail->get_row_safe("SELECT * FROM domains WHERE id = ? AND is_shared = 1 AND status = 'active'", [$domainId]);
        if (!$domain) api_error('No available domain');

        // Generate random name
        $attempts = 0;
        do {
            $name = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 4) . rand(1000, 9999);
            $emailAddress = $name . '@' . $domain['domain_name'];
            $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE email_address = ?", [$emailAddress]);
            $attempts++;
        } while ($exists > 0 && $attempts < 10);

        if ($exists > 0) api_error('Could not generate unique email. Try again.', 500);

        $token = bin2hex(random_bytes(32));
        $mbId = $ToryMail->insert_safe('mailboxes', [
            'user_id' => null, 'domain_id' => $domainId, 'email_address' => $emailAddress,
            'display_name' => $name, 'password_encrypted' => $token,
            'password' => hash_password($token),
            'quota' => intval(get_setting('default_quota', '1073741824')),
            'used_space' => 0, 'status' => 'active', 'created_at' => gettime(), 'updated_at' => gettime(),
        ]);
        if (!$mbId) api_error('Failed to create mailbox', 500);

        $ToryMail->insert_safe('activity_logs', [
            'user_id' => null, 'action' => 'api_mailbox_random',
            'details' => 'API random: ' . $emailAddress, 'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'created_at' => gettime(),
        ]);

        api_success(['email' => $emailAddress, 'token' => $token, 'created_at' => gettime()], 201);
        break;

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/inbox/{email}  — No auth, by email
    // GET /api/v1/inbox?token=   — By token
    // ═══════════════════════════════════════════════════════
    case 'inbox':
        if ($method !== 'GET') api_error('Method not allowed', 405);

        // Resolve mailbox: by email in URL or by token
        if (!empty($param1) && strpos($param1, '@') !== false) {
            $mailbox = get_mailbox_by_email($ToryMail, $param1);
            if (!$mailbox) api_error('Mailbox not found. Create it first via /create or /random.', 404);
        } elseif (!empty($param1) && is_numeric($param1)) {
            // /inbox/{id} — by mailbox id (backwards compat)
            api_error('Use /inbox/{email} or /inbox?token=xxx', 400);
        } else {
            $mailbox = resolve_mailbox($ToryMail);
        }

        $emails = $ToryMail->get_list_safe(
            "SELECT id, from_address, from_name, subject, is_read, has_attachments, received_at, created_at
             FROM emails WHERE mailbox_id = ? AND folder = 'inbox' ORDER BY created_at DESC LIMIT 50",
            [$mailbox['id']]
        );

        api_success([
            'email'  => $mailbox['email_address'],
            'count'  => count($emails ?: []),
            'emails' => array_map('format_email_item', $emails ?: []),
        ]);
        break;

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/read/{email}/{id}  — No auth
    // GET /api/v1/read/{id}?token=   — By token
    // ═══════════════════════════════════════════════════════
    case 'read':
        if ($method !== 'GET') api_error('Method not allowed', 405);

        if (!empty($param1) && strpos($param1, '@') !== false) {
            // /read/{email}/{id}
            $mailbox = get_mailbox_by_email($ToryMail, $param1);
            if (!$mailbox) api_error('Mailbox not found', 404);
            $emailId = intval($param2);
        } else {
            // /read/{id}?token=
            $mailbox = resolve_mailbox($ToryMail);
            $emailId = intval($param1);
        }

        if ($emailId <= 0) api_error('Email ID is required');

        $email = $ToryMail->get_row_safe("SELECT * FROM emails WHERE id = ? AND mailbox_id = ?", [$emailId, $mailbox['id']]);
        if (!$email) api_error('Email not found', 404);

        if (!$email['is_read']) $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$emailId]);

        $attachments = $ToryMail->get_list_safe("SELECT id, original_filename, mime_type, size FROM email_attachments WHERE email_id = ?", [$emailId]);

        api_success(['email' => format_email_full($email, $attachments)]);
        break;

    // ═══════════════════════════════════════════════════════
    // DELETE /api/v1/delete/{email}/{id}  — No auth
    // DELETE /api/v1/delete/{id}?token=   — By token
    // ═══════════════════════════════════════════════════════
    case 'delete':
        if ($method !== 'DELETE' && $method !== 'POST') api_error('Method not allowed', 405);

        if (!empty($param1) && strpos($param1, '@') !== false) {
            $mailbox = get_mailbox_by_email($ToryMail, $param1);
            if (!$mailbox) api_error('Mailbox not found', 404);
            $emailId = intval($param2);
        } else {
            $mailbox = resolve_mailbox($ToryMail);
            $emailId = intval($param1);
        }

        if ($emailId <= 0) api_error('Email ID is required');

        $email = $ToryMail->get_row_safe("SELECT id FROM emails WHERE id = ? AND mailbox_id = ?", [$emailId, $mailbox['id']]);
        if (!$email) api_error('Email not found', 404);

        $ToryMail->remove_safe('emails', 'id = ?', [$emailId]);
        api_success(['message' => 'Email deleted']);
        break;

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/check/{email} — Check if mailbox exists
    // ═══════════════════════════════════════════════════════
    case 'check':
        if ($method !== 'GET') api_error('Method not allowed', 405);
        if (empty($param1)) api_error('Email address is required: /check/{email}');

        $mb = get_mailbox_by_email($ToryMail, $param1);
        $emailCount = 0;
        if ($mb) {
            $emailCount = (int)$ToryMail->get_value_safe("SELECT COUNT(*) FROM emails WHERE mailbox_id = ? AND folder = 'inbox'", [$mb['id']]);
        }

        api_success([
            'email'      => $param1,
            'exists'     => (bool)$mb,
            'email_count'=> $emailCount,
            'created_at' => $mb ? $mb['created_at'] : null,
        ]);
        break;

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/send — Send email from mailbox (token required)
    // ═══════════════════════════════════════════════════════
    case 'send':
        if ($method !== 'POST') api_error('Method not allowed', 405);

        $mailbox = resolve_mailbox($ToryMail);

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $to = $input['to'] ?? '';
        $subject = trim($input['subject'] ?? '');
        $body = trim($input['body'] ?? $input['body_html'] ?? '');
        $cc = $input['cc'] ?? [];
        $bcc = $input['bcc'] ?? [];
        $reply_to = trim($input['reply_to'] ?? '');
        $priority = $input['priority'] ?? 'normal';

        if (empty($to)) api_error('Parameter "to" is required (recipient email)');
        if (empty($subject)) api_error('Parameter "subject" is required');
        if (empty($body)) api_error('Parameter "body" is required (HTML or plain text)');

        // Normalize to array
        if (is_string($to)) $to = array_map('trim', explode(',', $to));
        if (is_string($cc)) $cc = $cc ? array_map('trim', explode(',', $cc)) : [];
        if (is_string($bcc)) $bcc = $bcc ? array_map('trim', explode(',', $bcc)) : [];

        // Validate recipients
        foreach ($to as $addr) {
            if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) api_error('Invalid recipient: ' . $addr);
        }

        // Rate limit sending
        rate_limit('api_send_' . $mailbox['id'], 20, 3600);

        // Load EmailEngine
        require_once __DIR__ . '/../../libs/EmailEngine.php';
        $engine = new EmailEngine($ToryMail, $settings);

        // Handle file attachments
        $attachments = [];
        if (!empty($_FILES['attachments'])) {
            $storageDir = realpath(__DIR__ . '/../../') . '/storage/attachments/' . date('Y/m');
            if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);
            $files = $_FILES['attachments'];
            $count = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < $count; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $err = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                if ($err !== UPLOAD_ERR_OK || empty($name)) continue;
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $dest = $storageDir . '/att_' . time() . '_' . $safeName;
                if (move_uploaded_file($tmp, $dest)) {
                    $attachments[] = ['path' => $dest, 'filename' => $name, 'name' => $name, 'mime' => mime_content_type($dest) ?: 'application/octet-stream'];
                }
            }
        }

        $result = $engine->send($mailbox['email_address'], $to, $subject, $body, [
            'cc' => $cc,
            'bcc' => $bcc,
            'reply_to' => $reply_to,
            'priority' => $priority,
            'attachments' => $attachments,
        ]);

        if (!$result['success']) {
            api_error($result['error'] ?? 'Failed to send email', 500);
        }

        api_success([
            'message'    => 'Email queued for delivery',
            'from'       => $mailbox['email_address'],
            'to'         => $to,
            'subject'    => $subject,
            'email_id'   => $result['email_id'] ?? null,
            'queue_id'   => $result['queue_id'] ?? null,
            'message_id' => $result['message_id'] ?? null,
        ]);
        break;

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/mailbox/inbox — Alias for inbox (token auth)
    // GET /api/v1/mailbox/read/{id} — Alias for read (token auth)
    // ═══════════════════════════════════════════════════════
    case 'mailbox':
        if ($method !== 'GET') api_error('Method not allowed', 405);

        $subAction = $param1;
        $subId = intval($param2);

        if ($subAction === 'inbox') {
            $mailbox = resolve_mailbox($ToryMail);
            $emails = $ToryMail->get_list_safe(
                "SELECT id, from_address, from_name, subject, is_read, has_attachments, received_at, created_at
                 FROM emails WHERE mailbox_id = ? AND folder = 'inbox' ORDER BY created_at DESC LIMIT 50",
                [$mailbox['id']]
            );
            api_success([
                'email'  => $mailbox['email_address'],
                'count'  => count($emails ?: []),
                'emails' => array_map('format_email_item', $emails ?: []),
            ]);

        } elseif ($subAction === 'read' && $subId > 0) {
            $mailbox = resolve_mailbox($ToryMail);
            $email = $ToryMail->get_row_safe("SELECT * FROM emails WHERE id = ? AND mailbox_id = ?", [$subId, $mailbox['id']]);
            if (!$email) api_error('Email not found', 404);
            if (!$email['is_read']) $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$subId]);
            $attachments = $ToryMail->get_list_safe("SELECT id, original_filename, mime_type, size FROM email_attachments WHERE email_id = ?", [$subId]);
            api_success(['email' => format_email_full($email, $attachments)]);

        } else {
            api_error('Use /mailbox/inbox or /mailbox/read/{id}', 400);
        }
        break;

    // ═══════════════════════════════════════════════════════
    default:
        api_error('Unknown endpoint. See /auth/api-docs for documentation.', 404);
        break;
}
