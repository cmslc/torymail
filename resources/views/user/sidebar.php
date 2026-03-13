<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$currentAction = $_GET['action'] ?? '';
$currentFolder = $_GET['folder'] ?? 'inbox';

// Fetch user's labels
$userLabels = $ToryMail->get_list_safe("
    SELECT * FROM `labels`
    WHERE `user_id` = ?
    ORDER BY `name` ASC
", [$getUser['id']]);

// Folder counts
$folderCounts = [];
$countRows = $ToryMail->get_list_safe("
    SELECT `folder`, COUNT(*) as cnt
    FROM `emails`
    WHERE `user_id` = ? AND `is_read` = 0 AND `folder` IN ('inbox','spam')
    GROUP BY `folder`
", [$getUser['id']]);
foreach ($countRows as $row) {
    $folderCounts[$row['folder']] = (int)$row['cnt'];
}
$draftCount = $ToryMail->get_row_safe("
    SELECT COUNT(*) as cnt FROM `emails`
    WHERE `user_id` = ? AND `folder` = 'drafts'
", [$getUser['id']])['cnt'] ?? 0;

$unreadInbox = $folderCounts['inbox'] ?? ($getUser['unread_count'] ?? 0);
$unreadSpam = $folderCounts['spam'] ?? 0;

// Helper to check active state
if (!function_exists('tm_sidebar_active')) {
    function tm_sidebar_active($action, $folder = null) {
        $currentAction = $_GET['action'] ?? '';
        $currentFolder = $_GET['folder'] ?? 'inbox';
        if ($folder !== null) {
            return ($currentAction === 'inbox' || $currentAction === '') && $currentFolder === $folder ? 'active' : '';
        }
        return $currentAction === $action ? 'active' : '';
    }
}
?>

<!-- Sidebar -->
<div class="tm-sidebar">
    <!-- Compose Button -->
    <div class="px-3 mb-3">
        <a href="<?= base_url('compose'); ?>" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" style="padding:10px;border-radius:8px;font-weight:500;">
            <i class="ri-edit-line fs-18"></i>
            <span>Compose</span>
        </a>
    </div>

    <ul class="list-unstyled px-2 mb-0">
        <!-- Inbox -->
        <li>
            <a href="<?= base_url('inbox'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'inbox'); ?>">
                <i class="ri-inbox-line"></i>
                <span>Inbox</span>
                <?php if ($unreadInbox > 0): ?>
                <span class="tm-sidebar-badge"><?= $unreadInbox; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Starred -->
        <li>
            <a href="<?= base_url('inbox?folder=starred'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'starred'); ?>">
                <i class="ri-star-line"></i>
                <span>Starred</span>
            </a>
        </li>

        <!-- Sent -->
        <li>
            <a href="<?= base_url('inbox?folder=sent'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'sent'); ?>">
                <i class="ri-send-plane-line"></i>
                <span>Sent</span>
            </a>
        </li>

        <!-- Drafts -->
        <li>
            <a href="<?= base_url('inbox?folder=drafts'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'drafts'); ?>">
                <i class="ri-draft-line"></i>
                <span>Drafts</span>
                <?php if ($draftCount > 0): ?>
                <span class="tm-sidebar-badge bg-secondary"><?= $draftCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Spam -->
        <li>
            <a href="<?= base_url('inbox?folder=spam'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'spam'); ?>">
                <i class="ri-spam-2-line"></i>
                <span>Spam</span>
                <?php if ($unreadSpam > 0): ?>
                <span class="tm-sidebar-badge bg-warning"><?= $unreadSpam; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Trash -->
        <li>
            <a href="<?= base_url('inbox?folder=trash'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'trash'); ?>">
                <i class="ri-delete-bin-line"></i>
                <span>Trash</span>
            </a>
        </li>

        <!-- Archive -->
        <li>
            <a href="<?= base_url('inbox?folder=archive'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('inbox', 'archive'); ?>">
                <i class="ri-archive-line"></i>
                <span>Archive</span>
            </a>
        </li>
    </ul>

    <!-- Separator -->
    <div class="tm-sidebar-separator"></div>

    <!-- Labels Section -->
    <div class="px-3 mb-2 d-flex align-items-center justify-content-between">
        <span class="tm-sidebar-section-title">Labels</span>
        <a href="<?= base_url('labels'); ?>" class="tm-sidebar-section-action" title="Manage labels">
            <i class="ri-add-line"></i>
        </a>
    </div>
    <ul class="list-unstyled px-2 mb-0">
        <?php if (empty($userLabels)): ?>
        <li class="px-3 py-2" style="font-size:12px;color:#6b7280;">No labels yet</li>
        <?php else: ?>
        <?php foreach ($userLabels as $label): ?>
        <li>
            <a href="<?= base_url('inbox?label=' . urlencode($label['id'])); ?>" class="tm-sidebar-link">
                <span class="label-dot" style="background:<?= htmlspecialchars($label['color'] ?? '#6b7280'); ?>;"></span>
                <span><?= htmlspecialchars($label['name']); ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <!-- Separator -->
    <div class="tm-sidebar-separator"></div>

    <!-- Management Section -->
    <ul class="list-unstyled px-2 mb-0">
        <li>
            <a href="<?= base_url('domains'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('domains'); ?>">
                <i class="ri-global-line"></i>
                <span>Domains</span>
            </a>
        </li>
        <li>
            <a href="<?= base_url('mailboxes'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('mailboxes'); ?>">
                <i class="ri-inbox-2-line"></i>
                <span>Mailboxes</span>
            </a>
        </li>
        <li>
            <a href="<?= base_url('contacts'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('contacts'); ?>">
                <i class="ri-contacts-line"></i>
                <span>Contacts</span>
            </a>
        </li>
        <li>
            <a href="<?= base_url('templates'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('templates'); ?>">
                <i class="ri-file-copy-line"></i>
                <span>Templates</span>
            </a>
        </li>
        <li>
            <a href="<?= base_url('filters'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('filters'); ?>">
                <i class="ri-filter-3-line"></i>
                <span>Filters</span>
            </a>
        </li>
        <li>
            <a href="<?= base_url('settings'); ?>" class="tm-sidebar-link <?= tm_sidebar_active('settings'); ?>">
                <i class="ri-settings-3-line"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Sidebar overlay for mobile -->
<div class="tm-sidebar-overlay d-lg-none" onclick="document.querySelector('.tm-sidebar').classList.remove('show');this.style.display='none';"></div>

<style>
.tm-sidebar-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    border-radius: 8px;
    color: var(--tm-sidebar-text);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.15s;
    margin-bottom: 2px;
}

.tm-sidebar-link:hover {
    background: rgba(255,255,255,0.06);
    color: #e5e7eb;
}

.tm-sidebar-link.active {
    background: var(--tm-sidebar-active);
    color: #fff;
}

.tm-sidebar-link i {
    font-size: 18px;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
}

.tm-sidebar-badge {
    margin-left: auto;
    background: var(--tm-primary);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

.tm-sidebar-badge.bg-secondary { background: #6b7280 !important; }
.tm-sidebar-badge.bg-warning { background: #f59e0b !important; }

.tm-sidebar-separator {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 12px 16px;
}

.tm-sidebar-section-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
}

.tm-sidebar-section-action {
    color: #6b7280;
    font-size: 16px;
    text-decoration: none;
    transition: color 0.2s;
}

.tm-sidebar-section-action:hover {
    color: #fff;
}

.tm-sidebar-link .label-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.tm-sidebar-overlay {
    display: none;
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1029;
}

.tm-sidebar.show ~ .tm-sidebar-overlay,
.tm-sidebar.show + .tm-sidebar-overlay {
    display: block;
}
</style>

<!-- Main Content Wrapper Start -->
<div class="tm-main">
