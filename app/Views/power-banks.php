<?php
$pageTitle = "Best Power Bank Price in Pakistan – Affordable & Reliable";
$metaDescription = "Find the best power bank price in Pakistan. Choose from affordable, high-quality options starting at PKR 2499 to keep your devices powered on the go.";
$metaRobots = "index, follow";
$metaKeywords = "Power bank, Power bank price in Pakistan, Best power bank in Pakistan, Best power bank, fast charging power bank, best power bank online";
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';

// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Check if connection is established
if (!$conn) {
    die('Database connection error.');
}

// Pagination setup
$limit = 8;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

// Fetch products that belong to "power-banks" category
$query = "SELECT p.product_slug, p.product_name, p.regular_price, p.sale_price, p.stock_quantity,
                 pi.image_url, b.slug AS brand_slug, c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'power-banks' AND p.product_status != '0'
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="powerbank-section">
    <div class="powerbank-price-info">
        <h1><span>Power Banks</span> Prices in Pakistan - <?php echo date('F Y'); ?></h1>
    </div>
</div>
<?php include_once __DIR__ . '/ad/feed1.php'; ?>
<div class="product-section">
    <div class="category-header">
        <h2>Power <span>Banks</span></h2>
    </div>
    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php if (!empty($products)): ?>
                <?php
                foreach ($products as &$product):
                    // Process product data
                    $product['regular_price'] = floatval($product['regular_price']);
                    $product['sale_price'] = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                    $product['stock_quantity'] = intval($product['stock_quantity']);
                    $product['is_sold_out'] = ($product['stock_quantity'] <= 0);
                    $product['is_on_sale'] = ($product['stock_quantity'] > 0 && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);

                    // Construct product URL
                    $product_url = "/" . htmlspecialchars($product['category_slug']) . "/" .
                                   htmlspecialchars($product['brand_slug']) . "/" .
                                   htmlspecialchars($product['product_slug']);

                    // Set product image (fallback if empty)
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg';
                    ?>
                    <div class="product-card">
                        <div class="tagwrap">
                            <?php if ($product['is_sold_out']): ?>
                                <div class="sold-out">
                                    <span>Sold Out</span>
                                </div>
                            <?php elseif ($product['is_on_sale']): ?>
                                <div class="pro-tags">
                                    <span>Sale</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo $product_url; ?>">
                            <div class="product-img-wrapper">
                                <div class="product-img">
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </div>
                            </div>
                        </a>
                        <h3 class="product-title">
                            <a href="<?php echo $product_url; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h3>
                        <span class="prodline"></span>
                        <div class="product-price">
                            <?php
                            // Check if sale_price exists and product is on sale
                            if ($product['is_on_sale']) {
                                echo '<span class="r-regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> ';
                                echo '<span class="r-sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                            } else {
                                echo '<span class="r-regular-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found in this category.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once __DIR__ . '/ad/feed2.php'; ?>
    <div class="pagination">
        <?php
        // Count total products in the 'power-banks' category
        $count_sql = "SELECT COUNT(*) as total FROM products p
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE c.slug = 'power-banks' AND p.product_status != '0'";
        $stmt_count = $conn->prepare($count_sql);
        $stmt_count->execute();
        $total_rows = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);

        for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?paged=<?= $i ?>" class="<?= ($i == $paged) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php 
// Close connection
$conn = null;
?>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>