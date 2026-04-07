<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO (for horizontal layout) -->
                <div class="navbar-brand-box horizontal-logo">
                    <?php $navLogo = get_setting('site_logo', ''); $navSiteName = get_setting('site_name', 'Torymail'); ?>
                    <a href="<?= base_url('inbox'); ?>" class="logo logo-dark">
                        <span class="logo-sm"><?php if ($navLogo): ?><img src="<?= base_url($navLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22 text-primary"></i><?php endif; ?></span>
                        <span class="logo-lg"><?php if ($navLogo): ?><img src="<?= base_url($navLogo); ?>" alt="<?= sanitize($navSiteName); ?>" height="28"><?php else: ?><i class="ri-mail-line me-1 text-primary"></i> <span class="fw-bold"><?= sanitize($navSiteName); ?></span><?php endif; ?></span>
                    </a>
                    <a href="<?= base_url('inbox'); ?>" class="logo logo-light">
                        <span class="logo-sm"><?php if ($navLogo): ?><img src="<?= base_url($navLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                        <span class="logo-lg"><?php if ($navLogo): ?><img src="<?= base_url($navLogo); ?>" alt="<?= sanitize($navSiteName); ?>" height="28"><?php else: ?><i class="ri-mail-line me-1"></i> <span class="fw-bold"><?= sanitize($navSiteName); ?></span><?php endif; ?></span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- Search -->
                <form class="app-search d-none d-md-flex" action="<?= base_url('inbox'); ?>" method="GET">
                    <div class="position-relative">
                        <input type="text" name="search" class="form-control" placeholder="Search emails..." autocomplete="off" value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <input type="hidden" name="folder" value="<?= htmlspecialchars($_GET['folder'] ?? 'inbox'); ?>">
                        <span class="ri-search-line search-widget-icon"></span>
                    </div>
                </form>

                <!-- Compose shortcut -->
                <div class="d-none d-sm-flex align-items-center ms-2">
                    <a href="<?= base_url('compose'); ?>" class="btn btn-soft-primary btn-sm">
                        <i class="ri-edit-2-line me-1"></i> Compose
                    </a>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <!-- Language Switcher -->
                <div class="dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fs-14 fw-medium"><?= strtoupper(current_lang()); ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item <?= current_lang() === 'en' ? 'active' : ''; ?>" href="#" onclick="switchLang('en')">
                            <span class="align-middle">English</span>
                        </a>
                        <a class="dropdown-item <?= current_lang() === 'vi' ? 'active' : ''; ?>" href="#" onclick="switchLang('vi')">
                            <span class="align-middle">Tiếng Việt</span>
                        </a>
                    </div>
                </div>

                <!-- Fullscreen -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen">
                        <i class="ri-fullscreen-line fs-22"></i>
                    </button>
                </div>

                <!-- Dark/Light Mode -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class="ri-moon-line fs-22"></i>
                    </button>
                </div>

                <!-- Notifications -->
                <div class="dropdown topbar-head-dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-notification-3-line fs-22"></i>
                        <?php if ($notif_count > 0): ?>
                        <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger"><?= $notif_count > 9 ? '9+' : $notif_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white">Notifications</h6>
                                    </div>
                                    <?php if ($notif_count > 0): ?>
                                    <div class="col-auto dropdown-tabs">
                                        <a href="#" class="text-white text-decoration-none fs-13" id="markAllNotifRead">Mark all read</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="py-2 ps-2" style="max-height:300px;" data-simplebar>
                            <?php if (empty($notifications)): ?>
                            <div class="text-center py-4">
                                <i class="ri-notification-off-line fs-32 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0 fs-13">No new notifications</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                            <div class="text-reset notification-item d-block dropdown-item position-relative">
                                <a href="<?= base_url($notif['link'] ?? '#'); ?>" class="text-reset text-decoration-none">
                                    <div class="d-flex">
                                        <div class="avatar-xs me-3 flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                                                <i class="ri-mail-line"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 fs-13"><?= htmlspecialchars($notif['message']); ?></p>
                                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                <span><i class="ri-time-line"></i> <?= time_ago($notif['created_at']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <span class="rounded-circle header-profile-user bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:14px;font-weight:600;">
                                <?= strtoupper(substr($getUser['fullname'] ?? $getUser['email'] ?? 'U', 0, 1)); ?>
                            </span>
                            <span class="text-start ms-xl-2">
                                <?php if (!empty($_SESSION['mailbox_email'])): ?>
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"><?= htmlspecialchars($_SESSION['mailbox_email']); ?></span>
                                <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">Mailbox</span>
                                <?php else: ?>
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"><?= htmlspecialchars($getUser['fullname'] ?? $getUser['email'] ?? 'User'); ?></span>
                                <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text"><?= htmlspecialchars($getUser['role'] ?? 'User'); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Welcome!</h6>
                        <?php if (empty($_SESSION['mailbox_id'])): ?>
                        <a class="dropdown-item" href="<?= base_url('settings'); ?>">
                            <i class="ri-user-settings-line text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                        <?php if (($getUser['role'] ?? '') === 'admin'): ?>
                        <a class="dropdown-item" href="<?= base_url('admin'); ?>">
                            <i class="ri-admin-line text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Admin Panel</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= base_url('auth/logout'); ?>">
                            <i class="ri-shut-down-line text-danger fs-16 align-middle me-1"></i>
                            <span class="align-middle">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Mark all notifications as read
$(document).on('click', '#markAllNotifRead', function(e) {
    e.preventDefault();
    $.ajax({
        url: '<?= base_url("ajaxs/user/notifications.php"); ?>',
        method: 'POST', data: { action: 'mark_all_read' }, dataType: 'json',
        success: function(res) {
            if (res.success) location.reload();
        }
    });
});
</script>
