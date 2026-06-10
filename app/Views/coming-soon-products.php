<?php
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

$pageTitle = 'Coming Soon Products in Pakistan | Phones Dukan';
$metaDescription = 'Browse upcoming mobiles, earbuds, speakers, and accessories launching soon at Phones Dukan. View expected launch dates and explore full product details.';
$metaRobots = 'index, follow';
$metaKeywords = 'coming soon products, upcoming mobiles pakistan, phones dukan coming soon';

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die('Database connection error.');
}

$limit = 12;
$paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
$paged = $paged > 0 ? $paged : 1;
$offset = ($paged - 1) * $limit;

$query = "SELECT
            p.product_id,
            p.product_name,
            p.product_slug,
            p.regular_price,
            p.sale_price,
            p.expected_coming_date,
            pi.image_url,
            c.slug AS category_slug,
            b.slug AS brand_slug,
            sc.slug AS subcategory_slug
          FROM products p
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN categories sc ON p.subcategory_id = sc.category_id
          LEFT JOIN brands b ON p.brand_id = b.brand_id
          WHERE (p.product_status = 2 OR p.product_tag LIKE '%coming_soon%')
            AND p.product_status != '0'
          GROUP BY p.product_id
          ORDER BY
            CASE WHEN p.expected_coming_date IS NULL THEN 1 ELSE 0 END,
            p.expected_coming_date ASC,
            p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rawProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
foreach ($rawProducts as $product) {
    $regularPrice = (float) ($product['regular_price'] ?? 0);
    $salePrice = (float) ($product['sale_price'] ?? 0);
    $hasSale = $salePrice > 0 && $regularPrice > 0 && $salePrice < $regularPrice;
    $discountPct = $hasSale ? max(1, (int) round((($regularPrice - $salePrice) / $regularPrice) * 100)) : 0;

    $products[] = [
        'product_id' => (int) ($product['product_id'] ?? 0),
        'product_url' => buildProductPathFromRow($product),
        'product_name' => htmlspecialchars($product['product_name'] ?? 'Unnamed Product', ENT_QUOTES, 'UTF-8'),
        'product_image' => !empty($product['image_url'])
            ? htmlspecialchars(normalizeMediaUrl((string) $product['image_url']), ENT_QUOTES, 'UTF-8')
            : '/public/assets/images/Phones_dukan_favicon.png',
        'regular_price' => $regularPrice,
        'sale_price' => $salePrice,
        'has_sale' => $hasSale,
        'discount_pct' => $discountPct,
        'expected_label' => formatExpectedComingDate($product['expected_coming_date'] ?? null),
        'expected_iso' => getExpectedComingDateCountdownIso($product['expected_coming_date'] ?? null),
    ];
}

$countSql = "SELECT COUNT(DISTINCT p.product_id) AS total
             FROM products p
             WHERE (p.product_status = 2 OR p.product_tag LIKE '%coming_soon%')
               AND p.product_status != '0'";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute();
$totalRows = (int) ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
$totalPages = (int) ceil($totalRows / $limit);
?>

<section class="cs-hero">
    <div class="cs-container cs-hero-inner">
        <p class="cs-hero-eyebrow">PHONES DUKAN UPCOMING LAUNCHES</p>
        <h1 class="cs-hero-title">Coming <span>Soon</span> Products</h1>
        <p class="cs-hero-sub">Explore upcoming mobiles, audio gear, and accessories before they launch. View expected dates, compare prices, and open full specs for every coming soon item.</p>
    </div>
</section>

<section class="cs-products-section">
    <div class="cs-container">
        <?php if (!empty($products)): ?>
            <div class="cs-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="na-card cs-card">
                        <span class="na-badge cs-badge">Coming Soon</span>

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

                        <div class="na-body cs-card-body">
                            <h3 class="na-name">
                                <a href="<?= $product['product_url'] ?>"><?= $product['product_name'] ?></a>
                            </h3>

                            <div class="na-price cs-card-price">
                                <?php if ($product['has_sale']): ?>
                                    <?php if ($product['discount_pct'] > 0): ?>
                                        <span class="cs-discount-tag"><?= $product['discount_pct'] ?>% OFF</span>
                                    <?php endif; ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif ($product['regular_price'] > 0): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($product['expected_iso'])): ?>
                                <div class="cs-card-countdown" data-target="<?= htmlspecialchars($product['expected_iso']) ?>">
                                    <span class="cs-card-countdown__label">Launch Countdown</span>
                                    <div class="cs-card-countdown__grid">
                                        <div class="cs-card-countdown__unit">
                                            <span class="cs-card-countdown__num" data-part="days">00</span>
                                            <span class="cs-card-countdown__lbl">Days</span>
                                        </div>
                                        <div class="cs-card-countdown__unit">
                                            <span class="cs-card-countdown__num" data-part="hours">00</span>
                                            <span class="cs-card-countdown__lbl">Hours</span>
                                        </div>
                                        <div class="cs-card-countdown__unit">
                                            <span class="cs-card-countdown__num" data-part="mins">00</span>
                                            <span class="cs-card-countdown__lbl">Mins</span>
                                        </div>
                                        <div class="cs-card-countdown__unit">
                                            <span class="cs-card-countdown__num" data-part="secs">00</span>
                                            <span class="cs-card-countdown__lbl">Secs</span>
                                        </div>
                                    </div>
                                    <?php if (!empty($product['expected_label'])): ?>
                                        <p class="cs-card-countdown__date">Expected: <?= htmlspecialchars($product['expected_label']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="cs-card-soon" aria-hidden="true">
                                    <span class="cs-card-soon__ring"></span>
                                    <span class="cs-card-soon__ring cs-card-soon__ring--2"></span>
                                    <span class="cs-card-soon__dot"></span>
                                    <span class="cs-card-soon__text">Launching Soon</span>
                                </div>
                            <?php endif; ?>

                            <a href="<?= $product['product_url'] ?>" class="cs-view-btn">Show Details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cs-empty">
                <div class="cs-empty__icon" aria-hidden="true">⏳</div>
                <h2>No coming soon products right now</h2>
                <p>Check back soon for upcoming launches, or browse our latest deals.</p>
                <a href="<?= htmlspecialchars(url('/')) ?>" class="cs-view-btn cs-view-btn--wide">Back to Home</a>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="cs-pagination-wrap">
                <div class="pagination cs-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?paged=<?= $i ?>"
                           class="<?= ($i === $paged) ? 'active' : '' ?>"
                           <?= ($i === $paged) ? "aria-current='page'" : '' ?>>
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($products)): ?>
<script>
(function () {
    function pad(n) { return String(n).padStart(2, '0'); }

    function tickCountdown(el) {
        var targetMs = Date.parse(el.dataset.target || '');
        if (!Number.isFinite(targetMs)) return;

        var parts = {
            days: el.querySelector('[data-part="days"]'),
            hours: el.querySelector('[data-part="hours"]'),
            mins: el.querySelector('[data-part="mins"]'),
            secs: el.querySelector('[data-part="secs"]')
        };

        function update() {
            var diff = Math.max(0, targetMs - Date.now());
            if (diff <= 0) {
                el.classList.add('is-live');
                return;
            }

            var days = Math.floor(diff / 86400000);
            diff %= 86400000;
            var hours = Math.floor(diff / 3600000);
            diff %= 3600000;
            var mins = Math.floor(diff / 60000);
            diff %= 60000;
            var secs = Math.floor(diff / 1000);

            if (parts.days) parts.days.textContent = pad(days);
            if (parts.hours) parts.hours.textContent = pad(hours);
            if (parts.mins) parts.mins.textContent = pad(mins);
            if (parts.secs) parts.secs.textContent = pad(secs);
        }

        update();
        setInterval(update, 1000);
    }

    document.querySelectorAll('.cs-card-countdown[data-target]').forEach(tickCountdown);
})();
</script>
<?php endif; ?>

<?php
$conn = null;
require_once dirname(__DIR__, 2) . '/includes/footer.php';
