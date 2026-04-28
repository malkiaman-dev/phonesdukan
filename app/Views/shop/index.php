<?php
 require_once __DIR__ . '/../../Controllers/ShopController.php';
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>

<!-- Wrapper for sidebar + content -->
<div class="shop-page-wrapper">

    <!-- Sidebar Filter -->
    <aside class="shop-sidebar">
        <?php require_once __DIR__ . '/filters.php'; ?>
    </aside>

    <!-- Product Grid -->
    <div class="product-section">
    <div class="product-grid-container">
        <div class="product-grid-wrapper">
        <?php if (!empty($products) && is_array($products) && isset($products[0]) && is_array($products[0])): ?>
    <?php foreach ($products as $product): ?>
        <?php
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
    <p>No products found.</p>
<?php endif; ?>

            </div>
</div> <!-- End of .product-section -->
<!-- Pagination -->
<div class="pagination">
    <!-- Previous Button -->
    <?php if ($paged > 1): ?>
        <a href="?paged=<?= $paged - 1 ?>" class="prev">Previous</a>
    <?php endif; ?>

    <!-- Page Numbers -->
    <?php
    // Calculate the range of pages to show
    $start = max(1, $paged - 2);  // Start at 1 or 2 pages before the current one
    $end = min($total_pages, $paged + 2); // End at 2 pages after the current one
    
    for ($i = $start; $i <= $end; $i++): ?>
        <a href="?paged=<?= $i ?>" class="<?= ($i == $paged) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <!-- Next Button -->
    <?php if ($paged < $total_pages): ?>
        <a href="?paged=<?= $paged + 1 ?>" class="next">Next</a>
    <?php endif; ?>
</div>

        </div>
            </div>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
