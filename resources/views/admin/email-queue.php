<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Email Queue',
    'header' => '',
    'footer' => '',
];

// Get queue items
$queueItems = $ToryMail->get_list_safe("
    SELECT eq.*, m.email_address as from_mailbox
    FROM email_queue eq
    LEFT JOIN mailboxes m ON eq.mailbox_id = m.id
    ORDER BY eq.id DESC
    LIMIT 500
");

// Queue stats
$queuePending = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
$queueSending = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'sending'");
$queueSent = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'");
$queueFailed = $ToryMail->get_value_safe("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Email Queue</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Email Queue</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Queue Stats - 4 mini stat cards -->
<div class="row">
    <?php
    $queueCards = [
        ['bg' => 'warning', 'value' => number_format($queuePending), 'label' => 'Pending',  'icon' => 'ri-time-line'],
        ['bg' => 'info',    'value' => number_format($queueSending), 'label' => 'Sending',  'icon' => 'ri-send-plane-line'],
        ['bg' => 'success', 'value' => number_format($queueSent),    'label' => 'Sent',     'icon' => 'ri-check-double-line'],
        ['bg' => 'danger',  'value' => number_format($queueFailed),  'label' => 'Failed',   'icon' => 'ri-error-warning-line'],
    ];
    foreach ($queueCards as $card): ?>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-<?= $card['bg']; ?>-subtle rounded-2 fs-2">
                            <i class="<?= $card['icon']; ?> text-<?= $card['bg']; ?>"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1"><?= $card['label']; ?></p>
                        <h4 class="fs-20 fw-semibold mb-0"><?= $card['value']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Bulk Actions + Queue Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Queue Items</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-soft-primary" id="btnRetryAllFailed">
                        <i class="ri-refresh-line me-1"></i> Retry All Failed
                    </button>
                    <button class="btn btn-sm btn-soft-secondary" id="btnClearSent">
                        <i class="ri-delete-bin-line me-1"></i> Clear Sent
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Error</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queueItems as $item): ?>
                            <?php
                                $toAddresses = json_decode($item['to_addresses'], true);
                                $toDisplay = is_array($toAddresses) ? implode(', ', array_slice($toAddresses, 0, 2)) : ($item['to_addresses'] ?? '');
                                if (is_array($toAddresses) && count($toAddresses) > 2) {
                                    $toDisplay .= ' +' . (count($toAddresses) - 2);
                                }
                            ?>
                            <tr>
                                <td><?= $item['id']; ?></td>
                                <td><small><?= sanitize($item['from_address']); ?></small></td>
                                <td><small><?= sanitize($toDisplay); ?></small></td>
                                <td><small><?= sanitize(str_truncate($item['subject'], 40)); ?></small></td>
                                <td>
                                    <?php if ($item['status'] === 'sent'): ?>
                                        <span class="badge bg-success-subtle text-success">Sent</span>
                                    <?php elseif ($item['status'] === 'failed'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                                    <?php elseif ($item['status'] === 'sending'): ?>
                                        <span class="badge bg-info-subtle text-info">Sending</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= $item['attempts']; ?>/<?= $item['max_attempts']; ?></small></td>
                                <td>
                                    <?php if ($item['error_message']): ?>
                                        <small class="text-danger" title="<?= sanitize($item['error_message']); ?>">
                                            <?= sanitize(str_truncate($item['error_message'], 30)); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= format_date($item['created_at']); ?></small></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($item['status'] === 'failed'): ?>
                                            <button class="btn btn-sm btn-soft-primary btn-retry-queue" data-id="<?= $item['id']; ?>" title="Retry">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-soft-danger btn-delete-queue" data-id="<?= $item['id']; ?>" title="Delete">
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

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Retry single
    $(document).on('click', '.btn-retry-queue', function() {
        var itemId = $(this).data('id');
        $.ajax({
            url: '<?= base_url("ajaxs/admin/email-queue.php?action=retry"); ?>',
            method: 'POST',
            data: { queue_id: itemId },
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

    // Retry all failed
    $('#btnRetryAllFailed').on('click', function() {
        confirmAction('Retry All Failed?', 'This will reset all failed emails to pending status.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=retry_all_failed"); ?>',
                method: 'POST',
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

    // Clear sent
    $('#btnClearSent').on('click', function() {
        confirmAction('Clear Sent Emails?', 'This will remove all sent emails from the queue.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=clear_sent"); ?>',
                method: 'POST',
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

    // Delete single
    $(document).on('click', '.btn-delete-queue', function() {
        var itemId = $(this).data('id');
        confirmAction('Delete Queue Item?', '', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=delete"); ?>',
                method: 'POST',
                data: { queue_id: itemId },
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
