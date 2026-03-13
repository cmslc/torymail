<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Domains',
    'header' => '',
    'footer' => '',
];

// Get all domains with owner info
$domains = $ToryMail->get_list_safe("
    SELECT d.*, u.fullname as owner_name, u.email as owner_email,
           (SELECT COUNT(*) FROM mailboxes WHERE domain_id = d.id) as mailboxes_count
    FROM domains d
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY d.id DESC
");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Domains</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Domains</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">All Domains</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>MX</th>
                                <th>SPF</th>
                                <th>DKIM</th>
                                <th>DMARC</th>
                                <th>Mailboxes</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($domain['domain_name']); ?></td>
                                <td>
                                    <a href="<?= admin_url('user-edit&id=' . $domain['user_id']); ?>" class="text-decoration-none">
                                        <?= sanitize($domain['owner_name'] ?? 'Unknown'); ?>
                                    </a>
                                    <br><small class="text-muted"><?= sanitize($domain['owner_email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php if ($domain['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php elseif ($domain['status'] === 'suspended'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning"><?= ucfirst($domain['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['mx_verified'] ? 'check-line text-success' : 'close-line text-danger'; ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['spf_verified'] ? 'check-line text-success' : 'close-line text-danger'; ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['dkim_verified'] ? 'check-line text-success' : 'close-line text-danger'; ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['dmarc_verified'] ? 'check-line text-success' : 'close-line text-danger'; ?>"></i>
                                </td>
                                <td><?= $domain['mailboxes_count']; ?></td>
                                <td><small><?= format_date($domain['created_at']); ?></small></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-soft-info btn-verify-domain" data-id="<?= $domain['id']; ?>" title="Verify DNS">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                        <?php if ($domain['status'] === 'suspended'): ?>
                                            <button class="btn btn-sm btn-soft-success btn-toggle-domain" data-id="<?= $domain['id']; ?>" data-action="activate" title="Activate">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        <?php elseif ($domain['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-soft-warning btn-toggle-domain" data-id="<?= $domain['id']; ?>" data-action="suspend" title="Suspend">
                                                <i class="ri-forbid-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-soft-danger btn-delete-domain" data-id="<?= $domain['id']; ?>" title="Delete">
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

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Verify domain DNS
    $(document).on('click', '.btn-verify-domain', function() {
        var btn = $(this);
        var domainId = btn.data('id');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/domains.php?action=verify"); ?>',
            method: 'POST',
            data: { domain_id: domainId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', res.message);
                    btn.prop('disabled', false).html('<i class="ri-refresh-line"></i>');
                }
            },
            error: function() {
                showToast('error', 'Server connection error');
                btn.prop('disabled', false).html('<i class="ri-refresh-line"></i>');
            }
        });
    });

    // Suspend/Activate domain
    $(document).on('click', '.btn-toggle-domain', function() {
        var domainId = $(this).data('id');
        var action = $(this).data('action');
        var label = action === 'suspend' ? 'Suspend this domain?' : 'Activate this domain?';

        confirmAction(label, 'This will affect all mailboxes under this domain.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/domains.php?action=toggle_status"); ?>',
                method: 'POST',
                data: { domain_id: domainId, domain_action: action },
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

    // Delete domain
    $(document).on('click', '.btn-delete-domain', function() {
        var domainId = $(this).data('id');

        confirmAction('Delete Domain?', 'This will permanently delete the domain and all its mailboxes.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/domains.php?action=delete"); ?>',
                method: 'POST',
                data: { domain_id: domainId },
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
