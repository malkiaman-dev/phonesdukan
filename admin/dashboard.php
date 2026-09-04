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

// Prefer session name (already set at login) — avoid an extra admins query.
$admin_name = (string) ($_SESSION['admin_name'] ?? 'Admin');

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

/**
 * Fetch one associative row; empty array on failure.
 */
function getRow(PDO $conn, string $query): array
{
    try {
        $stmt = $conn->query($query);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    } catch (Throwable $e) {
        return [];
    }
}

// Batch KPIs — no information_schema probes (those were very slow on Hostinger).
$productStats = getRow($conn, "
    SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
        SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS products_added_today
    FROM products
");

$orderStats = getRow($conn, "
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN LOWER(order_status) = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN LOWER(order_status) = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN LOWER(order_status) = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN LOWER(order_status) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_orders,
        SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS month_orders,
        COALESCE(SUM(total_price), 0) AS revenue_total,
        COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN total_price ELSE 0 END), 0) AS revenue_month
    FROM orders
");

$b2bStats = getRow($conn, "
    SELECT
        COUNT(*) AS b2b_orders,
        SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS b2b_pending_orders
    FROM bulk_inquiries
");

$kpis = [
    'total_products' => (int) ($productStats['total_products'] ?? 0),
    'total_orders' => (int) ($orderStats['total_orders'] ?? 0),
    'total_users' => getCount($conn, 'SELECT COUNT(*) FROM users'),
    'total_reviews' => getCount($conn, 'SELECT COUNT(*) FROM reviews'),
    'total_posts' => getCount($conn, 'SELECT COUNT(*) FROM posts'),
    'pending_orders' => (int) ($orderStats['pending_orders'] ?? 0),
    'processing_orders' => (int) ($orderStats['processing_orders'] ?? 0),
    'completed_orders' => (int) ($orderStats['completed_orders'] ?? 0),
    'cancelled_orders' => (int) ($orderStats['cancelled_orders'] ?? 0),
    'out_of_stock' => (int) ($productStats['out_of_stock'] ?? 0),
    'low_stock' => (int) ($productStats['low_stock'] ?? 0),
    'today_orders' => (int) ($orderStats['today_orders'] ?? 0),
    'month_orders' => (int) ($orderStats['month_orders'] ?? 0),
    'unread_contacts' => getCount($conn, 'SELECT COUNT(*) FROM contact_messages'),
    'b2b_orders' => (int) ($b2bStats['b2b_orders'] ?? 0),
    'b2b_pending_orders' => (int) ($b2bStats['b2b_pending_orders'] ?? 0),
];

$revenueTotal = (float) ($orderStats['revenue_total'] ?? 0);
$revenueMonth = (float) ($orderStats['revenue_month'] ?? 0);

$recentOrders = [];
try {
    $recentOrders = $conn->query(
        'SELECT order_id, customer_name, order_status, total_price, created_at
         FROM orders ORDER BY created_at DESC LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $recentOrders = [];
}

$recentReviews = [];
try {
    $recentReviews = $conn->query(
        'SELECT r.author, r.content, r.rating, r.created_at, p.product_name
         FROM reviews r
         LEFT JOIN products p ON p.product_id = r.product_id
         ORDER BY r.created_at DESC
         LIMIT 2'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $recentReviews = [];
}

$quickStats = [
    'new_customers_today' => getCount($conn, 'SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()'),
    'new_messages_today' => getCount($conn, 'SELECT COUNT(*) FROM contact_messages WHERE DATE(created_at) = CURDATE()'),
    'new_reviews_today' => getCount($conn, 'SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = CURDATE()'),
    'products_added_today' => (int) ($productStats['products_added_today'] ?? 0),
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
        <article class="panel-card recent-orders-panel">
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
    </section>

    <section class="dashboard-bottom-grid">
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

        <div class="dashboard-right-stack">
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
        </div>
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
