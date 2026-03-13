<?php
$body = $body ?? [];
$pageTitle = $body['title'] ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - Torymail Admin</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remixicon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --sidebar-bg: #1e1e2d;
            --sidebar-width: 260px;
            --content-bg: #f5f8fa;
            --navbar-height: 60px;
        }

        body {
            background: var(--content-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* Navbar */
        .admin-navbar {
            height: var(--navbar-height);
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            z-index: 1030;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-navbar .navbar-brand {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .admin-navbar .nav-profile .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
        }

        .admin-navbar .nav-profile .dropdown-toggle::after {
            display: none;
        }

        .admin-navbar .nav-profile .avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            z-index: 1040;
            overflow-y: auto;
            transition: all 0.3s;
        }

        .admin-sidebar .sidebar-brand {
            height: var(--navbar-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .admin-sidebar .sidebar-brand h4 {
            color: #fff;
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .admin-sidebar .sidebar-brand h4 i {
            color: var(--primary);
            margin-right: 8px;
        }

        .admin-sidebar .nav-menu {
            list-style: none;
            padding: 16px 0;
            margin: 0;
        }

        .admin-sidebar .nav-menu .nav-label {
            padding: 12px 20px 6px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.3);
            font-weight: 600;
        }

        .admin-sidebar .nav-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .admin-sidebar .nav-menu li a:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }

        .admin-sidebar .nav-menu li a.active {
            color: #fff;
            background: rgba(79,70,229,0.15);
            border-left-color: var(--primary);
        }

        .admin-sidebar .nav-menu li a i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Content */
        .admin-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--navbar-height);
            padding: 24px;
            min-height: calc(100vh - var(--navbar-height));
        }

        /* Page header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-header h4 {
            margin: 0;
            font-weight: 700;
            color: #1f2937;
        }

        /* Cards */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .card-custom {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .card-custom .card-header {
            background: transparent;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
            font-weight: 600;
        }

        .card-custom .card-body {
            padding: 20px;
        }

        /* Status badges */
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f3f4f6; color: #6b7280; }
        .badge-banned { background: #fef2f2; color: #991b1b; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }
        .badge-sent { background: #dcfce7; color: #166534; }
        .badge-failed { background: #fef2f2; color: #991b1b; }
        .badge-sending { background: #dbeafe; color: #1e40af; }

        /* Buttons */
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* Table */
        .table th {
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom-width: 1px;
        }

        /* Modal */
        .modal-header {
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-footer {
            border-top: 1px solid #e5e7eb;
        }

        /* Tabs */
        .nav-tabs .nav-link {
            color: #6b7280;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 600;
        }

        /* Progress bar custom */
        .progress-quota {
            height: 8px;
            border-radius: 4px;
            background: #f3f4f6;
        }

        .progress-quota .progress-bar {
            border-radius: 4px;
        }

        /* DNS status icons */
        .dns-ok { color: #16a34a; }
        .dns-fail { color: #dc2626; }

        /* Responsive */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-navbar {
                left: 0;
            }
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="admin-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm d-lg-none" id="sidebarToggle">
            <i class="ri-menu-line fs-5"></i>
        </button>
        <span class="navbar-brand d-lg-none">
            <i class="ri-mail-line"></i> Torymail
        </span>
    </div>
    <div class="nav-profile dropdown">
        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
            <div class="avatar-sm">
                <?= strtoupper(substr($getAdmin['fullname'] ?? 'A', 0, 1)) ?>
            </div>
            <span class="d-none d-md-inline"><?= sanitize($getAdmin['fullname'] ?? 'Admin') ?></span>
            <i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= admin_url('profile') ?>"><i class="ri-user-line me-2"></i> Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= base_url('auth/logout') ?>"><i class="ri-logout-box-line me-2"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});
</script>
