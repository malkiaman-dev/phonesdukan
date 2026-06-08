<?php
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

$pageTitle = "Xiaomi Mobile Prices in Pakistan - " . date('F Y');
$metaDescription = "Discover Xiaomi mobiles prices in Pakistan starting at PKR 39,999. Compare features, prices, and updates for " . date('F Y') . " to find the best deals at Phones Dukan.";
$metaKeywords = "Xiaomi mobiles Pakistan, Xiaomi lowest prices, Xiaomi mobiles prices in Pakistan, Xiaomi mobile specifications, Xiaomi mobile features";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'xiaomi', 'Xiaomi');

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die('Database connection error.');

$brand = 'xiaomi';
$limit = 8;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

$query = "SELECT p.product_id, p.product_slug, p.product_name, p.regular_price, p.sale_price, p.stock_quantity,
                 p.product_status, p.product_tag,
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
        <h1 class="mob-hero-title"><span>Xiaomi Mobiles</span> Prices in Pakistan</h1>
        <p class="mob-hero-sub">Check out the latest Xiaomi smartphones — modern, affordable, and feature-packed. Updated prices with fast delivery across Pakistan. Starting from PKR 39,999 · <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- BRAND TABS -->
<div class="mob-brand-bar">
    <div class="mob-brand-bar-inner">
        <a href="/mobiles" class="mob-brand-tab">All Mobiles</a>
        <a href="/mobiles/infinix" class="mob-brand-tab">Infinix</a>
        <a href="/mobiles/oppo" class="mob-brand-tab">Oppo</a>
        <a href="/mobiles/realme" class="mob-brand-tab">Realme</a>
        <a href="/mobiles/samsung" class="mob-brand-tab">Samsung</a>
        <a href="/mobiles/tecno" class="mob-brand-tab">Tecno</a>
        <a href="/mobiles/vivo" class="mob-brand-tab">Vivo</a>
        <a href="/mobiles/xiaomi" class="mob-brand-tab is-active">Xiaomi</a>
    </div>
</div>

<?php include_once(__DIR__ . '/../ad/display1.php'); ?>

<!-- PRODUCT GRID -->
<div class="mob-products-section">
    <div class="mob-products-inner">
        <?php if (!empty($products)): ?>
        <div class="mob-product-grid">
            <?php foreach ($products as $row):
                $product = prepareProductCardFromRow($row);
            ?>
            <article class="na-card mob-na-card">
                <?php include __DIR__ . '/../partials/na-card-badge.php'; ?>
                <a href="<?= $product['product_url'] ?>" class="na-img-link">
                    <div class="na-img-box">
                        <img src="<?= $product['product_image'] ?>" alt="<?= $product['product_name'] ?>" loading="lazy" decoding="async">
                    </div>
                </a>
                <div class="na-body">
                    <h3 class="na-name"><a href="<?= $product['product_url'] ?>"><?= $product['product_name'] ?></a></h3>
                    <div class="na-price">
                        <?php if ($product['has_sale']): ?>
                            <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                            <span class="na-price--new">Rs. <?= number_format($product['sale_price']) ?></span>
                        <?php elseif ($product['regular_price'] > 0): ?>
                            <span class="na-price--new">Rs. <?= number_format($product['regular_price']) ?></span>
                        <?php else: ?>
                            <span class="na-price--na">Price on request</span>
                        <?php endif; ?>
                    </div>
                    <?php include __DIR__ . '/../partials/na-card-actions.php'; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="mob-empty">No products found for this brand. Please check back soon.</p>
        <?php endif; ?>

        <?php include_once(__DIR__ . '/../ad/display2.php'); ?>

        <?php if ($total_pages > 1): ?>
        <div class="mb-pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?paged=<?= $i ?>" class="mb-page-link <?= ($i == $paged) ? 'is-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once(__DIR__ . '/../ad/display1.php'); ?>

<!-- SEO CONTENT -->
<div class="mb-content-section">
<div class="mb-content-inner">

<h2>Why <span>Xiaomi is the Go-To Brand</span> for Many Pakistani Consumers</h2>
<p>Mobile phones are now essential due to the advancements in communication equipment. As a result, most individuals use cellphones to do various things, remain in contact with friends and family, and—above all—find information. Selecting a phone that is trustworthy, inexpensive, and capable of carrying out the necessary tasks is important in a nation like Pakistan where mobile phones are used for both business and communication.</p>
<p>Xiaomi has provided a variety of smartphones for the various individual categories in order to close this gap. Since they are well recognised for spending heavily in providing their clients with high-quality features, they have gained a lot of customers who are careful about their finances yet do not want to sacrifice performance.</p>

<h2>What Affects <span>Xiaomi Mobile Prices</span> in Pakistan?</h2>
<p>Before diving into the details of Xiaomi mobile prices in Pakistan, it's important to understand the factors that affect the cost of a mobile device. These include:</p>
<ul>
    <li><strong>Exchange Rates:</strong> The value of the Pakistani Rupee (PKR) against foreign currencies, particularly the US Dollar, plays a significant role in determining the price of imported goods, including smartphones.</li>
    <li><strong>Import Taxes and Duties:</strong> The Pakistani government imposes taxes and duties on imported goods, which can increase the overall price of mobile phones.</li>
    <li><strong>Model and Features:</strong> Different Xiaomi models come with varying features such as camera quality, processing power, battery life, and screen size, all of which impact the price.</li>
    <li><strong>Market Competition:</strong> Competition between mobile brands in Pakistan, including local players like QMobile and international giants like Samsung and Huawei, can influence Xiaomi's pricing strategy.</li>
</ul>

<h2>Why Choose <span>Xiaomi Mobiles</span> in Pakistan?</h2>
<ul>
    <li><strong>Affordable Prices:</strong> Xiaomi's price range is designed to cater to a wide variety of consumers. Whether you're on a tight budget or looking for a high-end device, Xiaomi has a solution for you.</li>
    <li><strong>Reliable Performance:</strong> With the latest processors, high-quality cameras, and long-lasting batteries, Xiaomi smartphones offer performance that can rival more expensive brands.</li>
    <li><strong>Wide Availability:</strong> Xiaomi smartphones are widely available across Pakistan, both online and in physical stores.</li>
    <li><strong>After-Sales Service:</strong> Xiaomi has a solid reputation for providing reliable after-sales service, with authorized service centers available in major cities across Pakistan.</li>
</ul>

<h2>Popular <span>Xiaomi Models</span></h2>
<h3>Xiaomi Mi Series</h3>
<p>The Mi series by Xiaomi is renowned for offering high-end features at competitive prices. With powerful processors, large AMOLED displays, and rapid charging capabilities, this series delivers a premium experience for users who demand the best performance without breaking the bank.</p>
<h3>Xiaomi Redmi Series</h3>
<p>The Redmi series is made for users on a cheap price without compromising necessary features. The Redmi series is perfect for students and first-time users who appreciate affordability because of its strong performance, huge batteries, and reliable cameras.</p>
<h3>Xiaomi Poco Series</h3>
<p>The amazing gaming capabilities, quick processors, and high-refresh-rate screens of the Poco series make it stand out for gamers and heavy multitasking individuals. Poco phones guarantee smooth gaming experiences and long battery life thanks to its excellent specs at a reasonable price range.</p>
<h3>Xiaomi Note Series</h3>
<p>The Xiaomi Note series is designed for buyers wanting a blend of performance and style. With larger screens, powerful chipsets, and outstanding cameras, the Note series is ideal for buyers who enjoy watching content, multitasking, and taking amazing images.</p>

<h2>Mobile Price Trends in Pakistan: <span>What You Need to Know</span></h2>
<p>It's important to keep up with the latest pricing trends because cell phone prices in Pakistan are subject to constant fluctuations owing to a variety of factors, including inflation, currency rates, and changing market dynamics.</p>
<p>Xiaomi's dedication to providing competitive pricing and value has enabled its phones to maintain their popularity among Pakistani consumers in spite of these challenges.</p>

<h2>Conclusion: <span>The Best Xiaomi Mobile for You</span></h2>
<p>No matter what your budget or needs are, Xiaomi has a wide selection of mobile phones to meet your needs. The cost, performance, and durability of Xiaomi smartphones make them a good choice for Pakistanis seeking to stay connected without breaking the bank.</p>
<p>For those looking for more options, don't forget to explore our <a href="https://www.phonesdukan.com/mobiles/">Mobile Price in Pakistan</a> page. If you're on a tight budget, you can also check out our guides for <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>, <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, and <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a>.</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">FAQs About <span>Xiaomi / Redmi Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Find answers to the most common questions about Redmi mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Redmi 8GB/128GB in Pakistan?</h3>
    <div class="mb-faq-answer"><p>As of 2025, the price of the Redmi 8GB/128GB variant in Pakistan ranges between <span>PKR 35,000</span> to <span>PKR 38,000</span>. Prices may vary based on location, promotions, and available stock.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Is the Redmi Note 12 a 5G?</h3>
    <div class="mb-faq-answer"><p>Yes, the Redmi Note 12 is a <span>5G-enabled smartphone</span>. It comes with next-generation network support, allowing you to enjoy fast internet speeds and improved connectivity.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Redmi 2025 8GB/256GB in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The Redmi 2025 8GB/256GB variant is available for approximately <span>PKR 45,000</span> to <span>PKR 50,000</span> in Pakistan. Prices may vary depending on retailer offers and market conditions.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which phone is the best in Xiaomi?</h3>
    <div class="mb-faq-answer"><p>Xiaomi offers a variety of top-performing smartphones. Currently, the <span>Xiaomi 13 Pro</span> stands out as one of the best with its advanced features, including a powerful camera system, fast processing, and premium design. However, for those on a budget, the <span>Redmi Note 12</span> also offers great value for money.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Is Xiaomi good or Redmi?</h3>
    <div class="mb-faq-answer"><p>Both Xiaomi and Redmi phones offer great performance, but they serve different markets. <span>Xiaomi phones</span> are more premium, featuring top-tier specs and designs. On the other hand, <span>Redmi phones</span> are known for their affordability while still offering solid performance, making them ideal for budget-conscious users.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Is Mi or Samsung better?</h3>
    <div class="mb-faq-answer"><p>Both <span>Mi (Xiaomi)</span> and <span>Samsung</span> offer high-quality phones. Xiaomi provides excellent value for money with cutting-edge technology at affordable prices, while Samsung is known for its premium build quality, display, and camera capabilities. The choice depends on your budget and preferred features.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which model is best in Redmi?</h3>
    <div class="mb-faq-answer"><p>The <span>Redmi Note 12 series</span> is among the best in the Redmi lineup, offering great performance, a good display, and fast charging. The <span>Redmi K40</span> is another excellent option for users seeking high-end specs at a reasonable price.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price of Redmi Note 12 8GB RAM 128GB storage in Pakistan in 2025?</h3>
    <div class="mb-faq-answer"><p>The Redmi Note 12 with 8GB RAM and 128GB storage is priced between <span>PKR 38,000</span> to <span>PKR 40,000</span> in Pakistan in 2025, depending on the seller and location.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which mobile is best in Pakistan?</h3>
    <div class="mb-faq-answer"><p>In Pakistan, the best mobile phones in 2025 are from brands like <span>Xiaomi, Samsung, and Oppo</span>. For Xiaomi, the <span>Redmi Note 12</span> and <span>Xiaomi 13 Pro</span> are among the top choices, offering a balance of performance, camera quality, and price.</p></div>
</div>

<p class="mb-faq-note">Prices and availability are subject to change. Please check official retailers or our website for the latest updates.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best Redmi mobile? Explore the latest Redmi smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and get the perfect mobile at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the price of Redmi 8GB/128GB in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"As of 2025, the price of the Redmi 8GB/128GB variant in Pakistan ranges between PKR 35,000 to PKR 38,000."}},{"@type":"Question","name":"Is the Redmi Note 12 a 5G?","acceptedAnswer":{"@type":"Answer","text":"Yes, the Redmi Note 12 is a 5G-enabled smartphone with next-generation network support."}},{"@type":"Question","name":"Which phone is the best in Xiaomi?","acceptedAnswer":{"@type":"Answer","text":"Currently, the Xiaomi 13 Pro stands out as one of the best with its advanced camera system, fast processing, and premium design. For budget users, the Redmi Note 12 offers great value."}},{"@type":"Question","name":"Is Xiaomi good or Redmi?","acceptedAnswer":{"@type":"Answer","text":"Both Xiaomi and Redmi phones offer great performance but serve different markets. Xiaomi phones are more premium, while Redmi phones are known for affordability with solid performance."}},{"@type":"Question","name":"Which mobile is best in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"In Pakistan, the best mobile phones in 2025 are from brands like Xiaomi, Samsung, and Oppo. For Xiaomi, the Redmi Note 12 and Xiaomi 13 Pro are among the top choices."}}]}
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
