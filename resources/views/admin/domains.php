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
                                <td class="fw-semibold">
                                    <?= sanitize($domain['domain_name']); ?>
                                    <?php if (!empty($domain['is_shared'])): ?>
                                        <span class="badge bg-info-subtle text-info ms-1"><?= __('shared'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($domain['user_id']): ?>
                                        <a href="<?= admin_url('user-edit/' . $domain['user_id']); ?>" class="text-decoration-none">
                                            <?= sanitize($domain['owner_name'] ?? 'Unknown'); ?>
                                        </a>
                                        <br><small class="text-muted"><?= sanitize($domain['owner_email'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="ri-global-line me-1"></i><?= __('system'); ?></span>
                                    <?php endif; ?>
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
                                        <button class="btn btn-sm btn-soft-secondary btn-dns-setup" data-domain="<?= htmlspecialchars(json_encode($domain)); ?>" title="<?= __('dns_setup'); ?>">
                                            <i class="ri-settings-3-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-soft-info btn-verify-domain" data-id="<?= $domain['id']; ?>" title="<?= __('verify_dns'); ?>">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                        <?php if (!empty($domain['is_shared'])): ?>
                                            <button class="btn btn-sm btn-soft-secondary btn-toggle-shared" data-id="<?= $domain['id']; ?>" title="<?= __('unset_shared'); ?>">
                                                <i class="ri-share-forward-fill"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-soft-info btn-toggle-shared" data-id="<?= $domain['id']; ?>" title="<?= __('set_shared'); ?>">
                                                <i class="ri-share-forward-line"></i>
                                            </button>
                                        <?php endif; ?>
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
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_shared" value="1" id="isSharedDomain">
                            <label class="form-check-label" for="isSharedDomain"><?= __('shared_domain'); ?></label>
                            <div class="form-text"><?= __('shared_domain_desc'); ?></div>
                        </div>
                    </div>
                    <div class="mb-3" id="ownerGroup">
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

<!-- DNS Setup Modal -->
<div class="modal fade" id="modalDnsSetup" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-dns-line me-1"></i> <?= __('dns_setup'); ?> - <span id="dnsDomainName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning fs-13">
                    <i class="ri-error-warning-line me-1 align-bottom"></i>
                    <?= __('dns_instructions'); ?> <?= __('dns_propagation'); ?>
                </div>

                <!-- MX Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('mx_record'); ?></span>
                        <span id="dnsMxBadge" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;"><?= __('type'); ?></th><td>MX</td></tr>
                            <tr><th><?= __('host'); ?></th><td>@</td></tr>
                            <tr><th><?= __('value'); ?></th><td id="dnsMxValue" class="user-select-all"></td></tr>
                            <tr><th><?= __('priority'); ?></th><td>10</td></tr>
                        </table>
                    </div>
                </div>

                <!-- SPF Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('spf_record'); ?></span>
                        <span id="dnsSpfBadge" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;"><?= __('type'); ?></th><td>TXT</td></tr>
                            <tr><th><?= __('host'); ?></th><td>@</td></tr>
                            <tr><th><?= __('value'); ?></th><td id="dnsSpfValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- DKIM Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('dkim_record'); ?></span>
                        <span id="dnsDkimBadge" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;"><?= __('type'); ?></th><td>TXT</td></tr>
                            <tr><th><?= __('host'); ?></th><td id="dnsDkimHost" class="user-select-all"></td></tr>
                            <tr><th><?= __('value'); ?></th><td id="dnsDkimValue" class="user-select-all" style="word-break:break-all;"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- DMARC Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('dmarc_record'); ?></span>
                        <span id="dnsDmarcBadge" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;"><?= __('type'); ?></th><td>TXT</td></tr>
                            <tr><th><?= __('host'); ?></th><td>_dmarc</td></tr>
                            <tr><th><?= __('value'); ?></th><td id="dnsDmarcValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= __('close'); ?></button>
                <button type="button" class="btn btn-primary" id="btnVerifyFromModal">
                    <i class="ri-refresh-line me-1"></i> <?= __('verify_dns_records'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
var currentDnsId = null;
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
                },
                error: function() {
                    showToast('error', '<?= __('server_error'); ?>');
                }
            });
        });
    });

    // Toggle shared domain checkbox - hide/show owner field
    $('#isSharedDomain').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ownerGroup').hide();
            $('#ownerGroup select').prop('required', false);
        } else {
            $('#ownerGroup').show();
            $('#ownerGroup select').prop('required', true);
        }
    });

    // Toggle shared
    $(document).on('click', '.btn-toggle-shared', function() {
        var domainId = $(this).data('id');
        $.ajax({
            url: '<?= base_url("ajaxs/admin/domains.php?action=toggle_shared"); ?>',
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
            },
            error: function() {
                showToast('error', '<?= __('server_error'); ?>');
            }
        });
    });

    // DNS Setup modal
    $(document).on('click', '.btn-dns-setup', function() {
        var domain = $(this).data('domain');
        currentDnsId = domain.id;
        var serverHost = '<?= get_setting("mail_server_hostname") ?: get_setting("mx_record_value") ?: "mail.example.com"; ?>';

        $('#dnsDomainName').text(domain.domain_name);
        $('#dnsMxValue').text(serverHost);
        $('#dnsSpfValue').text('v=spf1 mx a include:' + serverHost + ' ~all');
        $('#dnsDkimHost').text((domain.dkim_selector || 'default') + '._domainkey');
        $('#dnsDkimValue').text(domain.dkim_public_key ? 'v=DKIM1; k=rsa; p=' + domain.dkim_public_key : '<?= __("dkim_not_generated"); ?>');
        $('#dnsDmarcValue').text('v=DMARC1; p=quarantine; rua=mailto:postmaster@' + domain.domain_name);

        function badge(ok) {
            return ok ? 'badge bg-success-subtle text-success' : 'badge bg-danger-subtle text-danger';
        }
        function label(ok) { return ok ? '<?= __("verified"); ?>' : '<?= __("not_verified"); ?>'; }
        $('#dnsMxBadge').attr('class', badge(domain.mx_verified)).text(label(domain.mx_verified));
        $('#dnsSpfBadge').attr('class', badge(domain.spf_verified)).text(label(domain.spf_verified));
        $('#dnsDkimBadge').attr('class', badge(domain.dkim_verified)).text(label(domain.dkim_verified));
        $('#dnsDmarcBadge').attr('class', badge(domain.dmarc_verified)).text(label(domain.dmarc_verified));

        new bootstrap.Modal(document.getElementById('modalDnsSetup')).show();
    });

    // Verify from DNS modal
    $('#btnVerifyFromModal').on('click', function() {
        if (!currentDnsId) return;
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> <?= __("verifying"); ?>');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/domains.php?action=verify"); ?>',
            method: 'POST',
            data: { domain_id: currentDnsId },
            dataType: 'json',
            success: function(res) {
                showToast(res.status === 'success' ? 'success' : 'error', res.message);
                setTimeout(function() { location.reload(); }, 1000);
            },
            error: function() {
                showToast('error', '<?= __("server_error"); ?>');
                btn.prop('disabled', false).html('<i class="ri-refresh-line me-1"></i> <?= __("verify_dns_records"); ?>');
            }
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
                },
                error: function() {
                    showToast('error', '<?= __('server_error'); ?>');
                }
            });
        });
    });
});
</script>
