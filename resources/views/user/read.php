<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Load email by $id
$email = $ToryMail->get_row_safe("
    SELECT e.*, m.`email_address` as mailbox_email, m.`display_name` as mailbox_name
    FROM `emails` e
    LEFT JOIN `mailboxes` m ON e.`mailbox_id` = m.`id`
    WHERE e.`id` = ? AND m.`user_id` = ?
", [$id, $getUser['id']]);

if (!$email) {
    header('Location: ' . base_url('inbox'));
    exit;
}

$body = [
    'title' => htmlspecialchars($email['subject'] ?: __('no_subject')) . ' - Torymail',
    'desc'  => __('read_email'),
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch attachments
$attachments = $ToryMail->get_list_safe("
    SELECT * FROM `email_attachments`
    WHERE `email_id` = ?
    ORDER BY `original_filename` ASC
", [$email['id']]);

// Fetch labels on this email
$emailLabels = $ToryMail->get_list_safe("
    SELECT l.* FROM `email_labels` l
    JOIN `email_label_map` elm ON elm.`label_id` = l.`id`
    WHERE elm.`email_id` = ?
", [$email['id']]);

// Fetch all user labels for the label dropdown
$allLabels = $ToryMail->get_list_safe("
    SELECT * FROM `email_labels` WHERE `user_id` = ? ORDER BY `name` ASC
", [$getUser['id']]);

// Fetch thread emails
$threadEmails = [];
if (!empty($email['thread_id'])) {
    $threadEmails = $ToryMail->get_list_safe("
        SELECT e.* FROM `emails` e
        JOIN `mailboxes` m ON e.`mailbox_id` = m.`id`
        WHERE e.`thread_id` = ? AND m.`user_id` = ? AND e.`id` != ?
        ORDER BY e.`created_at` ASC
    ", [$email['thread_id'], $getUser['id'], $email['id']]);
}

// Sanitize HTML body for display
$emailBodyHtml = $email['body_html']
    ? $email['body_html']
    : nl2br(htmlspecialchars($email['body_text'] ?? ''));

$folderNames = [
    'inbox'   => __('inbox'),
    'starred' => __('starred'),
    'sent'    => __('sent'),
    'drafts'  => __('drafts'),
    'spam'    => __('spam'),
    'trash'   => __('trash'),
    'archive' => __('archive'),
];
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

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('read_email'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox?folder=' . ($email['folder'] ?? 'inbox')); ?>"><?= $folderNames[$email['folder'] ?? 'inbox'] ?? ucfirst($email['folder'] ?? 'Inbox'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('read_email'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <!-- Toolbar -->
    <div class="card-header border-bottom-dashed py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= base_url('inbox?folder=' . ($email['folder'] ?? 'inbox')); ?>" class="btn btn-soft-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i> <?= __('back'); ?>
            </a>
            <button class="btn btn-soft-secondary btn-sm" onclick="emailAction('archive')" title="<?= __('archive'); ?>">
                <i class="ri-archive-line"></i>
            </button>
            <button class="btn btn-soft-warning btn-sm" onclick="emailAction('spam')" title="<?= __('report_spam'); ?>">
                <i class="ri-spam-2-line"></i>
            </button>
            <button class="btn btn-soft-danger btn-sm" onclick="emailAction('delete')" title="<?= __('delete'); ?>">
                <i class="ri-delete-bin-line"></i>
            </button>

            <!-- Move to folder -->
            <div class="dropdown">
                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ri-folder-transfer-line"></i>
                </button>
                <ul class="dropdown-menu">
                    <?php foreach (['inbox', 'archive', 'spam', 'trash'] as $f): ?>
                    <li><a class="dropdown-item" href="#" onclick="emailAction('move','<?= $f; ?>');return false;">
                        <i class="ri-folder-line me-2 align-bottom"></i> <?= $folderNames[$f] ?? ucfirst($f); ?>
                    </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Labels dropdown -->
            <div class="dropdown">
                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
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
                            <i class="ri-circle-fill fs-8" style="color:<?= htmlspecialchars($lbl['color']); ?>;"></i>
                            <span><?= htmlspecialchars($lbl['name']); ?></span>
                            <?php if ($isApplied): ?>
                            <i class="ri-check-line ms-auto text-primary"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($allLabels)): ?>
                    <li><span class="dropdown-item text-muted"><?= __('no_labels'); ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="ms-auto">
                <button class="btn btn-sm <?= $email['is_starred'] ? 'btn-warning' : 'btn-soft-warning'; ?>"
                        onclick="toggleStarRead()" id="starBtn" title="<?= __('starred'); ?>">
                    <i class="<?= $email['is_starred'] ? 'ri-star-fill' : 'ri-star-line'; ?>"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Email Header -->
    <div class="card-body border-bottom">
        <!-- Subject -->
        <h5 class="fw-semibold mb-3 fs-18">
            <?= htmlspecialchars($email['subject'] ?: __('no_subject')); ?>
        </h5>

        <!-- Labels -->
        <?php if (!empty($emailLabels)): ?>
        <div class="mb-3 d-flex flex-wrap gap-1">
            <?php foreach ($emailLabels as $lbl): ?>
            <span class="badge" style="background:<?= htmlspecialchars($lbl['color']); ?>20;color:<?= htmlspecialchars($lbl['color']); ?>;">
                <i class="ri-circle-fill fs-8 me-1" style="color:<?= htmlspecialchars($lbl['color']); ?>;"></i>
                <?= htmlspecialchars($lbl['name']); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- From/To Info -->
        <div class="d-flex align-items-start gap-3">
            <div class="avatar-sm flex-shrink-0">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16 fw-semibold">
                    <?= strtoupper(substr($email['from_name'] ?: $email['from_address'], 0, 1)); ?>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($email['from_name'] ?: $email['from_address']); ?></h6>
                        <span class="text-muted fs-13">&lt;<?= htmlspecialchars($email['from_address']); ?>&gt;</span>
                    </div>
                    <span class="text-muted fs-13">
                        <?= format_date($email['created_at']); ?>
                        <span class="text-muted">(<?= time_ago($email['created_at']); ?>)</span>
                    </span>
                </div>
                <div class="text-muted mt-1 fs-13">
                    <?= __('to'); ?>: <?= htmlspecialchars($email['to_addresses'] ?? ''); ?>
                    <?php if (!empty($email['cc_addresses'])): ?>
                    <br><?= __('cc'); ?>: <?= htmlspecialchars($email['cc_addresses']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Body -->
    <div class="card-body" style="min-height:200px;">
        <div style="font-size:14px;line-height:1.8;overflow-wrap:break-word;">
            <?= $emailBodyHtml; ?>
        </div>
    </div>

    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="card-body border-top">
        <h6 class="fw-semibold mb-3 fs-14">
            <i class="ri-attachment-2 me-1 align-bottom"></i>
            <?= count($attachments); ?> <?= __('attachments'); ?>
        </h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($attachments as $att): ?>
            <a href="<?= base_url('ajaxs/user/download.php?id=' . $att['id']); ?>"
               class="d-flex align-items-center gap-2 border rounded p-2 text-decoration-none">
                <div class="avatar-xs flex-shrink-0">
                    <div class="avatar-title bg-primary-subtle text-primary rounded fs-18">
                        <i class="ri-file-line"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-medium text-body fs-13"><?= htmlspecialchars($att['original_filename']); ?></div>
                    <div class="text-muted fs-12"><?= format_email_size($att['size'] ?? 0); ?></div>
                </div>
                <i class="ri-download-line text-muted ms-2"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reply/Forward Actions -->
    <div class="card-footer">
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('compose?reply_to=' . $email['id']); ?>" class="btn btn-soft-primary">
                <i class="ri-reply-line me-1"></i> <?= __('reply'); ?>
            </a>
            <a href="<?= base_url('compose?reply_all=' . $email['id']); ?>" class="btn btn-soft-primary">
                <i class="ri-reply-all-line me-1"></i> <?= __('reply_all'); ?>
            </a>
            <a href="<?= base_url('compose?forward=' . $email['id']); ?>" class="btn btn-soft-secondary">
                <i class="ri-share-forward-line me-1"></i> <?= __('forward'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Thread View -->
<?php if (!empty($threadEmails)): ?>
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fs-14">
            <i class="ri-chat-thread-line me-1 align-bottom"></i>
            <?= count($threadEmails); ?> <?= __('earlier_messages'); ?>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="threadAccordion">
            <?php foreach ($threadEmails as $ti => $te): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#thread-<?= $ti; ?>">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs flex-shrink-0">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-12">
                                    <?= strtoupper(substr($te['from_name'] ?: $te['from_address'], 0, 1)); ?>
                                </div>
                            </div>
                            <div>
                                <span class="fw-medium fs-13"><?= htmlspecialchars($te['from_name'] ?: $te['from_address']); ?></span>
                                <span class="text-muted ms-2 fs-12"><?= format_date($te['created_at']); ?></span>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="thread-<?= $ti; ?>" class="accordion-collapse collapse" data-bs-parent="#threadAccordion">
                    <div class="accordion-body fs-13" style="line-height:1.7;">
                        <?= $te['body_html'] ?: nl2br(htmlspecialchars($te['body_text'] ?? '')); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function emailAction(action, target) {
    if (action === 'delete') {
        tmConfirm('<?= __("delete_email"); ?>', '<?= __("delete_email_desc"); ?>', function() {
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
            tmToast('success', res.message || '<?= __("done"); ?>');
            setTimeout(function() {
                window.location.href = '<?= base_url("inbox"); ?>';
            }, 800);
        } else {
            tmToast('error', res.message || '<?= __("error_occurred"); ?>');
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
            $btn.toggleClass('btn-warning btn-soft-warning');
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
        if (res.success) location.reload();
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
