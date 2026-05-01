<?php
$pageTitle = "Best Wireless Earbuds Prices in Pakistan | Phones Dukan";
$metaDescription = "Shop premium wireless earbuds at Phones Dukan with updated prices in Pakistan. Compare budget to premium earbuds with ANC, calling, and long battery life.";
$metaRobots = "index, follow";
$metaKeywords = "Wireless Earbuds, best wireless earbuds, wireless earbuds price in pakistan";

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
          WHERE c.slug = 'wireless-earbuds' AND p.product_status != '0'
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
        'product_url' => "/" . htmlspecialchars($product['category_slug']) . "/" . htmlspecialchars($product['brand_slug']) . "/" . htmlspecialchars($product['product_slug']),
        'product_name' => htmlspecialchars($product['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8'),
        'product_image' => !empty($product['image_url'])
            ? htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8')
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

<section class="we-hero">
    <div class="we-container we-hero-inner">
        <p class="we-hero-eyebrow">PHONES DUKAN EARBUDS COLLECTION</p>
        <h1 class="we-hero-title"><span>Wireless Earbuds</span> Prices in Pakistan</h1>
        <p class="we-hero-sub">Find the latest earbuds with clear calling, premium bass, and long battery life. Compare trusted models across every budget range in Pakistan.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="we-products-section">
    <div class="we-container">
        <?php if (!empty($products)): ?>
            <div class="we-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card we-na-card">
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
            <p class="we-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="we-pagination-wrap">
            <div class="pagination we-pagination">
                <?php
                $countSql = "SELECT COUNT(*) as total
                             FROM products p
                             JOIN categories c ON p.category_id = c.category_id
                             WHERE c.slug = 'wireless-earbuds' AND p.product_status != '0'";
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

<section class="we-content-section">
    <div class="we-container">
        <div class="we-content">
            <h2>Wireless Earbuds Pricing in <span>Pakistan</span></h2>
            <p>Wireless earbuds prices in Pakistan vary by sound tuning, battery timing, call quality, chipset performance, and feature depth. Buyers can pick anything from budget daily-use earbuds to premium ANC models with flagship-level audio.</p>

            <h2>Affordable vs <span>Premium Earbuds</span></h2>
            <p>Affordable earbuds usually focus on essential music playback, basic calling, and compact charging cases. Premium earbuds deliver stronger bass response, better microphones, low-latency gaming modes, and active noise cancellation for cleaner listening.</p>

            <h2>Earbuds <span>Price Ranges</span></h2>
            <ul>
                <li><strong>Budget Range (PKR 1,500 – 4,500):</strong> Entry-level Bluetooth earbuds for daily calls and music.</li>
                <li><strong>Mid Range (PKR 4,500 – 12,000):</strong> Better fit, stable connectivity, improved mic performance and battery.</li>
                <li><strong>Premium Range (PKR 12,000 – 45,000+):</strong> Advanced ANC, richer audio detail, and stronger app ecosystem support.</li>
            </ul>

            <h2>Features of <span>Wireless Earbuds</span></h2>
            <p>Most in-demand features include ENC/ANC calling, low-latency game mode, touch controls, IP-rated sweat protection, long backup with case, and quick pairing. For the best ecosystem experience, pair your earbuds with a latest <a href="https://www.phonesdukan.com/mobiles/">mobile phone</a>.</p>

            <h2>Wireless Earbuds <span>Buying Guide</span></h2>
            <p>Before buying, check battery life per charge, codec support, charging port type, replacement policy, and official warranty coverage. If you take frequent calls, prioritize dual-mic ENC models with a secure in-ear fit and stable Bluetooth range.</p>
        </div>

        <div class="we-table-section">
            <h2>Top Wireless Earbuds in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="we-table-wrap">
                <table class="we-table">
                    <thead>
                        <tr>
                            <th>Wireless Earbuds</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>AirPods Pro (2nd Gen)</td>
                            <td class="text-right"><span>74,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Samsung Galaxy Buds2 Pro</td>
                            <td class="text-right"><span>39,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Soundcore Liberty 4 NC</td>
                            <td class="text-right"><span>24,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Redmi Buds 5 Pro</td>
                            <td class="text-right"><span>18,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Realme Buds Air 5 Pro</td>
                            <td class="text-right"><span>16,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Oraimo FreePods Pro</td>
                            <td class="text-right"><span>9,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Ronin R-520 TWS</td>
                            <td class="text-right"><span>5,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>M10 Budget Earbuds</td>
                            <td class="text-right"><span>2,499 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="we-faq-section">
    <div class="we-container">
        <div class="we-faq-wrap">
            <h2>FAQs About <span>Wireless Earbuds Price in Pakistan</span></h2>
            <p class="we-faq-intro">These common questions help buyers choose the right earbuds based on price, performance, and brand reliability.</p>

            <div class="we-faq-list">
                <div class="we-faq-item">
                    <button class="we-faq-question" type="button" aria-expanded="false">
                        <span>What is the starting price of wireless earbuds in Pakistan?</span>
                        <span class="we-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="we-faq-answer">
                        <div class="we-faq-answer-inner">
                            <p>Wireless earbuds in Pakistan start from around <span>Rs. 1,500</span> for basic models and can go much higher for premium ANC earbuds.</p>
                        </div>
                    </div>
                </div>

                <div class="we-faq-item">
                    <button class="we-faq-question" type="button" aria-expanded="false">
                        <span>Which earbuds are best for calling and ANC?</span>
                        <span class="we-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="we-faq-answer">
                        <div class="we-faq-answer-inner">
                            <p>For calling and strong ANC, options from <span>Apple, Samsung, Soundcore, and Realme</span> are popular due to better mic clarity and noise suppression.</p>
                        </div>
                    </div>
                </div>

                <div class="we-faq-item">
                    <button class="we-faq-question" type="button" aria-expanded="false">
                        <span>Do earbuds come with warranty in Pakistan?</span>
                        <span class="we-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="we-faq-answer">
                        <div class="we-faq-answer-inner">
                            <p>Yes, most branded earbuds include official or seller-backed warranty, usually around <span>6 to 12 months</span> depending on brand and retailer.</p>
                        </div>
                    </div>
                </div>

                <div class="we-faq-item">
                    <button class="we-faq-question" type="button" aria-expanded="false">
                        <span>Which brand is best for earbuds?</span>
                        <span class="we-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="we-faq-answer">
                        <div class="we-faq-answer-inner">
                            <p>Top choices include <span>Apple, Samsung, Soundcore, Xiaomi, and Realme</span>. The best pick depends on your budget, device compatibility, and feature preference.</p>
                        </div>
                    </div>
                </div>

                <div class="we-faq-item">
                    <button class="we-faq-question" type="button" aria-expanded="false">
                        <span>How much do good earbuds cost?</span>
                        <span class="we-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="we-faq-answer">
                        <div class="we-faq-answer-inner">
                            <p>Good quality earbuds for daily use and clear calls are commonly found between <span>Rs. 5,000</span> and <span>Rs. 20,000</span> in Pakistan.</p>
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
      "name": "What is the starting price of wireless earbuds in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Wireless earbuds in Pakistan start from around Rs. 1,500 for basic models and can go much higher for premium ANC earbuds."
      }
    },
    {
      "@type": "Question",
      "name": "Which earbuds are best for calling and ANC?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "For calling and strong ANC, options from Apple, Samsung, Soundcore, and Realme are popular due to better mic clarity and noise suppression."
      }
    },
    {
      "@type": "Question",
      "name": "Do earbuds come with warranty in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, most branded earbuds include official or seller-backed warranty, usually around 6 to 12 months depending on brand and retailer."
      }
    },
    {
      "@type": "Question",
      "name": "Which brand is best for earbuds?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Top choices include Apple, Samsung, Soundcore, Xiaomi, and Realme. The best pick depends on your budget, device compatibility, and feature preference."
      }
    },
    {
      "@type": "Question",
      "name": "How much do good earbuds cost?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Good quality earbuds for daily use and clear calls are commonly found between Rs. 5,000 and Rs. 20,000 in Pakistan."
      }
    }
  ]
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
