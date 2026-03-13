<?php
/**
 * Download email attachment
 */
require_once __DIR__ . "/../bootstrap.php";

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) {
    http_response_code(401);
    die('Authentication required');
}

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) {
    http_response_code(401);
    die('Authentication required');
}

$att_id = intval($_GET['id'] ?? 0);
if ($att_id <= 0) {
    http_response_code(400);
    die('Invalid attachment ID');
}

// Fetch attachment and verify ownership
$att = $ToryMail->get_row_safe("
    SELECT a.*, e.mailbox_id
    FROM email_attachments a
    JOIN emails e ON a.email_id = e.id
    JOIN mailboxes m ON e.mailbox_id = m.id
    WHERE a.id = ? AND m.user_id = ?
", [$att_id, $getUser['id']]);

if (!$att) {
    http_response_code(404);
    die('Attachment not found');
}

$file_path = __DIR__ . '/../../' . $att['storage_path'];
if (!file_exists($file_path)) {
    http_response_code(404);
    die('File not found');
}

// Send file
$mime = $att['mime_type'] ?: 'application/octet-stream';
$filename = $att['original_filename'] ?: $att['filename'];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');

readfile($file_path);
exit;
