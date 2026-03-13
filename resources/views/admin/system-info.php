<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('system_info'),
    'header' => '',
    'footer' => '',
];

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

// ============================================================
// Gather system information
// ============================================================

// Torymail
$tmVersion = TORYMAIL_VERSION;
$tmInstallPath = realpath(__DIR__ . '/../../..');

// PHP
$phpVersion = PHP_VERSION;
$phpSapi = php_sapi_name();
$phpExtensions = get_loaded_extensions();
$phpMemoryLimit = ini_get('memory_limit');
$phpMaxUpload = ini_get('upload_max_filesize');
$phpPostMax = ini_get('post_max_size');
$phpMaxExecTime = ini_get('max_execution_time');
$requiredExts = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'curl', 'iconv', 'fileinfo'];

// Server
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$serverOS = PHP_OS . ' ' . php_uname('r');
$serverHostname = gethostname();
$serverIP = $_SERVER['SERVER_ADDR'] ?? (gethostbyname(gethostname()) ?: 'Unknown');

// Database
$dbVersion = '';
try {
    $dbVersionRow = $ToryMail->get_row_safe("SELECT VERSION() as ver", []);
    $dbVersion = $dbVersionRow['ver'] ?? 'Unknown';
} catch (Exception $e) {
    $dbVersion = 'Error: ' . $e->getMessage();
}

$dbName = getenv('DB_DATABASE') ?: 'torymail';

// Table sizes
$tableStats = [];
try {
    $tableStats = $ToryMail->get_list_safe(
        "SELECT table_name, table_rows, ROUND(data_length/1024/1024, 2) as data_mb, ROUND(index_length/1024/1024, 2) as index_mb
         FROM information_schema.tables
         WHERE table_schema = ?
         ORDER BY data_length DESC",
        [$dbName]
    );
} catch (Exception $e) {}

$totalDbSize = 0;
foreach ($tableStats as $t) {
    $totalDbSize += floatval($t['data_mb']) + floatval($t['index_mb']);
}

// Counts
$totalUsers = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users", []) ?: 0;
$totalDomains = $ToryMail->get_value_safe("SELECT COUNT(*) FROM domains", []) ?: 0;
$totalMailboxes = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes", []) ?: 0;
$totalEmails = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails", []) ?: 0;
$queuePending = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'", []) ?: 0;
$queueFailed = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'", []) ?: 0;

// Mail services detection
function checkService($name) {
    $output = [];
    $code = 0;
    @exec("systemctl is-active " . escapeshellarg($name) . " 2>/dev/null", $output, $code);
    $status = trim(implode('', $output));
    if ($status === 'active') return 'running';
    @exec("pgrep -x " . escapeshellarg($name) . " 2>/dev/null", $output2, $code2);
    return $code2 === 0 ? 'running' : 'stopped';
}

function checkPort($port) {
    $output = [];
    @exec("ss -tlnp 2>/dev/null | grep ':" . intval($port) . " '", $output);
    return !empty($output);
}

$services = [
    'Postfix (SMTP)' => ['service' => 'postfix', 'ports' => [25, 587]],
    'Dovecot (IMAP)' => ['service' => 'dovecot', 'ports' => [993, 143]],
    'Apache (HTTP)'  => ['service' => 'apache2', 'ports' => [80, 443]],
    'MariaDB/MySQL'  => ['service' => 'mariadb', 'ports' => [3306]],
];

$serviceStatuses = [];
foreach ($services as $label => $info) {
    $status = checkService($info['service']);
    $activePorts = [];
    foreach ($info['ports'] as $port) {
        if (checkPort($port)) {
            $activePorts[] = $port;
        }
    }
    $serviceStatuses[$label] = [
        'status' => $status,
        'ports'  => $activePorts,
        'all_ports' => $info['ports'],
    ];
}

// Outbound port 25 check (can we send mail directly?)
$outboundPort25 = 'unknown';
$outboundPort25Detail = '';
$testOutput = [];
$testCode = 0;
@exec("timeout 5 bash -c 'echo > /dev/tcp/gmail-smtp-in.l.google.com/25' 2>&1", $testOutput, $testCode);
if ($testCode === 0) {
    $outboundPort25 = 'open';
    $outboundPort25Detail = __('outbound_port25_open');
} else {
    $outboundPort25 = 'blocked';
    $outboundPort25Detail = __('outbound_port25_blocked');
}

// Disk usage
$diskTotal = @disk_total_space('/') ?: 0;
$diskFree = @disk_free_space('/') ?: 0;
$diskUsed = $diskTotal - $diskFree;
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

// Mail storage
$mailStoragePath = getenv('MAIL_STORAGE_PATH') ?: '/var/mail/vhosts';
$mailStorageSize = 'N/A';
$duOutput = [];
@exec("du -sh " . escapeshellarg($mailStoragePath) . " 2>/dev/null", $duOutput);
if (!empty($duOutput)) {
    $mailStorageSize = explode("\t", $duOutput[0])[0] ?? 'N/A';
}

// SMTP settings
$smtpHost = get_setting('smtp_host', 'localhost');
$smtpPort = get_setting('smtp_port', '25');
$smtpEnc = get_setting('smtp_encryption', 'none');
$smtpUser = get_setting('smtp_username', '');
$mailHostname = get_setting('mail_server_hostname', '');

// Cron status
$cronLogs = [];
@exec("tail -5 /var/log/torymail-cron.log 2>/dev/null", $cronLogs);
$fetchLogs = [];
@exec("tail -5 /var/log/torymail-fetch.log 2>/dev/null", $fetchLogs);

// SSL cert info
$sslInfo = 'N/A';
$sslExpiry = '';
if ($mailHostname) {
    $certOutput = [];
    @exec("openssl x509 -in /etc/letsencrypt/live/" . escapeshellarg($mailHostname) . "/cert.pem -noout -dates 2>/dev/null", $certOutput);
    if (!empty($certOutput)) {
        foreach ($certOutput as $line) {
            if (strpos($line, 'notAfter') !== false) {
                $sslExpiry = trim(str_replace('notAfter=', '', $line));
                $sslInfo = __('valid_until') . ' ' . $sslExpiry;
            }
        }
    }
}

// DNS records
$dnsRecords = [];
if ($mailHostname) {
    $domain = preg_replace('/^mail\./', '', $mailHostname);
    $mxRecords = @dns_get_record($domain, DNS_MX);
    $dnsRecords['MX'] = $mxRecords ?: [];
    $spfRecords = @dns_get_record($domain, DNS_TXT);
    $dnsRecords['TXT'] = $spfRecords ?: [];
}
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('system_information'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>"><?= __('admin'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('system_info'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Overview Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-2"><i class="ri-user-3-line"></i></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden ms-3">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-3"><?= __('users'); ?></p>
                        <h4 class="fs-22 fw-semibold mb-0"><?= number_format($totalUsers); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2"><i class="ri-global-line"></i></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden ms-3">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-3"><?= __('domains'); ?></p>
                        <h4 class="fs-22 fw-semibold mb-0"><?= number_format($totalDomains); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-2"><i class="ri-mail-line"></i></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden ms-3">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-3"><?= __('emails'); ?></p>
                        <h4 class="fs-22 fw-semibold mb-0"><?= number_format($totalEmails); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-2 fs-2"><i class="ri-mail-send-line"></i></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden ms-3">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-3"><?= __('queue_label'); ?></p>
                        <h4 class="fs-22 fw-semibold mb-0">
                            <?= $queuePending; ?> <span class="fs-13 text-muted fw-normal"><?= __('pending'); ?></span>
                            <?php if ($queueFailed > 0): ?>
                            <span class="text-danger fs-14"><?= $queueFailed; ?> <?= __('failed'); ?></span>
                            <?php endif; ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Services & Ports -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-server-line me-1 align-bottom"></i> <?= __('services_ports'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('service'); ?></th>
                                <th><?= __('status'); ?></th>
                                <th><?= __('active_ports'); ?></th>
                                <th><?= __('expected_ports'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceStatuses as $label => $info): ?>
                            <tr>
                                <td class="fw-medium"><?= $label; ?></td>
                                <td>
                                    <?php if ($info['status'] === 'running'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i><?= __('running'); ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-line me-1"></i><?= __('stopped'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($info['ports'])): ?>
                                    <span class="text-muted">-</span>
                                    <?php else: ?>
                                    <?php foreach ($info['ports'] as $p): ?>
                                    <span class="badge bg-primary-subtle text-primary"><?= $p; ?></span>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach ($info['all_ports'] as $p): ?>
                                    <span class="text-muted"><?= $p; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Outbound Port 25 -->
                            <tr>
                                <td class="fw-medium"><?= __('outbound_port25'); ?></td>
                                <td>
                                    <?php if ($outboundPort25 === 'open'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i><?= __('open'); ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-line me-1"></i><?= __('blocked'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td colspan="2">
                                    <small class="text-muted"><?= $outboundPort25Detail; ?></small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SMTP Configuration -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-mail-settings-line me-1 align-bottom"></i> <?= __('smtp_configuration'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><td class="fw-medium" style="width:200px;"><?= __('smtp_host'); ?></td><td><code><?= sanitize($smtpHost); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('smtp_port'); ?></td><td><code><?= sanitize($smtpPort); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('encryption'); ?></td><td><code><?= sanitize($smtpEnc ?: 'none'); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('smtp_username'); ?></td><td><?= $smtpUser ? '<code>' . sanitize($smtpUser) . '</code>' : '<span class="text-muted">' . __('not_set_local') . '</span>'; ?></td></tr>
                            <tr><td class="fw-medium"><?= __('mail_hostname'); ?></td><td><code><?= sanitize($mailHostname ?: __('not_configured')); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('mail_storage'); ?></td><td><code><?= sanitize($mailStoragePath); ?></code> (<?= $mailStorageSize; ?>)</td></tr>
                            <tr><td class="fw-medium"><?= __('ssl_certificate'); ?></td><td><?= sanitize($sslInfo); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Server & PHP Info -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-computer-line me-1 align-bottom"></i> <?= __('server_environment'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><td class="fw-medium" style="width:200px;"><?= __('torymail_version'); ?></td><td><span class="badge bg-primary"><?= $tmVersion; ?></span></td></tr>
                            <tr><td class="fw-medium"><?= __('install_path'); ?></td><td><code><?= sanitize($tmInstallPath); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('server_os'); ?></td><td><?= sanitize($serverOS); ?></td></tr>
                            <tr><td class="fw-medium"><?= __('hostname'); ?></td><td><code><?= sanitize($serverHostname); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('server_ip'); ?></td><td><code><?= sanitize($serverIP); ?></code></td></tr>
                            <tr><td class="fw-medium"><?= __('web_server'); ?></td><td><?= sanitize($serverSoftware); ?></td></tr>
                            <tr><td class="fw-medium"><?= __('php_version'); ?></td><td><code><?= $phpVersion; ?></code> (<?= $phpSapi; ?>)</td></tr>
                            <tr><td class="fw-medium"><?= __('memory_limit'); ?></td><td><?= $phpMemoryLimit; ?></td></tr>
                            <tr><td class="fw-medium"><?= __('upload_max_size'); ?></td><td><?= $phpMaxUpload; ?></td></tr>
                            <tr><td class="fw-medium"><?= __('post_max_size'); ?></td><td><?= $phpPostMax; ?></td></tr>
                            <tr><td class="fw-medium"><?= __('max_execution_time'); ?></td><td><?= $phpMaxExecTime; ?>s</td></tr>
                            <tr><td class="fw-medium"><?= __('database_label'); ?></td><td>MariaDB/MySQL <code><?= sanitize($dbVersion); ?></code></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-hard-drive-2-line me-1 align-bottom"></i> <?= __('disk_usage'); ?></h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span><?= round($diskUsed / 1073741824, 1); ?> GB <?= __('used_label'); ?></span>
                    <span><?= round($diskTotal / 1073741824, 1); ?> GB <?= __('total_label'); ?></span>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar <?= $diskPercent > 90 ? 'bg-danger' : ($diskPercent > 70 ? 'bg-warning' : 'bg-success'); ?>"
                         style="width:<?= $diskPercent; ?>%;"><?= $diskPercent; ?>%</div>
                </div>
                <div class="text-muted fs-12 mt-1"><?= round($diskFree / 1073741824, 1); ?> GB <?= __('free_label'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- PHP Extensions -->
<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-puzzle-line me-1 align-bottom"></i> <?= __('php_extensions'); ?></h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($requiredExts as $ext): ?>
                    <?php $loaded = in_array($ext, $phpExtensions); ?>
                    <span class="badge <?= $loaded ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                        <i class="<?= $loaded ? 'ri-check-line' : 'ri-close-line'; ?> me-1"></i><?= $ext; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Tables -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-database-2-line me-1 align-bottom"></i> <?= __('database_tables'); ?></h5>
                <span class="badge bg-primary-subtle text-primary"><?= __('total_label'); ?>: <?= round($totalDbSize, 2); ?> MB</span>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height:300px;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th><?= __('table_name'); ?></th><th><?= __('rows'); ?></th><th><?= __('data'); ?></th><th><?= __('index_col'); ?></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableStats as $t): ?>
                            <tr>
                                <td class="fw-medium fs-12"><code><?= sanitize($t['table_name']); ?></code></td>
                                <td class="fs-12"><?= number_format($t['table_rows']); ?></td>
                                <td class="fs-12"><?= $t['data_mb']; ?> MB</td>
                                <td class="fs-12"><?= $t['index_mb']; ?> MB</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cron Logs -->
<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-time-line me-1 align-bottom"></i> <?= __('cron_log_last5'); ?></h5>
            </div>
            <div class="card-body">
                <pre class="bg-dark text-light p-3 rounded mb-0 fs-12" style="max-height:200px;overflow:auto;"><?php
                    echo !empty($cronLogs) ? htmlspecialchars(implode("\n", $cronLogs)) : __('no_cron_logs');
                ?></pre>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-inbox-archive-line me-1 align-bottom"></i> <?= __('imap_fetch_log'); ?></h5>
            </div>
            <div class="card-body">
                <pre class="bg-dark text-light p-3 rounded mb-0 fs-12" style="max-height:200px;overflow:auto;"><?php
                    echo !empty($fetchLogs) ? htmlspecialchars(implode("\n", $fetchLogs)) : __('no_fetch_logs');
                ?></pre>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>
