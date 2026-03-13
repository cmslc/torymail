<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Mailboxes',
    'header' => '',
    'footer' => '',
];

// Get all mailboxes with domain and owner info
$mailboxes = $ToryMail->get_list_safe("
    SELECT m.*, d.domain_name, u.fullname as owner_name, u.email as owner_email
    FROM mailboxes m
    JOIN domains d ON m.domain_id = d.id
    JOIN users u ON m.user_id = u.id
    ORDER BY m.id DESC
");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Mailboxes</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Mailboxes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">All Mailboxes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Domain</th>
                                <th>Owner</th>
                                <th>Used / Quota</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mailboxes as $mb): ?>
                            <?php $pct = $mb['quota'] > 0 ? round(($mb['used_space'] / $mb['quota']) * 100) : 0; ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($mb['email_address']); ?></td>
                                <td><?= sanitize($mb['domain_name']); ?></td>
                                <td>
                                    <a href="<?= admin_url('user-edit&id=' . $mb['user_id']); ?>" class="text-decoration-none">
                                        <?= sanitize($mb['owner_name']); ?>
                                    </a>
                                </td>
                                <td style="min-width:180px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-sm flex-grow-1" style="height:6px;">
                                            <div class="progress-bar bg-<?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'primary'); ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>
                                        <small class="text-muted text-nowrap"><?= $pct; ?>%</small>
                                    </div>
                                    <small class="text-muted"><?= format_email_size($mb['used_space']); ?> / <?= format_email_size($mb['quota']); ?></small>
                                </td>
                                <td>
                                    <?php if ($mb['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php elseif ($mb['status'] === 'disabled'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Disabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning"><?= ucfirst($mb['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= format_date($mb['created_at']); ?></small></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($mb['status'] === 'disabled'): ?>
                                            <button class="btn btn-sm btn-soft-success btn-toggle-mailbox" data-id="<?= $mb['id']; ?>" data-action="enable" title="Enable">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-soft-warning btn-toggle-mailbox" data-id="<?= $mb['id']; ?>" data-action="disable" title="Disable">
                                                <i class="ri-forbid-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-soft-secondary btn-reset-password" data-id="<?= $mb['id']; ?>" data-email="<?= sanitize($mb['email_address']); ?>" title="Reset Password">
                                            <i class="ri-key-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-soft-danger btn-delete-mailbox" data-id="<?= $mb['id']; ?>" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-key-line me-2"></i> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" name="mailbox_id" id="resetMailboxId">
                <div class="modal-body">
                    <p class="mb-3">Reset password for: <strong id="resetMailboxEmail"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Enable/Disable mailbox
    $(document).on('click', '.btn-toggle-mailbox', function() {
        var mbId = $(this).data('id');
        var action = $(this).data('action');
        var label = action === 'disable' ? 'Disable this mailbox?' : 'Enable this mailbox?';

        confirmAction(label, '', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/mailboxes.php?action=toggle_status"); ?>',
                method: 'POST',
                data: { mailbox_id: mbId, mb_action: action },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        showToast('success', res.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast('error', res.message);
                    }
                }
            });
        });
    });

    // Reset password modal
    $(document).on('click', '.btn-reset-password', function() {
        $('#resetMailboxId').val($(this).data('id'));
        $('#resetMailboxEmail').text($(this).data('email'));
        new bootstrap.Modal('#resetPasswordModal').show();
    });

    // Reset password submit
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Resetting...');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/mailboxes.php?action=reset_password"); ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    bootstrap.Modal.getInstance('#resetPasswordModal').hide();
                } else {
                    showToast('error', res.message);
                }
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Reset');
            },
            error: function() {
                showToast('error', 'Server connection error');
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Reset');
            }
        });
    });

    // Delete mailbox
    $(document).on('click', '.btn-delete-mailbox', function() {
        var mbId = $(this).data('id');

        confirmAction('Delete Mailbox?', 'This will permanently delete the mailbox and all its emails.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/mailboxes.php?action=delete"); ?>',
                method: 'POST',
                data: { mailbox_id: mbId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        showToast('success', res.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast('error', res.message);
                    }
                }
            });
        });
    });
});
</script>
