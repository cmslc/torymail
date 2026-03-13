<?php
$body = ['title' => 'Dashboard'];

// Stats queries
$totalUsers = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users");
$totalDomains = $ToryMail->get_value_safe("SELECT COUNT(*) FROM domains");
$totalMailboxes = $ToryMail->get_value_safe("SELECT COUNT(*) FROM mailboxes");
$emailsToday = $ToryMail->get_value_safe("SELECT COUNT(*) FROM emails WHERE DATE(created_at) = CURDATE()");
$emailsInQueue = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status IN ('pending','sending')");
$storageUsed = $ToryMail->get_value_safe("SELECT COALESCE(SUM(storage_used), 0) FROM users");

// Emails per day (last 30 days)
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

// Recent activity logs
$recentLogs = $ToryMail->get_list_safe("
    SELECT al.*, u.fullname, u.email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 20
");

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h4><i class="ri-dashboard-line me-2"></i> Dashboard</h4>
        <span class="text-muted"><?= date('l, d/m/Y') ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(79,70,229,0.1); color: var(--primary);">
                        <i class="ri-group-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($totalUsers) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                        <i class="ri-global-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($totalDomains) ?></div>
                <div class="stat-label">Total Domains</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                        <i class="ri-mail-settings-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($totalMailboxes) ?></div>
                <div class="stat-label">Total Mailboxes</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                        <i class="ri-mail-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($emailsToday) ?></div>
                <div class="stat-label">Emails Today</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                        <i class="ri-mail-send-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($emailsInQueue) ?></div>
                <div class="stat-label">Emails in Queue</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                        <i class="ri-hard-drive-2-line"></i>
                    </div>
                </div>
                <div class="stat-value"><?= format_email_size($storageUsed) ?></div>
                <div class="stat-label">Storage Used</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="ri-line-chart-line me-2"></i> Emails - Last 30 Days</span>
                </div>
                <div class="card-body">
                    <canvas id="emailChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Activity -->
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-header">
                    <i class="ri-file-list-line me-2"></i> Recent Activity
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No activity logged yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td class="text-nowrap"><small><?= time_ago($log['created_at']) ?></small></td>
                                            <td><?= sanitize($log['fullname'] ?? 'System') ?></td>
                                            <td><span class="badge bg-light text-dark"><?= sanitize($log['action']) ?></span></td>
                                            <td><small class="text-muted"><?= sanitize(str_truncate($log['details'] ?? '', 60)) ?></small></td>
                                            <td><small class="text-muted"><?= sanitize($log['ip_address'] ?? '') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="col-lg-4">
            <div class="card-custom">
                <div class="card-header">
                    <i class="ri-server-line me-2"></i> System Info
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">PHP Version</td>
                            <td class="text-end fw-semibold"><?= PHP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Server</td>
                            <td class="text-end fw-semibold"><?= sanitize($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">OS</td>
                            <td class="text-end fw-semibold"><?= PHP_OS ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Disk Free</td>
                            <td class="text-end fw-semibold"><?= format_email_size(@disk_free_space('/') ?: 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Disk Total</td>
                            <td class="text-end fw-semibold"><?= format_email_size(@disk_total_space('/') ?: 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Memory Limit</td>
                            <td class="text-end fw-semibold"><?= ini_get('memory_limit') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Max Upload</td>
                            <td class="text-end fw-semibold"><?= ini_get('upload_max_filesize') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Torymail</td>
                            <td class="text-end fw-semibold"><?= defined('TORYMAIL_VERSION') ? TORYMAIL_VERSION : '1.0.0' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('emailChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Sent',
                    data: <?= json_encode($chartSent) ?>,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79,70,229,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#4F46E5'
                },
                {
                    label: 'Received',
                    data: <?= json_encode($chartReceived) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#10b981'
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

<?php require_once __DIR__ . '/footer.php'; ?>
