<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('activity_logs'),
    'header' => '',
    'footer' => '',
];

// Filters
$filterUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filterAction = isset($_GET['filter_action']) ? preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['filter_action']) : '';
$filterDateFrom = isset($_GET['date_from']) ? preg_replace('/[^0-9-]/', '', $_GET['date_from']) : '';
$filterDateTo = isset($_GET['date_to']) ? preg_replace('/[^0-9-]/', '', $_GET['date_to']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

// Build query
$whereClauses = [];
$whereParams = [];

if ($filterUser > 0) {
    $whereClauses[] = 'al.user_id = ?';
    $whereParams[] = $filterUser;
}
if ($filterAction) {
    $whereClauses[] = 'al.action = ?';
    $whereParams[] = $filterAction;
}
if ($filterDateFrom) {
    $whereClauses[] = 'al.created_at >= ?';
    $whereParams[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo) {
    $whereClauses[] = 'al.created_at <= ?';
    $whereParams[] = $filterDateTo . ' 23:59:59';
}

$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count total
$totalLogs = $ToryMail->get_value_safe("SELECT COUNT(*) FROM activity_logs al $whereSQL", $whereParams);
$pagination = paginate($totalLogs, $perPage, $page);

// Get logs
$logs = $ToryMail->get_list_safe("
    SELECT al.*, u.fullname, u.email as user_email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $whereSQL
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET {$pagination['offset']}
", $whereParams);

// Get distinct actions for filter
$actionTypes = $ToryMail->get_list_safe("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");

// Get users for filter
$allUsers = $ToryMail->get_list_safe("SELECT id, fullname, email FROM users ORDER BY fullname ASC");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('activity_logs'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>"><?= __('admin'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('activity_logs'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= number_format($totalLogs); ?> <?= __('total_entries'); ?></h5>
            </div>
            <div class="card-body">
                <!-- Filters Row -->
                <form method="GET" class="row align-items-end g-2 mb-4">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="activity-logs">

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label form-label-sm mb-1"><?= __('user'); ?></label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value=""><?= __('all_users'); ?></option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id']; ?>" <?= $filterUser == $u['id'] ? 'selected' : ''; ?>>
                                    <?= sanitize($u['fullname']); ?> (<?= sanitize($u['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm mb-1"><?= __('action'); ?></label>
                        <select name="filter_action" class="form-select form-select-sm">
                            <option value=""><?= __('all_actions'); ?></option>
                            <?php foreach ($actionTypes as $at): ?>
                                <option value="<?= sanitize($at['action']); ?>" <?= $filterAction === $at['action'] ? 'selected' : ''; ?>>
                                    <?= sanitize($at['action']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm mb-1"><?= __('date_from'); ?></label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filterDateFrom; ?>">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm mb-1"><?= __('date_to'); ?></label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filterDateTo; ?>">
                    </div>
                    <div class="col-md-3 col-sm-12">
                        <button type="submit" class="btn btn-sm btn-primary me-1">
                            <i class="ri-filter-line me-1"></i> <?= __('filter'); ?>
                        </button>
                        <a href="<?= admin_url('activity-logs'); ?>" class="btn btn-sm btn-soft-secondary">
                            <i class="ri-refresh-line me-1"></i> <?= __('reset'); ?>
                        </a>
                    </div>
                </form>

                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= __('date'); ?></th>
                                <th><?= __('user'); ?></th>
                                <th><?= __('action'); ?></th>
                                <th><?= __('details'); ?></th>
                                <th><?= __('ip'); ?></th>
                                <th><?= __('user_agent'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4"><?= __('no_logs'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <small><?= format_date($log['created_at']); ?></small>
                                        <br><small class="text-muted"><?= time_ago($log['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <a href="<?= admin_url('user-edit/' . $log['user_id']); ?>" class="text-decoration-none">
                                                <?= sanitize($log['fullname'] ?? 'Unknown'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= sanitize($log['action']); ?></span></td>
                                    <td>
                                        <small class="text-muted" title="<?= sanitize($log['details'] ?? ''); ?>">
                                            <?= sanitize(str_truncate($log['details'] ?? '', 60)); ?>
                                        </small>
                                    </td>
                                    <td><small class="text-muted"><?= sanitize($log['ip_address'] ?? ''); ?></small></td>
                                    <td>
                                        <small class="text-muted" title="<?= sanitize($log['user_agent'] ?? ''); ?>">
                                            <?= sanitize(str_truncate($log['user_agent'] ?? '', 30)); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="mt-3">
                        <?php
                            $paginationBaseUrl = admin_url('activity-logs');
                            $queryParams = [];
                            if ($filterUser) $queryParams[] = 'user_id=' . $filterUser;
                            if ($filterAction) $queryParams[] = 'filter_action=' . urlencode($filterAction);
                            if ($filterDateFrom) $queryParams[] = 'date_from=' . $filterDateFrom;
                            if ($filterDateTo) $queryParams[] = 'date_to=' . $filterDateTo;
                            if (!empty($queryParams)) {
                                $paginationBaseUrl .= '&' . implode('&', $queryParams);
                            }
                            echo render_pagination($pagination, $paginationBaseUrl);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>
