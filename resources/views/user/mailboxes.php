<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Mailboxes - Torymail',
    'desc'  => 'Manage your mailboxes',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's domains for the add mailbox modal
$userDomains = $ToryMail->get_list_safe("
    SELECT `id`, `domain`, `status` FROM `domains`
    WHERE `user_id` = ? AND `status` = 'verified'
    ORDER BY `domain` ASC
", [$getUser['id']]);

// Fetch mailboxes
$mailboxes = $ToryMail->get_list_safe("
    SELECT m.*, d.`domain`
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

<div class="tm-card">
    <div class="tm-card-header">
        <h5 class="mb-0 fw-semibold" style="font-size:18px;">
            <i class="ri-inbox-2-line me-2 text-primary"></i> Mailboxes
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mailboxModal" onclick="resetMailboxForm()">
            <i class="ri-add-line me-1"></i> Add Mailbox
        </button>
    </div>

    <?php if (empty($mailboxes)): ?>
    <div class="text-center py-5">
        <i class="ri-inbox-2-line" style="font-size:48px;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-1">No mailboxes configured</p>
        <p class="text-muted" style="font-size:13px;">
            <?php if (empty($userDomains)): ?>
            You need to <a href="<?= base_url('domains'); ?>">add and verify a domain</a> first.
            <?php else: ?>
            Create a mailbox to start sending and receiving emails.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:14px;">
            <thead class="table-light">
                <tr>
                    <th>Email Address</th>
                    <th>Display Name</th>
                    <th>Usage / Quota</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mailboxes as $mb): ?>
                <?php
                $usedMB = round(($mb['used_quota'] ?? 0) / 1024 / 1024, 1);
                $quotaMB = round(($mb['quota'] ?? 0) / 1024 / 1024, 0);
                $usagePercent = $quotaMB > 0 ? min(100, round($usedMB / $quotaMB * 100)) : 0;
                $usageColor = $usagePercent > 90 ? 'danger' : ($usagePercent > 70 ? 'warning' : 'success');
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($mb['email']); ?></div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($mb['display_name'] ?? '-'); ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress" style="width:80px;height:6px;">
                                <div class="progress-bar bg-<?= $usageColor; ?>" style="width:<?= $usagePercent; ?>%"></div>
                            </div>
                            <span style="font-size:12px;color:#6b7280;">
                                <?= $usedMB; ?> / <?= $quotaMB ?: 'Unlimited'; ?> MB
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$mb['status']] ?? 'secondary'; ?>">
                            <?= ucfirst($mb['status']); ?>
                        </span>
                    </td>
                    <td class="text-muted" style="font-size:12px;"><?= format_date($mb['created_at'], 'M j, Y'); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light" onclick="editMailbox(<?= htmlspecialchars(json_encode($mb)); ?>)" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-light"
                                    onclick="toggleMailbox(<?= $mb['id']; ?>, '<?= $mb['status']; ?>')"
                                    title="<?= $mb['status'] === 'active' ? 'Disable' : 'Enable'; ?>">
                                <i class="ri-<?= $mb['status'] === 'active' ? 'pause-circle-line' : 'play-circle-line'; ?>"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteMailbox(<?= $mb['id']; ?>, '<?= htmlspecialchars($mb['email']); ?>')" title="Delete">
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

<!-- Mailbox Modal -->
<div class="modal fade" id="mailboxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailboxModalTitle">Add Mailbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="mailboxForm">
                    <input type="hidden" name="mailbox_id" id="mbId">

                    <!-- Domain selector (only for add) -->
                    <div class="mb-3" id="domainSelectGroup">
                        <label class="form-label">Domain <span class="text-danger">*</span></label>
                        <select name="domain_id" id="mbDomain" class="form-select" required>
                            <option value="">Select domain</option>
                            <?php foreach ($userDomains as $d): ?>
                            <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['domain']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($userDomains)): ?>
                        <div class="form-text text-danger">No verified domains. <a href="<?= base_url('domains'); ?>">Add a domain first.</a></div>
                        <?php endif; ?>
                    </div>

                    <!-- Local part (only for add) -->
                    <div class="mb-3" id="localPartGroup">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="local_part" id="mbLocalPart" class="form-control" placeholder="username">
                            <span class="input-group-text" id="domainSuffix">@domain.com</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" id="mbDisplayName" class="form-control" placeholder="John Doe">
                    </div>

                    <!-- Password -->
                    <div class="mb-3" id="passwordGroup">
                        <label class="form-label">Password <span class="text-danger" id="pwdRequired">*</span></label>
                        <input type="password" name="password" id="mbPassword" class="form-control" placeholder="Mailbox password">
                        <div class="form-text" id="pwdHint">Minimum 8 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quota (MB)</label>
                        <input type="number" name="quota_mb" id="mbQuota" class="form-control" value="1024" min="0" step="1">
                        <div class="form-text">Set to 0 for unlimited. Default is 1024 MB (1 GB).</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveMailboxBtn">Save Mailbox</button>
            </div>
        </div>
    </div>
</div>

<script>
// Update domain suffix in real-time
$('#mbDomain').on('change', function() {
    var domain = $(this).find('option:selected').text();
    $('#domainSuffix').text('@' + (domain || 'domain.com'));
});

function resetMailboxForm() {
    $('#mailboxModalTitle').text('Add Mailbox');
    $('#mbId').val('');
    $('#mailboxForm')[0].reset();
    $('#domainSelectGroup, #localPartGroup').show();
    $('#pwdRequired').show();
    $('#pwdHint').text('Minimum 8 characters.');
    $('#domainSuffix').text('@domain.com');
}

function editMailbox(mb) {
    $('#mailboxModalTitle').text('Edit Mailbox');
    $('#mbId').val(mb.id);
    $('#mbDisplayName').val(mb.display_name || '');
    $('#mbQuota').val(Math.round((mb.quota || 0) / 1024 / 1024));
    $('#mbPassword').val('');
    // Hide domain/local part for editing
    $('#domainSelectGroup, #localPartGroup').hide();
    $('#pwdRequired').hide();
    $('#pwdHint').text('Leave blank to keep current password.');
    new bootstrap.Modal(document.getElementById('mailboxModal')).show();
}

$('#saveMailboxBtn').on('click', function() {
    var data = $('#mailboxForm').serialize();
    var isEdit = !!$('#mbId').val();
    data += '&action=' + (isEdit ? 'update' : 'create');

    $.post('<?= base_url("ajaxs/user/mailboxes.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Mailbox saved!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to save mailbox.');
        }
    }, 'json');
});

function toggleMailbox(id, currentStatus) {
    var newStatus = currentStatus === 'active' ? 'disabled' : 'active';
    $.post('<?= base_url("ajaxs/user/mailboxes.php"); ?>', {
        action: 'toggle_status',
        mailbox_id: id,
        status: newStatus
    }, function(res) {
        if (res.success) {
            tmToast('success', 'Mailbox ' + newStatus + '.');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to update mailbox.');
        }
    }, 'json');
}

function deleteMailbox(id, email) {
    tmConfirm('Delete mailbox "' + email + '"?', 'All emails in this mailbox will be permanently deleted.', function() {
        $.post('<?= base_url("ajaxs/user/mailboxes.php"); ?>', {
            action: 'delete',
            mailbox_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Mailbox deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete mailbox.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
