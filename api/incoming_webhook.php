<?php
/**
 * Incoming Email Webhook
 * For services like Mailgun, SendGrid, AWS SES that POST incoming emails
 */

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    http_response_code(503);
    exit;
}

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

// Verify webhook token
$webhook_token = $settings['webhook_token'] ?? '';
$provided_token = $_GET['token'] ?? $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '';

if ($webhook_token && $provided_token !== $webhook_token) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$engine = new EmailEngine($ToryMail, $settings);

// Handle different webhook formats
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($content_type, 'multipart/form-data') !== false) {
    // Mailgun / SendGrid format
    $raw_email = $_POST['body-mime'] ?? $_POST['email'] ?? '';

    if (empty($raw_email) && isset($_FILES['email'])) {
        $raw_email = file_get_contents($_FILES['email']['tmp_name']);
    }
} elseif (stripos($content_type, 'application/json') !== false) {
    // JSON webhook format
    $payload = json_decode(file_get_contents('php://input'), true);
    $raw_email = $payload['raw_email'] ?? $payload['mime'] ?? '';

    // AWS SES SNS notification
    if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
        $message = json_decode($payload['Message'], true);
        $raw_email = $message['content'] ?? '';
    }
} else {
    // Raw email body
    $raw_email = file_get_contents('php://input');
}

if (empty(trim($raw_email))) {
    http_response_code(400);
    echo json_encode(['error' => 'No email content']);
    exit;
}

$result = $engine->receiveEmail($raw_email);

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'email_id' => $result['email_id']]);
} else {
    http_response_code(422);
    echo json_encode(['error' => $result['error']]);
}
