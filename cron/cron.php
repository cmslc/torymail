<?php
/**
 * Torymail Cron Job Runner
 * Run this every minute: * * * * * php /path/to/torymail/cron/cron.php
 */

define('CRON_MODE', true);

// Load environment
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "Not installed.\n";
    exit(1);
}

$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
    putenv(trim($key) . '=' . trim($value));
}

require_once __DIR__ . '/../version.php';
require_once __DIR__ . '/../libs/db.php';
require_once __DIR__ . '/../libs/helper.php';
require_once __DIR__ . '/../libs/EmailEngine.php';

$ToryMail = new DB();

// Load settings
$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

echo "[" . date('Y-m-d H:i:s') . "] Cron started\n";

// ============================================================
// 1. Process Email Queue
// ============================================================
echo "Processing email queue...\n";
$engine = new EmailEngine($ToryMail, $settings);
$result = $engine->processQueue(50);
echo "  Sent: {$result['sent']}, Failed: {$result['failed']}\n";

// ============================================================
// 2. Clean up expired password reset tokens
// ============================================================
echo "Cleaning expired tokens...\n";
$cleaned = $ToryMail->remove_safe('password_resets', 'expires_at < NOW() OR used = 1');
echo "  Removed: {$cleaned}\n";

// ============================================================
// 3. Clean old sent queue entries (older than 7 days)
// ============================================================
echo "Cleaning old queue entries...\n";
$cleaned = $ToryMail->remove_safe('email_queue', "status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
echo "  Removed: {$cleaned}\n";

// ============================================================
// 4. Auto-empty trash (older than 30 days)
// ============================================================
echo "Auto-emptying old trash...\n";
$old_trash = $ToryMail->get_list_safe(
    "SELECT id, mailbox_id, size FROM emails WHERE folder = 'trash' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)",
    []
);
foreach ($old_trash as $email) {
    // Delete attachments
    $attachments = $ToryMail->get_list_safe("SELECT * FROM email_attachments WHERE email_id = ?", [$email['id']]);
    foreach ($attachments as $att) {
        $file_path = __DIR__ . '/../' . $att['storage_path'];
        if (file_exists($file_path)) @unlink($file_path);
    }
    $ToryMail->remove_safe('email_attachments', 'email_id = ?', [$email['id']]);
    $ToryMail->remove_safe('email_label_map', 'email_id = ?', [$email['id']]);
    $ToryMail->remove_safe('emails', 'id = ?', [$email['id']]);
    $ToryMail->decrement_safe('mailboxes', 'used_space', $email['size'], 'id = ?', [$email['mailbox_id']]);
}
echo "  Removed: " . count($old_trash) . " emails\n";

// ============================================================
// 5. Update user storage statistics
// ============================================================
echo "Updating storage stats...\n";
$ToryMail->raw_query("
    UPDATE users u SET storage_used = (
        SELECT COALESCE(SUM(m.used_space), 0)
        FROM mailboxes m WHERE m.user_id = u.id
    )
");
echo "  Done\n";

// ============================================================
// 6. Check domain DNS records (every hour - check timestamp)
// ============================================================
$last_dns_check = $ToryMail->get_value_safe(
    "SELECT setting_value FROM settings WHERE setting_key = 'last_dns_check'", []
);
if (!$last_dns_check || strtotime($last_dns_check) < strtotime('-1 hour')) {
    echo "Checking domain DNS records...\n";

    $domains = $ToryMail->get_list_safe(
        "SELECT * FROM domains WHERE status IN ('pending', 'active')", []
    );

    $mail_hostname = $settings['mail_server_hostname'] ?? '';

    foreach ($domains as $domain) {
        $updates = [];

        // Check MX record
        $mx_verified = check_dns_record($domain['domain_name'], 'MX', $mail_hostname);
        $updates['mx_verified'] = $mx_verified ? 1 : 0;

        // Check SPF record
        $spf_verified = check_dns_record($domain['domain_name'], 'TXT', 'v=spf1');
        $updates['spf_verified'] = $spf_verified ? 1 : 0;

        // Check DKIM record
        if ($domain['dkim_selector']) {
            $dkim_verified = check_dns_record($domain['dkim_selector'] . '._domainkey.' . $domain['domain_name'], 'TXT', 'v=DKIM1');
            $updates['dkim_verified'] = $dkim_verified ? 1 : 0;
        }

        // Check DMARC record
        $dmarc_verified = check_dns_record('_dmarc.' . $domain['domain_name'], 'TXT', 'v=DMARC1');
        $updates['dmarc_verified'] = $dmarc_verified ? 1 : 0;

        // Check TXT verification
        if ($domain['status'] === 'pending' && $domain['verification_token']) {
            $txt_verified = check_dns_record($domain['domain_name'], 'TXT', $domain['verification_token']);
            if ($txt_verified) {
                $updates['status'] = 'active';
                $updates['verified_at'] = gettime();
            }
        }

        $ToryMail->update_safe('domains', $updates, 'id = ?', [$domain['id']]);
        echo "  {$domain['domain_name']}: MX=" . ($updates['mx_verified'] ? 'OK' : 'NO') . " SPF=" . ($updates['spf_verified'] ? 'OK' : 'NO') . "\n";
    }

    set_setting('last_dns_check', gettime());
}

// ============================================================
// 7. Auto-delete expired temp mailboxes
// ============================================================
$expiryHours = intval(get_setting('temp_mailbox_expiry_hours', '24'));
if ($expiryHours > 0) {
    echo "Cleaning expired temp mailboxes ({$expiryHours}h)...\n";
    $expired = $ToryMail->get_list_safe(
        "SELECT id, email_address FROM mailboxes WHERE user_id IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)",
        [$expiryHours]
    );
    foreach ($expired as $mb) {
        // Delete attachments files
        $atts = $ToryMail->get_list_safe(
            "SELECT ea.storage_path FROM email_attachments ea JOIN emails e ON ea.email_id = e.id WHERE e.mailbox_id = ?",
            [$mb['id']]
        );
        foreach ($atts as $att) {
            $file_path = __DIR__ . '/../' . $att['storage_path'];
            if (file_exists($file_path)) @unlink($file_path);
        }
        // Cascade delete handles emails + attachments via FK
        $ToryMail->remove_safe('mailboxes', 'id = ?', [$mb['id']]);
    }
    echo "  Removed: " . count($expired) . " temp mailboxes\n";
}

// ============================================================
// 8. Clean old activity logs (older than 90 days)
// ============================================================
echo "Cleaning old logs...\n";
$cleaned = $ToryMail->remove_safe('activity_logs', 'created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
echo "  Removed: {$cleaned}\n";

echo "[" . date('Y-m-d H:i:s') . "] Cron completed\n";
