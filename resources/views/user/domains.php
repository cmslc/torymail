<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Domains - Torymail',
    'desc'  => 'Manage your email domains',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's domains
$domains = $ToryMail->get_list_safe("
    SELECT d.*,
           (SELECT COUNT(*) FROM `mailboxes` m WHERE m.`domain_id` = d.`id`) as mailbox_count
    FROM `domains` d
    WHERE d.`user_id` = ?
    ORDER BY d.`created_at` DESC
", [$getUser['id']]);

$statusColors = [
    'pending'  => 'warning',
    'verified' => 'success',
    'failed'   => 'danger',
    'inactive' => 'secondary',
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Domains</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>">Home</a></li>
                    <li class="breadcrumb-item active">Domains</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-global-line me-1 align-bottom text-primary"></i> Domains
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#domainModal">
                <i class="ri-add-line me-1"></i> Add Domain
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($domains)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-global-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted">No domains configured</h5>
            <p class="text-muted fs-13">Add a domain to start receiving and sending emails.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="text-center">MX</th>
                        <th class="text-center">SPF</th>
                        <th class="text-center">DKIM</th>
                        <th class="text-center">DMARC</th>
                        <th class="text-center">Mailboxes</th>
                        <th>Added</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($domain['domain']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$domain['status']] ?? 'secondary'; ?>-subtle text-<?= $statusColors[$domain['status']] ?? 'secondary'; ?>">
                                <?= ucfirst($domain['status']); ?>
                            </span>
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
                            <div class="d-flex gap-1">
                                <button class="btn btn-soft-secondary btn-sm" onclick="showDnsSetup(<?= htmlspecialchars(json_encode($domain)); ?>)" title="DNS Setup">
                                    <i class="ri-settings-3-line"></i>
                                </button>
                                <button class="btn btn-soft-primary btn-sm" onclick="verifyDomain(<?= $domain['id']; ?>)" title="Verify DNS">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteDomain(<?= $domain['id']; ?>, '<?= htmlspecialchars($domain['domain']); ?>')" title="Delete">
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

<!-- Add Domain Modal -->
<div class="modal fade" id="domainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Domain Name <span class="text-danger">*</span></label>
                    <input type="text" id="domainName" class="form-control" placeholder="example.com">
                    <div class="form-text">Enter your domain name without http:// or www.</div>
                </div>
                <div class="alert alert-info fs-13">
                    <i class="ri-information-line me-1 align-bottom"></i>
                    After adding the domain, you will need to configure DNS records (MX, SPF, DKIM, DMARC) to verify ownership and enable email delivery.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addDomainBtn">
                    <i class="ri-add-line me-1"></i> Add Domain
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
                <h5 class="modal-title">DNS Setup - <span id="dnsDomainName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning fs-13">
                    <i class="ri-error-warning-line me-1 align-bottom"></i>
                    Add the following DNS records to your domain's DNS settings. Changes may take up to 48 hours to propagate.
                </div>

                <!-- MX Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span>MX Record</span>
                        <span id="dnsMx" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;">Type</th><td>MX</td></tr>
                            <tr><th>Host</th><td>@</td></tr>
                            <tr><th>Value</th><td id="dnsMxValue" class="user-select-all"></td></tr>
                            <tr><th>Priority</th><td>10</td></tr>
                        </table>
                    </div>
                </div>

                <!-- SPF Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span>SPF Record</span>
                        <span id="dnsSpf" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;">Type</th><td>TXT</td></tr>
                            <tr><th>Host</th><td>@</td></tr>
                            <tr><th>Value</th><td id="dnsSpfValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- DKIM Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span>DKIM Record</span>
                        <span id="dnsDkim" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;">Type</th><td>TXT</td></tr>
                            <tr><th>Host</th><td id="dnsDkimHost" class="user-select-all"></td></tr>
                            <tr><th>Value</th><td id="dnsDkimValue" class="user-select-all" style="word-break:break-all;"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- DMARC Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span>DMARC Record</span>
                        <span id="dnsDmarc" class="badge"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 fs-13">
                            <tr><th style="width:100px;">Type</th><td>TXT</td></tr>
                            <tr><th>Host</th><td>_dmarc</td></tr>
                            <tr><th>Value</th><td id="dnsDmarcValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="verifyDnsBtn">
                    <i class="ri-refresh-line me-1"></i> Verify DNS Records
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var currentDomainId = null;

$('#addDomainBtn').on('click', function() {
    var domain = $('#domainName').val().trim();
    if (!domain) { tmToast('warning', 'Please enter a domain name.'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Adding...');

    $.post('<?= base_url("ajaxs/user/domains.php"); ?>', {
        action: 'create',
        domain: domain
    }, function(res) {
        if (res.success) {
            tmToast('success', 'Domain added! Please configure DNS records.');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            tmToast('error', res.message || 'Failed to add domain.');
            $btn.prop('disabled', false).html('<i class="ri-add-line me-1"></i> Add Domain');
        }
    }, 'json');
});

function showDnsSetup(domain) {
    currentDomainId = domain.id;
    $('#dnsDomainName').text(domain.domain);

    var serverHost = '<?= get_setting("mail_server_hostname") ?: $_SERVER["HTTP_HOST"]; ?>';
    $('#dnsMxValue').text(domain.mx_value || 'mail.' + serverHost);
    $('#dnsSpfValue').text(domain.spf_value || 'v=spf1 include:' + serverHost + ' ~all');
    $('#dnsDkimHost').text(domain.dkim_selector || 'default._domainkey');
    $('#dnsDkimValue').text(domain.dkim_value || '(DKIM key will be generated after verification)');
    $('#dnsDmarcValue').text(domain.dmarc_value || 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' + domain.domain);

    // Status indicators
    function dnsStatus(verified) {
        return verified ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
    }
    function dnsLabel(verified) { return verified ? 'Verified' : 'Pending'; }
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
    $.post('<?= base_url("ajaxs/user/domains.php"); ?>', {
        action: 'verify',
        domain_id: id
    }, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'DNS verification complete!');
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            tmToast('error', res.message || 'DNS verification failed. Please check your records.');
        }
    }, 'json');
}

function deleteDomain(id, name) {
    tmConfirm('Delete domain "' + name + '"?', 'All mailboxes under this domain will also be removed.', function() {
        $.post('<?= base_url("ajaxs/user/domains.php"); ?>', {
            action: 'delete',
            domain_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Domain deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete domain.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
