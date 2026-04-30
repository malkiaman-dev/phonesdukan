<?php
require_once __DIR__ . '/../../Controllers/ShopController.php';
require_once dirname(__DIR__, 3) . '/includes/header.php';

$selectedCategoryIds = $_GET['category'] ?? [];
if (!is_array($selectedCategoryIds)) {
    $selectedCategoryIds = array_filter(explode(',', (string) $selectedCategoryIds));
}
$selectedCategoryIds = array_map('strval', $selectedCategoryIds);

$selectedSort = $_GET['sort_by'] ?? 'latest';
$selectedSort = $selectedSort === '' ? 'latest' : $selectedSort;

$sectionTitle = 'All Products';
if (count($selectedCategoryIds) === 1 && !empty($categories)) {
    foreach ($categories as $category) {
        if ((string) $category['category_id'] === $selectedCategoryIds[0]) {
            $sectionTitle = $category['category_name'];
            break;
        }
    }
}

$titleBase   = $sectionTitle === 'All Products' ? 'All' : $sectionTitle;
$titleAccent = $sectionTitle === 'All Products' ? 'Products' : 'Collection';

$productCountOnPage = is_array($products) ? count($products) : 0;
$showingFrom = $total_rows > 0 ? (($paged - 1) * $limit) + 1 : 0;
$showingTo = $total_rows > 0 ? ($showingFrom + $productCountOnPage - 1) : 0;

$sortOptions = [
    'latest'     => 'Latest',
    'price_asc'  => 'Price Low to High',
    'price_desc' => 'Price High to Low',
];

$queryParams = $_GET;
unset($queryParams['paged']);
$buildPageUrl = function (int $page) use ($queryParams): string {
    $params = $queryParams;
    $params['paged'] = $page;
    return '?' . http_build_query($params);
};
?>

<section class="shop-page-shell">
    <div class="shop-mobile-toolbar">
        <button type="button" class="shop-mobile-filter-btn" id="shopFilterToggle">Filter</button>
        <div class="shop-mobile-sort-wrap" id="shopMobileSortWrap">
            <button type="button" class="shop-custom-sort-btn" id="shopCustomSortBtn" aria-haspopup="listbox" aria-expanded="false">
                <span class="shop-custom-sort-label" id="shopCustomSortLabel"><?= htmlspecialchars($sortOptions[$selectedSort] ?? 'Latest', ENT_QUOTES, 'UTF-8') ?></span>
                <svg class="shop-sort-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <ul class="shop-custom-sort-menu" id="shopCustomSortMenu" role="listbox" aria-label="Sort options">
                <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                    <li class="shop-custom-sort-option<?= $selectedSort === $sortValue ? ' is-active' : '' ?>"
                        data-value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>"
                        role="option"
                        aria-selected="<?= $selectedSort === $sortValue ? 'true' : 'false' ?>">
                        <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <select id="shopMobileSort" class="shop-mobile-sort-hidden" aria-hidden="true" tabindex="-1">
                <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                    <option value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSort === $sortValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="shop-page-wrapper">
        <aside class="shop-sidebar" id="shopSidebar">
            <div class="shop-sidebar-head">
                <h3>Refine Results</h3>
                <button type="button" class="shop-drawer-close" id="shopFilterClose" aria-label="Close filters">×</button>
            </div>
            <?php require_once __DIR__ . '/filters.php'; ?>
        </aside>

        <div class="shop-drawer-backdrop" id="shopDrawerBackdrop"></div>

        <section class="product-section">
            <header class="shop-results-head">
                <div class="shop-results-meta">
                    <h1 class="shop-section-title">
                        <?= htmlspecialchars($titleBase, ENT_QUOTES, 'UTF-8') ?>
                        <span><?= htmlspecialchars($titleAccent, ENT_QUOTES, 'UTF-8') ?></span>
                    </h1>
                    <p class="shop-results-count">
                        Showing <?= (int) $showingFrom ?>–<?= (int) $showingTo ?> of <?= (int) $total_rows ?> products
                    </p>
                </div>

                <div class="shop-results-sort" id="shopTopSortWrap">
                    <span class="shop-top-sort-label">Sort By</span>
                    <div class="shop-top-custom-sort">
                        <button type="button" class="shop-top-sort-btn" id="shopTopSortBtn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="shop-top-sort-current" id="shopTopSortCurrent"><?= htmlspecialchars($sortOptions[$selectedSort] ?? 'Latest', ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="shop-top-sort-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <ul class="shop-top-sort-menu" id="shopTopSortMenu" role="listbox" aria-label="Sort options">
                            <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                                <li class="shop-top-sort-option<?= $selectedSort === $sortValue ? ' is-active' : '' ?>"
                                    data-value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>"
                                    role="option"
                                    aria-selected="<?= $selectedSort === $sortValue ? 'true' : 'false' ?>">
                                    <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <select id="shopTopSort" class="shop-top-sort-hidden" aria-hidden="true" tabindex="-1">
                            <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                                <option value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSort === $sortValue ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </header>

            <?php if (!empty($products) && is_array($products) && isset($products[0]) && is_array($products[0])): ?>
                <div class="product-grid-container">
                    <div class="product-grid-wrapper">
                        <?php foreach ($products as $product): ?>
                            <?php
                                $productUrl = "/" . htmlspecialchars($product['category_slug']) . "/" .
                                    htmlspecialchars($product['brand_slug']) . "/" .
                                    htmlspecialchars($product['product_slug']);

                                $productName = htmlspecialchars($product['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8');
                                $productImage = !empty($product['image_url'])
                                    ? htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8')
                                    : '/public/assets/images/Phones_dukan_favicon.png';

                                $regularPrice = (float) ($product['regular_price'] ?? 0);
                                $salePrice = (float) ($product['sale_price'] ?? 0);
                                $hasSalePrice = $salePrice > 0 && $regularPrice > 0 && $salePrice < $regularPrice;
                                $unitPrice = $hasSalePrice ? $salePrice : $regularPrice;

                                $isSoldOut = isset($product['stock_quantity']) && (int) $product['stock_quantity'] <= 0;
                                $discountPct = $hasSalePrice
                                    ? max(1, (int) round((($regularPrice - $salePrice) / $regularPrice) * 100))
                                    : 0;

                            ?>

                            <article class="na-card shop-na-card">
                                <?php if ($isSoldOut): ?>
                                    <span class="na-badge na-badge--sold">Sold Out</span>
                                <?php elseif ($discountPct > 0): ?>
                                    <span class="na-badge"><?= (int) $discountPct ?>% OFF</span>
                                <?php endif; ?>

                                <a href="<?= $productUrl ?>" class="na-img-link">
                                    <div class="na-img-box product-img-wrapper">
                                        <img
                                            src="<?= $productImage ?>"
                                            alt="<?= $productName ?>"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </div>
                                </a>

                                <div class="na-body">
                                    <h3 class="na-name">
                                        <a href="<?= $productUrl ?>"><?= $productName ?></a>
                                    </h3>

                                    <div class="na-price">
                                        <?php if ($hasSalePrice): ?>
                                            <span class="na-price--old">Rs. <?= number_format($regularPrice) ?></span>
                                            <span class="na-price--new">Rs.<?= number_format($salePrice) ?></span>
                                        <?php elseif ($regularPrice > 0): ?>
                                            <span class="na-price--new">Rs.<?= number_format($regularPrice) ?></span>
                                        <?php else: ?>
                                            <span class="na-price--na">Price N/A</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="na-actions">
                                        <?php if (!$isSoldOut): ?>
                                            <button class="na-btn na-btn--cart"
                                                data-product-id="<?= (int) $product['product_id'] ?>"
                                                data-unit-price="<?= (float) $unitPrice ?>">
                                                Add to Cart
                                            </button>
                                            <button class="na-btn na-btn--buy buy-button"
                                                data-product-id="<?= (int) $product['product_id'] ?>"
                                                data-unit-price="<?= (float) $unitPrice ?>">
                                                Buy Now
                                            </button>
                                        <?php else: ?>
                                            <span class="na-btn na-btn--soldout">Sold Out</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="shop-empty-state">
                    <h3>No products found</h3>
                    <p>Try adjusting your filters to explore more products.</p>
                </div>
            <?php endif; ?>

            <?php if ((int) $total_pages > 1): ?>
                <nav class="pagination" aria-label="Shop pagination">
                    <?php if ($paged > 1): ?>
                        <a href="<?= htmlspecialchars($buildPageUrl($paged - 1), ENT_QUOTES, 'UTF-8') ?>" class="pagination-nav prev">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $paged - 2);
                    $end = min($total_pages, $paged + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a
                            href="<?= htmlspecialchars($buildPageUrl($i), ENT_QUOTES, 'UTF-8') ?>"
                            class="pagination-link <?= ((int) $i === (int) $paged) ? 'active' : '' ?>"
                            aria-current="<?= ((int) $i === (int) $paged) ? 'page' : 'false' ?>"
                        >
                            <?= (int) $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($paged < $total_pages): ?>
                        <a href="<?= htmlspecialchars($buildPageUrl($paged + 1), ENT_QUOTES, 'UTF-8') ?>" class="pagination-nav next">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
