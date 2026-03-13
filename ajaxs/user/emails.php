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

// Auth check (AJAX-friendly)
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

// Helper: get user's mailbox IDs
function getUserMailboxIds() {
    global $ToryMail, $getUser;
    $rows = $ToryMail->get_list_safe("SELECT id FROM mailboxes WHERE user_id = ?", [$getUser['id']]);
    return array_column($rows, 'id');
}

// Helper: verify mailbox ownership
function verifyMailboxOwnership($mailbox_id) {
    global $ToryMail, $getUser;
    $mb = $ToryMail->get_row_safe("SELECT id FROM mailboxes WHERE id = ? AND user_id = ?", [$mailbox_id, $getUser['id']]);
    if (!$mb) error_response('Mailbox not found or access denied', 403);
    return $mb;
}

// Helper: verify email ownership via mailbox
function verifyEmailOwnership($email_id) {
    global $ToryMail, $getUser;
    $email = $ToryMail->get_row_safe(
        "SELECT e.* FROM emails e
         JOIN mailboxes m ON e.mailbox_id = m.id
         WHERE e.id = ? AND m.user_id = ?",
        [$email_id, $getUser['id']]
    );
    if (!$email) error_response('Email not found or access denied', 403);
    return $email;
}

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // LIST
    // -------------------------------------------------------
    case 'list':
        $mailbox_id = intval($_GET['mailbox_id'] ?? 0);
        $folder     = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['folder'] ?? 'inbox');
        $page       = max(1, intval($_GET['page'] ?? 1));
        $per_page   = max(1, min(100, intval($_GET['per_page'] ?? 20)));
        $search     = trim($_GET['search'] ?? '');

        if ($mailbox_id > 0) {
            verifyMailboxOwnership($mailbox_id);
            $mailboxIds = [$mailbox_id];
        } else {
            $mailboxIds = getUserMailboxIds();
        }

        if (empty($mailboxIds)) {
            success_response('OK', ['emails' => [], 'pagination' => paginate(0, $per_page, $page)]);
        }

        $placeholders = implode(',', array_fill(0, count($mailboxIds), '?'));
        $params = $mailboxIds;

        $where = "e.mailbox_id IN ($placeholders) AND e.folder = ?";
        $params[] = $folder;

        if (!empty($search)) {
            $where .= " AND (e.subject LIKE ? OR e.from_address LIKE ? OR e.body_text LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Count
        $total = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails e WHERE $where", $params);
        $pagination = paginate($total, $per_page, $page);

        // Fetch
        $fetchParams = $params;
        $fetchParams[] = $pagination['per_page'];
        $fetchParams[] = $pagination['offset'];

        $emails = $ToryMail->get_list_safe(
            "SELECT e.id, e.mailbox_id, e.message_id, e.folder, e.from_address, e.from_name,
                    e.to_addresses, e.subject, e.is_read, e.is_starred, e.is_flagged,
                    e.priority, e.has_attachments, e.size, e.thread_id,
                    e.sent_at, e.received_at, e.created_at,
                    m.email_address as mailbox_email
             FROM emails e
             JOIN mailboxes m ON e.mailbox_id = m.id
             WHERE $where
             ORDER BY e.received_at DESC, e.created_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['emails' => $emails, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // READ
    // -------------------------------------------------------
    case 'read':
        $email_id = intval($_GET['id'] ?? 0);
        if ($email_id <= 0) error_response('Invalid email ID');

        $email = verifyEmailOwnership($email_id);

        // Mark as read
        if (!$email['is_read']) {
            $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$email_id]);
        }

        // Get attachments
        $attachments = $ToryMail->get_list_safe(
            "SELECT id, original_filename, mime_type, size, content_id FROM email_attachments WHERE email_id = ?",
            [$email_id]
        );

        // Get labels
        $labels = $ToryMail->get_list_safe(
            "SELECT el.id, el.name, el.color
             FROM email_labels el
             JOIN email_label_map elm ON el.id = elm.label_id
             WHERE elm.email_id = ?",
            [$email_id]
        );

        $email['attachments'] = $attachments;
        $email['labels'] = $labels;

        success_response('OK', ['email' => $email]);
        break;

    // -------------------------------------------------------
    // SEND
    // -------------------------------------------------------
    case 'send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailbox_id  = intval($_POST['mailbox_id'] ?? 0);
        $to          = trim($_POST['to'] ?? '');
        $cc          = trim($_POST['cc'] ?? '');
        $bcc         = trim($_POST['bcc'] ?? '');
        $subject     = sanitize($_POST['subject'] ?? '');
        $body_html   = $_POST['body_html'] ?? '';
        $body_text   = strip_tags($body_html);
        $priority    = in_array($_POST['priority'] ?? 'normal', ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';
        $in_reply_to = trim($_POST['in_reply_to'] ?? '');
        $references  = trim($_POST['references'] ?? '');
        $thread_id   = trim($_POST['thread_id'] ?? '');

        if ($mailbox_id <= 0 || empty($to) || empty($subject)) {
            error_response('Mailbox, recipient, and subject are required');
        }

        verifyMailboxOwnership($mailbox_id);

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ? AND status = 'active'", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox is not active');

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
        if (empty($thread_id)) {
            $thread_id = $messageId;
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
                    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $tmpName  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
                    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                    if ($fileError !== UPLOAD_ERR_OK) continue;
                    if ($fileSize > max_attachment_size()) {
                        $ToryMail->rollBack();
                        error_response('Attachment too large: ' . sanitize($fileName));
                    }
                    if (!in_array($fileType, allowed_attachment_types())) {
                        $ToryMail->rollBack();
                        error_response('File type not allowed: ' . sanitize($fileName));
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

            // Calculate email size
            $emailSize = strlen($body_html) + strlen($body_text);
            foreach ($attachmentRecords as $att) {
                $emailSize += $att['size'];
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

            // Insert into email queue
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

            // Update mailbox used space
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
    case 'save_draft':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $draft_id    = intval($_POST['draft_id'] ?? 0);
        $mailbox_id  = intval($_POST['mailbox_id'] ?? 0);
        $to          = trim($_POST['to'] ?? '');
        $cc          = trim($_POST['cc'] ?? '');
        $bcc         = trim($_POST['bcc'] ?? '');
        $subject     = sanitize($_POST['subject'] ?? '');
        $body_html   = $_POST['body_html'] ?? '';
        $body_text   = strip_tags($body_html);

        if ($mailbox_id <= 0) error_response('Mailbox is required');
        verifyMailboxOwnership($mailbox_id);

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ?", [$mailbox_id]);

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
            'size'          => strlen($body_html) + strlen($body_text),
        ];

        if ($draft_id > 0) {
            // Update existing draft
            $existing = verifyEmailOwnership($draft_id);
            if ($existing['folder'] !== 'drafts') error_response('Can only update drafts');
            $ToryMail->update_safe('emails', $data, 'id = ?', [$draft_id]);
            $emailId = $draft_id;
        } else {
            // Create new draft
            $domain = get_email_domain($mailbox['email_address']);
            $data['message_id']   = generate_message_id($domain);
            $data['thread_id']    = $data['message_id'];
            $data['created_at']   = gettime();
            $data['received_at']  = gettime();
            $emailId = $ToryMail->insert_safe('emails', $data);
        }

        if (!$emailId) error_response('Failed to save draft');
        success_response('Draft saved', ['email_id' => $emailId]);
        break;

    // -------------------------------------------------------
    // REPLY
    // -------------------------------------------------------
    case 'reply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $original_id = intval($_POST['original_id'] ?? 0);
        $mailbox_id  = intval($_POST['mailbox_id'] ?? 0);
        $body_html   = $_POST['body_html'] ?? '';
        $body_text   = strip_tags($body_html);
        $reply_all   = !empty($_POST['reply_all']);

        if ($original_id <= 0 || $mailbox_id <= 0) error_response('Original email and mailbox are required');

        $original = verifyEmailOwnership($original_id);
        verifyMailboxOwnership($mailbox_id);

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ? AND status = 'active'", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox is not active');

        // Build recipients
        $toList = [$original['from_address']];
        $ccList = [];
        if ($reply_all) {
            $origTo = json_decode($original['to_addresses'], true) ?: [];
            $origCc = json_decode($original['cc_addresses'], true) ?: [];
            $allAddrs = array_merge($origTo, $origCc);
            foreach ($allAddrs as $addr) {
                if ($addr !== $mailbox['email_address'] && !in_array($addr, $toList)) {
                    $ccList[] = $addr;
                }
            }
        }

        $domain = get_email_domain($mailbox['email_address']);
        $messageId = generate_message_id($domain);
        $subject = 'Re: ' . preg_replace('/^(Re:\s*)+/i', '', $original['subject']);

        // Build references chain
        $references = '';
        if (!empty($original['references_header'])) {
            $references = $original['references_header'] . ' ' . $original['message_id'];
        } elseif (!empty($original['message_id'])) {
            $references = $original['message_id'];
        }

        $threadId = !empty($original['thread_id']) ? $original['thread_id'] : $messageId;

        $emailSize = strlen($body_html) + strlen($body_text);

        $ToryMail->beginTransaction();
        try {
            $emailId = $ToryMail->insert_safe('emails', [
                'mailbox_id'        => $mailbox_id,
                'message_id'        => $messageId,
                'folder'            => 'sent',
                'from_address'      => $mailbox['email_address'],
                'from_name'         => $mailbox['display_name'],
                'to_addresses'      => json_encode($toList),
                'cc_addresses'      => !empty($ccList) ? json_encode($ccList) : null,
                'subject'           => $subject,
                'body_text'         => $body_text,
                'body_html'         => $body_html,
                'is_read'           => 1,
                'size'              => $emailSize,
                'in_reply_to'       => $original['message_id'],
                'references_header' => $references,
                'thread_id'         => $threadId,
                'sent_at'           => gettime(),
                'received_at'       => gettime(),
                'created_at'        => gettime(),
            ]);

            // Queue for delivery
            $ToryMail->insert_safe('email_queue', [
                'mailbox_id'   => $mailbox_id,
                'from_address' => $mailbox['email_address'],
                'to_addresses' => json_encode($toList),
                'cc_addresses' => !empty($ccList) ? json_encode($ccList) : null,
                'subject'      => $subject,
                'body_html'    => $body_html,
                'body_text'    => $body_text,
                'priority'     => 'normal',
                'status'       => 'pending',
                'attempts'     => 0,
                'max_attempts' => 3,
                'created_at'   => gettime(),
            ]);

            $ToryMail->increment_safe('mailboxes', 'used_space', $emailSize, 'id = ?', [$mailbox_id]);

            $ToryMail->commit();
            success_response('Reply sent', ['email_id' => $emailId]);
        } catch (Exception $e) {
            $ToryMail->rollBack();
            error_response('Failed to send reply');
        }
        break;

    // -------------------------------------------------------
    // FORWARD
    // -------------------------------------------------------
    case 'forward':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $original_id = intval($_POST['original_id'] ?? 0);
        $mailbox_id  = intval($_POST['mailbox_id'] ?? 0);
        $to          = trim($_POST['to'] ?? '');
        $body_html   = $_POST['body_html'] ?? '';

        if ($original_id <= 0 || $mailbox_id <= 0 || empty($to)) {
            error_response('Original email, mailbox, and recipient are required');
        }

        $original = verifyEmailOwnership($original_id);
        verifyMailboxOwnership($mailbox_id);

        $mailbox = $ToryMail->get_row_safe("SELECT * FROM mailboxes WHERE id = ? AND status = 'active'", [$mailbox_id]);
        if (!$mailbox) error_response('Mailbox is not active');

        $toList = array_filter(array_map('trim', explode(',', $to)));
        foreach ($toList as $addr) {
            if (!validate_email($addr)) error_response('Invalid recipient: ' . sanitize($addr));
        }

        $domain = get_email_domain($mailbox['email_address']);
        $messageId = generate_message_id($domain);
        $subject = 'Fwd: ' . preg_replace('/^(Fwd:\s*)+/i', '', $original['subject']);

        // Build forwarded body
        $quotedBody = '<br><br>---------- Forwarded message ----------<br>'
            . '<b>From:</b> ' . sanitize($original['from_name'] . ' <' . $original['from_address'] . '>') . '<br>'
            . '<b>Date:</b> ' . ($original['sent_at'] ?? $original['created_at']) . '<br>'
            . '<b>Subject:</b> ' . sanitize($original['subject']) . '<br><br>'
            . ($original['body_html'] ?? nl2br(sanitize($original['body_text'])));

        $fullBody = $body_html . $quotedBody;
        $body_text = strip_tags($fullBody);
        $emailSize = strlen($fullBody) + strlen($body_text);

        $ToryMail->beginTransaction();
        try {
            $emailId = $ToryMail->insert_safe('emails', [
                'mailbox_id'        => $mailbox_id,
                'message_id'        => $messageId,
                'folder'            => 'sent',
                'from_address'      => $mailbox['email_address'],
                'from_name'         => $mailbox['display_name'],
                'to_addresses'      => json_encode($toList),
                'subject'           => $subject,
                'body_text'         => $body_text,
                'body_html'         => $fullBody,
                'is_read'           => 1,
                'has_attachments'   => $original['has_attachments'],
                'size'              => $emailSize,
                'thread_id'         => $messageId,
                'sent_at'           => gettime(),
                'received_at'       => gettime(),
                'created_at'        => gettime(),
            ]);

            // Copy attachments from original
            if ($original['has_attachments']) {
                $origAttachments = $ToryMail->get_list_safe(
                    "SELECT * FROM email_attachments WHERE email_id = ?",
                    [$original_id]
                );
                foreach ($origAttachments as $att) {
                    $ToryMail->insert_safe('email_attachments', [
                        'email_id'          => $emailId,
                        'filename'          => $att['filename'],
                        'original_filename' => $att['original_filename'],
                        'mime_type'         => $att['mime_type'],
                        'size'              => $att['size'],
                        'storage_path'      => $att['storage_path'],
                        'content_id'        => $att['content_id'],
                        'created_at'        => gettime(),
                    ]);
                }
            }

            // Queue for delivery
            $ToryMail->insert_safe('email_queue', [
                'mailbox_id'   => $mailbox_id,
                'from_address' => $mailbox['email_address'],
                'to_addresses' => json_encode($toList),
                'subject'      => $subject,
                'body_html'    => $fullBody,
                'body_text'    => $body_text,
                'priority'     => 'normal',
                'status'       => 'pending',
                'attempts'     => 0,
                'max_attempts' => 3,
                'created_at'   => gettime(),
            ]);

            $ToryMail->increment_safe('mailboxes', 'used_space', $emailSize, 'id = ?', [$mailbox_id]);

            $ToryMail->commit();
            success_response('Email forwarded', ['email_id' => $emailId]);
        } catch (Exception $e) {
            $ToryMail->rollBack();
            error_response('Failed to forward email');
        }
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_id = intval($_POST['id'] ?? 0);
        if ($email_id <= 0) error_response('Invalid email ID');

        $email = verifyEmailOwnership($email_id);

        if ($email['folder'] === 'trash') {
            // Permanently delete
            $ToryMail->decrement_safe('mailboxes', 'used_space', $email['size'], 'id = ?', [$email['mailbox_id']]);
            $ToryMail->remove_safe('emails', 'id = ?', [$email_id]);
            success_response('Email permanently deleted');
        } else {
            // Move to trash
            $ToryMail->update_safe('emails', ['folder' => 'trash'], 'id = ?', [$email_id]);
            success_response('Email moved to trash');
        }
        break;

    // -------------------------------------------------------
    // MOVE
    // -------------------------------------------------------
    case 'move':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_ids = $_POST['ids'] ?? '';
        $folder    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['folder'] ?? '');

        if (empty($email_ids) || empty($folder)) error_response('Email IDs and folder are required');

        $validFolders = ['inbox', 'sent', 'drafts', 'trash', 'spam', 'archive'];
        if (!in_array($folder, $validFolders)) error_response('Invalid folder');

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $moved = 0;
        foreach ($ids as $id) {
            $email = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($email) {
                $ToryMail->update_safe('emails', ['folder' => $folder], 'id = ?', [$id]);
                $moved++;
            }
        }

        success_response("$moved email(s) moved to $folder");
        break;

    // -------------------------------------------------------
    // TOGGLE STAR
    // -------------------------------------------------------
    case 'toggle_star':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_id = intval($_POST['id'] ?? 0);
        if ($email_id <= 0) error_response('Invalid email ID');

        $email = verifyEmailOwnership($email_id);
        $newVal = $email['is_starred'] ? 0 : 1;
        $ToryMail->update_safe('emails', ['is_starred' => $newVal], 'id = ?', [$email_id]);

        success_response($newVal ? 'Email starred' : 'Star removed', ['is_starred' => $newVal]);
        break;

    // -------------------------------------------------------
    // TOGGLE READ
    // -------------------------------------------------------
    case 'toggle_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_id = intval($_POST['id'] ?? 0);
        if ($email_id <= 0) error_response('Invalid email ID');

        $email = verifyEmailOwnership($email_id);
        $newVal = $email['is_read'] ? 0 : 1;
        $ToryMail->update_safe('emails', ['is_read' => $newVal], 'id = ?', [$email_id]);

        success_response($newVal ? 'Marked as read' : 'Marked as unread', ['is_read' => $newVal]);
        break;

    // -------------------------------------------------------
    // MARK READ
    // -------------------------------------------------------
    case 'mark_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_ids = $_POST['ids'] ?? '';
        if (empty($email_ids)) error_response('Email IDs are required');

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $count = 0;
        foreach ($ids as $id) {
            $email = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($email) {
                $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$id]);
                $count++;
            }
        }

        success_response("$count email(s) marked as read");
        break;

    // -------------------------------------------------------
    // MARK UNREAD
    // -------------------------------------------------------
    case 'mark_unread':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_ids = $_POST['ids'] ?? '';
        if (empty($email_ids)) error_response('Email IDs are required');

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $count = 0;
        foreach ($ids as $id) {
            $email = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($email) {
                $ToryMail->update_safe('emails', ['is_read' => 0], 'id = ?', [$id]);
                $count++;
            }
        }

        success_response("$count email(s) marked as unread");
        break;

    // -------------------------------------------------------
    // BULK DELETE
    // -------------------------------------------------------
    case 'bulk_delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email_ids = $_POST['ids'] ?? '';
        if (empty($email_ids)) error_response('Email IDs are required');

        $ids = array_filter(array_map('intval', explode(',', $email_ids)));
        $deleted = 0;
        $trashed = 0;
        foreach ($ids as $id) {
            $email = $ToryMail->get_row_safe(
                "SELECT e.* FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($email) {
                if ($email['folder'] === 'trash') {
                    $ToryMail->decrement_safe('mailboxes', 'used_space', $email['size'], 'id = ?', [$email['mailbox_id']]);
                    $ToryMail->remove_safe('emails', 'id = ?', [$id]);
                    $deleted++;
                } else {
                    $ToryMail->update_safe('emails', ['folder' => 'trash'], 'id = ?', [$id]);
                    $trashed++;
                }
            }
        }

        success_response("$trashed moved to trash, $deleted permanently deleted");
        break;

    // -------------------------------------------------------
    // SEARCH
    // -------------------------------------------------------
    case 'search':
        $keyword  = trim($_GET['q'] ?? '');
        $page     = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, min(100, intval($_GET['per_page'] ?? 20)));

        if (empty($keyword)) error_response('Search keyword is required');

        $mailboxIds = getUserMailboxIds();
        if (empty($mailboxIds)) {
            success_response('OK', ['emails' => [], 'pagination' => paginate(0, $per_page, $page)]);
        }

        $placeholders = implode(',', array_fill(0, count($mailboxIds), '?'));
        $searchTerm = '%' . $keyword . '%';
        $params = array_merge($mailboxIds, [$searchTerm, $searchTerm, $searchTerm]);

        $where = "e.mailbox_id IN ($placeholders) AND (e.subject LIKE ? OR e.from_address LIKE ? OR e.body_text LIKE ?)";

        $total = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails e WHERE $where", $params);
        $pagination = paginate($total, $per_page, $page);

        $fetchParams = array_merge($params, [$pagination['per_page'], $pagination['offset']]);
        $emails = $ToryMail->get_list_safe(
            "SELECT e.id, e.mailbox_id, e.folder, e.from_address, e.from_name, e.to_addresses,
                    e.subject, e.is_read, e.is_starred, e.has_attachments, e.received_at, e.created_at,
                    m.email_address as mailbox_email
             FROM emails e
             JOIN mailboxes m ON e.mailbox_id = m.id
             WHERE $where
             ORDER BY e.received_at DESC
             LIMIT ? OFFSET ?",
            $fetchParams
        );

        success_response('OK', ['emails' => $emails, 'pagination' => $pagination]);
        break;

    // -------------------------------------------------------
    // EMPTY TRASH
    // -------------------------------------------------------
    case 'empty_trash':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailboxIds = getUserMailboxIds();
        if (empty($mailboxIds)) {
            success_response('Trash emptied');
        }

        $placeholders = implode(',', array_fill(0, count($mailboxIds), '?'));
        $params = $mailboxIds;

        // Reclaim space
        $trashEmails = $ToryMail->get_list_safe(
            "SELECT id, mailbox_id, size FROM emails WHERE mailbox_id IN ($placeholders) AND folder = 'trash'",
            $params
        );
        foreach ($trashEmails as $te) {
            $ToryMail->decrement_safe('mailboxes', 'used_space', $te['size'], 'id = ?', [$te['mailbox_id']]);
        }

        $ToryMail->remove_safe('emails', "mailbox_id IN ($placeholders) AND folder = 'trash'", $params);
        success_response('Trash emptied');
        break;

    // -------------------------------------------------------
    // EMPTY SPAM
    // -------------------------------------------------------
    case 'empty_spam':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $mailboxIds = getUserMailboxIds();
        if (empty($mailboxIds)) {
            success_response('Spam folder emptied');
        }

        $placeholders = implode(',', array_fill(0, count($mailboxIds), '?'));
        $params = $mailboxIds;

        $spamEmails = $ToryMail->get_list_safe(
            "SELECT id, mailbox_id, size FROM emails WHERE mailbox_id IN ($placeholders) AND folder = 'spam'",
            $params
        );
        foreach ($spamEmails as $se) {
            $ToryMail->decrement_safe('mailboxes', 'used_space', $se['size'], 'id = ?', [$se['mailbox_id']]);
        }

        $ToryMail->remove_safe('emails', "mailbox_id IN ($placeholders) AND folder = 'spam'", $params);
        success_response('Spam folder emptied');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
