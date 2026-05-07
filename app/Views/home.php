<?php
$pageTitle = 'Best Mobile Deals &amp; Prices in Pakistan - Phones Dukan';
$metaDescription = 'Shop top-rated mobiles, smartwatches, and accessories at Phones Dukan – Pakistan’s trusted store. Best prices, quality, and fast delivery. Explore now!';
$metaRobots = 'index, follow';  // Optional; default is good
$metaKeywords = 'Online Shopping in Pakistan, Buy Mobile in Pakistan, Smartwatch Price in Pakistan, Mobile Accessories, Wireless Earbuds, Tablet Price in Pakistan, Mobiles in Pakistan, Best Mobile Price, Phones Dukan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once __DIR__ . '/../Models/ProductCategoryModel.php';  // Correct path
require_once __DIR__ . '/../Models/PostModel.php';

$productModel = new ProductModel();

// Define specific product IDs
$product_ids = [1, 178, 177, 176, 175, 174, 1819];
$products = $productModel->getProductsByIds($product_ids);

// Remove duplicates
// Remove duplicates AND filter out products with status = 0 or 'out of stock'
$seen_ids = [];
$unique_products = [];

foreach ($products as $product) {
    $product_status = strtolower(trim($product['product_status'] ?? ''));

    // ✅ Skip if out of stock
    if ($product_status === '0' || $product_status === 'out of stock') {
        continue;
    }

    // ✅ Skip duplicates
    if (!in_array($product['product_id'], $seen_ids)) {
        $unique_products[] = $product;
        $seen_ids[] = $product['product_id'];
    }
}

$products = $unique_products;

// Latest blog posts for homepage section (after Shop By Brand)
$postModel = new PostModel();
$latest_posts_raw = $postModel->getPaginatedPosts(6, 0, null);
$latest_posts = [];
$latest_post_ids = [];

foreach ($latest_posts_raw as $post) {
    $post_id = (int)($post['id'] ?? 0);

    if ($post_id > 0 && in_array($post_id, $latest_post_ids, true)) {
        continue;
    }

    if ($post_id > 0) {
        $latest_post_ids[] = $post_id;
    }

    $latest_posts[] = $post;

    if (count($latest_posts) >= 3) {
        break;
    }
}

?>

<section class="pd-hero" aria-label="Featured offers">
    <div class="pd-hero-slider" data-pd-hero-slider tabindex="0">
        <article class="pd-hero-slide is-active" data-pd-slide aria-hidden="false">
            <div class="pd-hero-media">
                <img src="/public/assets/images/hero/hero-slide-1.png" alt="Latest smartphones and accessories at Mobile Island" loading="eager" fetchpriority="high">
            </div>
            <div class="pd-hero-overlay">
                <div class="pd-hero-content">
                    <h1>Pakistan’s Premium Store for<br><span>Smartphones & Smart Gadgets</span></h1>
                    <p>Shop PTA-approved mobiles, wireless earbuds, smart watches, and genuine accessories with fast nationwide delivery and trusted support.</p>
                    <div class="pd-hero-cta-wrap">
                        <a href="/shop" class="pd-hero-btn pd-hero-btn-primary">Shop Now</a>
                        <a href="/mobiles" class="pd-hero-btn pd-hero-btn-secondary">View Mobiles</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="pd-hero-slide" data-pd-slide aria-hidden="true">
            <div class="pd-hero-media">
                <img src="/public/assets/images/hero/hero-slide-2.png" alt="Hot discounts on wireless earbuds and smart gadgets" loading="lazy">
            </div>
            <div class="pd-hero-overlay">
                <div class="pd-hero-content">
                    <h2>Authentic Tech, <br>Fast Delivery,<br><span>Trusted Warranty</span></h2>
                    <p>Get genuine mobile phones and accessories with secure ordering, replacement support, and reliable delivery across Pakistan.</p>
                    <div class="pd-hero-cta-wrap">
                        <a href="/smart-watches" class="pd-hero-btn pd-hero-btn-primary">Shop Watches</a>
                        <a href="/wireless-earbuds" class="pd-hero-btn pd-hero-btn-secondary">Shop Earbuds</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="pd-hero-slide" data-pd-slide aria-hidden="true">
            <div class="pd-hero-media">
                <img src="/public/assets/images/hero/hero-slide-3.png" alt="Fast delivery and warranty by Mobile Island" loading="lazy">
            </div>
            <div class="pd-hero-overlay">
                <div class="pd-hero-content">
                    <h2>Upgrade Your Everyday <br><span>Tech Experience</span></h2>
                    <p>Discover premium earbuds, smart watches, chargers, power banks, and mobile accessories built for modern lifestyles.</p>
                    <div class="pd-hero-cta-wrap">
                        <a href="/contact-us/" class="pd-hero-btn pd-hero-btn-primary">Contact Us</a>
                        <a href="/return-policy/" class="pd-hero-btn pd-hero-btn-secondary">Return Policy</a>
                    </div>
                </div>
            </div>
        </article>

        <div class="pd-hero-dots" role="tablist" aria-label="Hero slide navigation">
            <button class="pd-hero-dot is-active" type="button" role="tab" aria-label="Slide 1" aria-selected="true" data-pd-hero-dot="0"></button>
            <button class="pd-hero-dot" type="button" role="tab" aria-label="Slide 2" aria-selected="false" data-pd-hero-dot="1"></button>
            <button class="pd-hero-dot" type="button" role="tab" aria-label="Slide 3" aria-selected="false" data-pd-hero-dot="2"></button>
        </div>
    </div>
</section>

<!-- <div class="advantages-section">
    <div class="advantages-container">
        <div class="advantage-item">
            <img src="/public/assets/images/customer-service.webp" alt="Customer Service">
            <p>Customer Service</p>
        </div>
        <div class="advantage-item">
            <img src="/public/assets/images/free-shipping.webp" alt="Fast Shipping">
            <p>Fast Shipping</p>
        </div>
        <div class="advantage-item">
            <img src="/public/assets/images/guarantee.webp" alt="Guarantee">
            <p>Guarantee</p>
        </div>
        <div class="advantage-item">
            <img src="/public/assets/images/trustpilot.webp" alt="trustpilot Payment">
            <p>Rated Excellent on Trustpilot!</p>
        </div>
        <div class="advantage-item">
            <img src="/public/assets/images/return.webp" alt="warranty Payment">
            <p>7 Day Easy Return</p>
        </div>
    </div>
</div> -->

<section class="cat-section">
    <div class="cat-inner">
        <div class="cat-header">
            <h2 class="cat-title">Discover Mobile Accessories & <span>Gadgets</span></h2>
        </div>
        <div class="cat-track-wrap">
            <button class="cat-arrow cat-prev" aria-label="Previous categories">&#8249;</button>
            <div class="cat-viewport">
            <div class="cat-track" id="cat-track">
                <a href="/mobiles" class="cat-card" style="--cat-bg:#dbeafe;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/mobile_category.webp" alt="Mobiles">
                    </div>
                    <p class="cat-label">Mobiles</p>
                </a>
                <a href="/smart-watches" class="cat-card" style="--cat-bg:#d1fae5;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/smartwatches_category.webp" alt="Smartwatches">
                    </div>
                    <p class="cat-label">Smart Watches</p>
                </a>
                <a href="/wireless-earbuds" class="cat-card" style="--cat-bg:#ede9fe;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/wireless_earbuds.webp" alt="Wireless Earbuds">
                    </div>
                    <p class="cat-label">Wireless Earbuds</p>
                </a>
                <a href="/mobile-accessories" class="cat-card" style="--cat-bg:#fde8d8;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/mobile_accessories.webp" alt="Accessories">
                    </div>
                    <p class="cat-label">Mobile Accessories</p>
                </a>
                <a href="/power-banks" class="cat-card" style="--cat-bg:#fef9c3;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/power-banks.webp" alt="Power Banks">
                    </div>
                    <p class="cat-label">Fast Charging Power Banks</p>
                </a>
                <a href="/bluetooth-speakers" class="cat-card" style="--cat-bg:#fce7f3;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/bluetooth-speakers.webp" alt="Bluetooth Speakers">
                    </div>
                    <p class="cat-label">Portable Bluetooth Speakers</p>
                </a>
            </div>
            </div><!-- /cat-viewport -->
            <button class="cat-arrow cat-next" aria-label="Next categories">&#8250;</button>
        </div>
    </div>
</section>
<script>
(function () {
    var viewport = document.querySelector('.cat-viewport');
    var track = document.getElementById('cat-track');
    var prevBtn = document.querySelector('.cat-prev');
    var nextBtn = document.querySelector('.cat-next');
    if (!viewport || !track) return;

    /* ── 1) Clone cards for infinite loop ── */
    var originals = Array.from(track.querySelectorAll('.cat-card'));
    if (!originals.length) return;
    originals.forEach(function (card) {
        var clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.tabIndex = -1;
        track.appendChild(clone);
    });

    function getCards() { return track.querySelectorAll('.cat-card'); }

    function getStepWidth() {
        var card = getCards()[0];
        if (!card) return 240;
        var styles = window.getComputedStyle(track);
        var gap = parseInt(styles.columnGap || styles.gap || '0', 10) || 0;
        return card.offsetWidth + gap;
    }

    function getOrigWidth() {
        return getStepWidth() * originals.length;
    }

    /* Start in the middle so user can go left/right infinitely */
    requestAnimationFrame(function () {
        viewport.scrollLeft = getOrigWidth();
    });

    /* ── 2) Seamless reset when entering clone zones ── */
    var resetting = false;
    viewport.addEventListener('scroll', function () {
        if (resetting) return;
        var origWidth = getOrigWidth();
        if (!origWidth) return;

        if (viewport.scrollLeft >= origWidth * 2) {
            resetting = true;
            viewport.style.scrollBehavior = 'auto';
            viewport.scrollLeft -= origWidth;
            requestAnimationFrame(function () {
                viewport.style.scrollBehavior = '';
                resetting = false;
            });
        } else if (viewport.scrollLeft <= 0) {
            resetting = true;
            viewport.style.scrollBehavior = 'auto';
            viewport.scrollLeft += origWidth;
            requestAnimationFrame(function () {
                viewport.style.scrollBehavior = '';
                resetting = false;
            });
        }
    }, { passive: true });

    function scrollNext() {
        viewport.scrollBy({ left: getStepWidth(), behavior: 'smooth' });
    }

    function scrollPrev() {
        viewport.scrollBy({ left: -getStepWidth(), behavior: 'smooth' });
    }

    /* ── 3) Continuous auto-scroll (marquee-style) ── */
    var paused = false;
    var resumeTimer = null;
    var lastT = 0;
    var speedPxPerSec = 34; /* subtle continuous speed */

    function wrapIfNeeded() {
        var origWidth = getOrigWidth();
        if (!origWidth) return;
        if (viewport.scrollLeft >= origWidth * 2) viewport.scrollLeft -= origWidth;
        if (viewport.scrollLeft <= 0) viewport.scrollLeft += origWidth;
    }

    function tick(t) {
        if (!lastT) lastT = t;
        var dt = Math.min(48, t - lastT); /* cap to reduce jumps on tab switch */
        lastT = t;

        if (!paused) {
            viewport.scrollLeft += (speedPxPerSec * dt) / 1000;
            wrapIfNeeded();
        }
        requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);

    function pauseThenResume() {
        paused = true;
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(function () { paused = false; }, 4200);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            scrollNext();
            pauseThenResume();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            scrollPrev();
            pauseThenResume();
        });
    }

    viewport.addEventListener('mouseenter', function () { paused = true; });
    viewport.addEventListener('mouseleave', function () { paused = false; });
    viewport.addEventListener('touchstart', pauseThenResume, { passive: true });
    viewport.addEventListener('wheel', pauseThenResume, { passive: true });
})();
</script>

<section class="na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">New Tech <span>Arrivals</span></h2>
            <a href="/shop" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php if (!empty($products)): ?>
                <?php foreach (array_slice($products, 0, 4) as $product): ?>
                <?php
                $product_name   = !empty($product['product_name'])   ? htmlspecialchars($product['product_name'])   : 'Unnamed Product';
                $category_slug  = !empty($product['category_slug'])  ? htmlspecialchars($product['category_slug'])  : 'unknown-category';
                $brand_slug     = !empty($product['brand_slug'])     ? htmlspecialchars($product['brand_slug'])     : 'unknown-brand';
                $product_slug   = !empty($product['product_slug'])   ? htmlspecialchars($product['product_slug'])   : 'unknown-product';
                $product_image  = !empty($product['image_url']) ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url'])) : '/public/assets/images/Phones_dukan_favicon.png';
                $product_url    = buildProductPath((string)($product['category_slug'] ?? ''), (string)($product['brand_slug'] ?? ''), (string)($product['product_slug'] ?? ''));

                $has_sale  = !empty($product['sale_price']) && !empty($product['regular_price']);
                $unit_price = $has_sale ? $product['sale_price'] : ($product['regular_price'] ?? 0);
                $discount_pct = $has_sale
                    ? max(1, round((($product['regular_price'] - $product['sale_price']) / $product['regular_price']) * 100))
                    : 0;
                ?>

                <div class="na-card">
                    <?php if ($product['is_sold_out']): ?>
                        <span class="na-badge na-badge--sold">Sold Out</span>
                    <?php elseif ($discount_pct > 0): ?>
                        <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                    <?php endif; ?>

                    <a href="<?= $product_url ?>" class="na-img-link">
                        <div class="na-img-box">
                            <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                        </div>
                    </a>

                    <div class="na-body">
                        <h3 class="na-name">
                            <a href="<?= $product_url ?>"><?= $product_name ?></a>
                        </h3>

                        <div class="na-price">
                            <?php if ($has_sale): ?>
                                <span class="na-price--old">Rs.<?= number_format($product['regular_price']) ?></span>
                                <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                            <?php elseif (!empty($product['regular_price'])): ?>
                                <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                            <?php else: ?>
                                <span class="na-price--na">Price N/A</span>
                            <?php endif; ?>
                        </div>

                        <div class="na-actions">
                            <?php if (!$product['is_sold_out']): ?>
                                <button class="na-btn na-btn--cart"
                                    data-product-id="<?= (int)$product['product_id'] ?>"
                                    data-unit-price="<?= (float)$unit_price ?>">
                                    Add to Cart
                                </button>
                                <button class="na-btn na-btn--buy buy-button"
                                    data-product-id="<?= (int)$product['product_id'] ?>"
                                    data-unit-price="<?= (float)$unit_price ?>">
                                    Buy Now
                                </button>
                            <?php else: ?>
                                <span class="na-btn na-btn--soldout">Sold Out</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column:1/-1;text-align:center;color:#888;">No products found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Trust strip -->
<div class="na-trust">
    <div class="na-trust-inner">
        <div class="na-trust-item">
            <svg class="na-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <div class="na-trust-text">
                <strong>FASTEST DELIVERY</strong>
                <span>Delivery in 24/H</span>
            </div>
        </div>
        <div class="na-trust-item">
            <svg class="na-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
            <div class="na-trust-text">
                <strong>24 HOURS RETURN</strong>
                <span>100% money-back guarantee</span>
            </div>
        </div>
        <div class="na-trust-item">
            <svg class="na-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <div class="na-trust-text">
                <strong>SECURE PAYMENT</strong>
                <span>Your money is safe</span>
            </div>
        </div>
        <div class="na-trust-item">
            <svg class="na-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.8 19.8 0 0 1 1.61 3.37 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l.86-.86a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <div class="na-trust-text">
                <strong>SUPPORT 24/7</strong>
                <span>Live contact/message</span>
            </div>
        </div>
    </div>
</div>

<!-- <div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Coming Soon Products</span></h2>
        <a href="/coming-soon-products" class="view-all-btn">Explore All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php

    // Fetch Coming Soon products
    $products = $productModel->getComingSoonProducts(12);

    if (!empty($products)):
        foreach ($products as $product):
            $product_url = buildProductPath(
                (string)($product['category_slug'] ?? ''),
                (string)($product['brand_slug'] ?? ''),
                (string)($product['product_slug'] ?? '')
            );
            $product_price = ($product['sale_price']) ? '<del>' . htmlspecialchars($product['regular_price']) . '</del> ' . htmlspecialchars($product['sale_price']) : htmlspecialchars($product['regular_price']);
            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <div class="coming-soon-badge">
                        <span>Coming Soon</span>
                    </div>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars(normalizeMediaUrl((string) $product['image_url'])); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice">
                    <span class="product-price"><?php echo $product_price; ?></span>
                </div>
            </div>
        <?php
        endforeach;
    else:
        echo '<p>No coming soon products found.</p>';
    endif;
    ?>
</div>

        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div> -->

<div class="s-banner s-banner--full sw-home-banner">
<a href="/smart-watches">
         <img src="/public/assets/images/smart-watch-banner.png" alt="smart-watches_banner" class="banner-image">
</a>
</div>

<section class="na-section sw-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Latest <span>Smart Watches</span></h2>
            <a href="/smart-watches" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            // Fetch Smart Watches products (only category_id = 11)
            $products = $productModel->getSmartWatches(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_name = !empty($product['product_name'])
                        ? htmlspecialchars($product['product_name'])
                        : 'Unnamed Product';

                    $brand_slug = !empty($product['brand_slug'])
                        ? htmlspecialchars($product['brand_slug'])
                        : 'unknown-brand';

                    $product_slug = !empty($product['product_slug'])
                        ? htmlspecialchars($product['product_slug'])
                        : 'unknown-product';

                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';

                    $product_url = buildProductPath('smart-watches', (string)($product['brand_slug'] ?? ''), (string)($product['product_slug'] ?? ''));

                    $has_sale = !empty($product['sale_price']) && !empty($product['regular_price']);

                    $unit_price = $has_sale
                        ? (float)$product['sale_price']
                        : (float)($product['regular_price'] ?? 0);

                    $discount_pct = ($has_sale && !empty($product['regular_price']) && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;

                    $is_sold_out = (isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0)
                        || (!empty($product['is_sold_out']));
                    ?>

                    <div class="na-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs.<?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No smartwatches found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>

<div class="s-banner s-banner--full earbuds-home-banner">
<a href="/wireless-earbuds">
         <img src="/public/assets/images/wireless_earbuds_banner.png" alt="wireless_earbuds_banner" class="banner-image">
</a>
</div>

<section class="na-section earbuds-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Latest <span>Wireless Earbuds</span></h2>
            <a href="/wireless-earbuds" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            // Fetch products from category 13 (limit to 4)
            $products = $productModel->getCategory13Products(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_name = !empty($product['product_name'])
                        ? htmlspecialchars($product['product_name'])
                        : 'Unnamed Product';

                    $category_slug = !empty($product['category_slug'])
                        ? htmlspecialchars($product['category_slug'])
                        : 'unknown-category';

                    $brand_slug = !empty($product['brand_slug'])
                        ? htmlspecialchars($product['brand_slug'])
                        : 'unknown-brand';

                    $product_slug = !empty($product['product_slug'])
                        ? htmlspecialchars($product['product_slug'])
                        : 'unknown-product';

                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';

                    $product_url = buildProductPath((string)($product['category_slug'] ?? ''), (string)($product['brand_slug'] ?? ''), (string)($product['product_slug'] ?? ''));

                    $has_sale = !empty($product['sale_price']) && !empty($product['regular_price']);

                    $unit_price = $has_sale
                        ? (float)$product['sale_price']
                        : (float)($product['regular_price'] ?? 0);

                    $discount_pct = ($has_sale && !empty($product['regular_price']) && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;

                    $is_sold_out = (isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0)
                        || (!empty($product['is_sold_out']));
                    ?>

                    <div class="na-card eb-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>
                        
                        <div class="eb-feature-strip">Upto 6 Hrs | High Gloss Finish</div>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>
                            
                            <div class="eb-colors">
                                <span class="eb-color-label">Colors:</span>
                                <span class="eb-color-circle" style="background: url('/public/assets/images/eb-white.png') center/cover, #f0f0f0;"></span>
                                <span class="eb-color-circle" style="background: url('/public/assets/images/eb-black.png') center/cover, #222;"></span>
                            </div>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs.<?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions eb-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No earbuds found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>

<!-- CATEGORY IMAGE STRIP -->
<section class="eb-category-strip">
    <div class="na-inner">
        <div class="eb-cat-grid">
            <a href="/bluetooth-headphones" class="eb-cat-item">
                <img src="/public/assets/images/Headphones.png" alt="Headphones">
                <span class="eb-cat-label">Headphones</span>
            </a>
            <a href="/smart-watches" class="eb-cat-item">
                <img src="/public/assets/images/Smartwatch.png" alt="Smartwatch">
                <span class="eb-cat-label">Smartwatch</span>
            </a>
            <a href="/wireless-earbuds" class="eb-cat-item">
                <img src="/public/assets/images/Earbuds.png" alt="Earbuds">
                <span class="eb-cat-label">Earbuds</span>
            </a>
            <a href="/power-banks" class="eb-cat-item">
                <img src="/public/assets/images/Powerbanks.png" alt="Powerbanks">
                <span class="eb-cat-label">Powerbanks</span>
            </a>
            <a href="/mobiles" class="eb-cat-item">
                <img src="/public/assets/images/Mobiles.png" alt="Mobiles">
                <span class="eb-cat-label">Mobiles</span>
            </a>
            <a href="/bluetooth-speakers" class="eb-cat-item">
                <img src="/public/assets/images/speakers.png" alt="speakers">
                <span class="eb-cat-label">speakers</span>
            </a>
        </div>
    </div>
</section>

<div class="s-banner s-banner--full">
<a href="/mobiles">
    <img src="/public/assets/images/mobiles_banner.png" alt="mobiles" class="banner-image">
</a>
</div>

<section class="na-section mob-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Latest <span>Smartphones</span></h2>
            <a href="/mobiles" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            $products = $productModel->getCategory2Products(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_url = buildProductPath(
                        (string)($product['category_slug'] ?? ''),
                        (string)($product['brand_slug'] ?? ''),
                        (string)($product['product_slug'] ?? '')
                    );

                    $has_sale    = !empty($product['sale_price']) && !empty($product['regular_price']);
                    $unit_price  = $has_sale ? (float)$product['sale_price'] : (float)($product['regular_price'] ?? 0);
                    $discount_pct = ($has_sale && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;
                    $is_sold_out  = isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0;
                    $product_name  = htmlspecialchars($product['product_name'] ?? 'Unnamed Product');
                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';
                    ?>

                    <div class="na-card mob-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="mob-feature-strip">Premium Specs | Best Value</div>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No mobiles found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>

<!-- HEADPHONE PROMO STRIP -->
<div class="mob-promo-strip">
    <div class="na-inner">
        <div class="mob-promo-grid">
            <a href="/bluetooth-headphones" class="mob-promo-item mob-promo-item--no-action" onclick="event.preventDefault(); return false;" aria-label="Headphone promo preview">
                <img src="/public/assets/images/Headphone_L29.png" alt="L-292 Velora Pro Headphones" loading="lazy">
            </a>
            <a href="/bluetooth-headphones" class="mob-promo-item mob-promo-item--no-action" onclick="event.preventDefault(); return false;" aria-label="Headphone promo preview">
                <img src="/public/assets/images/Headphone_L29.png" alt="L-292 Velora Pro Headphones" loading="lazy">
            </a>
            <a href="/bluetooth-headphones" class="mob-promo-item mob-promo-item--no-action" onclick="event.preventDefault(); return false;" aria-label="Headphone promo preview">
                <img src="/public/assets/images/Headphone_L29.png" alt="L-292 Velora Pro Headphones" loading="lazy">
            </a>
        </div>
    </div>
</div>

<div class="s-banner s-banner--full">
<a href="/mobile-accessories">
    <img src="/public/assets/images/mobiles_accessories_banner.png" alt="mobiles_accessories_banner" class="banner-image">
</a>
</div>

<section class="na-section acc-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Latest <span>Mobile Accessories</span></h2>
            <a href="/mobile-accessories" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            $products = $productModel->getCategory9Products(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_url = buildProductPath(
                        (string)($product['category_slug'] ?? ''),
                        (string)($product['brand_slug'] ?? ''),
                        (string)($product['product_slug'] ?? '')
                    );

                    $has_sale    = !empty($product['sale_price']) && !empty($product['regular_price']);
                    $unit_price  = $has_sale ? (float)$product['sale_price'] : (float)($product['regular_price'] ?? 0);
                    $discount_pct = ($has_sale && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;
                    $is_sold_out  = isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0;
                    $product_name  = htmlspecialchars($product['product_name'] ?? 'Unnamed Product');
                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';
                    ?>

                    <div class="na-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No accessories found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>
<?php include_once __DIR__ . '/ad/feed2.php'; ?>

<div class="s-banner s-banner--full">
<a href="/power-banks">
    <img src="/public/assets/images/powerbanks_banner.png" alt="power_banks_banner" class="banner-image">
</a>
</div>

<section class="na-section pb-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Fast Charging <span>Power Banks</span></h2>
            <a href="/power-banks" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            $products = $productModel->getCategory10Products(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_url = buildProductPath(
                        (string)($product['category_slug'] ?? ''),
                        (string)($product['brand_slug'] ?? ''),
                        (string)($product['product_slug'] ?? '')
                    );

                    $has_sale    = !empty($product['sale_price']) && !empty($product['regular_price']);
                    $unit_price  = $has_sale ? (float)$product['sale_price'] : (float)($product['regular_price'] ?? 0);
                    $discount_pct = ($has_sale && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;
                    $is_sold_out  = isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0;
                    $product_name  = htmlspecialchars($product['product_name'] ?? 'Unnamed Product');
                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';
                    ?>

                    <div class="na-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No power banks found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>

<div class="s-banner s-banner--full">
<a href="/bluetooth-speakers">
    <img src="/public/assets/images/bluetooth_speakers_banner.png" alt="bluetooth-speakers_banner" class="banner-image">
</a>
</div>

<section class="na-section spk-na-section">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Portable <span>Bluetooth Speakers</span></h2>
            <a href="/bluetooth-speakers" class="na-view-all">Explore All</a>
        </div>

        <div class="na-grid" data-carousel>
            <?php
            $products = $productModel->getCategory4Products(20);

            if (!empty($products)):
                foreach ($products as $product):
                    $product_url = buildProductPath(
                        (string)($product['category_slug'] ?? ''),
                        (string)($product['brand_slug'] ?? ''),
                        (string)($product['product_slug'] ?? '')
                    );

                    $has_sale    = !empty($product['sale_price']) && !empty($product['regular_price']);
                    $unit_price  = $has_sale ? (float)$product['sale_price'] : (float)($product['regular_price'] ?? 0);
                    $discount_pct = ($has_sale && (float)$product['regular_price'] > 0)
                        ? max(1, round((((float)$product['regular_price'] - (float)$product['sale_price']) / (float)$product['regular_price']) * 100))
                        : 0;
                    $is_sold_out  = isset($product['stock_quantity']) && (int)$product['stock_quantity'] === 0;
                    $product_name  = htmlspecialchars($product['product_name'] ?? 'Unnamed Product');
                    $product_image = !empty($product['image_url'])
                        ? htmlspecialchars(normalizeMediaUrl((string)$product['image_url']))
                        : '/public/assets/images/Phones_dukan_favicon.png';
                    ?>

                    <div class="na-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product_image ?>" alt="<?= $product_name ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= $product_name ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs.<?= number_format($product['sale_price']) ?></span>
                                <?php elseif (!empty($product['regular_price'])): ?>
                                    <span class="na-price--new">Rs.<?= number_format($product['regular_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out): ?>
                                    <button class="na-btn na-btn--cart"
                                        data-product-id="<?= (int)$product['product_id'] ?>"
                                        data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy buy-button"
                                        data-product-id="<?= (int)$product['product_id'] ?>">
                                        Buy Now
                                    </button>
                                <?php else: ?>
                                    <span class="na-btn na-btn--soldout">Sold Out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                echo '<p style="grid-column:1/-1;text-align:center;color:#888;">No speakers found.</p>';
            endif;
            ?>
        </div>
    </div>
</section>


<section id="shopByUniqueBrands" class="shopByUniqueBrandsSection pd-bottom">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Shop Top Mobile <span>Brands</span></h2>
        </div>
    </div>
    <div class="brand-marquee-outer">
        <div class="brand-marquee-track" id="brandMarqueeTrack">
            <!-- Set 1 — real items -->
            <a href="/mobiles/samsung" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/samsung_logo.webp" alt="Samsung" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/infinix/" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/infinix_logo.webp" alt="Infinix" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/oppo" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/oppo_logo.webp" alt="Oppo" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/xiaomi" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/xiaomi_logo.webp" alt="Xiaomi" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/vivo" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/vivo_logo.webp" alt="Vivo" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/tecno" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/tecno_logo.webp" alt="Tecno" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/realme" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/realme_logo.webp" alt="Realme" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/apple" class="brandItem"><div class="brandLogo"><img src="/public/assets/images/apple_logo.webp" alt="Apple" draggable="false" loading="lazy"></div></a>
            <!-- Set 2 — duplicate for seamless loop, hidden from assistive tech -->
            <a href="/mobiles/samsung" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/samsung_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/infinix/" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/infinix_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/oppo" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/oppo_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/xiaomi" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/xiaomi_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/vivo" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/vivo_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/tecno" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/tecno_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/realme" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/realme_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
            <a href="/mobiles/apple" class="brandItem" aria-hidden="true" tabindex="-1"><div class="brandLogo"><img src="/public/assets/images/apple_logo.webp" alt="" draggable="false" loading="lazy"></div></a>
        </div>
    </div>
</section>

<script>
(function () {
    var track = document.getElementById('brandMarqueeTrack');
    if (!track) return;
    var outer = track.parentElement;

    var pos      = 0;
    var speed    = 0.55;       /* px per frame — adjust for faster/slower */
    var dragging = false;
    var lastX    = 0;
    var paused   = false;

    function hw() { return track.scrollWidth / 2; }

    function clamp(p) {
        var h = hw();
        if (p <= -h) p += h;
        if (p >   0) p -= h;
        return p;
    }

    function tick() {
        if (!dragging && !paused) pos -= speed;
        pos = clamp(pos);
        track.style.transform = 'translateX(' + pos + 'px)';
        requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);

    /* ── Pause on hover ── */
    (outer || track).addEventListener('mouseenter', function () { paused = true; });
    (outer || track).addEventListener('mouseleave', function () { paused = false; });

    /* ── Mouse drag ── */
    track.addEventListener('mousedown', function (e) {
        dragging = true;  lastX = e.clientX;
        track.style.cursor = 'grabbing';
        e.preventDefault();
    });
    document.addEventListener('mousemove', function (e) {
        if (!dragging) return;
        pos += e.clientX - lastX;  lastX = e.clientX;
    });
    document.addEventListener('mouseup', function () {
        dragging = false;  track.style.cursor = 'grab';
    });

    /* ── Touch swipe ── */
    track.addEventListener('touchstart', function (e) {
        dragging = true;  lastX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchmove', function (e) {
        if (!dragging) return;
        pos += e.touches[0].clientX - lastX;  lastX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchend', function () { dragging = false; });
})();
</script>

<section class="na-section home-latest-blogs">
    <div class="na-inner">
        <div class="na-header">
            <h2 class="na-title">Latest Tech Blogs & <span>Mobile Updates</span></h2>
            <a href="/blog" class="na-view-all">Explore All</a>
        </div>

        <div class="home-blog-grid" data-carousel>
            <?php if (!empty($latest_posts)): ?>
                <?php foreach ($latest_posts as $post): ?>
                    <?php
                    $post_title = htmlspecialchars($post['title'] ?? 'Untitled Blog');
                    $post_slug = htmlspecialchars($post['slug'] ?? '');
                    $post_category_slug = htmlspecialchars($post['category_slug'] ?? '');
                    $post_category_name = htmlspecialchars($post['category_name'] ?? 'General');
                    $post_image = !empty($post['image_url'])
                        ? htmlspecialchars($post['image_url'])
                        : '/public/assets/images/Phones_dukan_favicon.png';

                    $post_url = ($post_category_slug !== '' && $post_slug !== '')
                        ? '/blog/' . $post_category_slug . '/' . $post_slug
                        : '/blog';

                    $post_excerpt_raw = trim((string)($post['excerpt'] ?? ''));
                    if ($post_excerpt_raw === '') {
                        $post_excerpt_raw = trim((string)($post['content'] ?? ''));
                    }

                    $post_excerpt_text = trim(preg_replace('/\s+/', ' ', strip_tags($post_excerpt_raw)));
                    if (strlen($post_excerpt_text) > 140) {
                        $post_excerpt_text = substr($post_excerpt_text, 0, 137) . '...';
                    }

                    $published_ts = !empty($post['published_at']) ? strtotime($post['published_at']) : false;
                    $published_date = $published_ts ? date('M d, Y', $published_ts) : '';
                    ?>

                    <article class="home-blog-card">
                        <a href="<?= $post_url ?>" class="home-blog-image-link">
                            <div class="home-blog-image-wrap">
                                <img src="<?= $post_image ?>" alt="<?= $post_title ?>" loading="lazy">
                            </div>
                        </a>

                        <div class="home-blog-body">
                            <div class="home-blog-meta">
                                <span class="home-blog-meta-item">
                                    <svg class="home-blog-meta-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4" stroke="#f7d117" stroke-width="2.2"/><path d="M4 20c0-3.866 3.582-7 8-7s8 3.134 8 7" stroke="#f7d117" stroke-width="2.2" stroke-linecap="round"/></svg>
                                    <?= $post_category_name ?>
                                </span>
                                <?php if (!empty($published_date)): ?>
                                <span class="home-blog-meta-item">
                                    <svg class="home-blog-meta-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#f7d117" stroke-width="2.2"/><path d="M3 10h18M8 2v4M16 2v4" stroke="#f7d117" stroke-width="2.2" stroke-linecap="round"/></svg>
                                    <?= htmlspecialchars($published_date) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="home-blog-title">
                                <a href="<?= $post_url ?>"><?= $post_title ?></a>
                            </h3>

                            <p class="home-blog-excerpt">
                                <?= htmlspecialchars($post_excerpt_text !== '' ? $post_excerpt_text : 'Read this post to learn more insights and updates from Phones Dukan.') ?>
                            </p>

                            <a href="<?= $post_url ?>" class="home-blog-readmore">READ MORE <span class="home-blog-readmore-arrow">&rarr;</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="home-blog-empty">No latest blogs found right now.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="testimonials">
    <div class="testimonials-inner">
        <div class="category-header testimonials-header">
            <h2>Customer Reviews & <span>Testimonials</span></h2>
        </div>
        <div class="testimonials-carousel">
            <div class="testimonial-box">
                <div class="testimonial-rating">
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                </div>
                <p>Very responsive and professional. I recommend this place! The prices are great. Fast delivery. Thank you Phones Dukan</p>
                <h3 class="testimonial-name">Muniza Saleh</h3>
            </div>
            <div class="testimonial-box">
                <div class="testimonial-rating">
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star star--empty">&#9734;</span>
                </div>
                <p>Great place! I managed a remote purchase for a local. Very responsive and professional. I recommend this place! The prices are great. Fast delivery.</p>
                <h3 class="testimonial-name">Vanessa</h3>
            </div>
            <div class="testimonial-box">
                <div class="testimonial-rating">
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                </div>
                <p>Their prices are low compared to the market. Additionally, they offer the best after-sale customer support. Recommended.</p>
                <h3 class="testimonial-name">Furqan Haider</h3>
            </div>
            <div class="testimonial-box">
                <div class="testimonial-rating">
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star star--empty">&#9734;</span>
                </div>
                <p>I like this place. It's not only a walk-in customer shop, but also an online store. That's why I give them 5 stars!</p>
                <h3 class="testimonial-name">Shadab Hussain</h3>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    var carousel = document.querySelector('.testimonials-carousel');
    if (!carousel) return;

    /* ── 1. Clone cards so the loop appears infinite ───────────────────
       We append duplicates of every card. The scroll track now holds
       [orig1, orig2, orig3, orig4, clone1, clone2, clone3, clone4].
       When we reach the clone zone we instantly teleport back to the
       identical-looking original zone — the user never sees a jump.   */
    var originals = Array.from(carousel.querySelectorAll('.testimonial-box'));
    originals.forEach(function (card) {
        var clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        carousel.appendChild(clone);
    });

    /* ── 2. Step width = one card width + gap ───────────────────────── */
    function getStepWidth() {
        var box = carousel.querySelector('.testimonial-box');
        if (!box) return 300;
        var gap = parseInt(getComputedStyle(carousel).columnGap) || 24;
        return box.offsetWidth + gap;
    }

    /* ── 3. Seamless reset when scrolled into the clone zone ────────── */
    var resetting = false;
    carousel.addEventListener('scroll', function () {
        if (resetting) return;
        var origWidth = getStepWidth() * originals.length;
        if (carousel.scrollLeft >= origWidth) {
            resetting = true;
            carousel.style.scrollBehavior = 'auto';  /* instant jump */
            carousel.scrollLeft -= origWidth;         /* same visual  */
            requestAnimationFrame(function () {
                carousel.style.scrollBehavior = '';   /* restore smooth */
                resetting = false;
            });
        }
    }, { passive: true });

    /* ── 4. Autoplay ────────────────────────────────────────────────── */
    var autoTimer  = null;
    var pauseTimer = null;

    function advance() {
        /* CSS scroll-behavior:smooth does the animation for us */
        carousel.scrollLeft += getStepWidth();
    }

    function startAutoplay() {
        clearInterval(autoTimer);
        autoTimer = setInterval(advance, 3500);
    }

    function stopAutoplay() {
        clearInterval(autoTimer);
        autoTimer = null;
    }

    /* Pause when user interacts, then resume after 5 s */
    function pauseThenResume() {
        stopAutoplay();
        clearTimeout(pauseTimer);
        pauseTimer = setTimeout(startAutoplay, 5000);
    }

    /* ── 5. Mouse drag ──────────────────────────────────────────────── */
    var dragging  = false;
    var startX    = 0;
    var startLeft = 0;
    var moved     = false;

    carousel.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        dragging  = true;
        moved     = false;
        startX    = e.clientX;
        startLeft = carousel.scrollLeft;
        carousel.classList.add('is-dragging');
        carousel.style.scrollSnapType = 'none';
        pauseThenResume();
        e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
        if (!dragging) return;
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 4) moved = true;
        carousel.scrollLeft = startLeft - dx;
    });

    document.addEventListener('mouseup', function () {
        if (!dragging) return;
        dragging = false;
        carousel.classList.remove('is-dragging');
        carousel.style.scrollSnapType = '';
    });

    /* Prevent a drag-release from firing a card link */
    carousel.addEventListener('click', function (e) {
        if (moved) {
            e.preventDefault();
            e.stopImmediatePropagation();
            moved = false;
        }
    }, true);

    /* ── 6. Touch swipe — CSS handles the scroll; we just pause ─────── */
    carousel.addEventListener('touchstart', pauseThenResume, { passive: true });

    /* ── 7. Go ──────────────────────────────────────────────────────── */
    startAutoplay();
})();
</script>
<script src="/public/assets/js/pd-carousel.js"></script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
