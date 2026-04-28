<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProductModel.php';

// Create DB connection
$database = new Database();
$conn = $database->getConnection();

// Create instance of Product
$productModel = new ProductModel($conn);

// Include Product model
// Pagination setup
$limit = 8;
$paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

// Fetch coming soon products using your model function
$stmt = $conn->prepare("
    SELECT p.product_id, p.product_name, p.product_slug, 
           p.regular_price, p.sale_price, pi.image_url,
           c.slug AS category_slug, b.slug AS brand_slug
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_tag LIKE '%coming_soon%'
    GROUP BY p.product_id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Product Section for Coming Soon Products -->
<div class="product-section">
    <div class="category-header">
        <h2>Coming <span>Soon</span> Products</h2>
    </div>
    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): 
                    $product_url = "/" . htmlspecialchars($product['category_slug']) . "/" .
                                   htmlspecialchars($product['brand_slug']) . "/" .
                                   htmlspecialchars($product['product_slug']);
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg';

                    $product_price = (!empty($product['sale_price']))
                        ? '<del>Rs. ' . number_format($product['regular_price']) . '</del> Rs. ' . number_format($product['sale_price'])
                        : 'Rs. ' . number_format($product['regular_price']);
                    ?>
                    <div class="product-card">
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
                        <div class="product-price">
                            <span><?php echo $product_price; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No coming soon products found.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once __DIR__ . '/ad/feed1.php'; ?>
    <!-- Pagination -->
    <div class="pagination">
        <?php
        // Count total coming soon products
        $count_sql = "SELECT COUNT(*) as total FROM products WHERE product_tag LIKE '%coming_soon%'";
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

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
