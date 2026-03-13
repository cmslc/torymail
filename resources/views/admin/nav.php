<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO (for horizontal layout) -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="<?= admin_url('home'); ?>" class="logo logo-dark">
                        <span class="logo-lg" style="font-size:18px;font-weight:700;color:#405189;">
                            <i class="ri-mail-line"></i> Torymail
                        </span>
                    </a>
                    <a href="<?= admin_url('home'); ?>" class="logo logo-light">
                        <span class="logo-lg" style="font-size:18px;font-weight:700;color:#fff;">
                            <i class="ri-mail-line"></i> Torymail
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- Quick Links -->
                <div class="d-none d-sm-flex align-items-center gap-2 ms-2">
                    <a href="<?= base_url(); ?>" class="btn btn-ghost-secondary btn-sm" target="_blank">
                        <i class="ri-external-link-line me-1"></i> View Site
                    </a>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <!-- Fullscreen -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen">
                        <i class='ri-fullscreen-line fs-22'></i>
                    </button>
                </div>

                <!-- Dark/Light Mode -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='ri-moon-line fs-22'></i>
                    </button>
                </div>

                <!-- User Profile -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <span class="rounded-circle header-profile-user bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                <i class="ri-admin-line fs-16"></i>
                            </span>
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"><?= sanitize($getAdmin['fullname'] ?? 'Admin'); ?></span>
                                <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">Administrator</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Welcome!</h6>
                        <a class="dropdown-item" href="<?= admin_url('settings'); ?>">
                            <i class="ri-settings-4-line text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Settings</span>
                        </a>
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
