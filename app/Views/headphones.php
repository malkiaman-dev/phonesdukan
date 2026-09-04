<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/app/Models/CatalogModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProductModel.php';

$pageTitle = 'Best Headphones Prices in Pakistan | Phones Dukan';
$metaDescription = 'Shop the best headphones at Phones Dukan with updated prices in Pakistan. Compare wireless, gaming, and over-ear headphones with fast delivery.';
$metaRobots = 'index, follow';
$metaKeywords = 'Headphones, headphones price in pakistan, wireless headphones, gaming headphones, best headphones';

$breadcrumbs = SeoHelper::categoryBreadcrumbs('headphones', 'Headphones');

$catalogModel = new CatalogModel();
$productModel = new ProductModel();

$category = $catalogModel->getActiveParentCategoryBySlug('headphones');
if (!$category) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

$categoryId = (int) $category['category_id'];
$limit = 16;
$paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
$paged = $paged > 0 ? $paged : 1;
$offset = ($paged - 1) * $limit;

$totalRows = $productModel->countListingProductsForCategory($categoryId);
$rawProducts = $productModel->getListingProductsForCategory($categoryId, $limit, $offset);
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 0;
if (function_exists('seoEnforceListingPagination')) {
    seoEnforceListingPagination($paged, $totalPages);
}

$products = [];
foreach ($rawProducts as $row) {
    $products[] = prepareProductCardFromRow($row);
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<section class="hp-hero">
    <div class="hp-container hp-hero-inner">
        <p class="hp-hero-eyebrow">PHONES DUKAN HEADPHONES COLLECTION</p>
        <h1 class="hp-hero-title"><span>Latest Headphones</span> Prices in Pakistan</h1>
        <p class="hp-hero-sub">Explore the latest headphones in Pakistan with deep bass, wireless freedom, gaming-ready sound, long battery life, and fast delivery from trusted audio brands.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="hp-products-section">
    <div class="hp-container">
        <?php if (!empty($products)): ?>
            <div class="hp-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card hp-na-card">
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
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif ($product['regular_price'] > 0): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
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
            <p class="hp-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="hp-pagination-wrap">
            <div class="pagination hp-pagination">
                <?php for ($i = 1; $i <= max(1, $totalPages); $i++): ?>
                    <a href="?paged=<?= $i ?>"
                       class="<?= ($i === $paged) ? 'active' : '' ?>"
                       <?= ($i === $paged) ? "aria-current='page'" : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

<section class="hp-content-section">
    <div class="hp-container">
        <div class="hp-content">
            <h2>Headphones Pricing in <span>Pakistan</span></h2>
            <p>Headphone prices in Pakistan depend on driver quality, Bluetooth version, ANC support, mic clarity, and battery backup. From budget daily-use models to premium wireless headsets, buyers can pick options based on music, gaming, or travel needs.</p>

            <h2>Affordable vs <span>Premium Headphones</span></h2>
            <p>Affordable headphones focus on essential playback, basic calling, and lightweight comfort. Premium models deliver stronger bass, better noise isolation, longer playtime, and improved build quality for heavy daily use.</p>

            <h2>Headphones <span>Price Ranges</span></h2>
            <ul>
                <li><strong>Budget Range (PKR 2,500 – 6,000):</strong> Entry-level wireless and wired headphones for music and calls.</li>
                <li><strong>Mid Range (PKR 6,000 – 15,000):</strong> Better sound tuning, longer battery, and improved mic performance.</li>
                <li><strong>Premium Range (PKR 15,000+):</strong> Advanced ANC, richer audio detail, and stronger durability.</li>
            </ul>

            <h2>Popular Headphone <span>Features</span></h2>
            <p>Top features include Bluetooth 5.x connectivity, ENC/ANC calling, low-latency gaming mode, foldable designs, TF/AUX support, and fast charging cases. Pair your headphones with the latest <a href="https://www.phonesdukan.com/mobiles/">mobile phones</a> for the best wireless experience.</p>

            <h2>Headphones <span>Buying Guide</span></h2>
            <p>Check battery life, comfort padding, latency for gaming, warranty coverage, and charging port type before buying. For frequent calls, prioritize dual-mic ENC models with a secure fit and stable Bluetooth range.</p>
        </div>

        <div class="hp-table-section">
            <h2>Top Headphones in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="hp-table-wrap">
                <table class="hp-table">
                    <thead>
                        <tr>
                            <th>Headphones</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Wireless ANC Headphones</td>
                            <td class="text-right"><span>12,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>RGB Gaming Headset</td>
                            <td class="text-right"><span>5,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Bluetooth 5.4 Over-Ear</td>
                            <td class="text-right"><span>4,299 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Foldable Travel Headphones</td>
                            <td class="text-right"><span>3,597 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Wired Studio Headphones</td>
                            <td class="text-right"><span>2,850 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="hp-faq-section">
    <div class="hp-container">
        <div class="hp-faq-wrap">
            <h2>FAQs About <span>Headphones Price in Pakistan</span></h2>
            <p class="hp-faq-intro">These common questions help buyers choose the right headphones based on price, performance, and brand reliability.</p>

            <div class="hp-faq-list">
                <div class="hp-faq-item">
                    <button class="hp-faq-question" type="button" aria-expanded="false">
                        <span>What is the starting price of headphones in Pakistan?</span>
                        <span class="hp-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="hp-faq-answer">
                        <div class="hp-faq-answer-inner">
                            <p>Headphones in Pakistan start from around <span>Rs. 2,500</span> for basic models and can go much higher for premium wireless ANC headsets.</p>
                        </div>
                    </div>
                </div>

                <div class="hp-faq-item">
                    <button class="hp-faq-question" type="button" aria-expanded="false">
                        <span>Which headphones are best for gaming?</span>
                        <span class="hp-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="hp-faq-answer">
                        <div class="hp-faq-answer-inner">
                            <p>For gaming, look for headphones with <span>low latency</span>, comfortable ear cushions, clear mic quality, and optional RGB styling for setup aesthetics.</p>
                        </div>
                    </div>
                </div>

                <div class="hp-faq-item">
                    <button class="hp-faq-question" type="button" aria-expanded="false">
                        <span>Do headphones come with warranty in Pakistan?</span>
                        <span class="hp-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="hp-faq-answer">
                        <div class="hp-faq-answer-inner">
                            <p>Yes, most headphones include official or seller-backed warranty, usually around <span>6 to 12 months</span> depending on brand and retailer.</p>
                        </div>
                    </div>
                </div>

                <div class="hp-faq-item">
                    <button class="hp-faq-question" type="button" aria-expanded="false">
                        <span>Which brand is best for headphones?</span>
                        <span class="hp-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="hp-faq-answer">
                        <div class="hp-faq-answer-inner">
                            <p>Popular choices include <span>JBL, Aula, Ronin, and local gaming audio brands</span>. The best pick depends on your budget, use case, and comfort preference.</p>
                        </div>
                    </div>
                </div>

                <div class="hp-faq-item">
                    <button class="hp-faq-question" type="button" aria-expanded="false">
                        <span>How much do good headphones cost?</span>
                        <span class="hp-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="hp-faq-answer">
                        <div class="hp-faq-answer-inner">
                            <p>Good quality headphones for daily music and calls are commonly found between <span>Rs. 4,000</span> and <span>Rs. 15,000</span> in Pakistan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
