<?php
$metaRobots = 'noindex, follow';

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProductModel.php';

if (!defined('SEARCH_RESULTS_PAGE')) {
    define('SEARCH_RESULTS_PAGE', true);
}

$searchQuery = isset($_GET['query']) ? trim((string) $_GET['query']) : '';
$pageTitle = $searchQuery !== ''
    ? 'Search Results for ' . $searchQuery . ' | Phones Dukan'
    : 'Search | Phones Dukan';

$products = [];

if ($searchQuery !== '') {
    $productModel = new ProductModel();
    $rawResults = $productModel->searchProducts($searchQuery, 48);

    foreach ($rawResults as $row) {
        $products[] = prepareProductCardFromRow($row);
    }
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="sr-page">
    <section class="sr-section">
        <div class="sr-container">
            <header class="sr-header">
                <h1 class="sr-title">
                    <?php if ($searchQuery !== ''): ?>
                        Search Results for: <span><?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php else: ?>
                        Search Products
                    <?php endif; ?>
                </h1>
                <?php if ($searchQuery !== '' && !empty($products)): ?>
                    <p class="sr-count">
                        <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found
                    </p>
                <?php endif; ?>
            </header>

            <?php if ($searchQuery === ''): ?>
                <div class="sr-empty">
                    <h2>Enter a search term</h2>
                    <p>Use the search bar above to find mobiles, accessories, earbuds, and more.</p>
                </div>
            <?php elseif (!empty($products)): ?>
                <div class="sr-product-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="na-card">
                            <?php include __DIR__ . '/partials/na-card-badge.php'; ?>

                            <a href="<?= $product['product_url'] ?>" class="na-img-link">
                                <div class="na-img-box">
                                    <img
                                        src="<?= $product['product_image'] ?>"
                                        alt="<?= $product['product_name'] ?>"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            </a>

                            <div class="na-body">
                                <h3 class="na-name">
                                    <a href="<?= $product['product_url'] ?>"><?= $product['product_name'] ?></a>
                                </h3>

                                <div class="na-price">
                                    <?php if ($product['has_sale']): ?>
                                        <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                        <span class="na-price--new">Rs. <?= number_format($product['sale_price']) ?></span>
                                    <?php elseif ($product['regular_price'] > 0): ?>
                                        <span class="na-price--new">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <?php else: ?>
                                        <span class="na-price--na">Price N/A</span>
                                    <?php endif; ?>
                                </div>

                                <?php include __DIR__ . '/partials/na-card-actions.php'; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sr-empty">
                    <h2>No results found</h2>
                    <p>
                        We couldn't find any products matching
                        "<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>".
                        Try different keywords or browse our shop.
                    </p>
                    <a class="sr-empty-btn" href="<?= htmlspecialchars(url('shop'), ENT_QUOTES, 'UTF-8') ?>">Browse Shop</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
