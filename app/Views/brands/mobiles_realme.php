<?php
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

$pageTitle = "Realme Mobile Price in Pakistan " . date('F Y') . " - Latest Realme Phones";
$metaDescription = "Discover the latest Realme mobile price in Pakistan for " . date('F Y') . ", starting at PKR 23,500. Shop feature-packed phones at Phones Dukan today!";
$metaKeywords = "Realme mobiles Pakistan, Realme lowest prices, Realme mobiles prices in Pakistan, Realme mobile specifications, Realme mobile features";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'realme', 'Realme');

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die('Database connection error.');

$brand = 'realme';
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
        <h1 class="mob-hero-title"><span>Realme Mobiles</span> Price in Pakistan</h1>
        <p class="mob-hero-sub">Discover the latest Realme smartphones combining innovation with style. Updated prices, verified specs, and fast delivery across Pakistan. Starting from PKR 23,500 · <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- BRAND TABS -->
<div class="mob-brand-bar">
    <div class="mob-brand-bar-inner">
        <a href="/mobiles" class="mob-brand-tab">All Mobiles</a>
        <a href="/mobiles/infinix" class="mob-brand-tab">Infinix</a>
        <a href="/mobiles/oppo" class="mob-brand-tab">Oppo</a>
        <a href="/mobiles/realme" class="mob-brand-tab is-active">Realme</a>
        <a href="/mobiles/samsung" class="mob-brand-tab">Samsung</a>
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

<h2>Why <span>Realme Phones Are Ideal</span></h2>
<p>Realme has reached a new level of popularity in Pakistan, as it is providing a strong blend of value and features in its smartphones. Here's why Realme is the one for you:</p>

<h3>1. Affordable Yet Efficient Devices</h3>
<p>Realme smartphones provide exceptional value for money with top performance and a multitude of features on a budget. Whether you are looking for a basic smartphone or a powerful flagship, Realme has something for everyone.</p>

<h3>2. Advanced Technology</h3>
<p>Realme smartphones always contain advanced technology. From the latest processors and displays to AI-powered cameras and fast charging, Realme is thoroughly committed to providing its users with a future-ready mobile experience.</p>

<h3>3. Attractive Design and Build Quality</h3>
<p>Realme has sleek, modern designs for their phones that protrude in the market. Whether you choose a sophisticated metal finish, glossy back, or premium glass design, Realme guarantees that every device will be as eye-catching as it is useful.</p>

<h3>4. Extended Battery Life</h3>
<p>Get ready to remain connected and productive all day thanks to the large battery inside Realme devices. You can recharge your device with considerable ease and then move back to being productive in no time.</p>

<h3>5. The Camera Does a Wonderful Job</h3>
<p>Realme put some thought into how the cameras perform. Most devices by Realme have multi-lens cameras, powered by AI to take great shots regardless of how well-lit the surroundings are.</p>

<h2>Explore <span>Realme Mobile Series</span></h2>
<p>Realme offers a range of smartphones, each designed to meet different needs:</p>

<h3>1. Affordable Models</h3>
<p>The best features these phones offer makes them affordable — the Realme C and Narzo series. This series balances price and features very well and guarantees solid battery life, camera, and other daily activities.</p>
<ul><li>Key Features: Long battery life, big screen, great for day-to-day use, camera enhancement through AI.</li></ul>

<h3>2. Mid-Range Options</h3>
<p>For users with more demanding requirements, the mid-range phones from Realme pack fast processors, vivid screens, and advanced camera setups while staying within reasonable price limits. Perfect for gaming, photography, and multitasking.</p>
<ul><li>Key Features: High-performance processors, fast refresh rate displays, solid camera setups, long-lasting battery.</li></ul>

<h3>3. Flagship Models</h3>
<p>Realme's GT Series is the culmination of advancement in mobile phone technology, featuring flagship performance and breath-taking designs. These phones are perfect for discerning users with the latest Snapdragon processors, 5G support, high refresh rates, and advanced AI camera functionality.</p>
<ul><li>Key Features: Snapdragon premium processors, 5G support, ultra-fast charging, advanced cameras.</li></ul>

<h2>Why Purchase <span>Realme Smartphones at Phones Dukan?</span></h2>
<ol>
    <li><strong>Authentic Realme Products:</strong> At Phones Dukan, we sell 100% original Realme brand smartphones only.</li>
    <li><strong>Price Promise:</strong> Our phonesdukan.com website contains the most affordable prices for Realme smartphones in Pakistan.</li>
    <li><strong>Reliable and Economical Delivery:</strong> Receive your order anywhere in Pakistan with our free shipping policy.</li>
    <li><strong>Safe Purchase Process:</strong> We accept many payment methods, including cash on delivery and bank transfer.</li>
</ol>

<h2>Key Features of <span>Realme Smartphones</span></h2>
<ul>
    <li><strong>Brilliant Screens:</strong> Realme mobile phones come with Full HD+ quality, AMOLED, or a 90Hz/120Hz refresh rate screen.</li>
    <li><strong>Great Performance:</strong> Mobile devices powered by Qualcomm Snapdragon and MediaTek chipsets ensure perfect performance.</li>
    <li><strong>AI Powered Cameras:</strong> Realme's AI camera technology ensures your photos are of the highest quality in any light condition.</li>
    <li><strong>Quick Charging:</strong> Dart Charge and VOOC Flash Charging technology enable fast charging up to 65W capability.</li>
    <li><strong>5G Compatibility:</strong> With 5G capable Realme, enjoy faster data speeds allowing you to stream, game, or browse without interruptions.</li>
</ul>

<h2>Realme Mobile Phones <span>Purchase Online in Pakistan</span></h2>
<p>Purchasing Realme phones in Pakistan has become effortless. Our easy-to-use website lets you check all smartphone models, evaluate their features, and place an order in a matter of minutes.</p>
<p>Are you looking to enhance your smartphone experience? Browse through our collection of Realme phones and make your purchase today! Starting price of Realme smartphones in Pakistan is <span>PKR 23,500</span>, making them an inexpensive option for many customers.</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">Frequently Asked Questions About <span>Realme Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Get answers to common queries about Realme mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which Realme smartphone is best for gaming?</h3>
    <div class="mb-faq-answer"><p>Realme's GT and Narzo series are ideal for gaming because of their strong CPUs, high refresh rate screens, and optimized performance.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Are Realme phones good for photography?</h3>
    <div class="mb-faq-answer"><p>Yes, Realme smartphones include AI-powered cameras, enhanced portrait settings, and night shooting capabilities, assuring high-quality photographs in any environment.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Does Realme offer 5G smartphones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, Realme provides multiple 5G-enabled smartphones, including the Realme GT series and various Narzo series phones, giving customers ultra-fast connectivity.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Do Realme phones have good battery life?</h3>
    <div class="mb-faq-answer"><p>Realme smartphones have 5000mAh battery life and fast-charging features like VOOC Flash Charge and Dart Charge for rapid power-ups.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">How can I check if my Realme smartphone is original?</h3>
    <div class="mb-faq-answer"><p>Check the IMEI number on Realme's official website to confirm authenticity, and make sure your phone has an official warranty card.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which Realme smartphone offers the best value for money?</h3>
    <div class="mb-faq-answer"><p>Both the Realme C and Narzo series are reasonably priced devices with great features including large displays, dependable cameras, and long-lasting batteries.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is Realme's flagship model?</h3>
    <div class="mb-faq-answer"><p>The most popular Realme GT series offers 5G compatibility, ultra-fast charging, and state-of-the-art performance.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Does Realme provide software updates?</h3>
    <div class="mb-faq-answer"><p>Yes, Realme frequently releases security patches and Android OS updates to boost security and performance.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Where can I buy Realme smartphones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Phones Dukan ensures genuine products at affordable prices with various payment modalities, plus they offer free shipping across Pakistan.</p></div>
</div>

<p class="mb-faq-note">Please note that prices and availability are subject to change. It's advisable to verify the latest information from official retailers or the Realme Pakistan website before making a purchase.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best Realme mobile? Explore the latest Realme smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and get the perfect mobile at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the starting price of Realme smartphones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Realme smartphones cost PKR 23,500 in Pakistan, making them an inexpensive option for many customers."}},{"@type":"Question","name":"Which Realme smartphone is best for gaming?","acceptedAnswer":{"@type":"Answer","text":"Realme's GT and Narzo series are ideal for gaming because of their strong CPUs, high refresh rate screens, and optimised performance."}},{"@type":"Question","name":"Are Realme phones good for photography?","acceptedAnswer":{"@type":"Answer","text":"Yes, Realme smartphones include AI-powered cameras, enhanced portrait settings, and night shooting capabilities, assuring high-quality photographs in any environment."}},{"@type":"Question","name":"Does Realme offer 5G smartphones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, Realme provides multiple 5G-enabled smartphones, including the Realme GT series and various Narzo series phones."}},{"@type":"Question","name":"Do Realme phones have good battery life?","acceptedAnswer":{"@type":"Answer","text":"Realme smartphones have 5000mAh battery life and fast-charging features like VOOC Flash Charge and Dart Charge for rapid power-ups."}},{"@type":"Question","name":"Where can I buy Realme smartphones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Phones Dukan ensures genuine products at affordable prices with various payment modalities, plus they offer free shipping."}}]}
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
