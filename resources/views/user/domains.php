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

<div class="tm-card">
    <div class="tm-card-header">
        <h5 class="mb-0 fw-semibold" style="font-size:18px;">
            <i class="ri-global-line me-2 text-primary"></i> Domains
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#domainModal">
            <i class="ri-add-line me-1"></i> Add Domain
        </button>
    </div>

    <?php if (empty($domains)): ?>
    <div class="text-center py-5">
        <i class="ri-global-line" style="font-size:48px;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-1">No domains configured</p>
        <p class="text-muted" style="font-size:13px;">Add a domain to start receiving and sending emails.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:14px;">
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
                        <div class="fw-semibold"><?= htmlspecialchars($domain['domain']); ?></div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$domain['status']] ?? 'secondary'; ?>">
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
                        <span class="badge bg-light text-dark"><?= $domain['mailbox_count']; ?></span>
                    </td>
                    <td class="text-muted" style="font-size:12px;"><?= format_date($domain['created_at'], 'M j, Y'); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light" onclick="showDnsSetup(<?= htmlspecialchars(json_encode($domain)); ?>)" title="DNS Setup">
                                <i class="ri-settings-3-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="verifyDomain(<?= $domain['id']; ?>)" title="Verify DNS">
                                <i class="ri-refresh-line"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteDomain(<?= $domain['id']; ?>, '<?= htmlspecialchars($domain['domain']); ?>')" title="Delete">
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
                <div class="alert alert-info" style="font-size:13px;">
                    <i class="ri-information-line me-1"></i>
                    After adding the domain, you will need to configure DNS records (MX, SPF, DKIM, DMARC) to verify ownership and enable email delivery.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
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
                <div class="alert alert-warning" style="font-size:13px;">
                    <i class="ri-error-warning-line me-1"></i>
                    Add the following DNS records to your domain's DNS settings. Changes may take up to 48 hours to propagate.
                </div>

                <!-- MX Record -->
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2">
                        <span>MX Record</span>
                        <span class="dns-status" id="dnsMx"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="font-size:13px;">
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
                        <span class="dns-status" id="dnsSpf"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="font-size:13px;">
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
                        <span class="dns-status" id="dnsDkim"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="font-size:13px;">
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
                        <span class="dns-status" id="dnsDmarc"></span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="font-size:13px;">
                            <tr><th style="width:100px;">Type</th><td>TXT</td></tr>
                            <tr><th>Host</th><td>_dmarc</td></tr>
                            <tr><th>Value</th><td id="dnsDmarcValue" class="user-select-all"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="verifyDnsBtn">
                    <i class="ri-refresh-line me-1"></i> Verify DNS Records
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.dns-status {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 500;
}
.dns-status.verified { background: #d1fae5; color: #065f46; }
.dns-status.pending { background: #fef3c7; color: #92400e; }
</style>

<script>
var currentDomainId = null;

$('#addDomainBtn').on('click', function() {
    var domain = $('#domainName').val().trim();
    if (!domain) {
        tmToast('warning', 'Please enter a domain name.');
        return;
    }

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

    // Set DNS values from domain data or defaults
    var serverHost = '<?= get_setting("mail_server_hostname") ?: $_SERVER["HTTP_HOST"]; ?>';
    $('#dnsMxValue').text(domain.mx_value || 'mail.' + serverHost);
    $('#dnsSpfValue').text(domain.spf_value || 'v=spf1 include:' + serverHost + ' ~all');
    $('#dnsDkimHost').text(domain.dkim_selector || 'default._domainkey');
    $('#dnsDkimValue').text(domain.dkim_value || '(DKIM key will be generated after verification)');
    $('#dnsDmarcValue').text(domain.dmarc_value || 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' + domain.domain);

    // Status indicators
    $('#dnsMx').text(domain.mx_verified ? 'Verified' : 'Pending').attr('class', 'dns-status ' + (domain.mx_verified ? 'verified' : 'pending'));
    $('#dnsSpf').text(domain.spf_verified ? 'Verified' : 'Pending').attr('class', 'dns-status ' + (domain.spf_verified ? 'verified' : 'pending'));
    $('#dnsDkim').text(domain.dkim_verified ? 'Verified' : 'Pending').attr('class', 'dns-status ' + (domain.dkim_verified ? 'verified' : 'pending'));
    $('#dnsDmarc').text(domain.dmarc_verified ? 'Verified' : 'Pending').attr('class', 'dns-status ' + (domain.dmarc_verified ? 'verified' : 'pending'));

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
