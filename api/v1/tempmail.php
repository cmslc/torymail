<?php
/**
 * Temporary Mail Public API v1
 *
 * Stateless REST API — uses token-based auth (no sessions).
 * Each mailbox gets a unique token on creation for subsequent requests.
 *
 * Endpoints:
 *   GET    /api/v1/domains              — List available domains
 *   POST   /api/v1/create               — Create temp mailbox → returns token
 *   GET    /api/v1/inbox?token=xxx      — List inbox emails
 *   GET    /api/v1/read/{id}?token=xxx  — Read a specific email
 *   DELETE /api/v1/delete/{id}?token=xxx — Delete a specific email
 */

// Bootstrap
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load env
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    api_error('System not configured', 500);
}
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

// Load settings
$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
if ($settingsRows) {
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// ─── Helpers ───
function api_success($data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function get_api_token() {
    // Check header first, then query param
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return trim($m[1]);
    }
    return trim($_GET['token'] ?? $_POST['token'] ?? '');
}

function get_mailbox_by_token($ToryMail, $token) {
    if (empty($token)) api_error('Missing API token. Provide via Authorization: Bearer <token> header or ?token= parameter.', 401);
    $mailbox = $ToryMail->get_row_safe(
        "SELECT * FROM mailboxes WHERE password_encrypted = ? AND status = 'active' AND user_id IS NULL",
        [$token]
    );
    if (!$mailbox) api_error('Invalid or expired token', 401);
    return $mailbox;
}

// ─── Routing ───
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = trim($_GET['endpoint'] ?? '', '/');

// Parse: endpoint might be "read/123" or "delete/123"
$parts = explode('/', $endpoint);
$action = $parts[0] ?? '';
$resourceId = intval($parts[1] ?? 0);

switch ($action) {

    // ═══════════════════════════════════════════════════════════
    // GET /api/v1/domains — List available shared domains
    // ═══════════════════════════════════════════════════════════
    case 'domains':
        if ($method !== 'GET') api_error('Method not allowed', 405);

        $domains = $ToryMail->get_list_safe(
            "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC",
            []
        );

        api_success([
            'domains' => array_map(function($d) {
                return ['id' => (int)$d['id'], 'domain' => $d['domain_name']];
            }, $domains ?: [])
        ]);
        break;

    // ═══════════════════════════════════════════════════════════
    // POST /api/v1/create — Create a temporary mailbox
    // ═══════════════════════════════════════════════════════════
    case 'create':
        if ($method !== 'POST') api_error('Method not allowed', 405);

        // Rate limit
        rate_limit('api_create_' . get_client_ip(), 10, 600);

        // Accept JSON or form data
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = strtolower(trim($input['name'] ?? $input['local_part'] ?? ''));
        $domainId = intval($input['domain_id'] ?? 0);
        $domainName = trim($input['domain'] ?? '');

        // Allow domain by name or by id
        if ($domainId <= 0 && !empty($domainName)) {
            $domLookup = $ToryMail->get_row_safe(
                "SELECT id FROM domains WHERE domain_name = ? AND is_shared = 1 AND status = 'active'",
                [$domainName]
            );
            if ($domLookup) $domainId = (int)$domLookup['id'];
        }

        if (empty($name)) api_error('Parameter "name" is required (email username)');
        if ($domainId <= 0) api_error('Parameter "domain_id" or "domain" is required');

        if (!preg_match('/^[a-z0-9._-]+$/', $name)) {
            api_error('Invalid name. Use only lowercase letters, numbers, dots, hyphens, underscores.');
        }
        if (mb_strlen($name) < 3) api_error('Name must be at least 3 characters');

        $domain = $ToryMail->get_row_safe(
            "SELECT * FROM domains WHERE id = ? AND is_shared = 1 AND status = 'active'",
            [$domainId]
        );
        if (!$domain) api_error('Domain not found or not available');

        $emailAddress = $name . '@' . $domain['domain_name'];

        // If exists, return existing token
        $existing = $ToryMail->get_row_safe(
            "SELECT id, email_address, password_encrypted, created_at FROM mailboxes WHERE email_address = ? AND status = 'active'",
            [$emailAddress]
        );
        if ($existing) {
            api_success([
                'email'      => $existing['email_address'],
                'token'      => $existing['password_encrypted'],
                'created_at' => $existing['created_at'],
            ]);
            break;
        }

        // Check limit
        $maxPerDomain = intval(get_setting('max_mailboxes_per_domain', 50));
        $count = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE domain_id = ?", [$domainId]);
        if ($count >= $maxPerDomain) api_error('This domain has reached the mailbox limit');

        // Create — token is stored as password_encrypted for API auth
        $token = bin2hex(random_bytes(32));
        $defaultQuota = intval(get_setting('default_quota', '1073741824'));

        $mailboxId = $ToryMail->insert_safe('mailboxes', [
            'user_id'            => null,
            'domain_id'          => $domainId,
            'email_address'      => $emailAddress,
            'display_name'       => $name,
            'password_encrypted' => $token,
            'quota'              => $defaultQuota,
            'used_space'         => 0,
            'status'             => 'active',
            'created_at'         => gettime(),
            'updated_at'         => gettime(),
        ]);

        if (!$mailboxId) api_error('Failed to create mailbox', 500);

        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => null,
            'action'     => 'api_mailbox_create',
            'details'    => 'API mailbox created: ' . $emailAddress,
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        api_success([
            'email'      => $emailAddress,
            'token'      => $token,
            'created_at' => gettime(),
        ], 201);
        break;

    // ═══════════════════════════════════════════════════════════
    // GET /api/v1/inbox — List emails in mailbox
    // ═══════════════════════════════════════════════════════════
    case 'inbox':
        if ($method !== 'GET') api_error('Method not allowed', 405);

        $mailbox = get_mailbox_by_token($ToryMail, get_api_token());

        $emails = $ToryMail->get_list_safe(
            "SELECT id, from_address, from_name, subject, is_read, has_attachments, received_at, created_at
             FROM emails WHERE mailbox_id = ? AND folder = 'inbox'
             ORDER BY created_at DESC LIMIT 50",
            [$mailbox['id']]
        );

        $result = [];
        foreach ($emails ?: [] as $e) {
            $result[] = [
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

        api_success([
            'email'  => $mailbox['email_address'],
            'count'  => count($result),
            'emails' => $result,
        ]);
        break;

    // ═══════════════════════════════════════════════════════════
    // GET /api/v1/read/{id} — Read a specific email
    // ═══════════════════════════════════════════════════════════
    case 'read':
        if ($method !== 'GET') api_error('Method not allowed', 405);
        if ($resourceId <= 0) api_error('Email ID is required in URL: /api/v1/read/{id}');

        $mailbox = get_mailbox_by_token($ToryMail, get_api_token());

        $email = $ToryMail->get_row_safe(
            "SELECT * FROM emails WHERE id = ? AND mailbox_id = ?",
            [$resourceId, $mailbox['id']]
        );
        if (!$email) api_error('Email not found', 404);

        // Mark as read
        if (!$email['is_read']) {
            $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$resourceId]);
        }

        // Attachments
        $attachments = $ToryMail->get_list_safe(
            "SELECT id, original_filename, mime_type, size FROM email_attachments WHERE email_id = ?",
            [$resourceId]
        );

        api_success([
            'email' => [
                'id'              => (int)$email['id'],
                'from_name'       => decode_mime($email['from_name'] ?? ''),
                'from_address'    => decode_mime($email['from_address'] ?? ''),
                'to'              => $email['to_addresses'],
                'subject'         => decode_mime($email['subject'] ?? ''),
                'body_text'       => $email['body_text'],
                'body_html'       => $email['body_html'] ? sanitize_email_html($email['body_html']) : null,
                'is_read'         => true,
                'has_attachments' => (bool)$email['has_attachments'],
                'attachments'     => array_map(function($a) {
                    return [
                        'id'       => (int)$a['id'],
                        'filename' => $a['original_filename'],
                        'mime'     => $a['mime_type'],
                        'size'     => (int)$a['size'],
                    ];
                }, $attachments ?: []),
                'received_at'     => $email['received_at'],
                'created_at'      => $email['created_at'],
            ],
        ]);
        break;

    // ═══════════════════════════════════════════════════════════
    // DELETE /api/v1/delete/{id} — Delete a specific email
    // ═══════════════════════════════════════════════════════════
    case 'delete':
        if ($method !== 'DELETE' && $method !== 'POST') api_error('Method not allowed', 405);
        if ($resourceId <= 0) api_error('Email ID is required in URL: /api/v1/delete/{id}');

        $mailbox = get_mailbox_by_token($ToryMail, get_api_token());

        $email = $ToryMail->get_row_safe(
            "SELECT id FROM emails WHERE id = ? AND mailbox_id = ?",
            [$resourceId, $mailbox['id']]
        );
        if (!$email) api_error('Email not found', 404);

        $ToryMail->remove_safe('emails', 'id = ?', [$resourceId]);
        api_success(['message' => 'Email deleted']);
        break;

    // ═══════════════════════════════════════════════════════════
    // Default
    // ═══════════════════════════════════════════════════════════
    default:
        api_error('Unknown endpoint. Available: domains, create, inbox, read/{id}, delete/{id}', 404);
        break;
}
