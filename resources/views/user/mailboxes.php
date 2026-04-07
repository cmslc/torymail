<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('mailboxes') . ' - ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('manage_mailboxes'),
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's domains + shared domains for the add mailbox modal
$userDomains = $ToryMail->get_list_safe("
    SELECT `id`, `domain_name`, `status`, `is_shared` FROM `domains`
    WHERE (`user_id` = ? OR `is_shared` = 1) AND `status` = 'active'
    ORDER BY `is_shared` ASC, `domain_name` ASC
", [$getUser['id']]);

// Fetch mailboxes
$mailboxes = $ToryMail->get_list_safe("
    SELECT m.*, d.`domain_name`
    FROM `mailboxes` m
    JOIN `domains` d ON m.`domain_id` = d.`id`
    WHERE m.`user_id` = ?
    ORDER BY m.`created_at` DESC
", [$getUser['id']]);

$statusColors = [
    'active'   => 'success',
    'disabled' => 'secondary',
    'suspended'=> 'danger',
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('mailboxes'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('mailboxes'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-mail-settings-line me-1 align-bottom text-primary"></i> <?= __('mailboxes'); ?>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mailboxModal" onclick="resetMailboxForm()">
                <i class="ri-add-line me-1"></i> <?= __('add_mailbox'); ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($mailboxes)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-mail-settings-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted"><?= __('no_mailboxes_user'); ?></h5>
            <p class="text-muted fs-13">
                <?php if (empty($userDomains)): ?>
                <?= __('need_domain_first'); ?>
                <?php else: ?>
                <?= __('no_mailboxes_hint'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= __('email_address'); ?></th>
                        <th><?= __('display_name'); ?></th>
                        <th><?= __('usage_quota'); ?></th>
                        <th><?= __('status'); ?></th>
                        <th><?= __('created'); ?></th>
                        <th style="width:140px;"><?= __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mailboxes as $mb): ?>
                    <?php
                    $usedMB = round(($mb['used_space'] ?? 0) / 1024 / 1024, 1);
                    $quotaMB = round(($mb['quota'] ?? 0) / 1024 / 1024, 0);
                    $usagePercent = $quotaMB > 0 ? min(100, round($usedMB / $quotaMB * 100)) : 0;
                    $usageColor = $usagePercent > 90 ? 'danger' : ($usagePercent > 70 ? 'warning' : 'success');
                    ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($mb['email_address']); ?></span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($mb['display_name'] ?? '-'); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress" style="width:80px;height:6px;">
                                    <div class="progress-bar bg-<?= $usageColor; ?>" style="width:<?= $usagePercent; ?>%"></div>
                                </div>
                                <span class="text-muted fs-12">
                                    <?= $usedMB; ?> / <?= $quotaMB ?: 'Unlimited'; ?> MB
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$mb['status']] ?? 'secondary'; ?>-subtle text-<?= $statusColors[$mb['status']] ?? 'secondary'; ?>">
                                <?= ucfirst($mb['status']); ?>
                            </span>
                        </td>
                        <td class="text-muted fs-12"><?= format_date($mb['created_at'], 'M j, Y'); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-soft-primary btn-sm" onclick="editMailbox(<?= htmlspecialchars(json_encode($mb)); ?>)" title="<?= __('edit'); ?>">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-soft-secondary btn-sm"
                                        onclick="toggleMailbox(<?= $mb['id']; ?>, '<?= $mb['status']; ?>')"
                                        title="<?= $mb['status'] === 'active' ? __('disable') : __('enable'); ?>">
                                    <i class="ri-<?= $mb['status'] === 'active' ? 'pause-circle-line' : 'play-circle-line'; ?>"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteMailbox(<?= $mb['id']; ?>, '<?= htmlspecialchars($mb['email_address']); ?>')" title="<?= __('delete'); ?>">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mailbox Modal -->
<div class="modal fade" id="mailboxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailboxModalTitle"><?= __('add_mailbox'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="mailboxForm">
                    <input type="hidden" name="mailbox_id" id="mbId">

                    <div class="mb-3" id="domainSelectGroup">
                        <label class="form-label"><?= __('domain'); ?> <span class="text-danger">*</span></label>
                        <select name="domain_id" id="mbDomain" class="form-select" required>
                            <option value=""><?= __('select_domain'); ?></option>
                            <?php foreach ($userDomains as $d): ?>
                            <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['domain_name']); ?><?= !empty($d['is_shared']) ? ' (' . __('shared') . ')' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($userDomains)): ?>
                        <div class="form-text text-danger"><?= __('no_verified_domains'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="localPartGroup">
                        <label class="form-label"><?= __('email_address'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="local_part" id="mbLocalPart" class="form-control" placeholder="<?= __('email_username'); ?>">
                            <span class="input-group-text" id="domainSuffix">@domain.com</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= __('display_name'); ?></label>
                        <input type="text" name="display_name" id="mbDisplayName" class="form-control" placeholder="John Doe">
                    </div>

                    <div class="mb-3" id="passwordGroup">
                        <label class="form-label"><?= __('password'); ?> <span class="text-danger" id="pwdRequired">*</span></label>
                        <input type="password" name="password" id="mbPassword" class="form-control" placeholder="<?= __('mailbox_password'); ?>">
                        <div class="form-text" id="pwdHint"><?= __('password_min_hint'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= __('quota_mb'); ?></label>
                        <input type="number" name="quota_mb" id="mbQuota" class="form-control" value="<?= round((int)get_setting('default_quota', '52428800') / 1024 / 1024) ?>" min="0" step="1">
                        <div class="form-text"><?= __('quota_hint'); ?></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><?= __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="saveMailboxBtn"><?= __('save_mailbox'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$('#mbDomain').on('change', function() {
    var domain = $(this).find('option:selected').text();
    $('#domainSuffix').text('@' + (domain || 'domain.com'));
});

function resetMailboxForm() {
    $('#mailboxModalTitle').text(<?= json_encode(__('add_mailbox')); ?>);
    $('#mbId').val('');
    $('#mailboxForm')[0].reset();
    $('#domainSelectGroup, #localPartGroup').show();
    $('#passwordGroup').show();
    $('#pwdRequired').show();
    $('#pwdHint').text(<?= json_encode(__('password_min_hint')); ?>);
    $('#domainSuffix').text('@domain.com');
}

function editMailbox(mb) {
    $('#mailboxModalTitle').text(<?= json_encode(__('edit_mailbox')); ?>);
    $('#mbId').val(mb.id);
    $('#mbDisplayName').val(mb.display_name || '');
    $('#mbQuota').val(Math.round((mb.quota || 0) / 1024 / 1024));
    $('#mbPassword').val('');
    $('#domainSelectGroup, #localPartGroup').hide();
    $('#passwordGroup').show();
    $('#pwdRequired').hide();
    $('#pwdHint').text(<?= json_encode(__('password_keep_hint')); ?>);
    new bootstrap.Modal(document.getElementById('mailboxModal')).show();
}

$('#saveMailboxBtn').on('click', function() {
    var btn = $(this);
    if (btn.prop('disabled')) return;
    btn.prop('disabled', true);

    var data = $('#mailboxForm').serialize();
    var isEdit = !!$('#mbId').val();
    var act = isEdit ? 'edit' : 'add';

    $.ajax({
        url: '<?= base_url("ajaxs/user/mailboxes.php"); ?>?action=' + act,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__('mailbox_saved')); ?>);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || <?= json_encode(__('mailbox_save_fail')); ?>);
                btn.prop('disabled', false);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('mailbox_save_fail')); ?>;
            tmToast('error', msg);
            btn.prop('disabled', false);
        }
    });
});

function toggleMailbox(id, currentStatus) {
    var newStatus = currentStatus === 'active' ? 'disabled' : 'active';
    $.ajax({
        url: '<?= base_url("ajaxs/user/mailboxes.php"); ?>?action=toggle_status',
        method: 'POST',
        data: { mailbox_id: id, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__('done')); ?>);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || <?= json_encode(__('mailbox_save_fail')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('mailbox_save_fail')); ?>;
            tmToast('error', msg);
        }
    });
}

function deleteMailbox(id, email) {
    tmConfirm(<?= json_encode(__('delete_mailbox_user')); ?>, <?= json_encode(__('delete_mailbox_user_desc')); ?>, function() {
        $.ajax({
            url: '<?= base_url("ajaxs/user/mailboxes.php"); ?>?action=delete',
            method: 'POST',
            data: { mailbox_id: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    tmToast('success', <?= json_encode(__('mailbox_deleted')); ?>);
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    tmToast('error', res.message || <?= json_encode(__('mailbox_delete_fail')); ?>);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('mailbox_delete_fail')); ?>;
                tmToast('error', msg);
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
