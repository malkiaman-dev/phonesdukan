<?php
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

$pageTitle = "Samsung Mobile Price in Pakistan - " . date('F Y');
$metaDescription = "Check out the latest Samsung mobile price in Pakistan for " . date('F Y') . ". From Galaxy A01 at Rs. 15,999 to Galaxy Z Fold 5 at Rs. 656,999.";
$metaKeywords = "Samsung mobiles Pakistan, Samsung lowest prices, lowest prices for Samsung mobiles, Samsung mobiles prices in Pakistan, Samsung mobile specifications, Samsung mobile features";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'samsung', 'Samsung');

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die('Database connection error.');

$brand = 'samsung';
$limit = 8;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

$query = "SELECT p.product_id, p.product_slug, p.product_name, p.regular_price, p.sale_price, p.stock_quantity,
                 pi.image_url, b.slug AS brand_slug, c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'mobiles' AND b.slug = :brand AND p.product_status != '0'
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':brand', $brand, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "SELECT COUNT(*) as total FROM products p
              JOIN brands b ON p.brand_id = b.brand_id
              JOIN categories c ON p.category_id = c.category_id
              WHERE c.slug = 'mobiles' AND b.slug = :brand AND p.product_status != '0'";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bindValue(':brand', $brand, PDO::PARAM_STR);
$stmt_count->execute();
$total_rows = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);
$conn = null;
?>

<!-- HERO -->
<div class="mob-hero">
    <div class="mob-hero-inner">
        <p class="mob-hero-eyebrow">Mobile Phones · Pakistan</p>
        <h1 class="mob-hero-title"><span>Samsung Mobiles</span> Price in Pakistan</h1>
        <p class="mob-hero-sub">Find the newest Samsung smartphones with competitive prices, verified specs, and fast delivery across Pakistan. Prices starting from PKR 22,399 · <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- BRAND TABS -->
<div class="mob-brand-bar">
    <div class="mob-brand-bar-inner">
        <a href="/mobiles" class="mob-brand-tab">All Mobiles</a>
        <a href="/mobiles/infinix" class="mob-brand-tab">Infinix</a>
        <a href="/mobiles/oppo" class="mob-brand-tab">Oppo</a>
        <a href="/mobiles/realme" class="mob-brand-tab">Realme</a>
        <a href="/mobiles/samsung" class="mob-brand-tab is-active">Samsung</a>
        <a href="/mobiles/tecno" class="mob-brand-tab">Tecno</a>
        <a href="/mobiles/vivo" class="mob-brand-tab">Vivo</a>
        <a href="/mobiles/xiaomi" class="mob-brand-tab">Xiaomi</a>
    </div>
</div>

<?php include_once(__DIR__ . '/../ad/feed1.php'); ?>

<!-- PRODUCT GRID -->
<div class="mob-products-section">
    <div class="mob-products-inner">
        <?php if (!empty($products)): ?>
        <div class="mob-product-grid">
            <?php foreach ($products as &$product):
                $product['regular_price']  = floatval($product['regular_price']);
                $product['sale_price']     = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                $product['stock_quantity'] = intval($product['stock_quantity']);
                $product['is_sold_out']    = ($product['stock_quantity'] <= 0);
                $product['is_on_sale']     = (!$product['is_sold_out'] && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);
                $product['unit_price']     = ($product['sale_price'] !== null && $product['sale_price'] > 0) ? $product['sale_price'] : $product['regular_price'];
                $product['discount_pct']   = ($product['is_on_sale'] && $product['regular_price'] > 0) ? round((($product['regular_price'] - $product['sale_price']) / $product['regular_price']) * 100) : 0;
                $product_url   = '/' . htmlspecialchars($product['category_slug']) . '/' . htmlspecialchars($product['brand_slug']) . '/' . htmlspecialchars($product['product_slug']);
                $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg';
            ?>
            <article class="na-card mob-na-card">
                <?php if ($product['is_sold_out']): ?>
                    <span class="na-badge na-badge--sold">Sold Out</span>
                <?php elseif ($product['is_on_sale']): ?>
                    <span class="na-badge"><?= $product['discount_pct'] ?>% OFF</span>
                <?php endif; ?>
                <a href="<?= $product_url ?>" class="na-img-link">
                    <div class="na-img-box">
                        <img src="<?= $product_image ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" loading="lazy" decoding="async">
                    </div>
                </a>
                <div class="na-body">
                    <h3 class="na-name"><a href="<?= $product_url ?>"><?= htmlspecialchars($product['product_name']) ?></a></h3>
                    <div class="na-price">
                        <?php if ($product['is_on_sale']): ?>
                            <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                            <span class="na-price--new">Rs. <?= number_format($product['sale_price']) ?></span>
                        <?php elseif (!$product['is_sold_out']): ?>
                            <span class="na-price--new">Rs. <?= number_format($product['regular_price']) ?></span>
                        <?php else: ?>
                            <span class="na-price--na">Price on request</span>
                        <?php endif; ?>
                    </div>
                    <div class="na-actions">
                        <?php if (!$product['is_sold_out']): ?>
                            <button class="na-btn na-btn--cart" data-product-id="<?= htmlspecialchars($product['product_id']) ?>" data-unit-price="<?= $product['unit_price'] ?>">Add to Cart</button>
                            <button class="na-btn na-btn--buy buy-button" data-product-id="<?= htmlspecialchars($product['product_id']) ?>" data-unit-price="<?= $product['unit_price'] ?>">Buy Now</button>
                        <?php else: ?>
                            <span class="na-btn--soldout">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="mob-empty">No products found for this brand. Please check back soon.</p>
        <?php endif; ?>

        <?php include_once(__DIR__ . '/../ad/feed2.php'); ?>

        <?php if ($total_pages > 1): ?>
        <div class="mb-pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?paged=<?= $i ?>" class="mb-page-link <?= ($i == $paged) ? 'is-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SEO CONTENT -->
<div class="mb-content-section">
<div class="mb-content-inner">

<h2>Why Samsung Dominates <span>the Pakistani Market</span></h2>
<p>Samsung success in Pakistan is not just due to the brand itself; rather, it earns more at various pricing points. So, what sets Samsung different from the competition? Here's why Samsung stands out:</p>
<ol>
    <li><strong>Multiple Options:</strong> Commenting on customer requirements, Samsung puts forward decent-range models, and also, premium ones. For web browsing and phone calls, there is a basic smart phone model, for gaming activities and photography there is a powerful device.</li>
    <li><strong>Innovation at Its Core:</strong> Due to things like new camera systems, Super AMOLED displays, and 5G connection Samsung smartphones have become a favorite among users. Samsung is a global hi tech firm that is consistently ranked alongside the best in the world.</li>
    <li><strong>After-Sales Support:</strong> Customers may feel comfortable knowing that Samsung offers a wide range of services in Pakistan. Samsung provides outstanding after-sales support, including repair services and genuine accessories.</li>
</ol>

<h2>Current Market Trends and <span>Pricing Insights</span></h2>
<p>With respect to the economic conditions of Pakistan, the cost of smartphones can vary a lot. However, Phones Dukan uploads the prices as they change so that you can be informed of the accurate and latest prices. For economical and expensive models of devices, you could depend on the existing price trends our platform provides.</p>

<h2>Comparing Samsung Prices with <span>Competitors</span></h2>
<p>When it comes to design and build quality, Xiaomi, Vivo, Oppo, and Samsung all perform well, but Samsung clearly stands out. However, not everyone has a big budget. For customers looking for affordable options, consider:</p>
<ul>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a></li>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a></li>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a></li>
</ul>

<h2>Samsung Mobile Prices <span>in Pakistan</span></h2>
<p>Choosing the right Samsung phone depends on the features you want:</p>
<ul>
    <li><strong>Budget Range:</strong> PKR 20,000 – 50,000</li>
    <li><strong>Mid-Range:</strong> PKR 50,000 – 100,000</li>
    <li><strong>Flagship Range:</strong> PKR 100,000+</li>
</ul>
<p>Such flexibility ensures that Pakistani customers can find a Samsung mobile tailored to their needs without compromising on quality.</p>

<h2>How to Choose the Right <span>Samsung Mobile</span></h2>
<p>Selecting the perfect Samsung smartphone depends on your specific requirements:</p>
<ul>
    <li><strong>For Photography Lovers:</strong> Samsung's smartphones including the <a href="https://www.phonesdukan.com/mobiles/samsung/samsung-galaxy-a55-5g/">Galaxy A55 5G</a> and <a href="https://www.phonesdukan.com/mobiles/samsung/samsung-galaxy-s24-ultra/">S24 Ultra</a> come with fantastic video capabilities alongside an impressive camera performance.</li>
    <li><strong>For Gamers:</strong> Owing to the integration of its Snapdragon CPU and a better performing GPU, the Samsung Galaxy S24 FE 5G offers a smooth gaming experience.</li>
    <li><strong>For Everyday Use:</strong> Availability of exceptional performance coupled with affordability, both the Galaxy A15 and A16 are excellent smartphones.</li>
</ul>

<h2>Tips to Save on Your <span>Samsung Mobile Purchase</span></h2>
<ul>
    <li><strong>Compare Prices:</strong> Use platforms like <a href="http://Phonesdukan.com">Phones Dukan</a> or similar e-commerce websites to find the best deals.</li>
    <li><strong>Seasonal Discounts:</strong> Look out for discount occasions such as Eid, Black Friday, and more.</li>
    <li><strong>Trade-In Programs:</strong> Regarding the purchase of a new phone, Samsung <a href="https://www.samsung.com/pk/trade-in/">Trade-In Program</a> comes in quite handy. All you have to do is to Trade-In an old phone and get a discount when buying a new one.</li>
</ul>

<p>Samsung mobile devices have been able to acquire a considerable market in Pakistan especially because of the good services offered to the users. If you're a student looking for something affordable but decent or a business person looking for a premium mobile, Samsung has the solution for you as it serves all.</p>
<p>For more insights on affordable smartphones, check out <a href="https://www.phonesdukan.com/mobiles/">Mobile Price in Pakistan</a>.</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">FAQs About <span>Samsung Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Find answers to the most common questions about Samsung mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung mobiles in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Samsung mobile prices vary depending on the model and specifications. Budget models start at <span>PKR 20,000</span>, while flagship models, such as the Samsung Galaxy S series, can cost up to <span>PKR 400,000</span> or more. Check our website for the latest prices.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung J7 Prime in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The Samsung Galaxy J7 Prime is a great option for budget-conscious buyers. As of 2025, pre-owned or refurbished units range between <span>PKR 18,000</span> to <span>PKR 22,000</span> since new stock is no longer available.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung C7 in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Samsung Galaxy C7 is a stylish and high-performing device. Currently, its resale value in the pre-owned market ranges from <span>PKR 15,000</span> to <span>PKR 18,000</span> in Pakistan.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung A10 in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Samsung Galaxy A10 is an affordable smartphone for daily use. In Pakistan, pre-owned models start at <span>PKR 18,999</span>.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung A30 in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The Samsung Galaxy A30 remains a popular mid-range option. Second-hand models are available for approximately <span>PKR 24,000</span> to <span>PKR 28,000</span> in Pakistan.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung A10s in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The Samsung Galaxy A10s is available in Pakistan for <span>PKR 19,999</span> to <span>PKR 22,999</span> in the second-hand market.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Samsung A20 in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The Samsung Galaxy A20 is an affordable and reliable smartphone. Refurbished or pre-owned units cost between <span>PKR 22,000</span> and <span>PKR 26,000</span>.</p></div>
</div>

<p class="mb-faq-note">Prices and availability are subject to change. Please check official retailers or our website for the most up-to-date information.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best Samsung mobile? Explore the latest Samsung smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and get the perfect mobile at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the price of Samsung mobiles in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Depending on the model and its specifications, Samsung mobile prices in Pakistan are different. The starting price for a budget Samsung mobile phone is 20,000 PKR and it can go as high as 400,000 PKR or more for flagship variants."}},{"@type":"Question","name":"What is the price of Samsung J7 Prime in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"For users with a limited budget, the Samsung Galaxy J7 Prime is a phone worth considering. Currently, the estimated price of pre-owned or refurbished units in Pakistan is between PKR 18,000 to PKR 22,000."}},{"@type":"Question","name":"What is the price of Samsung A30 in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"For a reasonable mid-range phone, the Samsung Galaxy A30 is still a top choice. Its second hand variants go for approximately between PKR 24,000 to PKR 28,000 in Pakistan."}}]}
</script>

<script>
document.querySelectorAll('.mb-faq-question').forEach(function (q) {
    q.addEventListener('click', function () {
        var item = this.closest('.mb-faq-item');
        var isOpen = item.classList.contains('is-open');
        document.querySelectorAll('.mb-faq-item.is-open').forEach(function (o) { o.classList.remove('is-open'); });
        if (!isOpen) item.classList.add('is-open');
    });
});
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
