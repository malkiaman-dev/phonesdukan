<?php
$pageTitle = "Best Power Bank Price in Pakistan – Affordable & Reliable";
$metaDescription = "Explore updated power bank prices in Pakistan at Phones Dukan. Compare affordable to premium fast-charging power banks with trusted backup performance.";
$metaRobots = "index, follow";
$metaKeywords = "Power bank, Power bank price in Pakistan, Best power bank in Pakistan, Best power bank, fast charging power bank, best power bank online";

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die('Database connection error.');
}

$limit = 16;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$paged = $paged > 0 ? $paged : 1;
$offset = ($paged - 1) * $limit;

$query = "SELECT
            p.product_id,
            p.product_slug,
            p.product_name,
            p.regular_price,
            p.sale_price,
            p.stock_quantity,
            pi.image_url,
            b.slug AS brand_slug,
            c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'power-banks' AND p.product_status != '0'
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rawProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
foreach ($rawProducts as $product) {
    $regularPrice = (float)($product['regular_price'] ?? 0);
    $salePrice = (float)($product['sale_price'] ?? 0);
    $isSoldOut = (int)($product['stock_quantity'] ?? 0) <= 0;
    $hasSale = !$isSoldOut && $salePrice > 0 && $regularPrice > 0 && $salePrice < $regularPrice;
    $discountPct = $hasSale ? max(1, (int)round((($regularPrice - $salePrice) / $regularPrice) * 100)) : 0;
    $unitPrice = $hasSale ? $salePrice : $regularPrice;

    $products[] = [
        'product_id' => (int)$product['product_id'],
        'product_url' => buildProductPath((string)($product['category_slug'] ?? ''), (string)($product['brand_slug'] ?? ''), (string)($product['product_slug'] ?? '')),
        'product_name' => htmlspecialchars($product['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8'),
        'product_image' => !empty($product['image_url'])
            ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']), ENT_QUOTES, 'UTF-8')
            : '/public/assets/images/Phones_dukan_favicon.png',
        'regular_price' => $regularPrice,
        'sale_price' => $salePrice,
        'is_sold_out' => $isSoldOut,
        'has_sale' => $hasSale,
        'discount_pct' => $discountPct,
        'unit_price' => $unitPrice,
    ];
}
?>

<section class="pb-hero">
    <div class="pb-container pb-hero-inner">
        <p class="pb-hero-eyebrow">PHONES DUKAN POWER BANK COLLECTION</p>
        <h1 class="pb-hero-title"><span>Latest Power Banks</span> Prices in Pakistan</h1>
        <p class="pb-hero-sub">Explore the latest power banks in Pakistan with fast charging support, high-capacity battery backup, Type-C connectivity, and portable designs from trusted tech brands.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="pb-products-section">
    <div class="pb-container">
        <?php if (!empty($products)): ?>
            <div class="pb-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card pb-na-card">
                        <?php if ($product['is_sold_out']): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($product['discount_pct'] > 0): ?>
                            <span class="na-badge"><?= $product['discount_pct'] ?>% OFF</span>
                        <?php endif; ?>

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

                            <div class="na-actions">
                                <?php if (!$product['is_sold_out']): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= $product['product_id'] ?>"
                                        data-unit-price="<?= (float)$product['unit_price'] ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= $product['product_id'] ?>"
                                        data-unit-price="<?= (float)$product['unit_price'] ?>">
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
            <p class="pb-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="pb-pagination-wrap">
            <div class="pagination pb-pagination">
                <?php
                $countSql = "SELECT COUNT(*) as total
                             FROM products p
                             JOIN categories c ON p.category_id = c.category_id
                             WHERE c.slug = 'power-banks' AND p.product_status != '0'";
                $stmtCount = $conn->prepare($countSql);
                $stmtCount->execute();
                $totalRows = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                $totalPages = (int)ceil($totalRows / $limit);

                for ($i = 1; $i <= $totalPages; $i++):
                ?>
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

<section class="pb-content-section">
    <div class="pb-container">
        <div class="pb-content">
            <h2>Power Bank Pricing in <span>Pakistan</span></h2>
            <p>Power bank pricing in Pakistan depends on battery capacity, charging speed standards, build quality, and brand reliability. Buyers can choose from compact everyday units to high-capacity power banks for heavy users and travelers.</p>

            <h2>Affordable vs <span>Premium Power Banks</span></h2>
            <p>Affordable power banks focus on essential backup and simple output support. Premium models offer faster charging protocols, better cell protection, stronger conversion efficiency, and improved long-term battery health.</p>

            <h2>Power Bank Capacity Guide <span>(mAh)</span></h2>
            <ul>
                <li><strong>10,000mAh:</strong> Ideal for 1–2 full smartphone charges and light daily use.</li>
                <li><strong>20,000mAh:</strong> Best for heavy users, work travel, and multi-device charging.</li>
                <li><strong>30,000mAh+:</strong> Suitable for extended trips and users needing maximum backup.</li>
            </ul>

            <h2>Fast Charging &amp; <span>Features</span></h2>
            <p>Popular features include PD/QC fast charging, Type-C bi-directional input/output, multiple ports, LED battery indicators, and smart safety control against overheating and overvoltage. Pair with genuine cables for better output consistency.</p>

            <h2>Power Bank <span>Buying Guide</span></h2>
            <p>Before purchase, verify real capacity rating, output wattage, charging protocol compatibility, weight, and warranty policy. For safe daily usage, prioritize certified brands with proven safety circuits and stable discharge performance.</p>
        </div>

        <div class="pb-table-section">
            <h2>Top Power Banks in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="pb-table-wrap">
                <table class="pb-table">
                    <thead>
                        <tr>
                            <th>Power Bank</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>10,000mAh Compact Power Bank</td>
                            <td class="text-right"><span>3,199 PKR</span></td>
                        </tr>
                        <tr>
                            <td>20,000mAh Fast Charging Power Bank</td>
                            <td class="text-right"><span>5,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>22.5W PD Quick Charge Power Bank</td>
                            <td class="text-right"><span>7,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>MagSafe Compatible Wireless Power Bank</td>
                            <td class="text-right"><span>8,299 PKR</span></td>
                        </tr>
                        <tr>
                            <td>30,000mAh High Capacity Power Bank</td>
                            <td class="text-right"><span>11,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Dual USB + Type-C Travel Power Bank</td>
                            <td class="text-right"><span>4,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Ultra-Slim Pocket Power Bank</td>
                            <td class="text-right"><span>2,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Premium Metal-Body Power Bank</td>
                            <td class="text-right"><span>9,499 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="pb-faq-section">
    <div class="pb-container">
        <div class="pb-faq-wrap">
            <h2>FAQs About <span>Power Banks in Pakistan</span></h2>
            <p class="pb-faq-intro">These frequently asked questions help buyers choose safer, longer-lasting, and better-performing power banks.</p>

            <div class="pb-faq-list">
                <div class="pb-faq-item">
                    <button class="pb-faq-question" type="button" aria-expanded="false">
                        <span>What is the price range of power banks in Pakistan?</span>
                        <span class="pb-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="pb-faq-answer">
                        <div class="pb-faq-answer-inner">
                            <p>Power banks in Pakistan generally range from <span>Rs. 2,500</span> to <span>Rs. 20,000+</span>, depending on capacity, charging speed, and brand quality.</p>
                        </div>
                    </div>
                </div>

                <div class="pb-faq-item">
                    <button class="pb-faq-question" type="button" aria-expanded="false">
                        <span>Which power bank capacity is best?</span>
                        <span class="pb-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="pb-faq-answer">
                        <div class="pb-faq-answer-inner">
                            <p>For most users, <span>10,000mAh to 20,000mAh</span> is the best balance between portability and backup performance for regular smartphone use.</p>
                        </div>
                    </div>
                </div>

                <div class="pb-faq-item">
                    <button class="pb-faq-question" type="button" aria-expanded="false">
                        <span>Do power banks support fast charging?</span>
                        <span class="pb-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="pb-faq-answer">
                        <div class="pb-faq-answer-inner">
                            <p>Yes, many modern power banks support fast charging protocols such as <span>PD and QC</span>, provided your phone and cable also support them.</p>
                        </div>
                    </div>
                </div>

                <div class="pb-faq-item">
                    <button class="pb-faq-question" type="button" aria-expanded="false">
                        <span>Which brands are reliable?</span>
                        <span class="pb-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="pb-faq-answer">
                        <div class="pb-faq-answer-inner">
                            <p>Reliable brands include <span>Anker, Xiaomi, Baseus, Samsung, and UGREEN</span> due to better battery protection, durability, and charging consistency.</p>
                        </div>
                    </div>
                </div>

                <div class="pb-faq-item">
                    <button class="pb-faq-question" type="button" aria-expanded="false">
                        <span>Are power banks safe for smartphones?</span>
                        <span class="pb-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="pb-faq-answer">
                        <div class="pb-faq-answer-inner">
                            <p>Yes, quality power banks are safe if they include standard protections against overcharging, overheating, and short-circuiting, and are used with genuine cables.</p>
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
      "name": "What is the price range of power banks in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Power banks in Pakistan generally range from Rs. 2,500 to Rs. 20,000+, depending on capacity, charging speed, and brand quality."
      }
    },
    {
      "@type": "Question",
      "name": "Which power bank capacity is best?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "For most users, 10,000mAh to 20,000mAh is the best balance between portability and backup performance for regular smartphone use."
      }
    },
    {
      "@type": "Question",
      "name": "Do power banks support fast charging?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, many modern power banks support fast charging protocols such as PD and QC, provided your phone and cable also support them."
      }
    },
    {
      "@type": "Question",
      "name": "Which brands are reliable?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Reliable brands include Anker, Xiaomi, Baseus, Samsung, and UGREEN due to better battery protection, durability, and charging consistency."
      }
    },
    {
      "@type": "Question",
      "name": "Are power banks safe for smartphones?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, quality power banks are safe if they include standard protections against overcharging, overheating, and short-circuiting, and are used with genuine cables."
      }
    }
  ]
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
