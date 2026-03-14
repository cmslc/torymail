<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('domains') . ' - Torymail',
    'desc'  => __('manage_domains'),
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's own domains + shared domains
$domains = $ToryMail->get_list_safe("
    SELECT d.*,
           (SELECT COUNT(*) FROM `mailboxes` m WHERE m.`domain_id` = d.`id`) as mailbox_count,
           (SELECT COUNT(*) FROM `mailboxes` m WHERE m.`domain_id` = d.`id` AND m.`user_id` = ?) as my_mailbox_count
    FROM `domains` d
    WHERE d.`user_id` = ? OR d.`is_shared` = 1
    ORDER BY d.`is_shared` ASC, d.`created_at` DESC
", [$getUser['id'], $getUser['id']]);

$statusColors = [
    'pending'   => 'warning',
    'active'    => 'success',
    'suspended' => 'danger',
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('domains'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('domains'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-global-line me-1 align-bottom text-primary"></i> <?= __('domains'); ?>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#domainModal">
                <i class="ri-add-line me-1"></i> <?= __('add_domain'); ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($domains)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-global-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted"><?= __('no_domains_user'); ?></h5>
            <p class="text-muted fs-13"><?= __('no_domains_hint'); ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= __('domain'); ?></th>
                        <th><?= __('status'); ?></th>
                        <th class="text-center">TXT</th>
                        <th class="text-center">MX</th>
                        <th class="text-center">SPF</th>
                        <th class="text-center">DKIM</th>
                        <th class="text-center">DMARC</th>
                        <th class="text-center"><?= __('mailboxes'); ?></th>
                        <th><?= __('created'); ?></th>
                        <th style="width:120px;"><?= __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($domain['domain_name']); ?></span>
                            <?php if (!empty($domain['is_shared'])): ?>
                                <span class="badge bg-info-subtle text-info ms-1"><?= __('shared'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$domain['status']] ?? 'secondary'; ?>-subtle text-<?= $statusColors[$domain['status']] ?? 'secondary'; ?>">
                                <?= ucfirst($domain['status']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php $txtOk = $domain['status'] === 'active' || $domain['verification_token'] === 'verified'; ?>
                            <?php if ($txtOk): ?>
                            <i class="ri-check-line text-success fs-18"></i>
                            <?php else: ?>
                            <i class="ri-close-line text-danger fs-18"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($domain['mx_verified'] ?? false): ?>
                            <i class="ri-check-line text-success fs-18"></i>
                            <?php else: ?>
                            <i class="ri-close-line text-danger fs-18"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($domain['spf_verified'] ?? false): ?>
                            <i class="ri-check-line text-success fs-18"></i>
                            <?php else: ?>
                            <i class="ri-close-line text-danger fs-18"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($domain['dkim_verified'] ?? false): ?>
                            <i class="ri-check-line text-success fs-18"></i>
                            <?php else: ?>
                            <i class="ri-close-line text-danger fs-18"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($domain['dmarc_verified'] ?? false): ?>
                            <i class="ri-check-line text-success fs-18"></i>
                            <?php else: ?>
                            <i class="ri-close-line text-danger fs-18"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary"><?= $domain['mailbox_count']; ?></span>
                        </td>
                        <td class="text-muted fs-12"><?= format_date($domain['created_at'], 'M j, Y'); ?></td>
                        <td>
                            <?php if (empty($domain['is_shared'])): ?>
                            <div class="d-flex gap-1">
                                <button class="btn btn-soft-secondary btn-sm" onclick="showDnsSetup(<?= htmlspecialchars(json_encode($domain)); ?>)" title="<?= __('dns_setup'); ?>">
                                    <i class="ri-settings-3-line"></i>
                                </button>
                                <button class="btn btn-soft-primary btn-sm" onclick="verifyDomain(<?= $domain['id']; ?>)" title="<?= __('verify_dns'); ?>">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteDomain(<?= $domain['id']; ?>, '<?= htmlspecialchars($domain['domain_name']); ?>')" title="<?= __('delete'); ?>">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <span class="text-muted fs-12"><?= __('managed_by_admin'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Domain Modal -->
<div class="modal fade" id="domainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('add_domain'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= __('domain_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="domainName" class="form-control" placeholder="<?= __('domain_placeholder'); ?>">
                    <div class="form-text"><?= __('domain_hint'); ?></div>
                </div>
                <div class="alert alert-info fs-13">
                    <i class="ri-information-line me-1 align-bottom"></i>
                    <?= __('domain_dns_note'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><?= __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="addDomainBtn">
                    <i class="ri-add-line me-1"></i> <?= __('add_domain'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DNS Setup Modal -->
<div class="modal fade" id="dnsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('dns_setup'); ?> - <span id="dnsDomainName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning fs-13">
                    <i class="ri-error-warning-line me-1 align-bottom"></i>
                    <?= __('dns_instructions'); ?> <?= __('dns_propagation'); ?>
                </div>

                <!-- TXT Verification Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('txt_verification'); ?></span>
                        <span id="dnsTxt" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;"><?= __('type'); ?></th><td>TXT</td></tr>
                            <tr><th><?= __('host'); ?></th><td>@</td></tr>
                            <tr><th><?= __('value'); ?></th><td id="dnsTxtValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- MX Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span><?= __('mx_record'); ?></span>
                        <span id="dnsMx" class="badge"></span>
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
                        <span id="dnsSpf" class="badge"></span>
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
                        <span id="dnsDkim" class="badge"></span>
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
                        <span id="dnsDmarc" class="badge"></span>
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
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><?= __('close'); ?></button>
                <button type="button" class="btn btn-primary" id="verifyDnsBtn">
                    <i class="ri-refresh-line me-1"></i> <?= __('verify_dns_records'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var currentDomainId = null;

$('#addDomainBtn').on('click', function() {
    var domain = $('#domainName').val().trim();
    if (!domain) { tmToast('warning', '<?= __("enter_domain_warning"); ?>'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> <?= __("adding"); ?>');

    $.post('<?= base_url("ajaxs/user/domains.php"); ?>?action=add', {
        domain_name: domain
    }, function(res) {
        if (res.success) {
            tmToast('success', '<?= __("domain_added"); ?>');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            tmToast('error', res.message || '<?= __("domain_add_fail"); ?>');
            $btn.prop('disabled', false).html('<i class="ri-add-line me-1"></i> <?= __("add_domain"); ?>');
        }
    }, 'json');
});

function showDnsSetup(domain) {
    currentDomainId = domain.id;
    $('#dnsDomainName').text(domain.domain_name);

    var serverHost = '<?= get_setting("mail_server_hostname") ?: $_SERVER["HTTP_HOST"]; ?>';
    var txtVerified = domain.status === 'active' || domain.verification_token === 'verified';
    $('#dnsTxtValue').text(domain.verification_token || '');
    $('#dnsMxValue').text(serverHost);
    $('#dnsSpfValue').text('v=spf1 mx a include:' + serverHost + ' ~all');
    $('#dnsDkimHost').text((domain.dkim_selector || 'default') + '._domainkey');
    $('#dnsDkimValue').text(domain.dkim_public_key ? 'v=DKIM1; k=rsa; p=' + domain.dkim_public_key : '(DKIM key will be generated after verification)');
    $('#dnsDmarcValue').text('v=DMARC1; p=quarantine; rua=mailto:postmaster@' + domain.domain_name);

    // Status indicators
    function dnsStatus(verified) {
        return verified ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
    }
    function dnsLabel(verified) { return verified ? '<?= __("verified"); ?>' : '<?= __("pending"); ?>'; }
    $('#dnsTxt').attr('class', 'badge ' + dnsStatus(txtVerified)).text(dnsLabel(txtVerified));
    $('#dnsMx').attr('class', 'badge ' + dnsStatus(domain.mx_verified)).text(dnsLabel(domain.mx_verified));
    $('#dnsSpf').attr('class', 'badge ' + dnsStatus(domain.spf_verified)).text(dnsLabel(domain.spf_verified));
    $('#dnsDkim').attr('class', 'badge ' + dnsStatus(domain.dkim_verified)).text(dnsLabel(domain.dkim_verified));
    $('#dnsDmarc').attr('class', 'badge ' + dnsStatus(domain.dmarc_verified)).text(dnsLabel(domain.dmarc_verified));

    new bootstrap.Modal(document.getElementById('dnsModal')).show();
}

$('#verifyDnsBtn').on('click', function() {
    if (currentDomainId) verifyDomain(currentDomainId);
});

function verifyDomain(id) {
    $.post('<?= base_url("ajaxs/user/domains.php"); ?>?action=verify', {
        domain_id: id
    }, function(res) {
        if (res.success) {
            tmToast('success', res.message || '<?= __("dns_complete"); ?>');
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            tmToast('error', res.message || '<?= __("dns_failed"); ?>');
        }
    }, 'json');
}

function deleteDomain(id, name) {
    tmConfirm('<?= __("delete_domain_user"); ?>', '<?= __("delete_domain_user_desc"); ?>', function() {
        $.post('<?= base_url("ajaxs/user/domains.php"); ?>?action=delete', {
            domain_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', '<?= __("domain_deleted"); ?>');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || '<?= __("domain_delete_fail"); ?>');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
