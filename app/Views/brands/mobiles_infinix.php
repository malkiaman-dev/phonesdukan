<?php
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

$pageTitle = "Infinix Mobile Price in Pakistan " . date('F Y') . " – Latest & Best Deals";
$metaDescription = "Explore Infinix mobile prices in Pakistan for " . date('F Y') . " at Phones Dukan. Find top models, features, and discounts. Prices starting from 15,000 PKR!";
$metaKeywords = "Infinix mobiles Pakistan, Infinix lowest prices, lowest prices for Infinix mobiles, Infinix mobiles prices in Pakistan, Infinix mobile specifications, Infinix mobile features";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'infinix', 'Infinix');

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die("Database connection error.");

$brand = "infinix";
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
        <h1 class="mob-hero-title"><span>Infinix Mobiles</span> Prices in Pakistan</h1>
        <p class="mob-hero-sub">Explore the latest Infinix smartphones with updated prices, verified specs, and fast delivery across Pakistan. Prices starting from PKR 22,999 · <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- BRAND TABS -->
<div class="mob-brand-bar">
    <div class="mob-brand-bar-inner">
        <a href="/mobiles" class="mob-brand-tab">All Mobiles</a>
        <a href="/mobiles/infinix" class="mob-brand-tab is-active">Infinix</a>
        <a href="/mobiles/oppo" class="mob-brand-tab">Oppo</a>
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
        <?php if (!empty($products)) : ?>
        <div class="mob-product-grid">
            <?php foreach ($products as &$product) :
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
                    <h3 class="na-name">
                        <a href="<?= $product_url ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                    </h3>
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
                            <button class="na-btn na-btn--cart"
                                data-product-id="<?= htmlspecialchars($product['product_id']) ?>"
                                data-unit-price="<?= $product['unit_price'] ?>">Add to Cart</button>
                            <button class="na-btn na-btn--buy buy-button"
                                data-product-id="<?= htmlspecialchars($product['product_id']) ?>"
                                data-unit-price="<?= $product['unit_price'] ?>">Buy Now</button>
                        <?php else: ?>
                            <span class="na-btn--soldout">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
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

<h2>Infinix is the <span>Right Choice for You</span></h2>
<p>In Pakistan, Infinix quickly achieved a reputation for producing reasonably priced smartphones. Infinix smartphones are perfect for people who want modern features at an affordable price since they combine stylish design with creative functionality.</p>
<h3>Here's why Infinix stands out:</h3>
<ul>
    <li><strong>Affordability Without Compromise:</strong> Infinix provides premium features like AI-powered cameras and large displays at prices starting as low as <span>PKR 15,000</span>.</li>
    <li><strong>Durability and Quality:</strong> Infinix phones are designed for durability and everyday use, thanks to their durable manufacturing and long-lasting batteries.</li>
    <li><strong>Innovative Technology:</strong> For smartphone lovers, Infinix is a great option because of features like modern gaming CPUs and AMOLED screens.</li>
</ul>

<h2>Compare <span>Infinix Models</span></h2>
<p><a href="https://www.phonesdukan.com">Phones Dukan</a> offers a large selection of mobile phones together with full information about their features, specs, and costs, making buying easy. This helps you in selecting the best mobile phone for your requirements and price range.</p>
<h3>You can explore models like:</h3>
<ul>
    <li><strong>Infinix GT 20 Pro:</strong> Ideal for photographers with its 108 MP + 2 MP + 2 MP camera and fast charging capabilities.</li>
    <li><strong>Infinix Hot 50 Pro:</strong> A budget-friendly option offering smooth performance and large (5000mAh) battery life.</li>
    <li><strong>Infinix Note 40 Pro Plus:</strong> Perfect for gamers with its MediaTek processor and AMOLED display.</li>
</ul>
<p>For more options, check out the <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>, <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, and <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a> to find the perfect match.</p>

<h2>Explore More About <span>Infinix Mobile Prices in Pakistan</span></h2>
<p>Pakistan's mobile phone market has always been active. Infinix leads the pack by selling smartphones at various price points. Phones Dukan offers Infinix mobile in Pakistan ranging from <span>PKR 15,000</span> to <span>PKR 60,000</span>, making them affordable to students, professionals, and families alike.</p>
<p>It's more important than ever to keep up with the most recent price trends because mobile phone prices have been increasing. However, in order to give you the greatest value for your money, Phones Dukan is dedicated to providing the most competitive pricing.</p>

<h2>Why Buy Infinix Mobiles from <span>Phones Dukan</span>?</h2>
<ul>
    <li><strong>Authenticity Guaranteed:</strong> Each product comes with a verified warranty to ensure customer satisfaction.</li>
    <li><strong>Best Prices:</strong> Our platform ensures that you always pay a fair price.</li>
    <li><strong>Nationwide Delivery:</strong> Whether you're in Islamabad, Karachi, Lahore, Peshawar or a small town, we deliver across Pakistan.</li>
    <li><strong>Exclusive Deals:</strong> Look out for seasonal sales and discounts on Infinix smartphones.</li>
    <li><strong>Trusted Support:</strong> Our experienced team is available to assist with any queries about your purchase.</li>
</ul>

<h2>Popular <span>Infinix Models</span></h2>
<p>Infinix offers a range of smartphones designed to cater to different user needs, from budget-friendly options to high-performance devices.</p>
<h3>Infinix Zero Series</h3>
<p>With premium features like fast charging, AMOLED panels, and refined styling, this series is perfect for professionals.</p>
<h3>Infinix Hot Series</h3>
<p>The Hot series is full of features, affordable, and has acceptable cameras and a long battery life, making it ideal for students and beginner smartphone users.</p>
<h3>Infinix Note Series</h3>
<p>For gamers and people who multitask, the Note series has larger screens, improved performance, and more lasting batteries.</p>
<p>Every series offers a wide variety of options that meet every lifestyle, while also keeping user demands in mind.</p>

<h2>Final <span>Decision</span></h2>
<p>Infinix mobiles combine low price, style, and creativity to change the smartphone experience in Pakistan. Phones Dukan is proud to be your primary resource for all mobile needs. We have all you need, whether you're searching for Pakistani smartphone prices or more specialised options like the <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>.</p>
<p>Start your Infinix journey now to find out why lots of people depend on Phones Dukan for all of their mobile needs. Find the perfect Infinix phone for you by looking through our selection now!</p>

</div>
</div>

<!-- FAQ -->
<div class="mb-faq-section">
<div class="mb-faq-inner">

<h2 class="mb-faq-title">Frequently Asked Questions About <span>Infinix Mobile Prices in Pakistan</span></h2>
<p class="mb-faq-subtitle">Get answers to common queries about Infinix mobile prices, features, and availability in Pakistan.</p>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What is the price range of Infinix mobile phones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>In Pakistan, Infinix provides a broad variety of smartphones that fit different price ranges. Beginning versions start at <span>PKR 15,000</span>, while elite mobiles can cost up to <span>PKR 234,999</span>.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">What are the latest Infinix mobile models available in Pakistan?</h3>
    <div class="mb-faq-answer">
        <p>As of 2025, some of the latest Infinix models available in Pakistan include:</p>
        <ul>
            <li>Infinix GT 20 Pro: Priced at <span>PKR 99,999</span></li>
            <li>Infinix Zero 40 4G: Priced at <span>PKR 65,999</span></li>
            <li>Infinix Hot 50 Pro: Priced at <span>PKR 42,999</span></li>
            <li>Infinix Note 40 Pro: Priced at <span>PKR 69,999</span></li>
        </ul>
    </div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Where can I purchase Infinix Mobile Phones in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Infinix smartphones are available through various online retailers and physical stores across Pakistan. Some popular online platforms include Phonesdukan.com.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Are Infinix Mobile Phones Available With Official Warranties In Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, Infinix mobile phones are available with official warranties in Pakistan. Purchasing from authorized retailers ensures that you receive genuine products along with warranty coverage.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Do Infinix Mobile Phones Support 5G Connectivity in Pakistan?</h3>
    <div class="mb-faq-answer"><p>Yes, certain Infinix models, such as the Infinix GT 20 Pro, support 5G connectivity, offering faster internet speeds where 5G networks are available.</p></div>
</div>

<div class="mb-faq-item">
    <h3 class="mb-faq-question">Are There Any Budget-Friendly Infinix Models Available?</h3>
    <div class="mb-faq-answer"><p>Yes, Infinix offers budget-friendly models such as the Infinix Smart 9 HD, priced at <span>PKR 22,999</span>, and the Infinix Smart 7 HD, priced at <span>PKR 17,999</span>, catering to users seeking affordable options.</p></div>
</div>

<p class="mb-faq-note">Please note that prices and availability are subject to change. It's advisable to verify the latest information from official retailers or the Infinix Pakistan website before making a purchase.</p>
</div>
</div>

<!-- CTA -->
<div class="mb-cta-wrap">
    <div class="mb-cta-note">
        Looking for the best Infinix mobile? Explore the latest Infinix smartphones at <a href="https://www.phonesdukan.com">Phones Dukan</a> and find the perfect match at unbeatable prices!
    </div>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {"@type":"Question","name":"What is the price range of Infinix mobile phones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"In Pakistan, Infinix provides a broad variety of smartphones that fit different price ranges. Entry-level models start at PKR 15,000, while premium options can cost up to PKR 234,999."}},
    {"@type":"Question","name":"What are the latest Infinix mobile models available in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"As of December 2024, the latest Infinix models in Pakistan include: Infinix GT 20 Pro (PKR 99,999), Infinix Zero 40 4G (PKR 65,999), Infinix Hot 50 Pro (PKR 42,999), and Infinix Note 40 Pro (PKR 69,999)."}},
    {"@type":"Question","name":"Where can I purchase Infinix mobile phones in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Infinix smartphones are available through various online retailers and physical stores in Pakistan. Popular online platforms include Phonesdukan.com."}},
    {"@type":"Question","name":"Are Infinix mobile phones available with official warranties in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, Infinix mobile phones come with official warranties in Pakistan when purchased from authorized retailers."}},
    {"@type":"Question","name":"Do Infinix mobile phones support 5G connectivity in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, certain Infinix models, such as the Infinix GT 20 Pro, support 5G connectivity, offering faster internet speeds in areas with 5G coverage."}}
  ]
}
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
