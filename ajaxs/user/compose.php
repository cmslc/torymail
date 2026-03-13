<?php
/**
 * Compose Email Handler
 * Handles send and save draft from compose.php view
 */
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . '/../../libs/EmailEngine.php';

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
csrf_verify();

$action = preg_replace('/[^a-z]/', '', $_POST['action'] ?? '');

// Common fields
$mailbox_id  = intval($_POST['from_mailbox_id'] ?? 0);
$to          = trim($_POST['to'] ?? '');
$cc          = trim($_POST['cc'] ?? '');
$bcc         = trim($_POST['bcc'] ?? '');
$subject     = sanitize($_POST['subject'] ?? '');
$body_html   = $_POST['body_html'] ?? '';
$body_text   = strip_tags($body_html);
$priority    = in_array($_POST['priority'] ?? 'normal', ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';
$draft_id    = intval($_POST['draft_id'] ?? 0);
$reply_to_id = intval($_POST['reply_to'] ?? 0);
$forward_id  = intval($_POST['forward'] ?? 0);

if ($mailbox_id <= 0) error_response('Please select a mailbox');

// Verify mailbox ownership
$mailbox = $ToryMail->get_row_safe(
    "SELECT * FROM mailboxes WHERE id = ? AND user_id = ? AND status = 'active'",
    [$mailbox_id, $getUser['id']]
);
if (!$mailbox) error_response('Mailbox not found or not active');

switch ($action) {

    // -------------------------------------------------------
    // SEND
    // -------------------------------------------------------
    case 'send':
        if (empty($to)) error_response('Recipient is required');
        if (empty($subject)) error_response('Subject is required');

        // Parse recipients
        $toList  = array_filter(array_map('trim', explode(',', $to)));
        $ccList  = !empty($cc) ? array_filter(array_map('trim', explode(',', $cc))) : [];
        $bccList = !empty($bcc) ? array_filter(array_map('trim', explode(',', $bcc))) : [];

        foreach ($toList as $addr) {
            if (!validate_email($addr)) error_response('Invalid recipient: ' . sanitize($addr));
        }
        foreach ($ccList as $addr) {
            if (!validate_email($addr)) error_response('Invalid CC: ' . sanitize($addr));
        }
        foreach ($bccList as $addr) {
            if (!validate_email($addr)) error_response('Invalid BCC: ' . sanitize($addr));
        }

        $domain = get_email_domain($mailbox['email_address']);
        $messageId = generate_message_id($domain);

        // Handle reply threading
        $in_reply_to = '';
        $references  = '';
        $thread_id   = $messageId;

        if ($reply_to_id > 0) {
            $original = $ToryMail->get_row_safe(
                "SELECT e.* FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$reply_to_id, $getUser['id']]
            );
            if ($original) {
                $in_reply_to = $original['message_id'] ?? '';
                if (!empty($original['references_header'])) {
                    $references = $original['references_header'] . ' ' . $original['message_id'];
                } elseif (!empty($original['message_id'])) {
                    $references = $original['message_id'];
                }
                $thread_id = !empty($original['thread_id']) ? $original['thread_id'] : $messageId;
            }
        }

        $ToryMail->beginTransaction();

        try {
            // Handle attachments
            $hasAttachments = 0;
            $attachmentRecords = [];
            if (!empty($_FILES['attachments'])) {
                $uploadDir = __DIR__ . '/../../storage/attachments/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $files = $_FILES['attachments'];
                $fileCount = is_array($files['name']) ? count($files['name']) : 1;

                for ($i = 0; $i < $fileCount; $i++) {
                    $fileName  = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $tmpName   = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $fileSize  = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    $fileType  = is_array($files['type']) ? $files['type'][$i] : $files['type'];
                    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                    if ($fileError !== UPLOAD_ERR_OK) continue;
                    if ($fileSize > max_attachment_size()) {
                        $ToryMail->rollBack();
                        error_response('Attachment too large: ' . sanitize($fileName));
                    }

                    $storedName = uniqid('att_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
                    $storagePath = $uploadDir . $storedName;

                    if (!move_uploaded_file($tmpName, $storagePath)) {
                        $ToryMail->rollBack();
                        error_response('Failed to upload attachment');
                    }

                    $attachmentRecords[] = [
                        'filename'          => $storedName,
                        'original_filename' => $fileName,
                        'mime_type'         => $fileType,
                        'size'              => $fileSize,
                        'storage_path'      => 'storage/attachments/' . $storedName,
                    ];
                    $hasAttachments = 1;
                }
            }

            // Calculate size
            $emailSize = strlen($body_html) + strlen($body_text);
            foreach ($attachmentRecords as $att) {
                $emailSize += $att['size'];
            }

            // If editing a draft, delete old draft first
            if ($draft_id > 0) {
                $oldDraft = $ToryMail->get_row_safe(
                    "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ? AND e.folder = 'drafts'",
                    [$draft_id, $getUser['id']]
                );
                if ($oldDraft) {
                    $ToryMail->remove_safe('emails', 'id = ?', [$draft_id]);
                }
            }

            // Insert email in sent folder
            $emailId = $ToryMail->insert_safe('emails', [
                'mailbox_id'        => $mailbox_id,
                'message_id'        => $messageId,
                'folder'            => 'sent',
                'from_address'      => $mailbox['email_address'],
                'from_name'         => $mailbox['display_name'],
                'to_addresses'      => json_encode($toList),
                'cc_addresses'      => !empty($ccList) ? json_encode($ccList) : null,
                'bcc_addresses'     => !empty($bccList) ? json_encode($bccList) : null,
                'subject'           => $subject,
                'body_text'         => $body_text,
                'body_html'         => $body_html,
                'is_read'           => 1,
                'priority'          => $priority,
                'has_attachments'   => $hasAttachments,
                'size'              => $emailSize,
                'in_reply_to'       => !empty($in_reply_to) ? $in_reply_to : null,
                'references_header' => !empty($references) ? $references : null,
                'thread_id'         => $thread_id,
                'sent_at'           => gettime(),
                'received_at'       => gettime(),
                'created_at'        => gettime(),
            ]);

            if (!$emailId) {
                $ToryMail->rollBack();
                error_response('Failed to save email');
            }

            // Insert attachments
            foreach ($attachmentRecords as $att) {
                $att['email_id'] = $emailId;
                $att['created_at'] = gettime();
                $ToryMail->insert_safe('email_attachments', $att);
            }

            // Queue for delivery
            $queueAttachments = [];
            foreach ($attachmentRecords as $att) {
                $queueAttachments[] = [
                    'filename'  => $att['original_filename'],
                    'path'      => $att['storage_path'],
                    'mime_type' => $att['mime_type'],
                ];
            }

            $ToryMail->insert_safe('email_queue', [
                'mailbox_id'   => $mailbox_id,
                'from_address' => $mailbox['email_address'],
                'to_addresses' => json_encode($toList),
                'cc_addresses' => !empty($ccList) ? json_encode($ccList) : null,
                'bcc_addresses'=> !empty($bccList) ? json_encode($bccList) : null,
                'subject'      => $subject,
                'body_html'    => $body_html,
                'body_text'    => $body_text,
                'attachments'  => !empty($queueAttachments) ? json_encode($queueAttachments) : null,
                'priority'     => $priority,
                'status'       => 'pending',
                'attempts'     => 0,
                'max_attempts' => 3,
                'created_at'   => gettime(),
            ]);

            // Update used space
            $ToryMail->increment_safe('mailboxes', 'used_space', $emailSize, 'id = ?', [$mailbox_id]);

            $ToryMail->commit();
            success_response('Email sent successfully', ['email_id' => $emailId]);

        } catch (Exception $e) {
            $ToryMail->rollBack();
            error_response('Failed to send email: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------------
    // SAVE DRAFT
    // -------------------------------------------------------
    case 'draft':
        $toList  = !empty($to) ? array_filter(array_map('trim', explode(',', $to))) : [];
        $ccList  = !empty($cc) ? array_filter(array_map('trim', explode(',', $cc))) : [];
        $bccList = !empty($bcc) ? array_filter(array_map('trim', explode(',', $bcc))) : [];

        $data = [
            'mailbox_id'    => $mailbox_id,
            'folder'        => 'drafts',
            'from_address'  => $mailbox['email_address'],
            'from_name'     => $mailbox['display_name'],
            'to_addresses'  => json_encode($toList),
            'cc_addresses'  => !empty($ccList) ? json_encode($ccList) : null,
            'bcc_addresses' => !empty($bccList) ? json_encode($bccList) : null,
            'subject'       => $subject,
            'body_text'     => $body_text,
            'body_html'     => $body_html,
            'is_read'       => 1,
            'priority'      => $priority,
            'size'          => strlen($body_html) + strlen($body_text),
        ];

        if ($draft_id > 0) {
            // Update existing draft
            $existing = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ? AND e.folder = 'drafts'",
                [$draft_id, $getUser['id']]
            );
            if ($existing) {
                $ToryMail->update_safe('emails', $data, 'id = ?', [$draft_id]);
                $emailId = $draft_id;
            } else {
                error_response('Draft not found');
            }
        } else {
            $domain = get_email_domain($mailbox['email_address']);
            $data['message_id']  = generate_message_id($domain);
            $data['thread_id']   = $data['message_id'];
            $data['created_at']  = gettime();
            $data['received_at'] = gettime();
            $emailId = $ToryMail->insert_safe('emails', $data);
        }

        if (!$emailId) error_response('Failed to save draft');
        success_response('Draft saved', ['email_id' => $emailId]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
