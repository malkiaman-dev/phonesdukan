<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: " . url('admin/login.php')); // Redirect to login page if not logged in
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Default name if not set
$currentAdminPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
$adminPageCssMap = [
    'add-product.php' => [
        '/public/assets/css/admin/add-product.css',
        '/admin/css/ai-seo.css?v=3.0',
        '/admin/css/product-media.css?v=1.2',
    ],
    'edit-product.php' => [
        '/public/assets/css/admin/add-product.css',
        '/admin/css/ai-seo.css?v=3.0',
        '/admin/css/product-media.css?v=1.2',
    ],
    'manage-catalog.php' => [
        '/public/assets/css/admin/manage-catalog.css',
    ],
];
?>

<!DOCTYPE html>
<html class="admin-loading" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>    
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= url('public/assets/css/admin/admin.css'); ?>">
    <link rel="stylesheet" href="<?= url('public/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?= url('public/assets/css/frontend/ui-controls.css'); ?>">
    <?php if (isset($adminPageCssMap[$currentAdminPage])): ?>
        <?php
        $adminPageStylesheets = $adminPageCssMap[$currentAdminPage];
        if (!is_array($adminPageStylesheets)) {
            $adminPageStylesheets = [$adminPageStylesheets];
        }
        foreach ($adminPageStylesheets as $adminStylesheet): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($adminStylesheet, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        /* Critical shell styles to prevent FOUC */
        html.admin-loading body {
            opacity: 0;
        }
        html, body {
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100%;
            padding-left: 248px;
            padding-top: 56px;
            color: #111111;
            opacity: 1;
            transition: opacity 0.15s ease;
        }
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            z-index: 1000;
            background: #ffffff;
            color: #111111;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        }
        #sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 248px;
            height: calc(100vh - 56px);
            z-index: 5000;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            overflow: visible;
            isolation: isolate;
        }
        #sidebar .nav {
            position: relative;
            z-index: 1;
            overflow: visible;
        }
        #sidebar .nav-item.has-submenu:hover {
            z-index: 6500;
        }
        #sidebar .submenu {
            z-index: 7000;
        }
        #sidebar .nav,
        #sidebar ul,
        .admin-sidebar ul,
        .sidebar ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        a {
            text-decoration: none !important;
        }
        #sidebar a,
        .admin-sidebar a,
        .sidebar a {
            text-decoration: none !important;
            color: #4b5563;
        }
        .content,
        .dashboard-content {
            position: relative;
            z-index: 1;
            overflow: visible;
        }
        @media (max-width: 992px) {
            body { padding-left: 0; }
            #sidebar { left: -270px; }
            #sidebar.is-open { left: 0; }
        }
    </style>
    <script src="<?= url('public/assets/js/common.js'); ?>" defer></script>
    <script src="<?= url('public/assets/js/faqs.js'); ?>" defer></script>
    <script src="<?= url('public/assets/js/admin/required-fields.js'); ?>" defer></script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand admin-logo" href="<?= url(); ?>" aria-label="Go to PhonesDukan homepage">
                <img src="/public/assets/images/phonesdukan_logo.png" alt="PhonesDukan Logo">
            </a>
        </div>

        <!-- Admin Profile Dropdown (Right Side) -->
        <div class="admin-dropdown">
            <a class="admin-name" href="#">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($admin_name) ?>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= url('admin/profile.php'); ?>"><i class="fas fa-user"></i> Profile</a></li>
                <li><a class="dropdown-item" href="<?= url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a class="dropdown-item logout" href="<?= url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<script>
window.addEventListener('load', function () {
    document.documentElement.classList.remove('admin-loading');
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (!toggleBtn || !sidebar) {
        return;
    }

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('is-open');
    });

    document.addEventListener('click', function (event) {
        if (window.innerWidth > 992) {
            return;
        }
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('is-open');
        }
    });
});
</script>

