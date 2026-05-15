<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

$pageTitle       = "Mobile Prices in Pakistan " . date('F Y') . " – Updated Rates & Top Brands";
$metaDescription = "Explore mobile prices in Pakistan " . date('F Y') . ". Compare latest models from Samsung, Infinix, Oppo, Vivo, Xiaomi & more. Updated daily!";
$metaRobots      = "index, follow";
$metaKeywords    = "mobile prices Pakistan, buy mobile online Pakistan, smartphone prices Pakistan";

$breadcrumbs = SeoHelper::categoryBreadcrumbs('mobiles', 'Mobiles');

require_once __DIR__ . '/../../database/db.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';

$database = new Database();
$conn     = $database->getConnection();
if (!$conn) { die("Database connection error."); }

$brandList = [
    'samsung' => 'Samsung',
    'infinix' => 'Infinix',
    'oppo'    => 'Oppo',
    'vivo'    => 'Vivo',
    'xiaomi'  => 'Xiaomi',
    'tecno'   => 'Tecno',
    'realme'  => 'Realme',
];

$allProducts = [];
foreach ($brandList as $brandSlug => $brandName) {
    $query = "SELECT p.product_id, p.product_slug, p.product_name,
                     p.regular_price, p.sale_price, p.stock_quantity,
                     pi.image_url, b.slug AS brand_slug, c.slug AS category_slug
              FROM products p
              JOIN brands b ON p.brand_id = b.brand_id
              JOIN categories c ON p.category_id = c.category_id
              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
              WHERE p.category_id = 2 AND b.slug = :brand AND p.product_status != '0'
              ORDER BY p.created_at DESC
              LIMIT 6";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':brand', $brandSlug);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $p) {
        $regularPrice = (float)($p['regular_price'] ?? 0);
        $salePrice    = (float)($p['sale_price'] ?? 0);
        $hasSale      = $salePrice > 0 && $regularPrice > 0 && $salePrice < $regularPrice;
        $isSoldOut    = (int)($p['stock_quantity'] ?? 0) <= 0;
        $discountPct  = $hasSale ? max(1, (int)round((($regularPrice - $salePrice) / $regularPrice) * 100)) : 0;
        $unitPrice    = $hasSale ? $salePrice : $regularPrice;
        $productUrl   = '/' . htmlspecialchars($p['category_slug']) . '/' .
                        htmlspecialchars($p['brand_slug']) . '/' .
                        htmlspecialchars($p['product_slug']);
        $productImage = !empty($p['image_url'])
            ? htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8')
            : '/public/assets/images/Phones_dukan_favicon.png';
        $productName  = htmlspecialchars($p['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8');

        $allProducts[] = [
            'product_id'    => (int)$p['product_id'],
            'product_url'   => $productUrl,
            'product_image' => $productImage,
            'product_name'  => $productName,
            'regular_price' => $regularPrice,
            'sale_price'    => $salePrice,
            'has_sale'      => $hasSale,
            'is_sold_out'   => $isSoldOut,
            'discount_pct'  => $discountPct,
            'unit_price'    => $unitPrice,
            'brand_slug'    => $brandSlug,
        ];
    }
}
$totalProducts = count($allProducts);
$totalBrands   = count($brandList);
?>

<!-- HERO -->
<section class="mob-hero">
    <div class="mob-hero-inner">
        <p class="mob-hero-eyebrow">Pakistan's Trusted Mobile Store</p>
        <h1 class="mob-hero-title"><span>Latest Smartphones </span> in Pakistan</h1>
        <p class="mob-hero-sub">Explore the latest smartphones from top mobile brands with PTA-approved devices, competitive prices, verified specifications, and fast delivery across Pakistan.</p>
    </div>
</section>

<!-- BRAND FILTER TABS -->
<div class="mob-brand-bar" id="mobBrandBar">
    <div class="mob-brand-bar-inner">
        <button class="mob-brand-tab is-active" data-brand="all" aria-pressed="true">All</button>
        <?php foreach ($brandList as $slug => $name): ?>
            <button class="mob-brand-tab" data-brand="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<!-- PRODUCT GRID -->
<div class="mob-products-section">
    <div class="mob-products-inner">
        <?php if (!empty($allProducts)): ?>
            <div class="mob-product-grid" id="mobProductGrid">
                <?php foreach ($allProducts as $p): ?>
                    <article class="na-card mob-na-card" data-brand="<?= htmlspecialchars($p['brand_slug'], ENT_QUOTES, 'UTF-8') ?>">

                        <?php if ($p['is_sold_out']): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($p['discount_pct'] > 0): ?>
                            <span class="na-badge"><?= $p['discount_pct'] ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $p['product_url'] ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img
                                    src="<?= $p['product_image'] ?>"
                                    alt="<?= $p['product_name'] ?>"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $p['product_url'] ?>"><?= $p['product_name'] ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($p['has_sale']): ?>
                                    <span class="na-price--old">Rs. <?= number_format($p['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($p['sale_price']) ?></span>
                                <?php elseif ($p['regular_price'] > 0): ?>
                                    <span class="na-price--new">Rs.<?= number_format($p['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$p['is_sold_out']): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= $p['product_id'] ?>"
                                        data-unit-price="<?= (float)$p['unit_price'] ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= $p['product_id'] ?>"
                                        data-unit-price="<?= (float)$p['unit_price'] ?>">
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

        <?php else: ?>
            <p class="mob-empty">No products found. Please check back soon.</p>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/ad/feed2.php'; ?>

<!-- SHOP BY BRAND CAROUSEL -->
<section class="mob-brands-section">
    <div class="mob-brands-inner">
        <h2 class="mob-brands-title">Shop by <span>Brand</span></h2>
        <div class="mob-brands-carousel-wrap" id="mobBrandsCarousel">
            <div class="mob-brands-track" id="mobBrandsTrack">
                <?php foreach ($brandList as $slug => $name):
                    $logoSrc  = url('public/assets/images/' . $slug . '_logo.webp');
                    $safeSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
                    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                ?>
                    <a href="<?= url('mobiles/' . $safeSlug) ?>"
                       class="mob-brand-card"
                       draggable="false">
                        <img
                            src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= $safeName ?>"
                            class="mob-brand-logo"
                            loading="lazy"
                            decoding="async"
                            draggable="false"
                            onerror="this.style.display='none'"
                        >
                        <span class="mob-brand-card-text">View all mobiles</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mob-brands-dots" id="mobBrandsDots" role="tablist" aria-label="Brand navigation"></div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="mob-why">
    <div class="mob-why-inner">
        <h2 class="mob-why-title">Why Choose <span>Phones Dukan</span></h2>
        <div class="mob-why-grid">
            <div class="mob-why-card">
                <div class="mob-why-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h4>Daily Updates</h4>
                <p>Prices updated daily from verified dealers across Pakistan.</p>
            </div>
            <div class="mob-why-card">
                <div class="mob-why-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <h4>Top Brands</h4>
                <p>Samsung, Infinix, Oppo, Vivo, Xiaomi, Tecno, Realme and more.</p>
            </div>
            <div class="mob-why-card">
                <div class="mob-why-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h4>Specs &amp; Comparisons</h4>
                <p>Detailed specifications and reviews to help you decide.</p>
            </div>
            <div class="mob-why-card">
                <div class="mob-why-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h4>Verified Deals</h4>
                <p>Exclusive discounts and authenticated offers on every order.</p>
            </div>
        </div>
    </div>
</section>

<!-- SEO CONTENT -->
<section class="mob-seo">
    <div class="mob-seo-inner">

        <h2>Understanding Mobile Prices in <span>Pakistan</span></h2>
        <p>Mobile phone prices in Pakistan are constantly changing due to taxes, exchange rates, and market demand. At Phones Dukan, we provide the most competitive mobile phone prices with full clarity and authentic products. For deeper insight, see this <a href="https://profit.pakistantoday.com.pk/2024/07/26/mobile-phones-set-to-become-more-expensive-in-pakistan/" target="_blank" rel="noopener">research article on mobile rates in Pakistan</a>.</p>
        <p>One of the main drivers of pricing uncertainty is the volatile exchange rate. Mobile phones are imported from China, South Korea, and the United States, and as the Pakistani Rupee fluctuates, import costs directly impact retail prices. Government-imposed taxes and duties further contribute to this volatility.</p>

        <h2>Finding Affordable &amp; Reliable <span>Options</span></h2>
        <p>At Phones Dukan, we understand the challenges of navigating fluctuating mobile prices. Our mission is to offer the latest smartphones at competitive prices — ensuring you get the best value for your money.</p>
        <ul>
            <li><strong>Transparent Pricing:</strong> Clear prices without hidden fees, updated regularly to reflect the latest market trends.</li>
            <li><strong>Wide Range of Options:</strong> From budget-friendly phones to premium devices — Samsung, Xiaomi, Infinix, and more. Explore our <a href="/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30,000</a> collection.</li>
            <li><strong>Detailed Product Information:</strong> Specifications, reviews, and comparisons to help you make an informed decision.</li>
            <li><strong>Easy Payment Options:</strong> Cash on delivery and flexible installment plans for your convenience.</li>
        </ul>

        <h2>Mobile Pricing Trends Shaping <span>Pakistan's Market</span></h2>
        <p>Budget smartphones from Infinix, Realme, and Tecno have gained massive market share due to affordable yet feature-packed specs in the PKR 20,000–40,000 range. High-end devices from Apple and Samsung continue to cater to premium buyers. 5G technology is gradually entering Pakistan's market, while pre-owned and refurbished devices are rising in popularity as consumers seek more affordable alternatives.</p>

    </div>
</section>

<script type="application/ld+json">
<?= SeoHelper::faqSchema([
    [
        'question' => 'Are all mobile phones at Phones Dukan PTA approved?',
        'answer'   => 'Yes. Every smartphone sold at Phones Dukan is 100% PTA (Pakistan Telecommunication Authority) approved. This ensures your device can be legally registered and used on all Pakistani networks without any network-lock issues or extra taxes.'
    ],
    [
        'question' => 'Do you offer cash on delivery across Pakistan?',
        'answer'   => 'Yes, we offer cash on delivery to all major cities including Islamabad, Lahore, Karachi, Rawalpindi, Faisalabad, Multan, Peshawar, and Quetta. Delivery typically takes 2–4 working days.'
    ],
    [
        'question' => 'What warranty comes with mobile phones purchased at Phones Dukan?',
        'answer'   => 'All smartphones at Phones Dukan come with an official 1-year brand warranty covering manufacturing defects. Some models may come with up to 2-year warranties. Warranty claims are handled directly through the official brand service centres in Pakistan.'
    ],
    [
        'question' => 'What is the return policy for mobiles?',
        'answer'   => 'We offer a 7-day return and exchange policy. If you receive a defective or incorrect product, contact us within 7 days of delivery with the original packaging and receipt. Products must be unused and in original condition for a full refund or replacement.'
    ],
    [
        'question' => 'Which mobile brands are available at Phones Dukan?',
        'answer'   => 'We stock a wide range of brands including Samsung, Apple iPhone, Xiaomi, Redmi, Vivo, Oppo, Infinix, Tecno, Realme, OnePlus, Google Pixel, Honor, and Nothing. All models are PTA approved and available at competitive prices.'
    ],
    [
        'question' => 'What is the best budget smartphone under PKR 50,000 in Pakistan?',
        'answer'   => 'Top picks under PKR 50,000 include the Infinix Hot series, Samsung Galaxy A series, Realme Narzo, Xiaomi Redmi Note, and Tecno Spark models. These offer a solid balance of performance, camera quality, and battery life for everyday use.'
    ],
]) ?>
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
