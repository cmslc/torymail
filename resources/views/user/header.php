<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Fetch unread notifications
$notifications = $ToryMail->get_list_safe("
    SELECT * FROM `notifications`
    WHERE `user_id` = ? AND `is_read` = 0
    ORDER BY `created_at` DESC
    LIMIT 10
", [$getUser['id']]);
$notif_count = count($notifications);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= isset($body['title']) ? htmlspecialchars($body['title']) : 'Torymail'; ?></title>
    <meta name="description" content="<?= isset($body['desc']) ? htmlspecialchars($body['desc']) : 'Torymail - Email Management'; ?>">
    <meta name="csrf-token" content="<?= csrf_token(); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remixicon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        beforeSend: function(xhr, settings) {
            if (settings.type === 'POST' && settings.data) {
                var token = $('meta[name="csrf-token"]').attr('content');
                if (typeof settings.data === 'string') {
                    settings.data += '&_csrf_token=' + encodeURIComponent(token);
                } else if (settings.data instanceof FormData) {
                    settings.data.append('_csrf_token', token);
                }
            }
        }
    });
    </script>

    <style>
    :root {
        --tm-primary: #4F46E5;
        --tm-primary-hover: #4338CA;
        --tm-sidebar-bg: #1e1e2d;
        --tm-sidebar-text: #a2a3b7;
        --tm-sidebar-active: #4F46E5;
        --tm-content-bg: #f5f8fa;
        --tm-sidebar-width: 260px;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--tm-content-bg);
        color: #333;
        overflow-x: hidden;
    }

    /* Top Navbar */
    .tm-topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        z-index: 1040;
        display: flex;
        align-items: center;
        padding: 0 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .tm-topbar .tm-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
        font-weight: 700;
        color: var(--tm-primary);
        text-decoration: none;
        min-width: 220px;
    }

    .tm-topbar .tm-logo i {
        font-size: 26px;
    }

    .tm-topbar .tm-search {
        flex: 1;
        max-width: 520px;
        margin: 0 20px;
    }

    .tm-topbar .tm-search input {
        width: 100%;
        padding: 8px 16px 8px 40px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: var(--tm-content-bg);
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }

    .tm-topbar .tm-search input:focus {
        border-color: var(--tm-primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }

    .tm-topbar .tm-search .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
    }

    .tm-topbar-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
    }

    .tm-topbar-actions .btn-compose {
        background: var(--tm-primary);
        color: #fff;
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }

    .tm-topbar-actions .btn-compose:hover {
        background: var(--tm-primary-hover);
    }

    .tm-topbar-actions .btn-icon-topbar {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6b7280;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: background 0.2s, color 0.2s;
    }

    .tm-topbar-actions .btn-icon-topbar:hover {
        background: var(--tm-content-bg);
        color: var(--tm-primary);
    }

    .tm-topbar-actions .notif-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }

    .tm-user-dropdown .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
        background: transparent;
        padding: 4px 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .tm-user-dropdown .dropdown-toggle:hover {
        background: var(--tm-content-bg);
    }

    .tm-user-dropdown .dropdown-toggle::after { display: none; }

    .tm-user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--tm-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .tm-user-name {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }

    /* Sidebar */
    .tm-sidebar {
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        width: var(--tm-sidebar-width);
        background: var(--tm-sidebar-bg);
        overflow-y: auto;
        z-index: 1030;
        padding: 16px 0;
        transition: transform 0.3s;
    }

    .tm-sidebar::-webkit-scrollbar { width: 4px; }
    .tm-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

    /* Main Content */
    .tm-main {
        margin-left: var(--tm-sidebar-width);
        margin-top: 60px;
        min-height: calc(100vh - 60px);
        padding: 24px;
    }

    /* Notification dropdown */
    .tm-notif-dropdown {
        width: 360px;
        max-height: 400px;
        overflow-y: auto;
        padding: 0;
    }

    .tm-notif-dropdown .notif-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tm-notif-dropdown .notif-item {
        padding: 10px 16px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        gap: 10px;
        transition: background 0.15s;
        text-decoration: none;
        color: inherit;
    }

    .tm-notif-dropdown .notif-item:hover {
        background: #f9fafb;
    }

    .tm-notif-dropdown .notif-item .notif-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(79,70,229,0.1);
        color: var(--tm-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
    }

    .tm-notif-dropdown .notif-item .notif-text {
        font-size: 13px;
        color: #4b5563;
        line-height: 1.4;
    }

    .tm-notif-dropdown .notif-item .notif-time {
        font-size: 11px;
        color: #9ca3af;
    }

    /* Email list styles */
    .tm-email-row {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        color: inherit;
        gap: 12px;
    }

    .tm-email-row:hover {
        background: #f0f4ff;
    }

    .tm-email-row.unread {
        background: #fff;
        font-weight: 600;
    }

    .tm-email-row.unread .email-from,
    .tm-email-row.unread .email-subject {
        font-weight: 600;
    }

    .tm-email-row .email-checkbox {
        flex-shrink: 0;
    }

    .tm-email-row .email-star {
        flex-shrink: 0;
        color: #d1d5db;
        cursor: pointer;
        font-size: 18px;
        transition: color 0.2s;
    }

    .tm-email-row .email-star.starred {
        color: #f59e0b;
    }

    .tm-email-row .email-star:hover {
        color: #f59e0b;
    }

    .tm-email-row .email-from {
        width: 200px;
        flex-shrink: 0;
        font-size: 14px;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tm-email-row .email-content {
        flex: 1;
        min-width: 0;
        display: flex;
        gap: 6px;
    }

    .tm-email-row .email-subject {
        font-size: 14px;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tm-email-row .email-preview {
        font-size: 14px;
        color: #9ca3af;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tm-email-row .email-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        margin-left: auto;
    }

    .tm-email-row .email-attachment {
        color: #9ca3af;
        font-size: 16px;
    }

    .tm-email-row .email-date {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
    }

    /* Card style */
    .tm-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .tm-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Toolbar */
    .tm-toolbar {
        padding: 10px 16px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .tm-toolbar .btn-toolbar {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #6b7280;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s;
    }

    .tm-toolbar .btn-toolbar:hover {
        background: var(--tm-content-bg);
        color: var(--tm-primary);
        border-color: var(--tm-primary);
    }

    /* Folder tabs */
    .tm-folder-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #f0f0f0;
        padding: 0 16px;
        overflow-x: auto;
    }

    .tm-folder-tabs a {
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .tm-folder-tabs a:hover {
        color: var(--tm-primary);
    }

    .tm-folder-tabs a.active {
        color: var(--tm-primary);
        border-bottom-color: var(--tm-primary);
    }

    /* Labels */
    .tm-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }

    .tm-label .label-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .tm-sidebar {
            transform: translateX(-100%);
        }
        .tm-sidebar.show {
            transform: translateX(0);
        }
        .tm-main {
            margin-left: 0;
        }
        .tm-topbar .tm-logo {
            min-width: auto;
        }
        .tm-topbar .tm-search {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .tm-main {
            padding: 16px;
        }
        .tm-email-row .email-from {
            width: 120px;
        }
        .tm-email-row .email-preview {
            display: none;
        }
    }

    /* Btn primary override */
    .btn-primary {
        background: var(--tm-primary) !important;
        border-color: var(--tm-primary) !important;
    }
    .btn-primary:hover {
        background: var(--tm-primary-hover) !important;
        border-color: var(--tm-primary-hover) !important;
    }
    .btn-outline-primary {
        color: var(--tm-primary) !important;
        border-color: var(--tm-primary) !important;
    }
    .btn-outline-primary:hover {
        background: var(--tm-primary) !important;
        color: #fff !important;
    }
    .text-primary { color: var(--tm-primary) !important; }

    /* Modal styling */
    .modal-content { border-radius: 12px; border: none; }
    .modal-header { border-bottom: 1px solid #f0f0f0; }
    .modal-footer { border-top: 1px solid #f0f0f0; }
    </style>
    <?= $body['header'] ?? ''; ?>
</head>
<body>

<!-- Top Navbar -->
<div class="tm-topbar">
    <button class="btn d-lg-none me-2 p-1" type="button" onclick="document.querySelector('.tm-sidebar').classList.toggle('show')">
        <i class="ri-menu-line fs-22"></i>
    </button>

    <a href="<?= base_url('inbox'); ?>" class="tm-logo">
        <i class="ri-mail-fill"></i>
        <span class="d-none d-sm-inline">Torymail</span>
    </a>

    <div class="tm-search position-relative d-none d-md-block">
        <i class="ri-search-line search-icon"></i>
        <input type="text" id="globalSearch" placeholder="Search emails..." autocomplete="off">
    </div>

    <div class="tm-topbar-actions">
        <a href="<?= base_url('compose'); ?>" class="btn-compose d-none d-sm-flex">
            <i class="ri-edit-line"></i>
            <span>Compose</span>
        </a>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn-icon-topbar" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <i class="ri-notification-3-line"></i>
                <?php if ($notif_count > 0): ?>
                <span class="notif-badge"><?= $notif_count > 9 ? '9+' : $notif_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end tm-notif-dropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <?php if ($notif_count > 0): ?>
                    <a href="#" class="text-primary text-decoration-none" style="font-size:12px;" id="markAllNotifRead">Mark all read</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($notifications)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="ri-notification-off-line fs-32 d-block mb-2"></i>
                    No new notifications
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <a href="<?= base_url($notif['link'] ?? '#'); ?>" class="notif-item">
                    <div class="notif-icon"><i class="ri-mail-line"></i></div>
                    <div>
                        <div class="notif-text"><?= htmlspecialchars($notif['message']); ?></div>
                        <div class="notif-time"><?= time_ago($notif['created_at']); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown tm-user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="tm-user-avatar">
                    <?= strtoupper(substr($getUser['username'] ?? $getUser['email'] ?? 'U', 0, 1)); ?>
                </div>
                <span class="tm-user-name d-none d-lg-inline"><?= htmlspecialchars($getUser['username'] ?? $getUser['email'] ?? 'User'); ?></span>
                <i class="ri-arrow-down-s-line d-none d-lg-inline" style="color:#9ca3af;font-size:16px;"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" style="min-width:200px;">
                <div class="px-3 py-2 border-bottom">
                    <div class="fw-semibold"><?= htmlspecialchars($getUser['username'] ?? ''); ?></div>
                    <div class="text-muted" style="font-size:12px;"><?= htmlspecialchars($getUser['email'] ?? ''); ?></div>
                </div>
                <a class="dropdown-item py-2" href="<?= base_url('settings'); ?>">
                    <i class="ri-user-settings-line me-2 text-muted"></i> Profile & Settings
                </a>
                <a class="dropdown-item py-2" href="<?= base_url('domains'); ?>">
                    <i class="ri-global-line me-2 text-muted"></i> Domains
                </a>
                <a class="dropdown-item py-2" href="<?= base_url('mailboxes'); ?>">
                    <i class="ri-inbox-line me-2 text-muted"></i> Mailboxes
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item py-2 text-danger" href="<?= base_url('logout'); ?>">
                    <i class="ri-shut-down-line me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Mark all notifications as read
$(document).on('click', '#markAllNotifRead', function(e) {
    e.preventDefault();
    $.post('<?= base_url("ajaxs/user/notifications.php"); ?>', { action: 'mark_all_read' }, function(res) {
        if (res.success) {
            $('.notif-badge').remove();
            location.reload();
        }
    }, 'json');
});

// Global search
$('#globalSearch').on('keypress', function(e) {
    if (e.which === 13) {
        var q = $(this).val().trim();
        if (q) {
            window.location.href = '<?= base_url("inbox"); ?>?search=' + encodeURIComponent(q);
        }
    }
});
</script>
