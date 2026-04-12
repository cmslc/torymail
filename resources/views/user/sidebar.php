<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$currentAction = $action ?? ($_GET['action'] ?? '');
$currentFolder = $_GET['folder'] ?? 'inbox';
$isMailboxLogin = !empty($_SESSION['mailbox_id']);

// Folder counts — scope to single mailbox if mailbox login
$folderCounts = [];
if ($isMailboxLogin) {
    $sidebarMailboxFilter = "`mailbox_id` = ?";
    $mailboxParam = [$_SESSION['mailbox_id']];
} else {
    $sidebarMailboxFilter = "`mailbox_id` IN (SELECT id FROM mailboxes WHERE user_id = ?)";
    $mailboxParam = [$getUser['id']];
}
$countRows = $ToryMail->get_list_safe("
    SELECT `folder`, COUNT(*) as cnt
    FROM `emails`
    WHERE {$sidebarMailboxFilter} AND `is_read` = 0 AND `folder` IN ('inbox','spam')
    GROUP BY `folder`
", $mailboxParam);
foreach ($countRows as $row) {
    $folderCounts[$row['folder']] = (int)$row['cnt'];
}
$draftCount = $ToryMail->get_row_safe("
    SELECT COUNT(*) as cnt FROM `emails`
    WHERE {$sidebarMailboxFilter} AND `folder` = 'drafts'
", $mailboxParam)['cnt'] ?? 0;

$unreadInbox = $folderCounts['inbox'] ?? ($getUser['unread_count'] ?? 0);
$unreadSpam = $folderCounts['spam'] ?? 0;

// Helper to check active state
if (!function_exists('tm_active')) {
    function tm_active($actions) {
        $current = $GLOBALS['currentAction'] ?? ($_GET['action'] ?? '');
        if (!is_array($actions)) $actions = [$actions];
        return in_array($current, $actions) ? 'active' : '';
    }
}
if (!function_exists('tm_folder_active')) {
    function tm_folder_active($folder) {
        $currentAction = $GLOBALS['currentAction'] ?? ($_GET['action'] ?? '');
        $currentFolder = $_GET['folder'] ?? 'inbox';
        return ($currentAction === 'inbox' || $currentAction === '') && $currentFolder === $folder ? 'active' : '';
    }
}

// Fetch user's labels for sidebar
$sidebarLabels = $ToryMail->get_list_safe("
    SELECT * FROM `email_labels`
    WHERE `user_id` = ?
    ORDER BY `name` ASC
", [$getUser['id']]);
?>
<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php require_once(__DIR__.'/nav.php'); ?>

        <!-- Two Column Menu (required by app.js) -->
        <div id="two-column-menu"></div>

        <!-- ========== App Menu ========== -->
        <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <?php $siteLogo = get_setting('site_logo', ''); $siteName = get_setting('site_name', 'Torymail'); ?>
                <a href="<?= base_url('inbox'); ?>" class="logo logo-dark">
                    <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22 text-primary"></i><?php endif; ?></span>
                    <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="<?= sanitize($siteName); ?>" height="28"><?php else: ?><i class="ri-mail-line me-1 text-primary fs-20"></i> <span class="fw-bold fs-16"><?= sanitize($siteName); ?></span><?php endif; ?></span>
                </a>
                <a href="<?= base_url('inbox'); ?>" class="logo logo-light">
                    <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                    <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="<?= sanitize($siteName); ?>" height="28"><?php else: ?><i class="ri-mail-line me-1 fs-20"></i> <span class="fw-bold fs-16"><?= sanitize($siteName); ?></span><?php endif; ?></span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar" data-simplebar>
                <div class="container-fluid">
                    <ul class="navbar-nav" id="navbar-nav">

                        <!-- Compose -->
                        <li class="nav-item">
                            <a href="<?= base_url('compose'); ?>" class="nav-link menu-link <?= tm_active(['compose']); ?>">
                                <i class="ri-edit-2-line"></i>
                                <span><?= __('compose'); ?></span>
                            </a>
                        </li>

                        <!-- Inbox -->
                        <li class="nav-item">
                            <a href="<?= base_url('inbox'); ?>" class="nav-link menu-link <?= tm_folder_active('inbox'); ?>">
                                <i class="ri-inbox-line"></i>
                                <span><?= __('inbox'); ?></span>
                                <?php if ($unreadInbox > 0): ?>
                                <span class="badge badge-center rounded-pill bg-danger ms-auto"><?= $unreadInbox; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- Labels -->
                        <?php if (!empty($sidebarLabels)): ?>
                        <li class="menu-title"><span><?= __('labels'); ?></span></li>
                        <?php foreach ($sidebarLabels as $sl): ?>
                        <li class="nav-item">
                            <a href="<?= base_url('inbox?label=' . urlencode($sl['id'])); ?>" class="nav-link menu-link">
                                <i class="ri-circle-fill fs-8" style="color:<?= htmlspecialchars($sl['color'] ?? '#878a99'); ?>;"></i>
                                <span><?= htmlspecialchars($sl['name']); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!$isMailboxLogin): ?>
                        <li class="menu-title"><span><?= __('management'); ?></span></li>

                        <!-- Mailboxes -->
                        <li class="nav-item">
                            <a href="<?= base_url('mailboxes'); ?>" class="nav-link menu-link <?= tm_active(['mailboxes']); ?>">
                                <i class="ri-mail-settings-line"></i>
                                <span><?= __('mailboxes'); ?></span>
                            </a>
                        </li>

                        <!-- Domains -->
                        <li class="nav-item">
                            <a href="<?= base_url('domains'); ?>" class="nav-link menu-link <?= tm_active(['domains']); ?>">
                                <i class="ri-global-line"></i>
                                <span><?= __('domains'); ?></span>
                            </a>
                        </li>

                        <!-- Contacts -->
                        <li class="nav-item">
                            <a href="<?= base_url('contacts'); ?>" class="nav-link menu-link <?= tm_active(['contacts']); ?>">
                                <i class="ri-contacts-line"></i>
                                <span><?= __('contacts'); ?></span>
                            </a>
                        </li>

                        <!-- Templates -->
                        <li class="nav-item">
                            <a href="<?= base_url('templates'); ?>" class="nav-link menu-link <?= tm_active(['templates']); ?>">
                                <i class="ri-file-copy-line"></i>
                                <span><?= __('templates'); ?></span>
                            </a>
                        </li>

                        <!-- Filters -->
                        <li class="nav-item">
                            <a href="<?= base_url('filters'); ?>" class="nav-link menu-link <?= tm_active(['filters']); ?>">
                                <i class="ri-filter-line"></i>
                                <span><?= __('filters'); ?></span>
                            </a>
                        </li>

                        <!-- Labels -->
                        <li class="nav-item">
                            <a href="<?= base_url('labels'); ?>" class="nav-link menu-link <?= tm_active(['labels']); ?>">
                                <i class="ri-price-tag-3-line"></i>
                                <span><?= __('labels'); ?></span>
                            </a>
                        </li>

                        <!-- Settings -->
                        <li class="nav-item">
                            <a href="<?= base_url('settings'); ?>" class="nav-link menu-link <?= tm_active(['settings']); ?>">
                                <i class="ri-settings-3-line"></i>
                                <span><?= __('settings'); ?></span>
                            </a>
                        </li>

                        <!-- API -->
                        <li class="nav-item">
                            <a href="<?= base_url('auth/api-docs'); ?>" class="nav-link menu-link" target="_blank">
                                <i class="ri-code-s-slash-line"></i>
                                <span>API</span>
                            </a>
                        </li>
                        <?php endif; ?>

                    </ul>
                </div>
            </div>
        </div>
        <!-- Sidebar overlay -->
        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
