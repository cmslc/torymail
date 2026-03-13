<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('domains'),
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
            <h4 class="mb-sm-0"><?= __('domains'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>"><?= __('admin'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('domains'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><?= __('all_domains'); ?></h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddDomain">
                    <i class="ri-add-line me-1"></i> <?= __('add_domain'); ?>
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= __('domain'); ?></th>
                                <th><?= __('owner'); ?></th>
                                <th><?= __('status'); ?></th>
                                <th>MX</th>
                                <th>SPF</th>
                                <th>DKIM</th>
                                <th>DMARC</th>
                                <th><?= __('mailboxes'); ?></th>
                                <th><?= __('created'); ?></th>
                                <th><?= __('actions'); ?></th>
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
                                        <span class="badge bg-success-subtle text-success"><?= __('active'); ?></span>
                                    <?php elseif ($domain['status'] === 'suspended'): ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= __('suspended'); ?></span>
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
                                        <button class="btn btn-sm btn-soft-info btn-verify-domain" data-id="<?= $domain['id']; ?>" title="<?= __('verify_dns'); ?>">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                        <?php if ($domain['status'] === 'suspended'): ?>
                                            <button class="btn btn-sm btn-soft-success btn-toggle-domain" data-id="<?= $domain['id']; ?>" data-action="activate" title="<?= __('activate'); ?>">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        <?php elseif ($domain['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-soft-warning btn-toggle-domain" data-id="<?= $domain['id']; ?>" data-action="suspend" title="<?= __('suspend'); ?>">
                                                <i class="ri-forbid-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-soft-danger btn-delete-domain" data-id="<?= $domain['id']; ?>" title="<?= __('delete'); ?>">
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

<!-- Modal Add Domain -->
<div class="modal fade" id="modalAddDomain" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-global-line me-1"></i> <?= __('add_domain'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddDomain">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('domain_name'); ?></label>
                        <input type="text" name="domain_name" class="form-control" placeholder="example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('owner'); ?></label>
                        <select name="user_id" class="form-select" required>
                            <option value=""><?= __('select_user'); ?></option>
                            <?php
                            $users = $ToryMail->get_list_safe("SELECT id, fullname, email FROM users ORDER BY fullname ASC");
                            foreach ($users as $u):
                            ?>
                                <option value="<?= $u['id']; ?>"><?= sanitize($u['fullname']); ?> (<?= sanitize($u['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_verify" value="1" id="autoVerify">
                        <label class="form-check-label" for="autoVerify"><?= __('auto_verify'); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><i class="ri-add-line me-1"></i> <?= __('add_domain'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Add domain
    $('#formAddDomain').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> <?= __('adding'); ?>');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/domains.php?action=add"); ?>',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', res.message);
                    btn.prop('disabled', false).html('<i class="ri-add-line me-1"></i> <?= __('add_domain'); ?>');
                }
            },
            error: function() {
                showToast('error', '<?= __('server_error'); ?>');
                btn.prop('disabled', false).html('<i class="ri-add-line me-1"></i> <?= __('add_domain'); ?>');
            }
        });
    });

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
                showToast('error', '<?= __('server_error'); ?>');
                btn.prop('disabled', false).html('<i class="ri-refresh-line"></i>');
            }
        });
    });

    // Suspend/Activate domain
    $(document).on('click', '.btn-toggle-domain', function() {
        var domainId = $(this).data('id');
        var action = $(this).data('action');
        var label = action === 'suspend' ? '<?= __('suspend_domain'); ?>' : '<?= __('activate_domain'); ?>';

        confirmAction(label, '<?= __('suspend_domain_desc'); ?>', function() {
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

        confirmAction('<?= __('delete_domain'); ?>', '<?= __('delete_domain_desc'); ?>', function() {
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
