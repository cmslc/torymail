<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$currentAction = $action ?? '';

$sidebarMenu = [
    [
        'label' => __('dashboard'),
        'icon'  => 'ri-dashboard-2-line',
        'url'   => admin_url('home'),
        'active' => ['home', ''],
    ],
    [
        'label' => __('users'),
        'icon'  => 'ri-group-line',
        'url'   => admin_url('users'),
        'active' => ['users', 'user-edit'],
    ],
    [
        'label' => __('domains'),
        'icon'  => 'ri-global-line',
        'url'   => admin_url('domains'),
        'active' => ['domains'],
    ],
    [
        'label' => __('mailboxes'),
        'icon'  => 'ri-mail-settings-line',
        'url'   => admin_url('mailboxes'),
        'active' => ['mailboxes'],
    ],
    [
        'label' => __('email_queue'),
        'icon'  => 'ri-mail-send-line',
        'url'   => admin_url('email-queue'),
        'active' => ['email-queue'],
    ],
    [
        'label' => __('activity_logs'),
        'icon'  => 'ri-file-list-3-line',
        'url'   => admin_url('activity-logs'),
        'active' => ['activity-logs'],
    ],
    [
        'label' => __('settings'),
        'icon'  => 'ri-settings-3-line',
        'url'   => admin_url('settings'),
        'active' => ['settings'],
    ],
    [
        'label' => __('system_info'),
        'icon'  => 'ri-information-line',
        'url'   => admin_url('system-info'),
        'active' => ['system-info'],
    ],
];
?>
<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php require_once(__DIR__.'/nav.php'); ?>

        <!-- ========== App Menu ========== -->
        <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <?php $adminLogo = get_setting('site_logo', ''); $adminSiteName = get_setting('site_name', 'Torymail'); ?>
                <a href="<?= admin_url('home'); ?>" class="logo logo-dark">
                    <span class="logo-sm"><?php if ($adminLogo): ?><img src="<?= base_url($adminLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                    <span class="logo-lg"><?php if ($adminLogo): ?><img src="<?= base_url($adminLogo); ?>" alt="<?= sanitize($adminSiteName); ?>" height="28"><?php else: ?><span style="font-size:18px;font-weight:700;color:#fff;"><i class="ri-mail-line"></i> <?= sanitize($adminSiteName); ?></span><?php endif; ?></span>
                </a>
                <a href="<?= admin_url('home'); ?>" class="logo logo-light">
                    <span class="logo-sm"><?php if ($adminLogo): ?><img src="<?= base_url($adminLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                    <span class="logo-lg"><?php if ($adminLogo): ?><img src="<?= base_url($adminLogo); ?>" alt="<?= sanitize($adminSiteName); ?>" height="28"><?php else: ?><span style="font-size:18px;font-weight:700;color:#fff;"><i class="ri-mail-line"></i> <?= sanitize($adminSiteName); ?></span><?php endif; ?></span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar" data-simplebar>
                <div class="container-fluid">
                    <ul class="navbar-nav" id="navbar-nav">
                        <?php foreach ($sidebarMenu as $index => $item): ?>
                            <?php
                                $isActive = in_array($currentAction, $item['active']);
                                $activeClass = $isActive ? ' active' : '';
                            ?>
                            <?php if (isset($item['children'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link menu-link <?= $isActive ? '' : 'collapsed'; ?>" href="#sidebarMenu<?= $index; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?= $isActive ? 'true' : 'false'; ?>" aria-controls="sidebarMenu<?= $index; ?>">
                                        <i class="<?= $item['icon']; ?>"></i>
                                        <span><?= $item['label']; ?></span>
                                    </a>
                                    <div class="collapse <?= $isActive ? 'show' : ''; ?>" id="sidebarMenu<?= $index; ?>">
                                        <ul class="nav nav-sm flex-column">
                                            <?php foreach ($item['children'] as $child): ?>
                                                <?php $childActive = in_array($currentAction, $child['active']) ? ' active' : ''; ?>
                                                <li class="nav-item">
                                                    <a href="<?= $child['url']; ?>" class="nav-link<?= $childActive; ?>"><?= $child['label']; ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a href="<?= $item['url']; ?>" class="nav-link menu-link<?= $activeClass; ?>">
                                        <i class="<?= $item['icon']; ?>"></i>
                                        <span><?= $item['label']; ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="sidebar-background"></div>
        </div>
        <!-- Left Sidebar End -->
        <div class="vertical-overlay"></div>

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
