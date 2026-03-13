<?php
/**
 * Email Action Handler
 * Handles single and bulk email actions from inbox.php and read.php
 */
require_once __DIR__ . "/../bootstrap.php";

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

// Allow GET for check_new
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['action'] ?? '');
if ($action === 'check_new') {
    $folder = preg_replace('/[^a-z]/', '', $_GET['folder'] ?? 'inbox');
    $lastId = intval($_GET['last_id'] ?? 0);
    $validFolders = ['inbox', 'starred', 'sent', 'drafts', 'spam', 'trash', 'archive'];
    if (!in_array($folder, $validFolders)) $folder = 'inbox';

    $where = "e.mailbox_id IN (SELECT id FROM mailboxes WHERE user_id = ?)";
    $params = [$getUser['id']];
    if ($folder === 'starred') {
        $where .= " AND e.is_starred = 1 AND e.folder NOT IN ('trash')";
    } else {
        $where .= " AND e.folder = ?";
        $params[] = $folder;
    }

    if ($lastId > 0) {
        $where .= " AND e.id > ?";
        $params[] = $lastId;
    }

    $count = $ToryMail->get_row_safe("SELECT COUNT(*) as cnt FROM emails e WHERE $where", $params);
    $total = $ToryMail->get_row_safe(
        "SELECT COUNT(*) as cnt FROM emails e WHERE e.mailbox_id IN (SELECT id FROM mailboxes WHERE user_id = ?) AND e.folder = ? AND e.is_read = 0",
        [$getUser['id'], $folder]
    );
    success_response('OK', [
        'new_count' => intval($count['cnt'] ?? 0),
        'unread_count' => intval($total['cnt'] ?? 0),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
csrf_verify();

$action   = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['action'] ?? $action);
$emailId  = intval($_POST['email_id'] ?? 0);
$emailIds = $_POST['email_ids'] ?? [];
$target   = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['target'] ?? '');
$labelId  = intval($_POST['label_id'] ?? 0);

// Helper: verify email belongs to user via mailbox
function verifyOwnership($email_id) {
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

switch ($action) {

    // -------------------------------------------------------
    // MARK READ (single email, from read.php on page load)
    // -------------------------------------------------------
    case 'mark_read':
        if ($emailId <= 0) error_response('Invalid email ID');
        $email = verifyOwnership($emailId);
        if (!$email['is_read']) {
            $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$emailId]);
        }
        success_response('Marked as read');
        break;

    // -------------------------------------------------------
    // TOGGLE STAR
    // -------------------------------------------------------
    case 'toggle_star':
        if ($emailId <= 0) error_response('Invalid email ID');
        $email = verifyOwnership($emailId);
        $newVal = $email['is_starred'] ? 0 : 1;
        $ToryMail->update_safe('emails', ['is_starred' => $newVal], 'id = ?', [$emailId]);
        success_response($newVal ? 'Email starred' : 'Star removed', ['is_starred' => $newVal]);
        break;

    // -------------------------------------------------------
    // DELETE (single)
    // -------------------------------------------------------
    case 'delete':
        if ($emailId <= 0) error_response('Invalid email ID');
        $email = verifyOwnership($emailId);
        if ($email['folder'] === 'trash') {
            $ToryMail->decrement_safe('mailboxes', 'used_space', $email['size'], 'id = ?', [$email['mailbox_id']]);
            $ToryMail->remove_safe('email_attachments', 'email_id = ?', [$emailId]);
            $ToryMail->remove_safe('email_label_map', 'email_id = ?', [$emailId]);
            $ToryMail->remove_safe('emails', 'id = ?', [$emailId]);
            success_response('Email permanently deleted');
        } else {
            $ToryMail->update_safe('emails', ['folder' => 'trash'], 'id = ?', [$emailId]);
            success_response('Email moved to trash');
        }
        break;

    // -------------------------------------------------------
    // ARCHIVE (single)
    // -------------------------------------------------------
    case 'archive':
        if ($emailId <= 0) error_response('Invalid email ID');
        verifyOwnership($emailId);
        $ToryMail->update_safe('emails', ['folder' => 'archive'], 'id = ?', [$emailId]);
        success_response('Email archived');
        break;

    // -------------------------------------------------------
    // SPAM (single)
    // -------------------------------------------------------
    case 'spam':
        if ($emailId <= 0) error_response('Invalid email ID');
        verifyOwnership($emailId);
        $ToryMail->update_safe('emails', ['folder' => 'spam'], 'id = ?', [$emailId]);
        success_response('Email marked as spam');
        break;

    // -------------------------------------------------------
    // MOVE (single)
    // -------------------------------------------------------
    case 'move':
        if ($emailId <= 0 || empty($target)) error_response('Email ID and target folder required');
        $validFolders = ['inbox', 'sent', 'drafts', 'trash', 'spam', 'archive'];
        if (!in_array($target, $validFolders)) error_response('Invalid folder');
        verifyOwnership($emailId);
        $ToryMail->update_safe('emails', ['folder' => $target], 'id = ?', [$emailId]);
        success_response('Email moved to ' . $target);
        break;

    // -------------------------------------------------------
    // ADD LABEL
    // -------------------------------------------------------
    case 'add_label':
        if ($emailId <= 0 || $labelId <= 0) error_response('Email ID and label ID required');
        verifyOwnership($emailId);
        // Verify label belongs to user
        $label = $ToryMail->get_row_safe("SELECT id FROM email_labels WHERE id = ? AND user_id = ?", [$labelId, $getUser['id']]);
        if (!$label) error_response('Label not found');
        // Check if already applied
        $exists = $ToryMail->get_row_safe("SELECT id FROM email_label_map WHERE email_id = ? AND label_id = ?", [$emailId, $labelId]);
        if (!$exists) {
            $ToryMail->insert_safe('email_label_map', [
                'email_id'   => $emailId,
                'label_id'   => $labelId,
                'created_at' => gettime(),
            ]);
        }
        success_response('Label added');
        break;

    // -------------------------------------------------------
    // REMOVE LABEL
    // -------------------------------------------------------
    case 'remove_label':
        if ($emailId <= 0 || $labelId <= 0) error_response('Email ID and label ID required');
        verifyOwnership($emailId);
        $ToryMail->remove_safe('email_label_map', 'email_id = ? AND label_id = ?', [$emailId, $labelId]);
        success_response('Label removed');
        break;

    // -------------------------------------------------------
    // BULK READ
    // -------------------------------------------------------
    case 'bulk_read':
        if (empty($emailIds)) error_response('No emails selected');
        $count = 0;
        foreach ((array)$emailIds as $id) {
            $id = intval($id);
            $e = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($e) {
                $ToryMail->update_safe('emails', ['is_read' => 1], 'id = ?', [$id]);
                $count++;
            }
        }
        success_response("$count email(s) marked as read");
        break;

    // -------------------------------------------------------
    // BULK UNREAD
    // -------------------------------------------------------
    case 'bulk_unread':
        if (empty($emailIds)) error_response('No emails selected');
        $count = 0;
        foreach ((array)$emailIds as $id) {
            $id = intval($id);
            $e = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($e) {
                $ToryMail->update_safe('emails', ['is_read' => 0], 'id = ?', [$id]);
                $count++;
            }
        }
        success_response("$count email(s) marked as unread");
        break;

    // -------------------------------------------------------
    // BULK ARCHIVE
    // -------------------------------------------------------
    case 'bulk_archive':
        if (empty($emailIds)) error_response('No emails selected');
        $count = 0;
        foreach ((array)$emailIds as $id) {
            $id = intval($id);
            $e = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($e) {
                $ToryMail->update_safe('emails', ['folder' => 'archive'], 'id = ?', [$id]);
                $count++;
            }
        }
        success_response("$count email(s) archived");
        break;

    // -------------------------------------------------------
    // BULK DELETE
    // -------------------------------------------------------
    case 'bulk_delete':
        if (empty($emailIds)) error_response('No emails selected');
        $trashed = 0;
        $deleted = 0;
        foreach ((array)$emailIds as $id) {
            $id = intval($id);
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
    // BULK MOVE
    // -------------------------------------------------------
    case 'bulk_move':
        if (empty($emailIds) || empty($target)) error_response('Emails and target folder required');
        $validFolders = ['inbox', 'sent', 'drafts', 'trash', 'spam', 'archive'];
        if (!in_array($target, $validFolders)) error_response('Invalid folder');
        $count = 0;
        foreach ((array)$emailIds as $id) {
            $id = intval($id);
            $e = $ToryMail->get_row_safe(
                "SELECT e.id FROM emails e JOIN mailboxes m ON e.mailbox_id = m.id WHERE e.id = ? AND m.user_id = ?",
                [$id, $getUser['id']]
            );
            if ($e) {
                $ToryMail->update_safe('emails', ['folder' => $target], 'id = ?', [$id]);
                $count++;
            }
        }
        success_response("$count email(s) moved to $target");
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
