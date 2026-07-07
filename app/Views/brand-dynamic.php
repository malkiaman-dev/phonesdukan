<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

if (!defined('BRAND_LISTING_PAGE')) {
    define('BRAND_LISTING_PAGE', true);
}

$brandName = (string) ($brand['brand_name'] ?? 'Brand');
$brandSlug = (string) ($brand['slug'] ?? '');
$listingCategoryName = (string) ($category['category_name'] ?? 'Category');
$listingCategorySlug = (string) ($category['slug'] ?? '');
$listingPath = (string) ($listingPath ?? ($listingCategorySlug . '/' . $brandSlug));

$pageTitle = $brandName . ' ' . $listingCategoryName . ' Price in Pakistan | Phones Dukan';
$metaDescription = 'Shop ' . $brandName . ' ' . $listingCategoryName . ' at Phones Dukan with updated prices in Pakistan. Browse the latest models and deals.';
$metaRobots = 'index, follow';
$metaKeywords = $brandName . ' ' . $listingCategoryName . ', ' . $brandName . ' price in pakistan, Phones Dukan';

$breadcrumbs = SeoHelper::categoryBrandBreadcrumbs($listingCategorySlug, $listingCategoryName, $brandSlug, $brandName);

require_once dirname(__DIR__, 2) . '/includes/header.php';

$categoryName = $listingCategoryName;
$categorySlug = $listingCategorySlug;
?>

<div class="bl-page">
    <section class="bl-hero">
        <div class="bl-container bl-hero-inner">
            <p class="bl-hero-eyebrow"><?= htmlspecialchars(strtoupper($categoryName), ENT_QUOTES, 'UTF-8') ?> · PAKISTAN</p>
            <h1 class="bl-hero-title">Latest <span><?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></span> <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="bl-hero-sub">Find the newest <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(strtolower($categoryName), ENT_QUOTES, 'UTF-8') ?> with competitive prices, verified specs, and fast delivery across Pakistan.</p>
        </div>
    </section>

    <?php
    $activeBrandSlug = $brandSlug;
    include __DIR__ . '/partials/category-brand-tabs.php';
    ?>

    <section class="bl-products-section">
        <div class="bl-container">
            <?php if (!empty($products)): ?>
                <div class="bl-product-grid">
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
                <div class="bl-empty">
                    <h2>No products yet</h2>
                    <p>We are adding <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(strtolower($categoryName), ENT_QUOTES, 'UTF-8') ?> soon. Please check back later or browse other brands.</p>
                    <a class="bl-empty-btn" href="<?= htmlspecialchars(url($categorySlug), ENT_QUOTES, 'UTF-8') ?>">Browse All <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="bl-pagination-wrap">
                    <div class="pagination bl-pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= htmlspecialchars(url($listingPath) . '?paged=' . $i, ENT_QUOTES, 'UTF-8') ?>"
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
