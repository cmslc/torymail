<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>
<!doctype html>
<html lang="<?= current_lang(); ?>" data-layout="vertical" data-bs-theme="light" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-sidebar-visibility="show" data-layout-width="fluid" data-layout-position="fixed" data-layout-style="default" data-preloader="disable">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= sanitize($body['title'] ?? 'Admin Panel'); ?> - Torymail Admin</title>
    <link rel="shortcut icon" href="<?= asset_url('images/favicon.ico'); ?>">
    <!-- Layout config Js (MUST be first) -->
    <script src="<?= asset_url('material/assets/js/layout.js'); ?>"></script>
    <!-- Bootstrap 5 Css -->
    <link href="<?= asset_url('material/assets/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="<?= asset_url('material/assets/css/icons.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- App Css -->
    <link href="<?= asset_url('material/assets/css/app.min.css'); ?>" rel="stylesheet" type="text/css">
    <!-- Custom Css -->
    <link href="<?= asset_url('material/assets/css/custom.css'); ?>" rel="stylesheet" type="text/css">
    <meta name="csrf-token" content="<?= csrf_token(); ?>">
    <!-- jQuery -->
    <script src="<?= asset_url('js/jquery-3.6.0.js'); ?>"></script>
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
    <!-- DataTables BS5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <!-- SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Sidebar anti-FOUC -->
    <style>
    .app-menu .navbar-nav { opacity: 0; transition: opacity .15s ease; }
    .app-menu .navbar-nav.ready { opacity: 1; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var nav = document.getElementById('navbar-nav');
            if (nav) nav.classList.add('ready');
        }, 50);
    });
    </script>
    <?= $body['header'] ?? ''; ?>
</head>
