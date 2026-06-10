<?php
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../includes/functions.php';

// Include AdminReviewModel and OrderModel for regular orders, BulkInquiryModel for B2B orders
require_once dirname(__DIR__, 1) . '/app/Models/AdminReviewModel.php';
require_once dirname(__DIR__, 1) . '/app/Models/OrderModel.php';
require_once dirname(__DIR__, 1) . '/app/Models/BulkInquiryModel.php';
$reviewModel = new AdminReviewModel();
$orderModel = new OrderModel();
$bulkInquiryModel = new BulkInquiryModel();
$pending_reviews_count = $reviewModel->getPendingReviewsCount();
$pending_orders_count = $orderModel->getPendingOrdersCount();
$pending_b2b_orders_count = $bulkInquiryModel->getPendingB2BOrdersCount();
?>

<!-- Sidebar Navigation -->
<nav id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= url('admin/dashboard.php'); ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'manage-orders.php') ? 'active' : '' ?>" href="<?= url('admin/manage-orders.php'); ?>">
                <i class="fas fa-shopping-cart"></i> Orders
                <?php if ($pending_orders_count > 0): ?>
                    <span class="nav-badge">
                        <?php echo htmlspecialchars($pending_orders_count); ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'manage-b2b-orders.php') ? 'active' : '' ?>" href="<?= url('admin/manage-b2b-orders.php'); ?>">
                <i class="fas fa-briefcase"></i> B2B Orders
                <?php if ($pending_b2b_orders_count > 0): ?>
                    <span class="nav-badge">
                        <?php echo htmlspecialchars($pending_b2b_orders_count); ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item has-submenu">
            <a class="nav-link <?= in_array($current_page, ['manage_posts.php', 'manage-attributes.php']) ? 'active' : '' ?>" href="<?= url('admin/manage_posts.php'); ?>">
                <i class="fas fa-blog"></i> Posts
            </a>
            <ul class="submenu">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'manage_posts.php') ? 'active' : '' ?>" href="<?= url('admin/manage_posts.php'); ?>">
                        Manage Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'add_post.php') ? 'active' : '' ?>" href="<?= url('admin/add_post.php'); ?>">
                        Add New Post
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'categories.php') ? 'active' : '' ?>" href="<?= url('admin/categories.php'); ?>">
                        Manage Categories
                    </a>
                </li>
            </ul>
        </li>
        <li class="nav-item has-submenu">
            <a class="nav-link <?= in_array($current_page, ['manage-products.php', 'manage-catalog.php', 'manage-attributes.php', 'manage-variations.php']) ? 'active' : '' ?>" href="<?= url('admin/manage-products.php'); ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <ul class="submenu">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'manage-products.php') ? 'active' : '' ?>" href="<?= url('admin/manage-products.php'); ?>">
                        Manage Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'manage-catalog.php') ? 'active' : '' ?>" href="<?= url('admin/manage-catalog.php'); ?>">
                        Manage Catalog
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'manage-variations.php') ? 'active' : '' ?>" href="<?= url('admin/manage-variations.php'); ?>">
                        Manage Variations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'manage-attributes.php') ? 'active' : '' ?>" href="<?= url('admin/manage-attributes.php'); ?>">
                        Manage Attributes
                    </a>
                </li>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'customers.php') ? 'active' : '' ?>" href="<?= url('admin/customers.php'); ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'manage-users.php') ? 'active' : '' ?>" href="<?= url('admin/manage-users.php'); ?>">
                <i class="fas fa-user-cog"></i> Manage Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'manage-reviews.php') ? 'active' : '' ?>" href="<?= url('admin/manage-reviews.php'); ?>">
                <i class="fas fa-star"></i> Reviews
                <?php if ($pending_reviews_count > 0): ?>
                    <span class="nav-badge">
                        <?php echo htmlspecialchars($pending_reviews_count); ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'manage-contact-messages.php') ? 'active' : '' ?>" href="<?= url('admin/manage-contact-messages.php'); ?>">
                <i class="fas fa-envelope"></i> Contacts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>" href="<?= url('admin/reports.php'); ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'media-library.php') ? 'active' : '' ?>" href="<?= url('admin/media-library.php'); ?>">
                <i class="fas fa-images"></i> Media Library
            </a>
        </li>
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
            <li class="nav-item has-submenu">
                <a class="nav-link <?= in_array($current_page, ['admin_list.php', 'add_admin_form.php']) ? 'active' : '' ?>" href="<?= url('admin/admin_list.php'); ?>">
                    <i class="fas fa-user"></i> Admins
                </a>
                <ul class="submenu">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin_list.php') ? 'active' : '' ?>" href="<?= url('admin/admin_list.php'); ?>">
                            Manage Admins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'add_admin_form.php') ? 'active' : '' ?>" href="<?= url('admin/add_admin_form.php'); ?>">
                            Add Admin
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link logout" href="<?= url('admin/logout.php'); ?>">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>