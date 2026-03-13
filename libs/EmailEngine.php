<?php
/**
 * Torymail Email Engine
 * Handles sending and receiving emails via SMTP/IMAP
 */

class EmailEngine
{
    private $db;
    private $settings;

    public function __construct($db, $settings = [])
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    // ============================================================
    // SMTP Sending
    // ============================================================

    /**
     * Send an email via SMTP
     */
    public function send($from_email, $to_addresses, $subject, $body_html, $options = [])
    {
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $reply_to = $options['reply_to'] ?? '';
        $attachments = $options['attachments'] ?? [];
        $priority = $options['priority'] ?? 'normal';
        $in_reply_to = $options['in_reply_to'] ?? '';
        $references = $options['references'] ?? '';

        // Get mailbox info
        $mailbox = $this->db->get_row_safe(
            "SELECT m.*, d.domain_name, d.dkim_private_key, d.dkim_selector
             FROM mailboxes m
             JOIN domains d ON m.domain_id = d.id
             WHERE m.email_address = ? AND m.status = 'active'",
            [$from_email]
        );

        if (!$mailbox) {
            return ['success' => false, 'error' => 'Mailbox not found or inactive'];
        }

        $domain = $mailbox['domain_name'];
        $message_id = $this->generateMessageId($domain);

        // Build email headers
        $headers = $this->buildHeaders($from_email, $mailbox['display_name'], $to_addresses, $cc, $bcc, $subject, $message_id, $reply_to, $in_reply_to, $references, $priority);

        // Build MIME body
        $boundary = 'ToryMail_' . md5(uniqid());
        $mime_body = $this->buildMimeBody($body_html, $boundary, $attachments);

        // Queue the email for sending
        $queue_id = $this->db->insert_safe('email_queue', [
            'mailbox_id' => $mailbox['id'],
            'from_address' => $from_email,
            'to_addresses' => json_encode($to_addresses),
            'cc_addresses' => json_encode($cc),
            'bcc_addresses' => json_encode($bcc),
            'subject' => $subject,
            'body_html' => $body_html,
            'body_text' => strip_tags($body_html),
            'attachments' => json_encode($attachments),
            'priority' => $priority === 'high' ? 1 : ($priority === 'low' ? 3 : 2),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'scheduled_at' => gettime(),
            'created_at' => gettime(),
        ]);

        // Store in sent folder
        $thread_id = $options['thread_id'] ?? $this->generateThreadId();
        $all_to = array_merge($to_addresses, $cc);

        $email_id = $this->db->insert_safe('emails', [
            'mailbox_id' => $mailbox['id'],
            'message_id' => $message_id,
            'folder' => 'sent',
            'from_address' => $from_email,
            'from_name' => $mailbox['display_name'],
            'to_addresses' => json_encode($to_addresses),
            'cc_addresses' => json_encode($cc),
            'bcc_addresses' => json_encode($bcc),
            'reply_to' => $reply_to,
            'subject' => $subject,
            'body_text' => strip_tags($body_html),
            'body_html' => $body_html,
            'is_read' => 1,
            'is_starred' => 0,
            'is_flagged' => 0,
            'priority' => $priority,
            'has_attachments' => !empty($attachments) ? 1 : 0,
            'size' => strlen($body_html),
            'headers' => json_encode($headers),
            'in_reply_to' => $in_reply_to,
            'references_header' => $references,
            'thread_id' => $thread_id,
            'sent_at' => gettime(),
            'created_at' => gettime(),
        ]);

        // Update mailbox storage
        $this->db->increment_safe('mailboxes', 'used_space', strlen($body_html), 'id = ?', [$mailbox['id']]);

        return [
            'success' => true,
            'email_id' => $email_id,
            'queue_id' => $queue_id,
            'message_id' => $message_id,
            'thread_id' => $thread_id,
        ];
    }

    /**
     * Process email queue - called by cron
     */
    public function processQueue($limit = 50)
    {
        $pending = $this->db->get_list_safe(
            "SELECT * FROM email_queue
             WHERE status IN ('pending', 'failed')
             AND attempts < max_attempts
             AND (scheduled_at IS NULL OR scheduled_at <= NOW())
             ORDER BY priority ASC, created_at ASC
             LIMIT ?",
            [$limit]
        );

        $results = ['sent' => 0, 'failed' => 0];

        foreach ($pending as $item) {
            $this->db->update_safe('email_queue', [
                'status' => 'sending',
                'attempts' => $item['attempts'] + 1,
            ], 'id = ?', [$item['id']]);

            $sent = $this->smtpSend($item);

            if ($sent['success']) {
                $this->db->update_safe('email_queue', [
                    'status' => 'sent',
                    'sent_at' => gettime(),
                    'error_message' => null,
                ], 'id = ?', [$item['id']]);
                $results['sent']++;
            } else {
                $newStatus = ($item['attempts'] + 1 >= $item['max_attempts']) ? 'failed' : 'pending';
                $this->db->update_safe('email_queue', [
                    'status' => $newStatus,
                    'error_message' => $sent['error'],
                ], 'id = ?', [$item['id']]);
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Actually send via SMTP socket
     */
    private function smtpSend($queueItem)
    {
        $smtp_host = $this->settings['smtp_host'] ?? 'localhost';
        $smtp_port = intval($this->settings['smtp_port'] ?? 587);
        $smtp_user = $this->settings['smtp_username'] ?? '';
        $smtp_pass = $this->settings['smtp_password'] ?? '';
        $smtp_enc = $this->settings['smtp_encryption'] ?? 'tls';

        $to = json_decode($queueItem['to_addresses'], true) ?: [];
        $cc = json_decode($queueItem['cc_addresses'], true) ?: [];
        $bcc = json_decode($queueItem['bcc_addresses'], true) ?: [];

        $all_recipients = array_merge($to, $cc, $bcc);

        if (empty($all_recipients)) {
            return ['success' => false, 'error' => 'No recipients'];
        }

        // Use PHP's mail() as fallback, or connect to configured SMTP
        if (empty($smtp_host) || $smtp_host === 'localhost') {
            return $this->sendViaPHPMail($queueItem, $all_recipients);
        }

        return $this->sendViaSMTPSocket($queueItem, $all_recipients, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_enc);
    }

    /**
     * Send via PHP mail() function (fallback)
     */
    private function sendViaPHPMail($item, $recipients)
    {
        $to = implode(', ', $recipients);
        $subject = $item['subject'];

        $boundary = 'ToryMail_' . md5(uniqid());
        $headers = "From: {$item['from_address']}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: Torymail/" . TORYMAIL_VERSION . "\r\n";

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $item['body_text'] . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $item['body_html'] . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";

        $sent = @mail($to, $subject, $body, $headers, '-f' . $item['from_address']);

        if ($sent) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'PHP mail() failed'];
    }

    /**
     * Send via SMTP socket connection
     */
    private function sendViaSMTPSocket($item, $recipients, $host, $port, $user, $pass, $encryption)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $conn = @stream_socket_client("{$prefix}{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        if (!$conn) {
            return ['success' => false, 'error' => "Connection failed: {$errstr}"];
        }

        $response = $this->smtpRead($conn);
        if (substr($response, 0, 3) !== '220') {
            fclose($conn);
            return ['success' => false, 'error' => "Server greeting error: {$response}"];
        }

        // EHLO
        $this->smtpWrite($conn, "EHLO " . gethostname());
        $response = $this->smtpRead($conn);

        // STARTTLS if needed
        if ($encryption === 'tls') {
            $this->smtpWrite($conn, "STARTTLS");
            $response = $this->smtpRead($conn);
            if (substr($response, 0, 3) === '220') {
                stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $this->smtpWrite($conn, "EHLO " . gethostname());
                $response = $this->smtpRead($conn);
            }
        }

        // AUTH LOGIN
        if ($user && $pass) {
            $this->smtpWrite($conn, "AUTH LOGIN");
            $response = $this->smtpRead($conn);
            if (substr($response, 0, 3) === '334') {
                $this->smtpWrite($conn, base64_encode($user));
                $response = $this->smtpRead($conn);
                $this->smtpWrite($conn, base64_encode($pass));
                $response = $this->smtpRead($conn);
                if (substr($response, 0, 3) !== '235') {
                    fclose($conn);
                    return ['success' => false, 'error' => "Auth failed: {$response}"];
                }
            }
        }

        // MAIL FROM
        $this->smtpWrite($conn, "MAIL FROM:<{$item['from_address']}>");
        $response = $this->smtpRead($conn);

        // RCPT TO
        foreach ($recipients as $rcpt) {
            $this->smtpWrite($conn, "RCPT TO:<{$rcpt}>");
            $response = $this->smtpRead($conn);
        }

        // DATA
        $this->smtpWrite($conn, "DATA");
        $response = $this->smtpRead($conn);

        // Build email content
        $to_list = json_decode($item['to_addresses'], true) ?: [];
        $cc_list = json_decode($item['cc_addresses'], true) ?: [];

        $data = "From: {$item['from_address']}\r\n";
        $data .= "To: " . implode(', ', $to_list) . "\r\n";
        if (!empty($cc_list)) {
            $data .= "Cc: " . implode(', ', $cc_list) . "\r\n";
        }
        $data .= "Subject: {$item['subject']}\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $data .= "X-Mailer: Torymail/" . TORYMAIL_VERSION . "\r\n";
        $data .= "\r\n";
        $data .= $item['body_html'] . "\r\n";
        $data .= ".\r\n";

        $this->smtpWrite($conn, $data);
        $response = $this->smtpRead($conn);

        // QUIT
        $this->smtpWrite($conn, "QUIT");
        fclose($conn);

        if (substr($response, 0, 3) === '250') {
            return ['success' => true];
        }

        return ['success' => false, 'error' => "Send failed: {$response}"];
    }

    private function smtpWrite($conn, $data)
    {
        fwrite($conn, $data . "\r\n");
    }

    private function smtpRead($conn)
    {
        $response = '';
        while ($line = fgets($conn, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($response);
    }

    // ============================================================
    // Incoming Email Processing
    // ============================================================

    /**
     * Process incoming email (called by webhook or pipe)
     */
    public function receiveEmail($raw_email)
    {
        $parsed = $this->parseRawEmail($raw_email);
        if (!$parsed) {
            return ['success' => false, 'error' => 'Failed to parse email'];
        }

        // Find recipient mailbox
        $to_addresses = $parsed['to'] ?? [];
        $delivered_to = $parsed['delivered_to'] ?? '';

        $mailbox = null;
        $check_addresses = $delivered_to ? [$delivered_to] : $to_addresses;

        foreach ($check_addresses as $addr) {
            $mailbox = $this->db->get_row_safe(
                "SELECT m.*, d.domain_name FROM mailboxes m
                 JOIN domains d ON m.domain_id = d.id
                 WHERE m.email_address = ? AND m.status = 'active'",
                [$addr]
            );
            if ($mailbox) break;
        }

        // Try catch-all
        if (!$mailbox && !empty($check_addresses)) {
            $domain = get_email_domain($check_addresses[0]);
            $mailbox = $this->db->get_row_safe(
                "SELECT m.*, d.domain_name FROM mailboxes m
                 JOIN domains d ON m.domain_id = d.id
                 WHERE d.domain_name = ? AND m.is_catch_all = 1 AND m.status = 'active'",
                [$domain]
            );
        }

        if (!$mailbox) {
            return ['success' => false, 'error' => 'No mailbox found for recipient'];
        }

        // Check quota
        $email_size = strlen($raw_email);
        if ($mailbox['quota'] > 0 && ($mailbox['used_space'] + $email_size) > $mailbox['quota']) {
            return ['success' => false, 'error' => 'Mailbox quota exceeded'];
        }

        // Determine folder (apply filters)
        $folder = $this->applyFilters($mailbox['user_id'], $parsed);

        // Spam check (basic)
        $spam_score = $this->calculateSpamScore($parsed);
        if ($spam_score > 5.0 && $folder === 'inbox') {
            $folder = 'spam';
        }

        // Determine thread
        $thread_id = $this->findOrCreateThread($parsed, $mailbox['id']);

        // Store email
        $email_id = $this->db->insert_safe('emails', [
            'mailbox_id' => $mailbox['id'],
            'message_id' => $parsed['message_id'] ?? '',
            'folder' => $folder,
            'from_address' => $parsed['from_email'] ?? '',
            'from_name' => $parsed['from_name'] ?? '',
            'to_addresses' => json_encode($parsed['to'] ?? []),
            'cc_addresses' => json_encode($parsed['cc'] ?? []),
            'bcc_addresses' => '[]',
            'reply_to' => $parsed['reply_to'] ?? '',
            'subject' => $parsed['subject'] ?? '(No Subject)',
            'body_text' => $parsed['body_text'] ?? '',
            'body_html' => $parsed['body_html'] ?? '',
            'is_read' => 0,
            'is_starred' => 0,
            'is_flagged' => 0,
            'priority' => $parsed['priority'] ?? 'normal',
            'has_attachments' => !empty($parsed['attachments']) ? 1 : 0,
            'size' => $email_size,
            'headers' => json_encode($parsed['headers'] ?? []),
            'in_reply_to' => $parsed['in_reply_to'] ?? '',
            'references_header' => $parsed['references'] ?? '',
            'thread_id' => $thread_id,
            'spam_score' => $spam_score,
            'received_at' => gettime(),
            'created_at' => gettime(),
        ]);

        // Store attachments and replace cid: references in HTML
        $cidMap = [];
        if (!empty($parsed['attachments'])) {
            foreach ($parsed['attachments'] as $att) {
                $stored_name = uniqid('att_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $att['filename']);
                $storage_path = 'storage/attachments/' . date('Y/m/') . $stored_name;
                $full_path = __DIR__ . '/../' . $storage_path;

                @mkdir(dirname($full_path), 0755, true);
                file_put_contents($full_path, $att['content']);

                $att_id = $this->db->insert_safe('email_attachments', [
                    'email_id' => $email_id,
                    'filename' => $stored_name,
                    'original_filename' => $att['filename'],
                    'mime_type' => $att['mime_type'] ?? 'application/octet-stream',
                    'size' => strlen($att['content']),
                    'storage_path' => $storage_path,
                    'content_id' => $att['content_id'] ?? null,
                    'is_inline' => !empty($att['is_inline']) ? 1 : 0,
                    'created_at' => gettime(),
                ]);

                // Map content_id to URL for inline images
                if (!empty($att['content_id'])) {
                    $cidMap[$att['content_id']] = $storage_path;
                }
            }
        }

        // Replace cid: references in HTML body with actual URLs
        if (!empty($cidMap)) {
            $body_html = $parsed['body_html'] ?? '';
            foreach ($cidMap as $cid => $path) {
                $body_html = str_replace('cid:' . $cid, '/' . $path, $body_html);
            }
            $this->db->update_safe('emails', ['body_html' => $body_html], 'id = ?', [$email_id]);
        }

        // Update mailbox storage
        $this->db->increment_safe('mailboxes', 'used_space', $email_size, 'id = ?', [$mailbox['id']]);

        // Handle auto-reply
        if ($mailbox['auto_reply_enabled'] && $folder === 'inbox') {
            $this->sendAutoReply($mailbox, $parsed);
        }

        // Handle forwarding
        if ($mailbox['forwarding_enabled'] && $mailbox['forwarding_address']) {
            $this->forwardEmail($mailbox, $parsed);
        }

        // Auto-add to contacts
        $this->autoAddContact($mailbox['user_id'], $parsed['from_email'] ?? '', $parsed['from_name'] ?? '');

        return ['success' => true, 'email_id' => $email_id, 'folder' => $folder];
    }

    /**
     * Parse raw email (RFC 2822)
     */
    private function parseRawEmail($raw)
    {
        $parts = preg_split('/\r?\n\r?\n/', $raw, 2);
        if (count($parts) < 2) return null;

        $header_text = $parts[0];
        $body_text = $parts[1];

        // Parse headers
        $headers = $this->parseHeaders($header_text);

        // Extract from
        $from = $headers['from'] ?? '';
        $from_name = '';
        $from_email = '';
        if (preg_match('/^"?([^"<]*)"?\s*<([^>]+)>/', $from, $m)) {
            $from_name = trim($m[1]);
            $from_email = trim($m[2]);
        } elseif (preg_match('/^([^@\s]+@[^@\s]+)/', $from, $m)) {
            $from_email = $m[1];
        }

        // Extract to
        $to_header = $headers['to'] ?? '';
        preg_match_all('/([^@\s,<]+@[^@\s,>]+)/', $to_header, $m);
        $to = $m[1] ?? [];

        // Extract CC
        $cc = [];
        if (isset($headers['cc'])) {
            preg_match_all('/([^@\s,<]+@[^@\s,>]+)/', $headers['cc'], $m);
            $cc = $m[1] ?? [];
        }

        // Subject (decode MIME)
        $subject = $headers['subject'] ?? '';
        if (preg_match('/=\?/', $subject)) {
            $subject = mb_decode_mimeheader($subject);
        }

        // Priority
        $priority = 'normal';
        $x_priority = $headers['x-priority'] ?? '';
        if ($x_priority === '1' || $x_priority === '2') $priority = 'high';
        if ($x_priority === '4' || $x_priority === '5') $priority = 'low';

        // Parse MIME tree recursively
        $content_type = $headers['content-type'] ?? 'text/plain';
        $transfer_encoding = $headers['content-transfer-encoding'] ?? '';
        $mimeParts = $this->parseMimeParts($body_text, $content_type, $transfer_encoding);

        return [
            'headers' => $headers,
            'from_name' => $from_name,
            'from_email' => $from_email,
            'to' => $to,
            'cc' => $cc,
            'delivered_to' => $headers['delivered-to'] ?? '',
            'subject' => $subject,
            'message_id' => $headers['message-id'] ?? '',
            'in_reply_to' => $headers['in-reply-to'] ?? '',
            'references' => $headers['references'] ?? '',
            'reply_to' => $headers['reply-to'] ?? '',
            'priority' => $priority,
            'body_text' => $mimeParts['text'] ?? '',
            'body_html' => $mimeParts['html'] ?? '',
            'attachments' => $mimeParts['attachments'] ?? [],
        ];
    }

    /**
     * Parse header block into associative array
     */
    private function parseHeaders($header_text)
    {
        $headers = [];
        $current_header = '';
        foreach (preg_split('/\r?\n/', $header_text) as $line) {
            if (preg_match('/^\s+/', $line)) {
                $current_header .= ' ' . trim($line);
            } else {
                if ($current_header) {
                    $colon = strpos($current_header, ':');
                    if ($colon !== false) {
                        $key = strtolower(trim(substr($current_header, 0, $colon)));
                        $val = trim(substr($current_header, $colon + 1));
                        $headers[$key] = $val;
                    }
                }
                $current_header = $line;
            }
        }
        if ($current_header) {
            $colon = strpos($current_header, ':');
            if ($colon !== false) {
                $key = strtolower(trim(substr($current_header, 0, $colon)));
                $val = trim(substr($current_header, $colon + 1));
                $headers[$key] = $val;
            }
        }
        return $headers;
    }

    /**
     * Recursively parse MIME parts, returning text, html, and attachments
     */
    private function parseMimeParts($body, $content_type, $transfer_encoding, $disposition = '', $content_id_header = '')
    {
        $result = ['text' => '', 'html' => '', 'attachments' => []];

        // Check if this is a multipart message
        if (stripos($content_type, 'multipart/') !== false) {
            preg_match('/boundary="?([^";\s]+)"?/', $content_type, $m);
            if (empty($m[1])) return $result;

            $boundary = $m[1];
            $parts = explode('--' . $boundary, $body);

            // Remove preamble (first) and epilogue (last with --)
            array_shift($parts);
            foreach ($parts as $i => $part) {
                $part = ltrim($part, "\r\n");
                if ($part === '--' || strpos($part, '--') === 0) {
                    unset($parts[$i]);
                    continue;
                }
                // Remove trailing boundary marker
                $parts[$i] = preg_replace('/\r?\n?--\s*$/', '', $part);
            }

            foreach ($parts as $part) {
                // Split part headers and body
                $split = preg_split('/\r?\n\r?\n/', $part, 2);
                if (count($split) < 2) continue;

                $partHeaderText = $split[0];
                $partBody = $split[1];
                $partHeaders = $this->parseHeaders($partHeaderText);

                $partCT = $partHeaders['content-type'] ?? 'text/plain';
                $partTE = $partHeaders['content-transfer-encoding'] ?? '';
                $partDisp = $partHeaders['content-disposition'] ?? '';
                $partCID = $partHeaders['content-id'] ?? '';

                // Recurse into nested multipart
                $sub = $this->parseMimeParts($partBody, $partCT, $partTE, $partDisp, $partCID);

                if (!$result['text'] && $sub['text']) $result['text'] = $sub['text'];
                if (!$result['html'] && $sub['html']) $result['html'] = $sub['html'];
                $result['attachments'] = array_merge($result['attachments'], $sub['attachments']);
            }

            // If we got HTML but no text, generate text from HTML
            if ($result['html'] && !$result['text']) {
                $result['text'] = strip_tags($result['html']);
            }
            // If we got text but no HTML, generate HTML from text
            if ($result['text'] && !$result['html']) {
                $result['html'] = nl2br(htmlspecialchars($result['text']));
            }

            return $result;
        }

        // Non-multipart: decode the content
        $decoded = $this->decodeMimeContent($body, $content_type, $transfer_encoding);

        // Check if it's an attachment or inline image
        $isAttachment = (stripos($disposition, 'attachment') !== false);
        $isInline = (stripos($disposition, 'inline') !== false);
        $hasFilename = (preg_match('/filename/i', $content_type . ' ' . $disposition));

        if ($isAttachment || ($isInline && $hasFilename && stripos($content_type, 'text/') === false)) {
            $filename = 'attachment';
            if (preg_match('/filename="?([^";\r\n]+)"?/i', $disposition . "\r\n" . $content_type, $fm)) {
                $filename = trim($fm[1]);
                // Decode MIME encoded filename
                if (preg_match('/=\?/', $filename)) {
                    $filename = mb_decode_mimeheader($filename);
                }
            }

            $mime = 'application/octet-stream';
            if (preg_match('/^([^;\s]+)/i', $content_type, $tm)) {
                $mime = trim($tm[1]);
            }

            $content_id = '';
            if ($content_id_header) {
                $content_id = trim($content_id_header, '<> ');
            }

            // For attachments, decode as binary
            $binContent = $decoded;
            if (strtolower($transfer_encoding) === 'base64') {
                $binContent = base64_decode(preg_replace('/\s+/', '', $body));
            } elseif (strtolower($transfer_encoding) === 'quoted-printable') {
                $binContent = quoted_printable_decode($body);
            }

            $result['attachments'][] = [
                'filename' => $filename,
                'mime_type' => $mime,
                'content' => $binContent,
                'content_id' => $content_id,
                'is_inline' => $isInline,
            ];
            return $result;
        }

        // Text content
        if (stripos($content_type, 'text/html') !== false) {
            $result['html'] = $decoded;
        } elseif (stripos($content_type, 'text/plain') !== false) {
            $result['text'] = $decoded;
        }

        return $result;
    }

    private function decodeMimeContent($content, $content_type, $transfer_encoding = '')
    {
        $encoding = strtolower(trim($transfer_encoding));
        if ($encoding === 'quoted-printable') {
            $content = quoted_printable_decode($content);
        } elseif ($encoding === 'base64') {
            $content = base64_decode(preg_replace('/\s+/', '', $content));
        }

        // Convert charset to UTF-8 if needed
        if (preg_match('/charset="?([^";\s]+)"?/i', $content_type, $cm)) {
            $charset = strtolower(trim($cm[1]));
            if ($charset && $charset !== 'utf-8' && $charset !== 'utf8') {
                $converted = @iconv($charset, 'UTF-8//IGNORE', $content);
                if ($converted !== false) $content = $converted;
            }
        }

        return $content;
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function buildHeaders($from, $from_name, $to, $cc, $bcc, $subject, $message_id, $reply_to, $in_reply_to, $references, $priority)
    {
        $headers = [
            'From' => $from_name ? "\"$from_name\" <$from>" : $from,
            'To' => implode(', ', $to),
            'Subject' => $subject,
            'Message-ID' => $message_id,
            'Date' => date('r'),
            'MIME-Version' => '1.0',
            'X-Mailer' => 'Torymail/' . TORYMAIL_VERSION,
        ];

        if (!empty($cc)) $headers['Cc'] = implode(', ', $cc);
        if ($reply_to) $headers['Reply-To'] = $reply_to;
        if ($in_reply_to) $headers['In-Reply-To'] = $in_reply_to;
        if ($references) $headers['References'] = $references;

        switch ($priority) {
            case 'high':
                $headers['X-Priority'] = '1';
                $headers['Importance'] = 'High';
                break;
            case 'low':
                $headers['X-Priority'] = '5';
                $headers['Importance'] = 'Low';
                break;
        }

        return $headers;
    }

    private function buildMimeBody($html, $boundary, $attachments = [])
    {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= strip_tags($html) . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n\r\n";

        if (!empty($attachments)) {
            foreach ($attachments as $att) {
                if (isset($att['path']) && file_exists($att['path'])) {
                    $content = base64_encode(file_get_contents($att['path']));
                    $body .= "--{$boundary}\r\n";
                    $body .= "Content-Type: {$att['mime_type']}; name=\"{$att['filename']}\"\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"{$att['filename']}\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= chunk_split($content) . "\r\n";
                }
            }
        }

        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    private function applyFilters($user_id, $parsed)
    {
        $filters = $this->db->get_list_safe(
            "SELECT * FROM email_filters WHERE user_id = ? AND is_active = 1 ORDER BY priority_order ASC",
            [$user_id]
        );

        foreach ($filters as $filter) {
            $conditions = json_decode($filter['conditions'], true) ?: [];
            $actions = json_decode($filter['actions'], true) ?: [];

            $match = true;
            foreach ($conditions as $cond) {
                $field_value = '';
                switch ($cond['field'] ?? '') {
                    case 'from': $field_value = $parsed['from_email'] ?? ''; break;
                    case 'to': $field_value = implode(',', $parsed['to'] ?? []); break;
                    case 'subject': $field_value = $parsed['subject'] ?? ''; break;
                }

                $op = $cond['operator'] ?? 'contains';
                $val = $cond['value'] ?? '';

                switch ($op) {
                    case 'contains':
                        if (stripos($field_value, $val) === false) $match = false;
                        break;
                    case 'equals':
                        if (strtolower($field_value) !== strtolower($val)) $match = false;
                        break;
                    case 'starts_with':
                        if (stripos($field_value, $val) !== 0) $match = false;
                        break;
                    case 'ends_with':
                        if (substr(strtolower($field_value), -strlen($val)) !== strtolower($val)) $match = false;
                        break;
                }
            }

            if ($match && !empty($actions)) {
                if (isset($actions['move_to'])) {
                    return $actions['move_to'];
                }
                if (isset($actions['delete']) && $actions['delete']) {
                    return 'trash';
                }
            }
        }

        return 'inbox';
    }

    private function calculateSpamScore($parsed)
    {
        $score = 0.0;

        $subject = strtolower($parsed['subject'] ?? '');
        $spam_words = ['viagra', 'casino', 'lottery', 'winner', 'click here', 'act now', 'limited time', 'free money', 'congratulations'];
        foreach ($spam_words as $word) {
            if (strpos($subject, $word) !== false) $score += 2.0;
        }

        // Check for excessive caps
        $caps_ratio = strlen(preg_replace('/[^A-Z]/', '', $parsed['subject'] ?? '')) / max(1, strlen($parsed['subject'] ?? ''));
        if ($caps_ratio > 0.7) $score += 1.5;

        // Check for missing fields
        if (empty($parsed['from_email'])) $score += 3.0;
        if (empty($parsed['message_id'])) $score += 1.0;

        return $score;
    }

    private function findOrCreateThread($parsed, $mailbox_id)
    {
        // Try to find existing thread via In-Reply-To or References
        if (!empty($parsed['in_reply_to'])) {
            $parent = $this->db->get_row_safe(
                "SELECT thread_id FROM emails WHERE message_id = ? AND mailbox_id = ?",
                [$parsed['in_reply_to'], $mailbox_id]
            );
            if ($parent && $parent['thread_id']) {
                return $parent['thread_id'];
            }
        }

        if (!empty($parsed['references'])) {
            $refs = preg_split('/\s+/', $parsed['references']);
            foreach (array_reverse($refs) as $ref) {
                $parent = $this->db->get_row_safe(
                    "SELECT thread_id FROM emails WHERE message_id = ? AND mailbox_id = ?",
                    [trim($ref), $mailbox_id]
                );
                if ($parent && $parent['thread_id']) {
                    return $parent['thread_id'];
                }
            }
        }

        return $this->generateThreadId();
    }

    private function sendAutoReply($mailbox, $parsed)
    {
        // Don't auto-reply to noreply, mailer-daemon, or our own messages
        $from = strtolower($parsed['from_email'] ?? '');
        if (strpos($from, 'noreply') !== false || strpos($from, 'mailer-daemon') !== false) {
            return;
        }
        if ($from === strtolower($mailbox['email_address'])) {
            return;
        }

        // Check if we already auto-replied to this sender recently (24h)
        $recent = $this->db->get_row_safe(
            "SELECT id FROM emails
             WHERE mailbox_id = ? AND to_addresses LIKE ? AND folder = 'sent'
             AND subject LIKE '%Auto-Reply%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$mailbox['id'], '%' . $from . '%']
        );
        if ($recent) return;

        $this->send(
            $mailbox['email_address'],
            [$from],
            'Auto-Reply: ' . ($mailbox['auto_reply_subject'] ?: 'Out of Office'),
            $mailbox['auto_reply_message'] ?: '<p>Thank you for your email. I am currently unavailable and will respond as soon as possible.</p>',
            ['priority' => 'low']
        );
    }

    private function forwardEmail($mailbox, $parsed)
    {
        $fwd_body = '<p>---------- Forwarded message ----------</p>';
        $fwd_body .= '<p>From: ' . htmlspecialchars($parsed['from_email'] ?? '') . '<br>';
        $fwd_body .= 'Subject: ' . htmlspecialchars($parsed['subject'] ?? '') . '<br>';
        $fwd_body .= 'Date: ' . date('Y-m-d H:i:s') . '</p>';
        $fwd_body .= '<hr>' . ($parsed['body_html'] ?: nl2br(htmlspecialchars($parsed['body_text'] ?? '')));

        $this->send(
            $mailbox['email_address'],
            [$mailbox['forwarding_address']],
            'Fwd: ' . ($parsed['subject'] ?? ''),
            $fwd_body
        );
    }

    private function autoAddContact($user_id, $email, $name)
    {
        if (!$email) return;
        $exists = $this->db->get_row_safe(
            "SELECT id FROM contacts WHERE user_id = ? AND email = ?",
            [$user_id, $email]
        );
        if (!$exists) {
            $this->db->insert_safe('contacts', [
                'user_id' => $user_id,
                'email' => $email,
                'name' => $name ?: get_email_local($email),
                'created_at' => gettime(),
                'updated_at' => gettime(),
            ]);
        }
    }

    private function generateMessageId($domain)
    {
        return '<' . uniqid('tm-', true) . '@' . $domain . '>';
    }

    private function generateThreadId()
    {
        return 'thread-' . bin2hex(random_bytes(16));
    }
}
