<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

$pageTitle = "Best Mobile Accessories Prices in Pakistan | Phones Dukan";
$metaDescription = "Discover updated mobile accessories prices in Pakistan at Phones Dukan. Compare chargers, cables, cases, and premium smartphone accessories in one place.";
$metaRobots = "index, follow";
$metaKeywords = "mobile accessories, mobile accessories online, mobile accessories in Pakistan, phone chargers, phone cases, screen protectors";

$breadcrumbs = SeoHelper::categoryBreadcrumbs('mobile-accessories', 'Mobile Accessories');

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die('Database connection error.');
}

$limit = 16;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$paged = $paged > 0 ? $paged : 1;

$countSql = "SELECT COUNT(*) as total
             FROM products p
             JOIN categories c ON p.category_id = c.category_id
             WHERE c.slug = 'mobile-accessories' AND p.product_status != '0'";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute();
$totalRows = (int) ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 0;
seoEnforceListingPagination($paged, $totalPages);
$offset = ($paged - 1) * $limit;

require_once dirname(__DIR__, 2) . '/includes/header.php';

$query = "SELECT
            p.product_id,
            p.product_slug,
            p.product_name,
            p.regular_price,
            p.sale_price,
            p.stock_quantity,
            p.product_status,
            p.product_tag,
            pi.image_url,
            b.slug AS brand_slug,
            c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'mobile-accessories' AND p.product_status != '0'
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rawProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
foreach ($rawProducts as $product) {
    $products[] = prepareProductCardFromRow($product);
}
?>

<section class="ma-hero">
    <div class="ma-container ma-hero-inner">
        <p class="ma-hero-eyebrow">PHONES DUKAN ACCESSORIES COLLECTION</p>
        <h1 class="ma-hero-title"><span>Latest Mobile Accessories</span> Prices in Pakistan</h1>
        <p class="ma-hero-sub">Shop the latest mobile accessories in Pakistan including fast chargers, Type-C cables, gaming accessories, power banks, ring lights, and premium smartphone essentials from trusted brands.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="ma-products-section">
    <div class="ma-container">
        <?php if (!empty($products)): ?>
            <div class="ma-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card ma-na-card">
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
            <p class="ma-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="ma-pagination-wrap">
            <div class="pagination ma-pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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

<?php
$conn = null;
?>

<section class="ma-content-section">
    <div class="ma-container">
        <div class="ma-content">
            <h2>Mobile Accessories Pricing in <span>Pakistan</span></h2>
            <p>Mobile accessories prices in Pakistan depend on quality, brand value, durability, and device compatibility. From essential chargers and cables to performance-focused accessories, users can choose options based on need and budget.</p>

            <h2>Affordable vs <span>Premium Accessories</span></h2>
            <p>Affordable accessories focus on daily utility like charging, basic protection, and simple connectivity. Premium accessories offer faster charging standards, better material quality, longer lifespan, and stronger safety features.</p>

            <h2>Accessories <span>Price Ranges</span></h2>
            <ul>
                <li><strong>Budget Range (PKR 300 – 1,500):</strong> Basic cables, local chargers, simple phone covers, and screen guards.</li>
                <li><strong>Mid Range (PKR 1,500 – 6,000):</strong> Better build quality, branded adapters, durable cases, and improved reliability.</li>
                <li><strong>Premium Range (PKR 6,000 – 35,000+):</strong> Fast wireless chargers, branded power accessories, and high-end protective solutions.</li>
            </ul>

            <h2>Popular Accessories <span>Features</span></h2>
            <p>Top features include fast-charge support (PD/QC), braided long-life cables, shockproof designs, anti-scratch surfaces, and certified power safety. For full ecosystem performance, pair accessories with latest <a href="https://www.phonesdukan.com/mobiles/">mobile phones</a> and smart wearables.</p>

            <h2>Mobile Accessories <span>Buying Guide</span></h2>
            <p>Always verify power ratings, port compatibility (USB-C/Lightning), warranty support, and authenticity. For charging products, choose trusted brands with overheating and overvoltage protection to keep your devices safe long term.</p>
        </div>

        <div class="ma-table-section">
            <h2>Top Mobile Accessories in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="ma-table-wrap">
                <table class="ma-table">
                    <thead>
                        <tr>
                            <th>Mobile Accessory</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>20W USB-C Fast Charger</td>
                            <td class="text-right"><span>3,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>65W GaN Fast Adapter</td>
                            <td class="text-right"><span>8,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Premium Braided Type-C Cable</td>
                            <td class="text-right"><span>1,299 PKR</span></td>
                        </tr>
                        <tr>
                            <td>MagSafe Compatible Charging Stand</td>
                            <td class="text-right"><span>5,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Military-Grade Protective Case</td>
                            <td class="text-right"><span>2,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Tempered Glass Screen Protector</td>
                            <td class="text-right"><span>799 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Car Fast Charger Dual Port</td>
                            <td class="text-right"><span>2,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Magnetic Phone Holder</td>
                            <td class="text-right"><span>1,499 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="ma-faq-section">
    <div class="ma-container">
        <div class="ma-faq-wrap">
            <h2>FAQs About <span>Mobile Accessories in Pakistan</span></h2>
            <p class="ma-faq-intro">These quick answers help you choose reliable accessories with better compatibility and long-term value.</p>

            <div class="ma-faq-list">
                <div class="ma-faq-item">
                    <button class="ma-faq-question" type="button" aria-expanded="false">
                        <span>What is the price range of mobile accessories in Pakistan?</span>
                        <span class="ma-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="ma-faq-answer">
                        <div class="ma-faq-answer-inner">
                            <p>Most mobile accessories in Pakistan start from around <span>Rs. 300</span> and go up to <span>Rs. 35,000+</span> depending on product type and brand quality.</p>
                        </div>
                    </div>
                </div>

                <div class="ma-faq-item">
                    <button class="ma-faq-question" type="button" aria-expanded="false">
                        <span>Which accessories are essential for smartphones?</span>
                        <span class="ma-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="ma-faq-answer">
                        <div class="ma-faq-answer-inner">
                            <p>Essential accessories include a good charger, durable cable, protective case, tempered glass, and for many users, a car charger or phone mount.</p>
                        </div>
                    </div>
                </div>

                <div class="ma-faq-item">
                    <button class="ma-faq-question" type="button" aria-expanded="false">
                        <span>Are mobile accessories covered under warranty?</span>
                        <span class="ma-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="ma-faq-answer">
                        <div class="ma-faq-answer-inner">
                            <p>Yes, many branded accessories come with warranty coverage, typically between <span>3 months</span> and <span>12 months</span> depending on category and brand.</p>
                        </div>
                    </div>
                </div>

                <div class="ma-faq-item">
                    <button class="ma-faq-question" type="button" aria-expanded="false">
                        <span>Which brands are best for accessories?</span>
                        <span class="ma-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="ma-faq-answer">
                        <div class="ma-faq-answer-inner">
                            <p>Popular choices include <span>Anker, Samsung, Apple, Baseus, Xiaomi, and UGREEN</span> because of better durability, compatibility, and charging safety standards.</p>
                        </div>
                    </div>
                </div>

                <div class="ma-faq-item">
                    <button class="ma-faq-question" type="button" aria-expanded="false">
                        <span>How to choose good quality accessories?</span>
                        <span class="ma-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="ma-faq-answer">
                        <div class="ma-faq-answer-inner">
                            <p>Choose accessories with proper certification, verified watt ratings, quality material finish, and trusted warranty support from reliable sellers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the price range of mobile accessories in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Most mobile accessories in Pakistan start from around Rs. 300 and go up to Rs. 35,000+ depending on product type and brand quality."
      }
    },
    {
      "@type": "Question",
      "name": "Which accessories are essential for smartphones?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Essential accessories include a good charger, durable cable, protective case, tempered glass, and for many users, a car charger or phone mount."
      }
    },
    {
      "@type": "Question",
      "name": "Are mobile accessories covered under warranty?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, many branded accessories come with warranty coverage, typically between 3 months and 12 months depending on category and brand."
      }
    },
    {
      "@type": "Question",
      "name": "Which brands are best for accessories?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Popular choices include Anker, Samsung, Apple, Baseus, Xiaomi, and UGREEN because of better durability, compatibility, and charging safety standards."
      }
    },
    {
      "@type": "Question",
      "name": "How to choose good quality accessories?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Choose accessories with proper certification, verified watt ratings, quality material finish, and trusted warranty support from reliable sellers."
      }
    }
  ]
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
