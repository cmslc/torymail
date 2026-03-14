<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('dashboard'),
    'header' => '',
    'footer' => '',
];

// =====================================================
// Stats queries
// =====================================================
$totalUsers = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users");
$activeDomains = $ToryMail->get_value_safe("SELECT COUNT(*) FROM domains WHERE status = 'active'");
$activeMailboxes = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes WHERE status = 'active'");
$emailsToday = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails WHERE DATE(created_at) = CURDATE()");
$queuePending = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status IN ('pending','sending')");
$storageUsed = $ToryMail->get_value_safe("SELECT COALESCE(SUM(storage_used), 0) FROM users");

// Previous day comparisons
$usersYesterday = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users WHERE DATE(created_at) <= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$emailsYesterday = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

// =====================================================
// Emails per day (last 30 days) for chart
// =====================================================
$emailStats = $ToryMail->get_list_safe("
    SELECT DATE(created_at) as day,
           SUM(CASE WHEN folder = 'sent' THEN 1 ELSE 0 END) as sent_count,
           SUM(CASE WHEN folder = 'inbox' THEN 1 ELSE 0 END) as received_count
    FROM emails
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");

$chartLabels = [];
$chartSent = [];
$chartReceived = [];
foreach ($emailStats as $stat) {
    $chartLabels[] = date('d/m', strtotime($stat['day']));
    $chartSent[] = (int)$stat['sent_count'];
    $chartReceived[] = (int)$stat['received_count'];
}

// =====================================================
// Recent activity logs (last 20)
// =====================================================
$recentLogs = $ToryMail->get_list_safe("
    SELECT al.*, u.fullname, u.email as user_email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('dashboard'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>"><?= __('admin'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('dashboard'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- 6 Stat Cards -->
<div class="row">
    <?php
    $kpiCards = [
        ['bg' => 'primary', 'value' => number_format($totalUsers),      'label' => __('total_users'),       'icon' => 'ri-group-line',          'link' => admin_url('users')],
        ['bg' => 'success', 'value' => number_format($activeDomains),   'label' => __('active_domains'),    'icon' => 'ri-global-line',         'link' => admin_url('domains')],
        ['bg' => 'info',    'value' => number_format($activeMailboxes),  'label' => __('active_mailboxes'),  'icon' => 'ri-mail-settings-line',  'link' => admin_url('mailboxes')],
        ['bg' => 'warning', 'value' => number_format($emailsToday),     'label' => __('emails_today'),      'icon' => 'ri-mail-line',           'link' => admin_url('activity-logs')],
        ['bg' => 'danger',  'value' => number_format($queuePending),    'label' => __('queue_pending'),     'icon' => 'ri-mail-send-line',      'link' => admin_url('email-queue')],
        ['bg' => 'secondary','value' => format_email_size($storageUsed),'label' => __('total_storage'),     'icon' => 'ri-hard-drive-2-line',   'link' => admin_url('users')],
    ];
    foreach ($kpiCards as $card): ?>
    <div class="col-xl-4 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0"><?= $card['label']; ?></p>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?= $card['value']; ?></h4>
                        <a href="<?= $card['link']; ?>" class="text-decoration-underline text-<?= $card['bg']; ?>"><?= __('view_details'); ?> <i class="ri-arrow-right-line"></i></a>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-<?= $card['bg']; ?>-subtle rounded fs-3">
                            <i class="<?= $card['icon']; ?> text-<?= $card['bg']; ?>"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart: Emails sent/received last 30 days -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-line-chart-line me-1"></i> <?= __('emails_last_30'); ?></h5>
            </div>
            <div class="card-body">
                <canvas id="emailChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> <?= __('recent_activity'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= __('time'); ?></th>
                                <th><?= __('user'); ?></th>
                                <th><?= __('action'); ?></th>
                                <th><?= __('details'); ?></th>
                                <th><?= __('ip'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4"><?= __('no_activity'); ?></td></tr>
                            <?php else: ?>
                                <?php $i = 1; foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><?= $i++; ?></td>
                                    <td class="text-nowrap">
                                        <small><?= format_date($log['created_at']); ?></small>
                                        <br><small class="text-muted"><?= time_ago($log['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <a href="<?= admin_url('user-edit/' . $log['user_id']); ?>"><?= sanitize($log['fullname'] ?? 'Unknown'); ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= sanitize($log['action']); ?></span></td>
                                    <td><small class="text-muted"><?= sanitize(str_truncate($log['details'] ?? '', 60)); ?></small></td>
                                    <td><small class="text-muted"><?= sanitize($log['ip_address'] ?? ''); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('emailChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels); ?>,
            datasets: [
                {
                    label: '<?= __('sent'); ?>',
                    data: <?= json_encode($chartSent); ?>,
                    borderColor: '#405189',
                    backgroundColor: 'rgba(64,81,137,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#405189',
                    borderWidth: 2
                },
                {
                    label: '<?= __('received'); ?>',
                    data: <?= json_encode($chartReceived); ?>,
                    borderColor: '#0ab39c',
                    backgroundColor: 'rgba(10,179,156,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#0ab39c',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
