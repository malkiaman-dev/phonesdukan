<?php
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

$pageTitle = "Vivo Mobile Price in Pakistan " . date('F Y') . " - Latest Deals & Offers";
$metaDescription = "Check Vivo mobile price in Pakistan at Phones Dukan. Get the latest deals and fast delivery in cities like Islamabad, Lahore, Karachi, and more.";
$metaKeywords = "Vivo mobiles Pakistan, Vivo lowest prices, Vivo mobiles prices in Pakistan, Vivo mobile specifications, Vivo mobile features";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'vivo', 'Vivo');

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die('Database connection error.');

$brand = 'vivo';
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
        <h1 class="mob-hero-title"><span>Vivo Mobiles</span> Prices in Pakistan</h1>
        <p class="mob-hero-sub">Get the latest Vivo smartphones with genuine products, exclusive discounts, and fast delivery across Pakistan. Starting from PKR 21,999 · <?php echo date('F Y'); ?></p>
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
        <a href="/mobiles/vivo" class="mob-brand-tab is-active">Vivo</a>
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

<h2>Vivo <span>Mobile Prices in Pakistan</span></h2>
<ul>
    <li><strong>Value-Packed Features for Every Budget:</strong> Vivo is famous for offering premium features at a reasonable cost. With features like powerful CPUs, bright screens, and AI-enhanced cameras, even low-cost phones provide an outstanding user experience. For example, Vivo's V series is for people who look for phones with outstanding performance and cameras, while the Y series offers affordable phones with long-lasting batteries.</li>
    <li><strong>Creative Camera Technology:</strong> If you're passionate about photography, Vivo specialises in providing modern camera functionality. Many Vivo phones have features like video stabilisation, night photography, and portrait mode that guarantee you can shoot photos with outstanding quality.</li>
    <li><strong>Competitive Pricing in Pakistan:</strong> Vivo mobiles are made to be fairly priced, covering a wide range of buyers. Take a look at suggestions based on expected pricing points:
        <ul>
            <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000">Best Mobiles Under 30000</a></li>
            <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000">Best Mobiles Under 40000</a></li>
            <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a></li>
        </ul>
    </li>
    <li><strong>Durable and Stylish Designs:</strong> Vivo phones focus on being strong and stylish. They are made with good materials, bright colors, and slim designs to stand out.</li>
</ul>

<h2>Vivo <span>Mobile Price Trends and Insights</span></h2>
<p>Vivo mobile phones in Pakistan range in price from <span>PKR 20,000</span> to <span>PKR 150,000</span>, making them accessible to a wide range of people. According to a recent study, rising import taxes and changing exchange rates caused fluctuations in smartphone pricing.</p>

<h2>Compare the <span>Best Vivo Deals</span></h2>
<p>It can be tough to find the best Vivo phone as there are a lot of them available. Phones Dukan has a website that assists you in determining how to choose a Vivo phone by allowing users to compare Vivo mobiles. You will notice that there are several models available and you can select the appropriate phone by checking its features, specifics, and cost.</p>

<h3>Comparison Made Easy at Phones Dukan</h3>
<p>Selecting the best option might be challenging if there are too many options. Phones Dukan provides features for comparing Vivo mobile phones. You can make decisions with confidence when you compare features, specifications, and prices side by side.</p>

<h3>Why Choose Phones Dukan for Your Vivo Mobile?</h3>
<ul>
    <li><strong>Authenticity Guaranteed:</strong> All Vivo mobiles sold through our platform are 100% original and come with a company warranty.</li>
    <li><strong>Up-to-Date Pricing:</strong> We ensure real-time price updates so you always know the best deals.</li>
    <li><strong>Customer-Centric Services:</strong> Our user-friendly interface and detailed product information make shopping effortless.</li>
</ul>

<h2>Final Thoughts on <span>Vivo Mobiles</span></h2>
<p>Vivo phones are popular in Pakistan because they are reliable, affordable, and have cool features. If you're a student searching for a reliable mobile or a qualified IT expert wanting advanced features, Vivo's selection might meet all of your needs.</p>
<p>For the best deals and to check real-time updates on <a href="https://www.phonesdukan.com/mobiles/">Mobile Prices in Pakistan</a>, be sure to visit Phones Dukan.</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">FAQs About <span>Vivo Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Find answers to the most common questions about Vivo mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price range of Vivo mobiles in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Vivo offers smartphones in a wide price range in Pakistan. Entry-level models start from around <span>PKR 20,000</span>, while premium models can go up to approximately <span>PKR 150,000</span>. Whether you're looking for an affordable phone or a high-end device, Vivo has something for everyone.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Which Vivo mobile is the best under PKR 40,000?</h3>
    <div class="mb-faq-answer"><p>A popular option under <span>PKR 40,000</span> is the <a href="https://www.phonesdukan.com/mobiles/vivo/vivo-y19s/">Vivo Y19s</a>. At just <span>PKR 38,999</span>, it offers an excellent combination of features and performance, making it a wonderful choice for buyers on a budget.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Can I buy Vivo smartphones online in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, Vivo smartphones are available for purchase online in Pakistan through various platforms, including the official Vivo website and trusted online retailers. These platforms offer the convenience of home delivery and exclusive online discounts.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What are the features of the Vivo V40e?</h3>
    <div class="mb-faq-answer"><p>The <a href="https://www.phonesdukan.com/mobiles/vivo/vivo-v40e/">Vivo V40e</a>, priced at <span>PKR 92,999</span>, is packed with great features. It offers a stunning <span>6.77-inch AMOLED display</span> with a <span>120Hz refresh rate</span>, a powerful <span>MediaTek Dimensity processor</span>, <span>8GB RAM</span>, and a <span>5000mAh battery</span> with fast charging. It also has a <span>triple rear camera setup</span>, including a <span>50MP primary sensor</span>.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">How does the Vivo Y37 compare to other models in terms of pricing?</h3>
    <div class="mb-faq-answer"><p>The <span>Vivo Y37</span> is priced at <span>PKR 82,999</span>, making it a mid-range option. It offers a solid combination of features, ideal for users who want more than an entry-level smartphone but aren't looking for a flagship model.</p></div>
</div>

<p class="mb-faq-note">Prices and availability are subject to change. Please check official retailers or our website for the latest updates.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best Vivo mobile? Explore the latest Vivo smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and get the perfect mobile at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the price range of Vivo mobiles in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Vivo offers smartphones in a wide price range in Pakistan. Entry-level models start from around PKR 20,000, while premium models can go up to approximately PKR 150,000."}},{"@type":"Question","name":"Which Vivo mobile is the best under PKR 40,000?","acceptedAnswer":{"@type":"Answer","text":"A popular option under PKR 40,000 is the Vivo Y19s. At just PKR 38,999, it offers an excellent combination of features and performance."}},{"@type":"Question","name":"Can I buy Vivo smartphones online in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, Vivo smartphones are available for purchase online in Pakistan through various platforms, including trusted online retailers like Phones Dukan."}},{"@type":"Question","name":"What are the features of the Vivo V40e?","acceptedAnswer":{"@type":"Answer","text":"The Vivo V40e, priced at PKR 92,999, offers a 6.77-inch AMOLED display with 120Hz refresh rate, MediaTek Dimensity processor, 8GB RAM, 5000mAh battery with fast charging, and a triple rear camera setup including a 50MP primary sensor."}},{"@type":"Question","name":"How does the Vivo Y37 compare to other models in terms of pricing?","acceptedAnswer":{"@type":"Answer","text":"The Vivo Y37 is priced at PKR 82,999, making it a mid-range option ideal for users who want more than an entry-level smartphone."}}]}
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
