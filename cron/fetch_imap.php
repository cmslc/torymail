<?php
/**
 * Fetch emails from IMAP (Dovecot) into database
 * Run via cron every minute: * * * * * php /var/www/torymail/cron/fetch_imap.php
 */

define('CRON_MODE', true);

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) exit(1);

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

$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$engine = new EmailEngine($ToryMail, $settings);

// Get all active mailboxes
$mailboxes = $ToryMail->get_list_safe(
    "SELECT m.*, d.domain_name FROM mailboxes m
     JOIN domains d ON m.domain_id = d.id
     WHERE m.status = 'active' AND d.status = 'active'",
    []
);

$mail_storage = getenv('MAIL_STORAGE_PATH') ?: '/var/mail/vhosts';
$total_imported = 0;

foreach ($mailboxes as $mb) {
    $local_part = explode('@', $mb['email_address'])[0];
    $domain = $mb['domain_name'];
    $maildir_new = "{$mail_storage}/{$domain}/{$local_part}/new";

    if (!is_dir($maildir_new)) continue;

    $files = glob("{$maildir_new}/*");
    if (empty($files)) continue;

    foreach ($files as $file) {
        $raw_email = file_get_contents($file);
        if (empty(trim($raw_email))) continue;

        $basename = basename($file);

        // Extract message-id for dedup
        if (preg_match('/^Message-I[Dd]:\s*(.+)$/mi', $raw_email, $mid)) {
            $msg_id = trim($mid[1]);
            $existing = $ToryMail->get_row_safe(
                "SELECT id FROM emails WHERE mailbox_id = ? AND message_id = ?",
                [$mb['id'], $msg_id]
            );
            if ($existing) {
                $cur_dir = str_replace('/new', '/cur', $maildir_new);
                @rename($file, $cur_dir . '/' . $basename . ':2,S');
                continue;
            }
        }

        // Process email
        $result = $engine->receiveEmail($raw_email);

        if ($result['success']) {
            // Move to cur/ (mark as seen by Dovecot)
            $cur_dir = str_replace('/new', '/cur', $maildir_new);
            @rename($file, $cur_dir . '/' . $basename . ':2,S');

            $total_imported++;
            echo "  Imported: {$mb['email_address']} - {$basename}\n";
        } else {
            echo "  Failed: {$mb['email_address']} - {$result['error']}\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] IMAP fetch done. Imported: {$total_imported}\n";
