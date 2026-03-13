<?php
$body = ['title' => 'Domains'];

// Get all domains with owner info
$domains = $ToryMail->get_list_safe("
    SELECT d.*, u.fullname as owner_name, u.email as owner_email,
           (SELECT COUNT(*) FROM mailboxes WHERE domain_id = d.id) as mailboxes_count
    FROM domains d
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY d.id DESC
");

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h4><i class="ri-global-line me-2"></i> Domains</h4>
    </div>

    <!-- Filters -->
    <div class="card-custom mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="domainsTable">
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
                                <td class="fw-semibold"><?= sanitize($domain['domain_name']) ?></td>
                                <td>
                                    <a href="<?= admin_url('user-edit&id=' . $domain['user_id']) ?>" class="text-decoration-none">
                                        <?= sanitize($domain['owner_name'] ?? 'Unknown') ?>
                                    </a>
                                    <br><small class="text-muted"><?= sanitize($domain['owner_email'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $domain['status'] ?>"><?= ucfirst($domain['status']) ?></span>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['mx_verified'] ? 'check-line dns-ok' : 'close-line dns-fail' ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['spf_verified'] ? 'check-line dns-ok' : 'close-line dns-fail' ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['dkim_verified'] ? 'check-line dns-ok' : 'close-line dns-fail' ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="ri-<?= $domain['dmarc_verified'] ? 'check-line dns-ok' : 'close-line dns-fail' ?>"></i>
                                </td>
                                <td><?= $domain['mailboxes_count'] ?></td>
                                <td><small><?= format_date($domain['created_at']) ?></small></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary btn-verify-domain" data-id="<?= $domain['id'] ?>" title="Verify DNS">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                        <?php if ($domain['status'] === 'suspended'): ?>
                                            <button class="btn btn-sm btn-outline-success btn-toggle-domain" data-id="<?= $domain['id'] ?>" data-action="activate" title="Activate">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        <?php elseif ($domain['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-outline-warning btn-toggle-domain" data-id="<?= $domain['id'] ?>" data-action="suspend" title="Suspend">
                                                <i class="ri-forbid-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-domain" data-id="<?= $domain['id'] ?>" title="Delete">
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

<script>
$(document).ready(function() {
    var table = $('#domainsTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "Search:",
            emptyTable: "No domains found"
        }
    });

    // Filter by status
    $('#filterStatus').on('change', function() {
        table.column(2).search($(this).val()).draw();
    });

    // Verify domain DNS
    $(document).on('click', '.btn-verify-domain', function() {
        var btn = $(this);
        var domainId = btn.data('id');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/domains.php?action=verify") ?>',
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
                url: '<?= base_url("ajaxs/admin/domains.php?action=toggle_status") ?>',
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
                url: '<?= base_url("ajaxs/admin/domains.php?action=delete") ?>',
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

<?php require_once __DIR__ . '/footer.php'; ?>
