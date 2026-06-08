<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

$pageTitle = "Buy Top Bluetooth Speakers Online in Pakistan | Phones Dukan";
$metaDescription = "Shop the best Bluetooth speakers at Phones Dukan with updated prices in Pakistan. Compare compact to premium speakers with stronger bass and longer battery life.";
$metaKeywords = "Bluetooth speaker, bluetooth speaker price in pakistan, best bluetooth speaker, mini bluetooth speaker, bluetooth speaker price, bluetooth speakers in pakistan";
$metaRobots = "index, follow";

$breadcrumbs = SeoHelper::categoryBreadcrumbs('bluetooth-speakers', 'Bluetooth Speakers');

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
            p.product_status,
            p.product_tag,
            pi.image_url,
            b.slug AS brand_slug,
            c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'bluetooth-speakers' AND p.product_status != '0'
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

<section class="bs-hero">
    <div class="bs-container bs-hero-inner">
        <p class="bs-hero-eyebrow">PHONES DUKAN AUDIO COLLECTION</p>
        <h1 class="bs-hero-title"><span>Latest Bluetooth Speakers</span> Prices in Pakistan</h1>
        <p class="bs-hero-sub">Explore the latest Bluetooth speakers in Pakistan with deep bass, portable designs, RGB lighting, waterproof builds, and long battery life from trusted audio brands.</p>
    </div>
</section>

<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<section class="bs-products-section">
    <div class="bs-container">
        <?php if (!empty($products)): ?>
            <div class="bs-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card bs-na-card">
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
            <p class="bs-empty">No products found in this category.</p>
        <?php endif; ?>

        <?php include_once __DIR__ . '/ad/feed2.php'; ?>

        <div class="bs-pagination-wrap">
            <div class="pagination bs-pagination">
                <?php
                $countSql = "SELECT COUNT(*) as total
                             FROM products p
                             JOIN categories c ON p.category_id = c.category_id
                             WHERE c.slug = 'bluetooth-speakers' AND p.product_status != '0'";
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

<section class="bs-content-section">
    <div class="bs-container">
        <div class="bs-content">
            <h2>Bluetooth Speaker Prices in <span>Pakistan</span></h2>
            <p>Bluetooth speaker prices in Pakistan vary based on driver quality, output power, battery size, wireless version, and build durability. Users can choose compact portable speakers or higher-output models for stronger room-filling sound.</p>

            <h2>Affordable vs <span>Premium Speakers</span></h2>
            <p>Affordable speakers are ideal for casual music and everyday use with basic audio output. Premium speakers deliver clearer mids, deeper bass response, better stereo staging, and stronger overall performance at higher volumes.</p>

            <h2>Speaker Power &amp; Sound Guide <span>(Watt, Bass, Stereo)</span></h2>
            <ul>
                <li><strong>Watt Output:</strong> Higher wattage generally gives stronger and louder sound projection.</li>
                <li><strong>Bass Performance:</strong> Larger drivers and tuned passive radiators improve low-frequency depth.</li>
                <li><strong>Stereo Experience:</strong> Dual-driver or TWS pair modes provide better channel separation.</li>
            </ul>

            <h2>Speaker <span>Features</span></h2>
            <p>Top features include waterproof ratings, RGB lighting modes, long battery backup, Bluetooth 5.x stability, AUX/USB playback, and hands-free calling. Choose feature sets according to usage, whether indoor listening or outdoor travel.</p>

            <h2>Bluetooth Speakers <span>Buying Guide</span></h2>
            <p>Before purchasing, check battery capacity, charging time, output power, codec quality, and official warranty. If portability matters, choose compact models with durable build and splash resistance for safer day-to-day use.</p>
        </div>

        <div class="bs-table-section">
            <h2>Top Bluetooth Speakers in Pakistan – <span>Prices &amp; Features</span></h2>
            <div class="bs-table-wrap">
                <table class="bs-table">
                    <thead>
                        <tr>
                            <th>Bluetooth Speaker</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Portable 10W Wireless Speaker</td>
                            <td class="text-right"><span>4,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>20W Deep Bass Bluetooth Speaker</td>
                            <td class="text-right"><span>8,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>RGB Party Speaker with Mic Input</td>
                            <td class="text-right"><span>12,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Waterproof Outdoor Speaker (IPX7)</td>
                            <td class="text-right"><span>10,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Mini Pocket Bluetooth Speaker</td>
                            <td class="text-right"><span>2,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Stereo Dual-Driver Speaker</td>
                            <td class="text-right"><span>14,999 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Premium Metal-Body Wireless Speaker</td>
                            <td class="text-right"><span>17,499 PKR</span></td>
                        </tr>
                        <tr>
                            <td>Long Backup Travel Speaker</td>
                            <td class="text-right"><span>6,999 PKR</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="bs-faq-section">
    <div class="bs-container">
        <div class="bs-faq-wrap">
            <h2>FAQs About <span>Bluetooth Speakers in Pakistan</span></h2>
            <p class="bs-faq-intro">These buyer FAQs help you choose the right wireless speaker for sound quality, portability, and battery performance.</p>

            <div class="bs-faq-list">
                <div class="bs-faq-item">
                    <button class="bs-faq-question" type="button" aria-expanded="false">
                        <span>What is the price of Bluetooth speakers in Pakistan?</span>
                        <span class="bs-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="bs-faq-answer">
                        <div class="bs-faq-answer-inner">
                            <p>Bluetooth speakers in Pakistan are commonly available from around <span>Rs. 2,500</span> to <span>Rs. 40,000+</span> depending on brand and sound output class.</p>
                        </div>
                    </div>
                </div>

                <div class="bs-faq-item">
                    <button class="bs-faq-question" type="button" aria-expanded="false">
                        <span>Which Bluetooth speaker has the best sound quality?</span>
                        <span class="bs-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="bs-faq-answer">
                        <div class="bs-faq-answer-inner">
                            <p>Premium models from brands like <span>JBL, Sony, Anker, and Harman</span> are generally preferred for better clarity, bass control, and stereo balance.</p>
                        </div>
                    </div>
                </div>

                <div class="bs-faq-item">
                    <button class="bs-faq-question" type="button" aria-expanded="false">
                        <span>Are waterproof speakers worth it?</span>
                        <span class="bs-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="bs-faq-answer">
                        <div class="bs-faq-answer-inner">
                            <p>Yes, waterproof speakers are worth buying for travel and outdoor use, as they offer better protection against splashes, rain, and accidental moisture exposure.</p>
                        </div>
                    </div>
                </div>

                <div class="bs-faq-item">
                    <button class="bs-faq-question" type="button" aria-expanded="false">
                        <span>Which brands are best for speakers?</span>
                        <span class="bs-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="bs-faq-answer">
                        <div class="bs-faq-answer-inner">
                            <p>Reliable speaker brands include <span>JBL, Sony, Anker Soundcore, Xiaomi, and Marshall</span>, depending on your preferred sound profile and budget.</p>
                        </div>
                    </div>
                </div>

                <div class="bs-faq-item">
                    <button class="bs-faq-question" type="button" aria-expanded="false">
                        <span>How long does battery last in wireless speakers?</span>
                        <span class="bs-faq-icon" aria-hidden="true"></span>
                    </button>
                    <div class="bs-faq-answer">
                        <div class="bs-faq-answer-inner">
                            <p>Battery timing varies by size and volume level, but many wireless speakers offer around <span>6 to 24 hours</span> of playback per full charge.</p>
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
      "name": "What is the price of Bluetooth speakers in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Bluetooth speakers in Pakistan are commonly available from around Rs. 2,500 to Rs. 40,000+, depending on brand and sound output class."
      }
    },
    {
      "@type": "Question",
      "name": "Which Bluetooth speaker has the best sound quality?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Premium models from brands like JBL, Sony, Anker, and Harman are generally preferred for better clarity, bass control, and stereo balance."
      }
    },
    {
      "@type": "Question",
      "name": "Are waterproof speakers worth it?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, waterproof speakers are worth buying for travel and outdoor use, as they offer better protection against splashes, rain, and accidental moisture exposure."
      }
    },
    {
      "@type": "Question",
      "name": "Which brands are best for speakers?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Reliable speaker brands include JBL, Sony, Anker Soundcore, Xiaomi, and Marshall, depending on your preferred sound profile and budget."
      }
    },
    {
      "@type": "Question",
      "name": "How long does battery last in wireless speakers?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Battery timing varies by size and volume level, but many wireless speakers offer around 6 to 24 hours of playback per full charge."
      }
    }
  ]
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
