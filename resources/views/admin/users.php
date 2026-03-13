<?php
$body = ['title' => 'Users'];

// Get all users with counts
$users = $ToryMail->get_list_safe("
    SELECT u.*,
           (SELECT COUNT(*) FROM domains WHERE user_id = u.id) as domains_count,
           (SELECT COUNT(*) FROM mailboxes WHERE user_id = u.id) as mailboxes_count
    FROM users u
    ORDER BY u.id DESC
");

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h4><i class="ri-group-line me-2"></i> Users</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="ri-user-add-line me-1"></i> Add User
        </button>
    </div>

    <!-- Filters -->
    <div class="card-custom mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <select id="filterRole" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="banned">Banned</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
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
                            <tr data-role="<?= $user['role'] ?>" data-status="<?= $user['status'] ?>">
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <a href="<?= admin_url('user-edit&id=' . $user['id']) ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($user['fullname']) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($user['email']) ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-primary"><?= $user['role'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $user['role'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $user['domains_count'] ?></td>
                                <td><?= $user['mailboxes_count'] ?></td>
                                <td>
                                    <small><?= format_email_size($user['storage_used']) ?> / <?= format_email_size($user['storage_quota']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= $user['last_activity'] ? time_ago($user['last_activity']) : 'Never' ?></small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= admin_url('user-edit&id=' . $user['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <?php if ($user['status'] === 'banned'): ?>
                                            <button class="btn btn-sm btn-outline-success btn-toggle-ban" data-id="<?= $user['id'] ?>" data-action="unban" title="Unban">
                                                <i class="ri-lock-unlock-line"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-warning btn-toggle-ban" data-id="<?= $user['id'] ?>" data-action="ban" title="Ban">
                                                <i class="ri-forbid-line"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="<?= $user['id'] ?>" title="Delete">
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search:",
            emptyTable: "No users found",
            zeroRecords: "No matching users"
        }
    });

    // Filter by role
    $('#filterRole').on('change', function() {
        var role = $(this).val();
        table.column(3).search(role).draw();
    });

    // Filter by status
    $('#filterStatus').on('change', function() {
        var status = $(this).val();
        table.column(7).search(status).draw();
    });

    // Add user
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Creating...');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/users.php?action=add") ?>',
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
                url: '<?= base_url("ajaxs/admin/users.php?action=toggle_ban") ?>',
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
                url: '<?= base_url("ajaxs/admin/users.php?action=delete") ?>',
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

<?php require_once __DIR__ . '/footer.php'; ?>
