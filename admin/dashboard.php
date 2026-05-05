<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

// Include the header
include __DIR__ . '/admin_header.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in by checking both session variables
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Fetch admin details
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];

    $sql = "SELECT name FROM admins WHERE id = :admin_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":admin_id", $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $admin_name = $admin ? htmlspecialchars($admin['name']) : "Admin";
} else {
    $admin_name = "Admin";
}

/**
 * Returns true if a table exists.
 */
function tableExists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name");
    $stmt->execute([':table_name' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Returns true if a column exists in a table.
 */
function columnExists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name");
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Run scalar query safely and return int.
 */
function getCount(PDO $conn, string $query): int
{
    try {
        $stmt = $conn->query($query);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$hasOrders = tableExists($conn, 'orders');
$hasUsers = tableExists($conn, 'users');
$hasProducts = tableExists($conn, 'products');
$hasReviews = tableExists($conn, 'reviews');
$hasContacts = tableExists($conn, 'contact_messages');
$hasPosts = tableExists($conn, 'posts');
$hasB2B = tableExists($conn, 'bulk_inquiries');

$orderHasStatus = $hasOrders && columnExists($conn, 'orders', 'order_status');
$orderHasCreatedAt = $hasOrders && columnExists($conn, 'orders', 'created_at');
$orderHasTotal = $hasOrders && columnExists($conn, 'orders', 'total_price');
$productHasStock = $hasProducts && columnExists($conn, 'products', 'stock_quantity');
$productHasCreatedAt = $hasProducts && columnExists($conn, 'products', 'created_at');
$userHasCreatedAt = $hasUsers && columnExists($conn, 'users', 'created_at');
$reviewHasCreatedAt = $hasReviews && columnExists($conn, 'reviews', 'created_at');
$contactHasCreatedAt = $hasContacts && columnExists($conn, 'contact_messages', 'created_at');
$postHasCreatedAt = $hasPosts && columnExists($conn, 'posts', 'created_at');
$b2bHasStatus = $hasB2B && columnExists($conn, 'bulk_inquiries', 'status');

$kpis = [
    'total_products' => $hasProducts ? getCount($conn, "SELECT COUNT(*) FROM products") : 0,
    'total_orders' => $hasOrders ? getCount($conn, "SELECT COUNT(*) FROM orders") : 0,
    'total_users' => $hasUsers ? getCount($conn, "SELECT COUNT(*) FROM users") : 0,
    'total_reviews' => $hasReviews ? getCount($conn, "SELECT COUNT(*) FROM reviews") : 0,
    'total_posts' => $hasPosts ? getCount($conn, "SELECT COUNT(*) FROM posts") : 0,
    'pending_orders' => ($hasOrders && $orderHasStatus) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE LOWER(order_status) = 'pending'") : 0,
    'processing_orders' => ($hasOrders && $orderHasStatus) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE LOWER(order_status) = 'processing'") : 0,
    'completed_orders' => ($hasOrders && $orderHasStatus) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE LOWER(order_status) = 'completed'") : 0,
    'cancelled_orders' => ($hasOrders && $orderHasStatus) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE LOWER(order_status) = 'cancelled'") : 0,
    'out_of_stock' => ($hasProducts && $productHasStock) ? getCount($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity <= 0") : 0,
    'low_stock' => ($hasProducts && $productHasStock) ? getCount($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5") : 0,
    'today_orders' => ($hasOrders && $orderHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()") : 0,
    'month_orders' => ($hasOrders && $orderHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())") : 0,
    'unread_contacts' => $hasContacts ? getCount($conn, "SELECT COUNT(*) FROM contact_messages") : 0,
    'b2b_orders' => $hasB2B ? getCount($conn, "SELECT COUNT(*) FROM bulk_inquiries") : 0,
    'b2b_pending_orders' => ($hasB2B && $b2bHasStatus) ? getCount($conn, "SELECT COUNT(*) FROM bulk_inquiries WHERE LOWER(status) = 'pending'") : 0,
];

$revenueTotal = 0.0;
$revenueMonth = 0.0;
if ($hasOrders && $orderHasTotal) {
    try {
        $revenueTotal = (float) $conn->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn();
        if ($orderHasCreatedAt) {
            $revenueMonth = (float) $conn->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn();
        }
    } catch (Throwable $e) {
        $revenueTotal = 0.0;
        $revenueMonth = 0.0;
    }
}

$recentOrders = [];
if ($hasOrders) {
    try {
        $query = "SELECT order_id, customer_name, order_status, total_price, created_at FROM orders ORDER BY created_at DESC LIMIT 5";
        $recentOrders = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $recentOrders = [];
    }
}

$recentReviews = [];
if ($hasReviews) {
    try {
        $query = "SELECT r.author, r.content, r.rating, r.created_at, p.product_name
                  FROM reviews r
                  LEFT JOIN products p ON p.product_id = r.product_id
                  ORDER BY r.created_at DESC
                  LIMIT 3";
        $recentReviews = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $recentReviews = [];
    }
}

$quickStats = [
    'new_customers_today' => ($hasUsers && $userHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()") : 0,
    'new_messages_today' => ($hasContacts && $contactHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM contact_messages WHERE DATE(created_at) = CURDATE()") : 0,
    'new_reviews_today' => ($hasReviews && $reviewHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = CURDATE()") : 0,
    'products_added_today' => ($hasProducts && $productHasCreatedAt) ? getCount($conn, "SELECT COUNT(*) FROM products WHERE DATE(created_at) = CURDATE()") : 0,
];
?>
<?php // Only include the sidebar if the admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    include __DIR__ . '/admin_sidebar.php';
}?>

<div class="content dashboard-content">
    <section class="dashboard-page-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?= htmlspecialchars($admin_name) ?>. Here is what is happening in your store today.</p>
        </div>
        <div class="dashboard-header-actions">
            <span class="dashboard-date"><?= date('l, d M Y') ?></span>
            <a class="quick-btn" href="<?= url('admin/add-product.php'); ?>">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-products.php'); ?>" role="button" tabindex="0" aria-label="Open Total Products">
            <div class="kpi-icon"><i class="fas fa-box"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Total Products</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['total_products']) ?></h3>
                <small>Catalog items in store</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-orders.php'); ?>" role="button" tabindex="0" aria-label="Open Total Orders">
            <div class="kpi-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Total Orders</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['total_orders']) ?></h3>
                <small><?= htmlspecialchars((string) $kpis['today_orders']) ?> placed today</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/customers.php'); ?>" role="button" tabindex="0" aria-label="Open Total Users">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Total Users</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['total_users']) ?></h3>
                <small><?= htmlspecialchars((string) $quickStats['new_customers_today']) ?> new today</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/reports.php'); ?>" role="button" tabindex="0" aria-label="Open Total Revenue">
            <div class="kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Total Revenue</p>
                <h3 class="kpi-value">Rs. <?= number_format($revenueTotal, 0) ?></h3>
                <small>Rs. <?= number_format($revenueMonth, 0) ?> this month</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-orders.php?status=pending'); ?>" role="button" tabindex="0" aria-label="Open Pending Orders">
            <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Pending Orders</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['pending_orders']) ?></h3>
                <small>Need fulfillment action</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-orders.php?status=processing'); ?>" role="button" tabindex="0" aria-label="Open Processing Orders">
            <div class="kpi-icon"><i class="fas fa-spinner"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Processing Orders</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['processing_orders']) ?></h3>
                <small>Currently being prepared</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-orders.php?status=completed'); ?>" role="button" tabindex="0" aria-label="Open Completed Orders">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Completed Orders</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['completed_orders']) ?></h3>
                <small>Successfully delivered</small>
            </div>
        </article>
        <article class="kpi-card kpi-clickable" data-href="<?= url('admin/manage-orders.php?status=cancelled'); ?>" role="button" tabindex="0" aria-label="Open Cancelled Orders">
            <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-meta">
                <p class="kpi-title">Cancelled Orders</p>
                <h3 class="kpi-value"><?= htmlspecialchars((string) $kpis['cancelled_orders']) ?></h3>
                <small>Cancelled transactions</small>
            </div>
        </article>
    </section>

    <section class="dashboard-main-grid">
        <article class="panel-card">
            <div class="panel-header">
                <h2>Recent Orders</h2>
                <a href="<?= url('admin/manage-orders.php'); ?>">View all</a>
            </div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php $status = strtolower((string) ($order['order_status'] ?? 'pending')); ?>
                            <tr class="interactive-row" data-href="<?= url('admin/manage-orders.php'); ?>">
                                <td>#<?= htmlspecialchars((string) ($order['order_id'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($order['customer_name'] ?? 'Guest')) ?></td>
                                <td><span class="status-badge status-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                                <td>Rs. <?= number_format((float) ($order['total_price'] ?? 0), 0) ?></td>
                                <td><?= !empty($order['created_at']) ? date('d M Y', strtotime((string) $order['created_at'])) : '-' ?></td>
                                <td><a class="table-action" href="<?= url('admin/manage-orders.php'); ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-header">
                <h2>Recent Reviews</h2>
                <a href="<?= url('admin/manage-reviews.php'); ?>">Manage reviews</a>
            </div>
            <?php if (!empty($recentReviews)): ?>
                <div class="review-list">
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="review-item">
                            <div class="review-top">
                                <div class="review-meta">
                                    <strong><?= htmlspecialchars((string) ($review['author'] ?? 'Customer')) ?></strong>
                                    <p class="review-product"><?= htmlspecialchars((string) ($review['product_name'] ?? 'Product')) ?></p>
                                </div>
                                <small><?= !empty($review['created_at']) ? date('d M Y', strtotime((string) $review['created_at'])) : '-' ?></small>
                            </div>
                            <div class="rating-stars">
                                <?php
                                $rating = (int) ($review['rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++):
                                    ?>
                                    <i class="fas fa-star <?= $i <= $rating ? 'filled' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="review-text"><?= htmlspecialchars((string) ($review['content'] ?? '')) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state block-empty">No recent reviews found.</div>
            <?php endif; ?>
        </article>
    </section>

    <section class="dashboard-secondary-grid">
        <article class="panel-card">
            <div class="panel-header">
                <h2>Store Activity</h2>
            </div>
            <div class="activity-list">
                <div class="activity-item"><p class="activity-label"><i class="fas fa-shopping-bag"></i>New orders today</p><strong><?= htmlspecialchars((string) $kpis['today_orders']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-calendar-alt"></i>Orders this month</p><strong><?= htmlspecialchars((string) $kpis['month_orders']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-user-plus"></i>New customers today</p><strong><?= htmlspecialchars((string) $quickStats['new_customers_today']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-hourglass-half"></i>Pending orders</p><strong><?= htmlspecialchars((string) $kpis['pending_orders']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-star"></i>Total reviews</p><strong><?= htmlspecialchars((string) $kpis['total_reviews']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-envelope"></i>Total contacts</p><strong><?= htmlspecialchars((string) $kpis['unread_contacts']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-blog"></i>Total posts</p><strong><?= htmlspecialchars((string) $kpis['total_posts']) ?></strong></div>
                <div class="activity-item"><p class="activity-label"><i class="fas fa-briefcase"></i>B2B orders</p><strong><?= htmlspecialchars((string) $kpis['b2b_orders']) ?></strong></div>
            </div>
        </article>
        <article class="panel-card">
            <div class="panel-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="quick-actions-grid">
                <a href="<?= url('admin/add-product.php'); ?>" class="quick-action-card"><i class="fas fa-plus-circle"></i><p>Add Product</p></a>
                <a href="<?= url('admin/manage-orders.php'); ?>" class="quick-action-card"><i class="fas fa-shopping-cart"></i><p>View Orders</p></a>
                <a href="<?= url('admin/customers.php'); ?>" class="quick-action-card"><i class="fas fa-user-friends"></i><p>Manage Customers</p></a>
                <a href="<?= url('admin/manage-reviews.php'); ?>" class="quick-action-card"><i class="fas fa-star"></i><p>Manage Reviews</p></a>
                <a href="<?= url('admin/media-library.php'); ?>" class="quick-action-card"><i class="fas fa-upload"></i><p>Upload Media</p></a>
                <a href="<?= url('admin/reports.php'); ?>" class="quick-action-card"><i class="fas fa-chart-line"></i><p>View Reports</p></a>
            </div>
        </article>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var interactiveNodes = document.querySelectorAll('[data-href]');
    interactiveNodes.forEach(function (node) {
        var goToHref = function () {
            var href = node.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        };

        node.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }
            goToHref();
        });

        node.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                goToHref();
            }
        });
    });
});
</script>
