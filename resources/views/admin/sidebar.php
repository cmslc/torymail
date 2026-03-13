<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$currentAction = $action ?? '';

$sidebarMenu = [
    [
        'label' => 'Dashboard',
        'icon'  => 'ri-dashboard-2-line',
        'url'   => admin_url('home'),
        'active' => ['home', ''],
    ],
    [
        'label' => 'Users',
        'icon'  => 'ri-group-line',
        'url'   => admin_url('users'),
        'active' => ['users', 'user-edit'],
    ],
    [
        'label' => 'Domains',
        'icon'  => 'ri-global-line',
        'url'   => admin_url('domains'),
        'active' => ['domains'],
    ],
    [
        'label' => 'Mailboxes',
        'icon'  => 'ri-mail-settings-line',
        'url'   => admin_url('mailboxes'),
        'active' => ['mailboxes'],
    ],
    [
        'label' => 'Email Queue',
        'icon'  => 'ri-mail-send-line',
        'url'   => admin_url('email-queue'),
        'active' => ['email-queue'],
    ],
    [
        'label' => 'Activity Logs',
        'icon'  => 'ri-file-list-3-line',
        'url'   => admin_url('activity-logs'),
        'active' => ['activity-logs'],
    ],
    [
        'label' => 'Settings',
        'icon'  => 'ri-settings-3-line',
        'url'   => admin_url('settings'),
        'active' => ['settings'],
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
                <a href="<?= admin_url('home'); ?>" class="logo logo-dark">
                    <span class="logo-lg" style="font-size:18px;font-weight:700;color:#fff;">
                        <i class="ri-mail-line"></i> Torymail
                    </span>
                </a>
                <a href="<?= admin_url('home'); ?>" class="logo logo-light">
                    <span class="logo-lg" style="font-size:18px;font-weight:700;color:#fff;">
                        <i class="ri-mail-line"></i> Torymail
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar" data-simplebar>
                <div class="container-fluid">
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span>Menu</span></li>

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
