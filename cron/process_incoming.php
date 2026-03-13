<?php
/**
 * Process incoming email piped from Postfix/mail server
 * Usage in Postfix virtual transport:
 *   torymail unix - n n - - pipe
 *   flags=DRhu user=www-data argv=/usr/bin/php /path/to/torymail/cron/process_incoming.php
 */

define('CRON_MODE', true);

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) exit(1);

$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
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

// Read raw email from stdin
$raw_email = '';
$stdin = fopen('php://stdin', 'r');
while (!feof($stdin)) {
    $raw_email .= fread($stdin, 8192);
}
fclose($stdin);

if (empty(trim($raw_email))) {
    error_log("Torymail: Empty email received");
    exit(1);
}

// Process the email
$engine = new EmailEngine($ToryMail, $settings);
$result = $engine->receiveEmail($raw_email);

if ($result['success']) {
    error_log("Torymail: Email received and stored (ID: {$result['email_id']}, Folder: {$result['folder']})");
    exit(0);
} else {
    error_log("Torymail: Failed to process email: {$result['error']}");
    exit(1);
}
