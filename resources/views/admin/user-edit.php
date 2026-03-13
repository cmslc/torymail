<?php
$body = ['title' => 'Edit User'];

if (!$id) {
    redirect(admin_url('users'));
}

$user = $ToryMail->get_row_safe("SELECT * FROM users WHERE id = ?", [$id]);
if (!$user) {
    redirect(admin_url('users'));
}

// User's domains
$userDomains = $ToryMail->get_list_safe("
    SELECT d.*,
           (SELECT COUNT(*) FROM mailboxes WHERE domain_id = d.id) as mailboxes_count
    FROM domains d
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC
", [$id]);

// User's mailboxes
$userMailboxes = $ToryMail->get_list_safe("
    SELECT m.*, d.domain_name
    FROM mailboxes m
    JOIN domains d ON m.domain_id = d.id
    WHERE m.user_id = ?
    ORDER BY m.created_at DESC
", [$id]);

// User's activity logs (last 50)
$userLogs = $ToryMail->get_list_safe("
    SELECT * FROM activity_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
", [$id]);

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h4>
            <a href="<?= admin_url('users') ?>" class="text-muted text-decoration-none me-2"><i class="ri-arrow-left-line"></i></a>
            Edit User: <?= sanitize($user['fullname']) ?>
        </h4>
        <span class="badge badge-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span>
    </div>

    <div class="row g-4">
        <!-- User Edit Form -->
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-header">
                    <i class="ri-user-settings-line me-2"></i> User Details
                </div>
                <div class="card-body">
                    <form id="editUserForm">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" value="<?= sanitize($user['fullname']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Domains</label>
                                <input type="number" name="max_domains" class="form-control" value="<?= $user['max_domains'] ?>" min="0">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Max Mailboxes per Domain</label>
                                <input type="number" name="max_mailboxes_per_domain" class="form-control" value="<?= $user['max_mailboxes_per_domain'] ?>" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Storage Quota (MB)</label>
                                <input type="number" name="storage_quota_mb" class="form-control" value="<?= round($user['storage_quota'] / 1048576) ?>" min="1">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">New Password <small class="text-muted">(leave empty to keep current)</small></label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="btnSaveUser">
                                <i class="ri-save-line me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Domains -->
            <div class="card-custom mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-global-line me-2"></i> Domains (<?= count($userDomains) ?>)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>DNS</th>
                                    <th>Mailboxes</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($userDomains)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No domains</td></tr>
                                <?php else: ?>
                                    <?php foreach ($userDomains as $domain): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= sanitize($domain['domain_name']) ?></td>
                                            <td><span class="badge badge-<?= $domain['status'] ?>"><?= ucfirst($domain['status']) ?></span></td>
                                            <td>
                                                <span class="<?= $domain['mx_verified'] ? 'dns-ok' : 'dns-fail' ?>" title="MX"><i class="ri-<?= $domain['mx_verified'] ? 'check' : 'close' ?>-line"></i></span>
                                                <span class="<?= $domain['spf_verified'] ? 'dns-ok' : 'dns-fail' ?>" title="SPF"><i class="ri-<?= $domain['spf_verified'] ? 'check' : 'close' ?>-line"></i></span>
                                                <span class="<?= $domain['dkim_verified'] ? 'dns-ok' : 'dns-fail' ?>" title="DKIM"><i class="ri-<?= $domain['dkim_verified'] ? 'check' : 'close' ?>-line"></i></span>
                                                <span class="<?= $domain['dmarc_verified'] ? 'dns-ok' : 'dns-fail' ?>" title="DMARC"><i class="ri-<?= $domain['dmarc_verified'] ? 'check' : 'close' ?>-line"></i></span>
                                            </td>
                                            <td><?= $domain['mailboxes_count'] ?></td>
                                            <td><small><?= format_date($domain['created_at']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- User Mailboxes -->
            <div class="card-custom mt-4">
                <div class="card-header">
                    <i class="ri-mail-settings-line me-2"></i> Mailboxes (<?= count($userMailboxes) ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Email Address</th>
                                    <th>Domain</th>
                                    <th>Storage</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($userMailboxes)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No mailboxes</td></tr>
                                <?php else: ?>
                                    <?php foreach ($userMailboxes as $mb): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= sanitize($mb['email_address']) ?></td>
                                            <td><?= sanitize($mb['domain_name']) ?></td>
                                            <td>
                                                <?php $pct = $mb['quota'] > 0 ? round(($mb['used_space'] / $mb['quota']) * 100) : 0; ?>
                                                <div class="progress progress-quota" style="width:100px;">
                                                    <div class="progress-bar bg-<?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'primary') ?>" style="width:<?= $pct ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= format_email_size($mb['used_space']) ?> / <?= format_email_size($mb['quota']) ?></small>
                                            </td>
                                            <td><span class="badge badge-<?= $mb['status'] ?>"><?= ucfirst($mb['status']) ?></span></td>
                                            <td><small><?= format_date($mb['created_at']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Info Sidebar -->
        <div class="col-lg-4">
            <div class="card-custom">
                <div class="card-header">
                    <i class="ri-information-line me-2"></i> User Info
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">ID</td>
                            <td class="text-end fw-semibold">#<?= $user['id'] ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Registered</td>
                            <td class="text-end fw-semibold"><?= format_date($user['created_at']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Activity</td>
                            <td class="text-end fw-semibold"><?= $user['last_activity'] ? time_ago($user['last_activity']) : 'Never' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Storage</td>
                            <td class="text-end fw-semibold"><?= format_email_size($user['storage_used']) ?> / <?= format_email_size($user['storage_quota']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Timezone</td>
                            <td class="text-end fw-semibold"><?= sanitize($user['timezone']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card-custom mt-4">
                <div class="card-header">
                    <i class="ri-file-list-line me-2"></i> Recent Activity
                </div>
                <div class="card-body p-0" style="max-height:400px; overflow-y:auto;">
                    <?php if (empty($userLogs)): ?>
                        <div class="text-center text-muted py-4">No activity</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($userLogs as $log): ?>
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-light text-dark"><?= sanitize($log['action']) ?></span>
                                        <small class="text-muted"><?= time_ago($log['created_at']) ?></small>
                                    </div>
                                    <?php if ($log['details']): ?>
                                        <small class="text-muted d-block mt-1"><?= sanitize(str_truncate($log['details'], 80)) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSaveUser');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/users.php?action=update") ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                } else {
                    showToast('error', res.message);
                }
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save Changes');
            },
            error: function() {
                showToast('error', 'Server connection error');
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save Changes');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
