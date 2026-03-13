<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Edit User',
    'header' => '',
    'footer' => '',
];

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

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit User: <?= sanitize($user['fullname']); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= admin_url('users'); ?>">Users</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Profile Card + Edit Form -->
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                        <?= strtoupper(substr($user['fullname'] ?? 'U', 0, 1)); ?>
                    </span>
                </div>
                <h5 class="mb-1"><?= sanitize($user['fullname']); ?></h5>
                <p class="text-muted mb-2"><?= sanitize($user['email']); ?></p>
                <?php if ($user['status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success">Active</span>
                <?php elseif ($user['status'] === 'banned'): ?>
                    <span class="badge bg-danger-subtle text-danger">Banned</span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning"><?= ucfirst($user['status']); ?></span>
                <?php endif; ?>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">ID</td>
                            <td class="text-end fw-semibold">#<?= $user['id']; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Registered</td>
                            <td class="text-end fw-semibold"><?= format_date($user['created_at']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Activity</td>
                            <td class="text-end fw-semibold"><?= $user['last_activity'] ? time_ago($user['last_activity']) : 'Never'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Storage</td>
                            <td class="text-end fw-semibold"><?= format_email_size($user['storage_used']); ?> / <?= format_email_size($user['storage_quota']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Timezone</td>
                            <td class="text-end fw-semibold"><?= sanitize($user['timezone'] ?? 'UTC'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-user-settings-line me-1"></i> User Details</h5>
            </div>
            <div class="card-body">
                <form id="editUserForm">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?= sanitize($user['fullname']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Max Domains</label>
                            <input type="number" name="max_domains" class="form-control" value="<?= $user['max_domains']; ?>" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Mailboxes/Domain</label>
                            <input type="number" name="max_mailboxes_per_domain" class="form-control" value="<?= $user['max_mailboxes_per_domain']; ?>" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Storage Quota (MB)</label>
                        <input type="number" name="storage_quota_mb" class="form-control" value="<?= round($user['storage_quota'] / 1048576); ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(leave empty to keep current)</small></label>
                        <input type="password" name="new_password" class="form-control" minlength="6">
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100" id="btnSaveUser">
                            <i class="ri-save-line me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Domains, Mailboxes, Activity -->
    <div class="col-lg-8">
        <!-- User Domains -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-global-line me-1"></i> Domains (<?= count($userDomains); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
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
                                    <td class="fw-semibold"><?= sanitize($domain['domain_name']); ?></td>
                                    <td>
                                        <?php if ($domain['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php elseif ($domain['status'] === 'suspended'): ?>
                                            <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning"><?= ucfirst($domain['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $domain['mx_verified'] ? 'text-success' : 'text-danger'; ?>" title="MX"><i class="ri-<?= $domain['mx_verified'] ? 'check' : 'close'; ?>-line"></i></span>
                                        <span class="<?= $domain['spf_verified'] ? 'text-success' : 'text-danger'; ?>" title="SPF"><i class="ri-<?= $domain['spf_verified'] ? 'check' : 'close'; ?>-line"></i></span>
                                        <span class="<?= $domain['dkim_verified'] ? 'text-success' : 'text-danger'; ?>" title="DKIM"><i class="ri-<?= $domain['dkim_verified'] ? 'check' : 'close'; ?>-line"></i></span>
                                        <span class="<?= $domain['dmarc_verified'] ? 'text-success' : 'text-danger'; ?>" title="DMARC"><i class="ri-<?= $domain['dmarc_verified'] ? 'check' : 'close'; ?>-line"></i></span>
                                    </td>
                                    <td><?= $domain['mailboxes_count']; ?></td>
                                    <td><small><?= format_date($domain['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Mailboxes -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-mail-settings-line me-1"></i> Mailboxes (<?= count($userMailboxes); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
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
                                    <td class="fw-semibold"><?= sanitize($mb['email_address']); ?></td>
                                    <td><?= sanitize($mb['domain_name']); ?></td>
                                    <td>
                                        <?php $pct = $mb['quota'] > 0 ? round(($mb['used_space'] / $mb['quota']) * 100) : 0; ?>
                                        <div class="progress progress-sm mb-1" style="width:100px;">
                                            <div class="progress-bar bg-<?= $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'primary'); ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= format_email_size($mb['used_space']); ?> / <?= format_email_size($mb['quota']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($mb['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning"><?= ucfirst($mb['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= format_date($mb['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Recent Activity</h5>
            </div>
            <div class="card-body" style="max-height:400px; overflow-y:auto;">
                <?php if (empty($userLogs)): ?>
                    <div class="text-center text-muted py-4">No activity</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($userLogs as $log): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-primary-subtle text-primary"><?= sanitize($log['action']); ?></span>
                                <small class="text-muted"><?= time_ago($log['created_at']); ?></small>
                            </div>
                            <?php if (!empty($log['details'])): ?>
                                <small class="text-muted d-block mt-1"><?= sanitize(str_truncate($log['details'], 80)); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSaveUser');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/users.php?action=update"); ?>',
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
