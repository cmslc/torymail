<?php if (!defined('IN_SITE')) {
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
<html lang="<?= current_lang(); ?>" data-layout="vertical" data-bs-theme="light" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-sidebar-visibility="show" data-layout-width="fluid" data-layout-position="fixed" data-layout-style="default" data-preloader="disable">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $__siteName = get_setting('site_name', 'Torymail'); ?>
    <title><?= isset($body['title']) ? htmlspecialchars($body['title']) : htmlspecialchars($__siteName); ?></title>
    <meta name="description" content="<?= isset($body['desc']) ? htmlspecialchars($body['desc']) : htmlspecialchars($__siteName . ' - Email Management'); ?>">
    <meta name="csrf-token" content="<?= csrf_token(); ?>">
    <!-- Layout config Js (MUST be first) -->
    <script src="<?= base_url('public/material/assets/js/layout.js'); ?>"></script>
    <!-- Bootstrap 5 Css -->
    <link href="<?= base_url('public/material/assets/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="<?= base_url('public/material/assets/css/icons.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- App Css -->
    <link href="<?= base_url('public/material/assets/css/app.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- Custom Css -->
    <link href="<?= base_url('public/material/assets/css/custom.css'); ?>" rel="stylesheet" type="text/css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- jQuery -->
    <script src="<?= base_url('public/js/jquery-3.6.0.js'); ?>"></script>
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
    <!-- SweetAlert2 -->
    <link href="<?= base_url('public/sweetalert2/default.css'); ?>" rel="stylesheet" type="text/css">
    <script src="<?= base_url('public/sweetalert2/sweetalert2.js'); ?>"></script>
    <!-- Sidebar anti-FOUC -->
    <style>
    [data-sidebar-size="sm"] .app-menu { width: 70px; }
    .app-menu .simplebar-content-wrapper { overflow: hidden; }
    </style>
    <?= $body['header'] ?? ''; ?>
</head>
