<?php
$pageTitle = "Smart Watch Price in Pakistan – " . date('F Y') . " | Phones Dukan";
$metaDescription = "Discover updated Smart Watch Price in Pakistan for " . date('F Y') . ". Shop top brands like Apple, Samsung, and Fitbit at competitive rates.";
$metaRobots = "index, follow";
$metaKeywords = "Smart watch, smart watch price in pakistan, smart watch price";

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
          WHERE c.slug = 'smart-watches' AND p.product_status != '0'
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

<section class="sw-hero">
    <div class="sw-container sw-hero-inner">
        <h1 class="sw-hero-title"><span>Smart Watch</span> Prices in Pakistan</h1>
        <p class="sw-hero-sub">Explore the latest smartwatch deals in Pakistan with trusted pricing, premium models, and budget-friendly options for every lifestyle.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="sw-products-section">
    <div class="sw-container">
        <?php if (!empty($products)): ?>
            <div class="sw-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card sw-na-card">
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
            <p class="sw-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="sw-pagination-wrap">
            <div class="pagination sw-pagination">
                <?php
                $countSql = "SELECT COUNT(*) as total
                             FROM products p
                             JOIN categories c ON p.category_id = c.category_id
                             WHERE c.slug = 'smart-watches' AND p.product_status != '0'";
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

<section class="sw-content-section">
    <div class="sw-container">
        <div class="sw-content">
            <h2>Smartwatch Pricing in <span>Pakistan</span></h2>
            <p>The smartwatch price in Pakistan varies by brand, display quality, health tracking accuracy, and ecosystem support. From entry-level fitness watches to advanced flagship wearables, users can choose according to both feature needs and budget goals.</p>

            <h2>Affordable vs <span>Premium</span></h2>
            <p>Affordable smartwatches focus on daily essentials like step tracking, heart rate monitoring, and notifications. Premium models deliver AMOLED displays, stronger build quality, GPS precision, and deeper integration with Android and iOS devices.</p>

            <h2>Smartwatch <span>Price Ranges</span></h2>
            <ul>
                <li><strong>Budget Range (PKR 1,500 – 5,000):</strong> Basic calling, fitness and notification support for day-to-day use.</li>
                <li><strong>Mid Range (PKR 5,000 – 20,000):</strong> Better sensors, battery life, and improved durability for active users.</li>
                <li><strong>Premium Range (PKR 20,000 – 50,000+):</strong> Top-end Apple, Samsung, Garmin, and Fitbit wearables with advanced health features.</li>
            </ul>

            <h2>Top Smartwatch <span>Features</span></h2>
            <p>Popular features in Pakistan include Bluetooth calling, SpO2 monitoring, sleep analysis, multiple sports modes, long battery backup, water resistance, and stylish interchangeable straps. If you want a complete connected setup, pair your watch with a latest <a href="https://www.phonesdukan.com/mobiles/">mobile phone</a> and quality <a href="https://www.phonesdukan.com/wireless-earbuds/">wireless earbuds</a>.</p>

            <h2>Smartwatch <span>Buying Guide</span></h2>
            <p>Before buying, confirm smartphone compatibility, warranty coverage, software update support, and charging cycle expectations. Choose AMOLED/OLED displays for sharper visuals, and prioritize durable water-resistant models if you plan to use your watch for workouts and outdoor activities.</p>
        </div>

        <div class="sw-table-section">
            <h2>Top Smartwatches in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="sw-table-wrap">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th>Smartwatch</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/variety/watch-8-ultra-smart-watch/">Watch 8 Ultra Smart Watch</a></td>
                            <td class="text-right"><span>2,150 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/variety/kw9-max-smart-watch/">KW9 Max Smart Watch</a></td>
                            <td class="text-right"><span>2,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/variety/fendior-s300-ultra-2-7-straps-smart-watch/">Fendior S300 Ultra 2 7 Straps Smart Watch</a></td>
                            <td class="text-right"><span>3,299 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/yolo/yolo-epic-bluetooth-calling-smart-watch/">Yolo Epic Smart Watch</a></td>
                            <td class="text-right"><span>7,199 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/login/logini-lt-w1-luxe-smart-watch/">Logini Lt-w1 Luxe Smart Watch</a></td>
                            <td class="text-right"><span>8,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/login/login-lt-w2-horizon/">Login LT-W2 HORIZON</a></td>
                            <td class="text-right"><span>9,399 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/yolo/yolo-fortuner-pro-calling-watch/">Yolo Fortuner Pro Calling Watch</a></td>
                            <td class="text-right"><span>9,500 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/xinji/xinji-cobee-c1-pros-smart-watch/">Xinji Cobee C1 Pros Smart Watch</a></td>
                            <td class="text-right"><span>10,399 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/zero/zero-revoltt-smart-watch/">Zero Revoltt Smart Watch</a></td>
                            <td class="text-right"><span>10,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td><a href="https://www.phonesdukan.com/smart-watches/yolo/yolo-supreme-bluetooth-calling-watch/">Yolo Supreme Bluetooth Calling Watch</a></td>
                            <td class="text-right"><span>12,499 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="sw-faq-section">
    <div class="sw-container">
        <div class="sw-faq-wrap">
            <h2>FAQs About <span>Smart Watch Price in Pakistan</span></h2>
            <p class="sw-faq-intro">When shopping for a smartwatch in Pakistan, accurate details help you make the right choice. Here are the most common buyer questions.</p>

            <div class="sw-faq-list">
                <div class="sw-faq-item">
                    <button class="sw-faq-question" type="button" aria-expanded="false">
                        <span>What is the starting price of smartwatches in Pakistan?</span>
                        <span class="sw-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="sw-faq-answer">
                        <div class="sw-faq-answer-inner">
                            <p>The starting price of smartwatches in Pakistan is as low as <span>Rs. 1,499</span>, with basic models offering essential features like time display and fitness tracking. Premium models can go up to <span>Rs. 150,000</span> or more.</p>
                        </div>
                    </div>
                </div>

                <div class="sw-faq-item">
                    <button class="sw-faq-question" type="button" aria-expanded="false">
                        <span>Which smartwatch is best for fitness tracking in Pakistan?</span>
                        <span class="sw-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="sw-faq-answer">
                        <div class="sw-faq-answer-inner">
                            <p>For better fitness monitoring, brands such as <span>Fitbit, Garmin, Amazfit, and Huawei</span> are highly recommended. These brands offer detailed health and activity tracking for active individuals.</p>
                        </div>
                    </div>
                </div>

                <div class="sw-faq-item">
                    <button class="sw-faq-question" type="button" aria-expanded="false">
                        <span>Do smartwatches have warranty coverage in Pakistan?</span>
                        <span class="sw-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="sw-faq-answer">
                        <div class="sw-faq-answer-inner">
                            <p>Yes, most smartwatches sold by authorized retailers come with a <span>1-year warranty</span>, covering manufacturing defects. Always check with the seller for specific warranty terms.</p>
                        </div>
                    </div>
                </div>

                <div class="sw-faq-item">
                    <button class="sw-faq-question" type="button" aria-expanded="false">
                        <span>Which brand is good in smartwatches?</span>
                        <span class="sw-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="sw-faq-answer">
                        <div class="sw-faq-answer-inner">
                            <ul>
                                <li><strong>Apple Watches</strong> – Advanced health tracking and premium design.</li>
                                <li><strong>Samsung Galaxy Watch</strong> – Best for Android users.</li>
                                <li><strong>Fitbit &amp; Garmin</strong> – Ideal for fitness-focused users.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="sw-faq-item">
                    <button class="sw-faq-question" type="button" aria-expanded="false">
                        <span>How much does a smartwatch cost in Pakistan?</span>
                        <span class="sw-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="sw-faq-answer">
                        <div class="sw-faq-answer-inner">
                            <p>Smartwatches in Pakistan range between <span>Rs. 1,499</span> to <span>Rs. 150,000</span>, depending on the brand and features.</p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="sw-note"><em>Smartwatch prices and availability are subject to change. Please check official retailers or our website for the latest updates.</em></p>
            <p class="sw-cta-note">Looking for the best smartwatch? Explore the latest smartwatches at <a href="https://www.phonesdukan.com" class="sw-cta-link">Phones Dukan</a> and find the perfect one at unbeatable prices!</p>
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
      "name": "What is the starting price of smartwatches in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The starting price of smartwatches in Pakistan is as low as Rs. 1,499, with basic models offering essential features like time display and fitness tracking. Premium models can go up to Rs. 150,000 or more."
      }
    },
    {
      "@type": "Question",
      "name": "Which smartwatch is best for fitness tracking in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "For better fitness monitoring, brands such as Fitbit, Garmin, Amazfit and Huawei have come highly recommended. All these brands have mechanisms for detailed health and activity tracking for active people."
      }
    },
    {
      "@type": "Question",
      "name": "Do smartwatches have warranty coverage in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, most smartwatches sold by authorized retailers come with a 1-year warranty, covering manufacturing defects. Always check with the seller for specific warranty terms."
      }
    },
    {
      "@type": "Question",
      "name": "Which brand is good in smartwatches?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Popular and reliable smartwatch brands include Apple, Samsung, Garmin, Fitbit, Amazfit, and Huawei. The best brand depends on your requirements: Apple Watches are leading for advanced health tracking and premium design, Samsung Galaxy Watch offers excellent features for Android users, and fitness-focused users often prefer Fitbit or Garmin for their detailed tracking."
      }
    },
    {
      "@type": "Question",
      "name": "How much does a smartwatch cost in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Smartwatches in Pakistan range between Rs. 1,499 to Rs. 150,000 or more, depending on the brand and features. Budget-friendly options like Amazfit or local brands offer basic functionality, while high-end models such as Samsung Galaxy Watches and Apple Watches provide premium features like advanced fitness tracking and cellular connectivity."
      }
    }
  ]
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>