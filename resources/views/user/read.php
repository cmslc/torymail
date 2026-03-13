<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Load email by $id
$email = $ToryMail->get_row_safe("
    SELECT e.*, m.`email` as mailbox_email, m.`display_name` as mailbox_name
    FROM `emails` e
    LEFT JOIN `mailboxes` m ON e.`mailbox_id` = m.`id`
    WHERE e.`id` = ? AND e.`user_id` = ?
", [$id, $getUser['id']]);

if (!$email) {
    header('Location: ' . base_url('inbox'));
    exit;
}

$body = [
    'title' => htmlspecialchars($email['subject'] ?: '(No subject)') . ' - Torymail',
    'desc'  => 'Read email',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch attachments
$attachments = $ToryMail->get_list_safe("
    SELECT * FROM `attachments`
    WHERE `email_id` = ?
    ORDER BY `filename` ASC
", [$email['id']]);

// Fetch labels on this email
$emailLabels = $ToryMail->get_list_safe("
    SELECT l.* FROM `labels` l
    JOIN `email_labels` el ON el.`label_id` = l.`id`
    WHERE el.`email_id` = ?
", [$email['id']]);

// Fetch all user labels for the label dropdown
$allLabels = $ToryMail->get_list_safe("
    SELECT * FROM `labels` WHERE `user_id` = ? ORDER BY `name` ASC
", [$getUser['id']]);

// Fetch thread emails
$threadEmails = [];
if (!empty($email['thread_id'])) {
    $threadEmails = $ToryMail->get_list_safe("
        SELECT * FROM `emails`
        WHERE `thread_id` = ? AND `user_id` = ? AND `id` != ?
        ORDER BY `created_at` ASC
    ", [$email['thread_id'], $getUser['id'], $email['id']]);
}

// Sanitize HTML body for display
$emailBodyHtml = $email['body_html']
    ? $email['body_html']
    : nl2br(htmlspecialchars($email['body_text'] ?? ''));
?>

<!-- Mark as read on load -->
<script>
$(function() {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: 'mark_read',
        email_id: <?= (int)$email['id']; ?>
    });
});
</script>

<div class="tm-card">
    <!-- Top Toolbar -->
    <div class="tm-toolbar">
        <a href="<?= base_url('inbox?folder=' . ($email['folder'] ?? 'inbox')); ?>" class="btn-toolbar">
            <i class="ri-arrow-left-line"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>
        <button class="btn-toolbar" onclick="emailAction('archive')" title="Archive">
            <i class="ri-archive-line"></i>
        </button>
        <button class="btn-toolbar" onclick="emailAction('spam')" title="Report spam">
            <i class="ri-spam-2-line"></i>
        </button>
        <button class="btn-toolbar" onclick="emailAction('delete')" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>

        <!-- Move to folder -->
        <div class="dropdown">
            <button class="btn-toolbar dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-folder-transfer-line"></i>
            </button>
            <ul class="dropdown-menu">
                <?php foreach (['inbox', 'archive', 'spam', 'trash'] as $f): ?>
                <li><a class="dropdown-item" href="#" onclick="emailAction('move','<?= $f; ?>');return false;">
                    <?= ucfirst($f); ?>
                </a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Labels dropdown -->
        <div class="dropdown">
            <button class="btn-toolbar dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-price-tag-3-line"></i>
            </button>
            <ul class="dropdown-menu" style="min-width:200px;">
                <?php foreach ($allLabels as $lbl): ?>
                <?php
                $isApplied = false;
                foreach ($emailLabels as $el) {
                    if ($el['id'] == $lbl['id']) { $isApplied = true; break; }
                }
                ?>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="#"
                       onclick="toggleLabel(<?= $email['id']; ?>, <?= $lbl['id']; ?>, <?= $isApplied ? 'true' : 'false'; ?>);return false;">
                        <span class="label-dot" style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($lbl['color']); ?>;"></span>
                        <span><?= htmlspecialchars($lbl['name']); ?></span>
                        <?php if ($isApplied): ?>
                        <i class="ri-check-line ms-auto text-primary"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <?php if (empty($allLabels)): ?>
                <li><span class="dropdown-item text-muted">No labels</span></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="ms-auto">
            <button class="btn-toolbar <?= $email['is_starred'] ? 'text-warning' : ''; ?>"
                    onclick="toggleStarRead()" id="starBtn" title="Star">
                <i class="<?= $email['is_starred'] ? 'ri-star-fill' : 'ri-star-line'; ?>"></i>
            </button>
        </div>
    </div>

    <!-- Email Header -->
    <div class="p-4 border-bottom">
        <!-- Subject -->
        <h4 class="fw-semibold mb-3" style="font-size:22px;">
            <?= htmlspecialchars($email['subject'] ?: '(No subject)'); ?>
        </h4>

        <!-- Labels -->
        <?php if (!empty($emailLabels)): ?>
        <div class="mb-3 d-flex flex-wrap gap-1">
            <?php foreach ($emailLabels as $lbl): ?>
            <span class="tm-label" style="background:<?= htmlspecialchars($lbl['color']); ?>20;color:<?= htmlspecialchars($lbl['color']); ?>;">
                <span class="label-dot" style="background:<?= htmlspecialchars($lbl['color']); ?>;"></span>
                <?= htmlspecialchars($lbl['name']); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- From/To Info -->
        <div class="d-flex align-items-start gap-3">
            <div class="tm-user-avatar" style="width:42px;height:42px;font-size:16px;flex-shrink:0;">
                <?= strtoupper(substr($email['from_name'] ?: $email['from_email'], 0, 1)); ?>
            </div>
            <div class="flex-fill">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong style="font-size:15px;"><?= htmlspecialchars($email['from_name'] ?: $email['from_email']); ?></strong>
                        <span class="text-muted" style="font-size:13px;">&lt;<?= htmlspecialchars($email['from_email']); ?>&gt;</span>
                    </div>
                    <span class="text-muted" style="font-size:13px;">
                        <?= format_date($email['created_at']); ?>
                        (<?= time_ago($email['created_at']); ?>)
                    </span>
                </div>
                <div class="text-muted mt-1" style="font-size:13px;">
                    To: <?= htmlspecialchars($email['to_email'] ?? ''); ?>
                    <?php if (!empty($email['cc'])): ?>
                    <br>Cc: <?= htmlspecialchars($email['cc']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Body -->
    <div class="p-4" style="min-height:200px;">
        <div class="email-body-content" style="font-size:14px;line-height:1.8;overflow-wrap:break-word;">
            <?= $emailBodyHtml; ?>
        </div>
    </div>

    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="border-top px-4 py-3">
        <h6 class="fw-semibold mb-2" style="font-size:14px;">
            <i class="ri-attachment-2 me-1"></i>
            <?= count($attachments); ?> Attachment<?= count($attachments) > 1 ? 's' : ''; ?>
        </h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($attachments as $att): ?>
            <a href="<?= base_url('ajaxs/user/download.php?id=' . $att['id']); ?>"
               class="d-flex align-items-center gap-2 p-2 border rounded-3 text-decoration-none"
               style="font-size:13px;transition:background 0.15s;"
               onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background=''">
                <i class="ri-file-line text-primary fs-20"></i>
                <div>
                    <div class="fw-medium text-dark"><?= htmlspecialchars($att['filename']); ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= format_email_size($att['size'] ?? 0); ?></div>
                </div>
                <i class="ri-download-line text-muted ms-2"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reply/Forward Actions -->
    <div class="border-top px-4 py-3 d-flex flex-wrap gap-2">
        <a href="<?= base_url('compose?reply_to=' . $email['id']); ?>" class="btn btn-outline-primary">
            <i class="ri-reply-line me-1"></i> Reply
        </a>
        <a href="<?= base_url('compose?reply_all=' . $email['id']); ?>" class="btn btn-outline-primary">
            <i class="ri-reply-all-line me-1"></i> Reply All
        </a>
        <a href="<?= base_url('compose?forward=' . $email['id']); ?>" class="btn btn-outline-secondary">
            <i class="ri-share-forward-line me-1"></i> Forward
        </a>
    </div>
</div>

<!-- Thread View -->
<?php if (!empty($threadEmails)): ?>
<div class="mt-3">
    <h6 class="fw-semibold mb-3 text-muted" style="font-size:13px;">
        <i class="ri-chat-thread-line me-1"></i>
        <?= count($threadEmails); ?> earlier message<?= count($threadEmails) > 1 ? 's' : ''; ?> in this thread
    </h6>
    <?php foreach ($threadEmails as $te): ?>
    <div class="tm-card mb-2">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="cursor:pointer;"
             onclick="$(this).next().toggle();">
            <div class="d-flex align-items-center gap-2">
                <div class="tm-user-avatar" style="width:32px;height:32px;font-size:12px;">
                    <?= strtoupper(substr($te['from_name'] ?: $te['from_email'], 0, 1)); ?>
                </div>
                <div>
                    <strong style="font-size:13px;"><?= htmlspecialchars($te['from_name'] ?: $te['from_email']); ?></strong>
                    <span class="text-muted ms-2" style="font-size:12px;"><?= format_date($te['created_at']); ?></span>
                </div>
            </div>
            <i class="ri-arrow-down-s-line text-muted"></i>
        </div>
        <div class="p-3" style="display:none;font-size:13px;line-height:1.7;">
            <?= $te['body_html'] ?: nl2br(htmlspecialchars($te['body_text'] ?? '')); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function emailAction(action, target) {
    if (action === 'delete') {
        tmConfirm('Delete this email?', 'It will be moved to trash.', function() {
            doEmailAction(action, target);
        });
        return;
    }
    doEmailAction(action, target);
}

function doEmailAction(action, target) {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: action === 'move' ? 'move' : action,
        email_id: <?= (int)$email['id']; ?>,
        target: target || ''
    }, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Done!');
            setTimeout(function() {
                window.location.href = '<?= base_url("inbox"); ?>';
            }, 800);
        } else {
            tmToast('error', res.message || 'An error occurred.');
        }
    }, 'json');
}

function toggleStarRead() {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: 'toggle_star',
        email_id: <?= (int)$email['id']; ?>
    }, function(res) {
        if (res.success) {
            var $btn = $('#starBtn');
            $btn.toggleClass('text-warning');
            var $i = $btn.find('i');
            $i.toggleClass('ri-star-line ri-star-fill');
        }
    }, 'json');
}

function toggleLabel(emailId, labelId, isApplied) {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: isApplied ? 'remove_label' : 'add_label',
        email_id: emailId,
        label_id: labelId
    }, function(res) {
        if (res.success) {
            location.reload();
        }
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
