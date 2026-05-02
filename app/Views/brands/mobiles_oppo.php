<?php
$pageTitle = "OPPO Mobile Price in Pakistan " . date('F Y') . " – Latest & Best Deals";
$metaDescription = "Find the latest OPPO mobile prices in Pakistan for " . date('F Y') . " at Phones Dukan. Prices starting from PKR 34,899.";
$metaKeywords = "OPPO mobiles Pakistan, OPPO lowest prices, OPPO mobiles prices in Pakistan, OPPO mobile specifications, OPPO mobile features";
$metaRobots = "index, follow";
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die('Database connection error.');

$brand = 'oppo';
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
        <h1 class="mob-hero-title"><span>OPPO Mobiles</span> Price in Pakistan</h1>
        <p class="mob-hero-sub">Find the latest OPPO smartphones with updated prices, innovative camera technology, and fast delivery across Pakistan. Prices starting from PKR 34,899 · <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- BRAND TABS -->
<div class="mob-brand-bar">
    <div class="mob-brand-bar-inner">
        <a href="/mobiles" class="mob-brand-tab">All Mobiles</a>
        <a href="/mobiles/infinix" class="mob-brand-tab">Infinix</a>
        <a href="/mobiles/oppo" class="mob-brand-tab is-active">Oppo</a>
        <a href="/mobiles/realme" class="mob-brand-tab">Realme</a>
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

<h2>OPPO <span>Mobile Prices in Pakistan – A Range for Every Budget</span></h2>
<p>From OPPO, you're guaranteed to find a smartphone that fits perfectly with your budget. Be it an ultra-premium flagship device or a smartphone with all the base features, your needs will always be met. OPPO makes sure that their entire lineup caters to their customers, no matter what price range they look for.</p>

<h3>Affordable OPPO Smartphones</h3>
<p>OPPO sells affordable smartphones to the general public. They perform just as well as higher-end ones. These phones are great for daily activities boasting reliable performance, decent camera specs, and a good battery life.</p>
<ul>
    <li><strong>Powerful Battery Life:</strong> Don't worry about batteries anymore with high capacity batteries, great for people who love to use their smartphones throughout the day.</li>
    <li><strong>Decent Performance:</strong> Everyday basic activities like browsing the internet, using social media or light gaming are smooth on OPPO's cheaper models.</li>
    <li><strong>User-Friendly Interface:</strong> OPPO's ColorOS is not complicated and provides everyone with an effortless experience.</li>
    <li><strong>Good Camera Quality:</strong> Even OPPO's budget phones deliver decent quality cameras for photos and videos.</li>
</ul>

<h3>Mid-Range OPPO Smartphones</h3>
<p>Users who are looking for advanced features in their device will find that OPPO has the perfect balance between performance and price in their mid range smartphones.</p>
<ul>
    <li><strong>Accelerated Processors:</strong> Run games and other applications simultaneously without any lags or issues.</li>
    <li><strong>Improved Camera Systems:</strong> Capture photos and videos using multiple photo sensors including wide angle and HD telephoto lenses.</li>
    <li><strong>AMOLED Displays:</strong> Vibrant colors made possible by the AMOLED technology.</li>
    <li><strong>Fast Charging:</strong> Thanks to OPPO's VOOC charge, the operating system allows rapid charging.</li>
</ul>

<h3>Premium OPPO Smartphones</h3>
<p>Those who love technology and aspire to have the best will love OPPO's premium range. These flagship smartphones come packed with features including powerful processors, great displays, outstanding cameras, and high quality technology.</p>
<ul>
    <li><strong>Professional Cameras:</strong> For keen photographers, OPPO phones are a great pick as there is no compromise on quality.</li>
    <li><strong>Full-Screen Design:</strong> Wide full-screen designs with great color reproduction and resolution ensure a highly engaging visual experience.</li>
    <li><strong>Supports 5G:</strong> OPPO's premium models support faster uploads and downloads through 5G capabilities.</li>
</ul>

<h3>Why Choose OPPO Mobiles?</h3>
<ul>
    <li><strong>Exceptional Camera Quality:</strong> OPPO puts a lot of effort into developing its camera technology. Features like Ultra Night Mode, AI Beautification, and Super Zoom help them rank high in camera performance.</li>
    <li><strong>Long-Lasting Battery Life:</strong> Many OPPO phones come equipped with a 5000mAh battery or higher and support super-fast charging mechanics.</li>
    <li><strong>Fast Charging Technology:</strong> With VOOC Flash Charge, you can charge your device to 50% in just 30 minutes.</li>
    <li><strong>Sleek Design and Build Quality:</strong> Slim surfaces, smooth finishes, and premium materials enhance the luxurious feel of the phone.</li>
    <li><strong>Software and User Experience:</strong> OPPO's ColorOS gives the user an easy to use interface with plenty of opportunities for customization.</li>
</ul>

<h3>Buy OPPO Mobile Phones in Pakistan</h3>
<p>Browse through our stock at Phones Dukan, where you'll find a variety of OPPO smartphones to suit every style and budget. The website offers a wallet-friendly shopping experience while providing payment flexibility, quick delivery, and great customer support.</p>
<p>For great deals on OPPO smartphones, check out our selections of <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>, <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, and <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a>.</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">Frequently Asked Questions About <span>OPPO Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Get answers to common queries about OPPO mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the current OPPO mobile price in Pakistan in 2025?</h3>
    <div class="mb-faq-answer"><p>In Pakistan, OPPO mobile prices largely depend on the model and specifications. Basic OPPO models start at <span>PKR 15,000</span>, mid-range devices range between <span>PKR 30,000</span> to <span>PKR 50,000</span>, while premium smartphones cost around <span>PKR 60,000</span> to <span>PKR 150,000</span> or more. Visit Phones Dukan for the latest prices.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Where can I find the best deals on OPPO mobiles in Pakistan?</h3>
    <div class="mb-faq-answer"><p>You can find the best deals on OPPO smartphones at Phones Dukan and other online retailers in Pakistan. OPPO mobile phones are known for their affordability and premium features.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">How do OPPO mobile prices compare to other brands in Pakistan?</h3>
    <div class="mb-faq-answer"><p>OPPO offers competitive pricing with high-end features such as advanced cameras, long battery life, and fast charging. Compared to other brands, OPPO maintains lower prices while providing premium smartphone experiences.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Is OPPO a reliable brand in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, OPPO is a trusted and reputable smartphone brand in Pakistan, known for its innovative features, stylish designs, and excellent customer support.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Does OPPO offer 5G phones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, OPPO provides 5G-enabled smartphones in Pakistan, allowing users to experience high-speed internet with low latency. Check the latest 5G OPPO models at Phones Dukan.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What are the key features of OPPO smartphones?</h3>
    <div class="mb-faq-answer"><p>OPPO smartphones are known for their AI-integrated cameras, VOOC fast charging, long battery life, and sleek, modern designs.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which OPPO mobile is best for gaming in Pakistan?</h3>
    <div class="mb-faq-answer"><p>The OPPO Reno series and OPPO F19 Pro+ are excellent choices for gaming, offering powerful chipsets, high RAM capacity, and a smooth gaming experience.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Does OPPO provide a warranty for its phones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, OPPO offers a one-year official warranty on all smartphones purchased in Pakistan, covering manufacturing defects.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Are OPPO phones available for purchase in installments in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, many online retailers, including Phones Dukan, offer installment plans for OPPO smartphones in Pakistan.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the resale value of OPPO phones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>OPPO smartphones hold a good resale value in Pakistan, especially when kept in excellent condition with original accessories and packaging.</p></div>
</div>

<p class="mb-faq-note">Please note that prices and availability are subject to change. It's advisable to verify the latest information from official retailers or the OPPO Pakistan website before making a purchase.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best OPPO mobile? Explore the latest OPPO smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and get the perfect mobile at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the current OPPO mobile price in Pakistan in 2025?","acceptedAnswer":{"@type":"Answer","text":"In Pakistan, OPPO mobile prices largely depend on the model and specifications. Basic OPPO models start at PKR 15,000, mid-range devices range between PKR 30,000 to PKR 50,000, while premium smartphones can be bought roughly between PKR 60,000 to PKR 150,000 or more."}},{"@type":"Question","name":"Is OPPO a reliable brand in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, OPPO is a reputable and popular brand in Pakistan, widely recognized for its advanced features, modern looks, and powerful devices."}},{"@type":"Question","name":"Does OPPO offer 5G phones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Indeed, OPPO sells 5G-enabled mobile phones in Pakistan as 5G networks are on the rise."}},{"@type":"Question","name":"Does OPPO provide a warranty for its phones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Absolutely, OPPO has provided a one-year warranty for all smartphones bought in Pakistan that protects against any manufacturing damages."}},{"@type":"Question","name":"Are OPPO phones available for purchase in installments in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, a number of online retailers such as Phones Dukan sell OPPO smartphones in Pakistan on simple installment plans."}}]}
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
