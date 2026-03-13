<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Users',
    'header' => '',
    'footer' => '',
];

// Get all users with counts
$users = $ToryMail->get_list_safe("
    SELECT u.*,
           (SELECT COUNT(*) FROM domains WHERE user_id = u.id) as domains_count,
           (SELECT COUNT(*) FROM mailboxes WHERE user_id = u.id) as mailboxes_count
    FROM users u
    ORDER BY u.id DESC
");

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Users</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">All Users</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="ri-user-add-line me-1"></i> Add User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Domains</th>
                                <th>Mailboxes</th>
                                <th>Storage</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id']; ?></td>
                                <td>
                                    <a href="<?= admin_url('user-edit&id=' . $user['id']); ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($user['fullname']); ?>
                                    </a>
                                </td>
                                <td><?= sanitize($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-primary-subtle text-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $user['domains_count']; ?></td>
                                <td><?= $user['mailboxes_count']; ?></td>
                                <td>
                                    <small><?= format_email_size($user['storage_used']); ?> / <?= format_email_size($user['storage_quota']); ?></small>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php elseif ($user['status'] === 'banned'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Banned</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning"><?= ucfirst($user['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= $user['last_activity'] ? time_ago($user['last_activity']) : 'Never'; ?></small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                            <i class="ri-more-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="<?= admin_url('user-edit&id=' . $user['id']); ?>">
                                                    <i class="ri-edit-line me-2"></i> Edit
                                                </a>
                                            </li>
                                            <?php if ($user['status'] === 'banned'): ?>
                                                <li>
                                                    <a class="dropdown-item btn-toggle-ban" href="javascript:void(0);" data-id="<?= $user['id']; ?>" data-action="unban">
                                                        <i class="ri-lock-unlock-line me-2"></i> Unban
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <a class="dropdown-item btn-toggle-ban" href="javascript:void(0);" data-id="<?= $user['id']; ?>" data-action="ban">
                                                        <i class="ri-forbid-line me-2"></i> Ban
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger btn-delete-user" href="javascript:void(0);" data-id="<?= $user['id']; ?>">
                                                    <i class="ri-delete-bin-line me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-user-add-line me-2"></i> Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Domains</label>
                            <input type="number" name="max_domains" class="form-control" value="5" min="1">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Storage Quota (MB)</label>
                        <input type="number" name="storage_quota_mb" class="form-control" value="1024" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Add user
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Creating...');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/users.php?action=add"); ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', res.message);
                    btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Create User');
                }
            },
            error: function() {
                showToast('error', 'Server connection error');
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Create User');
            }
        });
    });

    // Ban/Unban user
    $(document).on('click', '.btn-toggle-ban', function() {
        var userId = $(this).data('id');
        var action = $(this).data('action');
        var label = action === 'ban' ? 'Ban this user?' : 'Unban this user?';

        confirmAction(label, 'This will change the user\'s access.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/users.php?action=toggle_ban"); ?>',
                method: 'POST',
                data: { user_id: userId, ban_action: action },
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

    // Delete user
    $(document).on('click', '.btn-delete-user', function() {
        var userId = $(this).data('id');

        confirmAction('Delete User?', 'This action cannot be undone. All user data will be permanently deleted.', function() {
            $.ajax({
                url: '<?= base_url("ajaxs/admin/users.php?action=delete"); ?>',
                method: 'POST',
                data: { user_id: userId },
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
