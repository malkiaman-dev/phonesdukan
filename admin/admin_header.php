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
        'public/assets/css/admin/add-product.css',
        'admin/css/ai-seo.css',
        'admin/css/product-media.css',
    ],
    'edit-product.php' => [
        'public/assets/css/admin/add-product.css',
        'admin/css/ai-seo.css',
        'admin/css/product-media.css',
    ],
    'manage-catalog.php' => [
        'public/assets/css/admin/manage-catalog.css',
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>    
    
    <!-- FontAwesome for Icons (non-blocking load) -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"></noscript>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('public/assets/css/admin/admin.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('public/assets/css/admin/admin-components.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('public/assets/css/frontend/ui-controls.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (isset($adminPageCssMap[$currentAdminPage])): ?>
        <?php
        $adminPageStylesheets = $adminPageCssMap[$currentAdminPage];
        if (!is_array($adminPageStylesheets)) {
            $adminPageStylesheets = [$adminPageStylesheets];
        }
        foreach ($adminPageStylesheets as $adminStylesheet): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl(ltrim((string) $adminStylesheet, '/')), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        /* Critical shell styles to prevent FOUC */
        /* style.css locks storefront scroll; admin must be able to scroll */
        html,
        body {
            background: #f8fafc !important;
            margin: 0 !important;
            height: auto !important;
            min-height: 100% !important;
            max-height: none !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
        }
        /* Use longhands only — a later padding:0 shorthand must not wipe the sidebar offset */
        html body {
            padding-top: 56px !important;
            padding-right: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 248px !important;
            color: #111111;
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
            display: flex;
            flex-direction: column;
            overflow: hidden;
            isolation: isolate;
        }
        #sidebar .sidebar-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        #sidebar .sidebar-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }
        #sidebar .sidebar-footer {
            flex: 0 0 auto;
            border-top: 1px solid #e5e7eb;
            padding: 8px 10px 12px;
            background: #ffffff;
        }
        #sidebar .nav {
            position: relative;
            z-index: 1;
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
        }
        #sidebar .nav-link {
            color: #374151 !important;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #111111 !important;
            background: #fffbeb !important;
            border-left: 3px solid #f7cf04 !important;
        }
        #sidebar .nav-item.has-submenu > .nav-link:hover,
        #sidebar .nav-item.has-submenu > .nav-link.active {
            color: #374151 !important;
            background: transparent !important;
            border-left-color: transparent !important;
        }
        #sidebar .nav-link i {
            color: #6b7280 !important;
        }
        #sidebar .nav-link:hover i,
        #sidebar .nav-link.active i {
            color: #111111 !important;
        }
        #sidebar .nav-item.has-submenu > .nav-link:hover i,
        #sidebar .nav-item.has-submenu > .nav-link.active i {
            color: #6b7280 !important;
        }
        #sidebar .nav-link.logout {
            color: #ef4444 !important;
        }
        #sidebar .nav-link.logout:hover {
            color: #ef4444 !important;
            background: #fef2f2 !important;
        }
        .content,
        .dashboard-content {
            position: relative;
            z-index: 1;
            overflow: visible;
        }
        .admin-logo {
            display: inline-flex !important;
            align-items: center !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            border: 0 !important;
            border-radius: 0 !important;
            padding: 0 !important;
        }
        .admin-logo img {
            display: block !important;
            height: 36px !important;
            width: auto !important;
            max-height: 40px !important;
            object-fit: contain !important;
            background: transparent !important;
        }
        @media (max-width: 992px) {
            html body { padding-left: 0 !important; }
            #sidebar { left: -270px; }
            #sidebar.is-open { left: 0; }
        }
    </style>
    <script src="<?= htmlspecialchars(assetUrl('public/assets/js/admin/required-fields.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand admin-logo" href="<?= url(); ?>" aria-label="Go to PhonesDukan homepage" style="background:transparent!important;background-color:transparent!important;padding:0!important;border:0!important;box-shadow:none!important;">
                <img src="<?= htmlspecialchars(assetUrl('public/assets/images/phonesdukan_logo_yellow.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="PhonesDukan Logo" width="140" height="36" decoding="async" style="background:transparent!important;">
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
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
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
    }

    // Flyout submenus to the right (fixed so they aren't clipped by sidebar scroll)
    var scrollEl = document.querySelector('#sidebar .sidebar-scroll');
    document.querySelectorAll('#sidebar .nav-item.has-submenu').forEach(function (item) {
        var submenu = item.querySelector(':scope > .submenu');
        var link = item.querySelector(':scope > .nav-link');
        if (!submenu || !link) {
            return;
        }

        var closeTimer = null;

        function placeSubmenu() {
            var rect = link.getBoundingClientRect();
            var left = Math.round(rect.right + 6);
            var top = Math.round(rect.top);
            submenu.style.left = left + 'px';
            submenu.style.top = top + 'px';

            var subRect = submenu.getBoundingClientRect();
            if (subRect.bottom > window.innerHeight - 8) {
                top = Math.max(8, Math.round(window.innerHeight - subRect.height - 8));
                submenu.style.top = top + 'px';
            }
        }

        function openSubmenu() {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
            item.classList.add('is-open');
            placeSubmenu();
        }

        function scheduleClose() {
            closeTimer = setTimeout(function () {
                item.classList.remove('is-open');
            }, 140);
        }

        item.addEventListener('mouseenter', openSubmenu);
        item.addEventListener('mouseleave', scheduleClose);
        submenu.addEventListener('mouseenter', openSubmenu);
        submenu.addEventListener('mouseleave', scheduleClose);

        if (scrollEl) {
            scrollEl.addEventListener('scroll', function () {
                if (item.classList.contains('is-open')) {
                    placeSubmenu();
                }
            }, { passive: true });
        }
    });
});
</script>

