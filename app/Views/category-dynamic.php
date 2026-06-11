<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

if (!defined('CATEGORY_LISTING_PAGE')) {
    define('CATEGORY_LISTING_PAGE', true);
}

$categoryName = (string) ($category['category_name'] ?? 'Category');
$categorySlug = (string) ($category['slug'] ?? '');

$pageTitle = $categoryName . ' Prices in Pakistan | Phones Dukan';
$metaDescription = 'Shop ' . $categoryName . ' at Phones Dukan with updated prices in Pakistan. Browse the latest products and deals.';
$metaRobots = 'index, follow';
$metaKeywords = $categoryName . ', ' . $categoryName . ' price in pakistan, Phones Dukan';

$breadcrumbs = SeoHelper::categoryBreadcrumbs($categorySlug, $categoryName);

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="cl-page">
    <section class="cl-hero">
        <div class="cl-container cl-hero-inner">
            <p class="cl-hero-eyebrow">PHONES DUKAN COLLECTION</p>
            <h1 class="cl-hero-title"><span><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span> in Pakistan</h1>
            <p class="cl-hero-sub">Browse the latest <?= htmlspecialchars(strtolower($categoryName), ENT_QUOTES, 'UTF-8') ?> with updated prices, fast delivery, and trusted service from Phones Dukan.</p>
        </div>
    </section>

    <section class="cl-products-section">
        <div class="cl-container">
            <?php if (!empty($products)): ?>
                <div class="cl-product-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="na-card cl-na-card">
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
                <div class="cl-empty">
                    <h2>No products yet</h2>
                    <p>We are adding products to <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?> soon. Please check back later or browse other categories.</p>
                    <a class="cl-empty-btn" href="<?= htmlspecialchars(url('shop'), ENT_QUOTES, 'UTF-8') ?>">Browse All Products</a>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="cl-pagination-wrap">
                    <div class="pagination cl-pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= htmlspecialchars(url($categorySlug) . '?paged=' . $i, ENT_QUOTES, 'UTF-8') ?>"
                               class="<?= ($i === $paged) ? 'active' : '' ?>"
                               <?= ($i === $paged) ? "aria-current='page'" : '' ?>>
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
