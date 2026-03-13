<?php
$body = ['title' => 'Email Queue'];

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

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h4><i class="ri-mail-send-line me-2"></i> Email Queue</h4>
        <div class="d-flex gap-2 align-items-center">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="autoRefresh">
                <label class="form-check-label" for="autoRefresh">Auto-refresh</label>
            </div>
        </div>
    </div>

    <!-- Queue Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-value text-warning"><?= number_format($queuePending) ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-value text-info"><?= number_format($queueSending) ?></div>
                <div class="stat-label">Sending</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-value text-success"><?= number_format($queueSent) ?></div>
                <div class="stat-label">Sent</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-value text-danger"><?= number_format($queueFailed) ?></div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card-custom mb-3">
        <div class="card-body py-2 d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline-primary" id="btnRetryAllFailed">
                <i class="ri-refresh-line me-1"></i> Retry All Failed
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="btnClearSent">
                <i class="ri-delete-bin-line me-1"></i> Clear Sent
            </button>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="queueTable">
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
                                <td><?= $item['id'] ?></td>
                                <td><small><?= sanitize($item['from_address']) ?></small></td>
                                <td><small><?= sanitize($toDisplay) ?></small></td>
                                <td><small><?= sanitize(str_truncate($item['subject'], 40)) ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span>
                                </td>
                                <td>
                                    <small><?= $item['attempts'] ?>/<?= $item['max_attempts'] ?></small>
                                </td>
                                <td>
                                    <?php if ($item['error_message']): ?>
                                        <small class="text-danger" title="<?= sanitize($item['error_message']) ?>">
                                            <?= sanitize(str_truncate($item['error_message'], 30)) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= format_date($item['created_at']) ?></small></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($item['status'] === 'failed'): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-retry-queue" data-id="<?= $item['id'] ?>" title="Retry">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-queue" data-id="<?= $item['id'] ?>" title="Delete">
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
    var table = $('#queueTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search:",
            emptyTable: "Queue is empty"
        }
    });

    // Auto-refresh
    var refreshInterval = null;
    $('#autoRefresh').on('change', function() {
        if ($(this).is(':checked')) {
            refreshInterval = setInterval(function() {
                location.reload();
            }, 10000);
        } else {
            clearInterval(refreshInterval);
        }
    });

    // Retry single
    $(document).on('click', '.btn-retry-queue', function() {
        var itemId = $(this).data('id');
        $.ajax({
            url: '<?= base_url("ajaxs/admin/email-queue.php?action=retry") ?>',
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
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=retry_all_failed") ?>',
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
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=clear_sent") ?>',
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
                url: '<?= base_url("ajaxs/admin/email-queue.php?action=delete") ?>',
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

<?php require_once __DIR__ . '/footer.php'; ?>
