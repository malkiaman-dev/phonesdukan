<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: " . url('admin/login.php')); // Redirect to login page if not logged in
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Default name if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>    
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <?php loadCSS(); ?>
    <?php loadJS(); ?>
    <body>
<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="<?= url(); ?>">
                <i class="fas fa-chart-line"></i> Visit Website
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

