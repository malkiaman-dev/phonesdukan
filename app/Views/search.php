<?php
// Search results are unique per query — noindex to avoid thin-content indexing
$metaRobots = 'noindex, follow';

require_once __DIR__ . '/../../includes/header.php';
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProductModel.php';

// Initialize Database & Product Model
$database = new Database();
$conn = $database->getConnection();
$productModel = new ProductModel($conn);

if (!$conn) {
    die('Database connection error.');
}

// Get search query from GET request
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

$results = [];

if (!empty($searchQuery)) {
    // Use the search function from ProductModel
    $results = $productModel->searchProducts($searchQuery);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="product-section">
        <div class="product-grid-container">
            <div class="category-header">
                <h2>Search Results for: <span><?php echo htmlspecialchars($searchQuery); ?></span></h2>
            </div>
            <div class="product-grid-wrapper">
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $product): ?>
                        <div class="product-card">
                            <a href="<?php echo htmlspecialchars(buildProductPathFromRow($product)); ?>">
                                <div class="product-img-wrapper">
                                    <div class="product-img">
                                        <img alt="<?php echo htmlspecialchars($product['product_name']); ?>" width="300" height="300" src="<?php echo htmlspecialchars($product['image_url']); ?>">
                                    </div>
                                </div>
                            </a>
                            <h3 class="product-title">
                                <a href="<?php echo htmlspecialchars(buildProductPathFromRow($product)); ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </a>
                            </h3>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No results found for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
