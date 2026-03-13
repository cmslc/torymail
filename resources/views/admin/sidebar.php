<?php
$currentAction = $action ?? '';
$menuItems = [
    ['action' => 'home',          'icon' => 'ri-dashboard-line',      'label' => 'Dashboard'],
    ['action' => 'users',         'icon' => 'ri-group-line',          'label' => 'Users'],
    ['action' => 'domains',       'icon' => 'ri-global-line',         'label' => 'Domains'],
    ['action' => 'mailboxes',     'icon' => 'ri-mail-settings-line',  'label' => 'Mailboxes'],
    ['action' => 'email-queue',   'icon' => 'ri-mail-send-line',      'label' => 'Email Queue'],
    ['action' => 'activity-logs', 'icon' => 'ri-file-list-line',      'label' => 'Activity Logs'],
    ['action' => 'settings',      'icon' => 'ri-settings-3-line',     'label' => 'Settings'],
];
?>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <h4><i class="ri-mail-line"></i> Torymail</h4>
    </div>

    <ul class="nav-menu">
        <li class="nav-label">Main Menu</li>
        <?php foreach ($menuItems as $item): ?>
            <?php
                $isActive = ($currentAction === $item['action']);
                if ($item['action'] === 'home' && ($currentAction === '' || $currentAction === 'home')) {
                    $isActive = true;
                }
                if ($item['action'] === 'users' && $currentAction === 'user-edit') {
                    $isActive = true;
                }
            ?>
            <li>
                <a href="<?= admin_url($item['action']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    if (toggle) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
});
</script>
