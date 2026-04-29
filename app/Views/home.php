<?php
$pageTitle = 'Best Mobile Deals &amp; Prices in Pakistan - Phones Dukan';
$metaDescription = 'Shop top-rated mobiles, smartwatches, and accessories at Phones Dukan – Pakistan’s trusted store. Best prices, quality, and fast delivery. Explore now!';
$metaRobots = 'index, follow';  // Optional; default is good
$metaKeywords = 'Online Shopping in Pakistan, Buy Mobile in Pakistan, Smartwatch Price in Pakistan, Mobile Accessories, Wireless Earbuds, Tablet Price in Pakistan, Mobiles in Pakistan, Best Mobile Price, Phones Dukan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once __DIR__ . '/../Models/ProductCategoryModel.php';  // Correct path

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

?>

<section class="pd-hero" aria-label="Featured offers">
    <div class="pd-hero-slider" data-pd-hero-slider tabindex="0">
        <article class="pd-hero-slide is-active" data-pd-slide aria-hidden="false">
            <div class="pd-hero-media">
                <img src="/public/assets/images/hero/hero-slide-1.png" alt="Latest smartphones and accessories at Mobile Island" loading="eager" fetchpriority="high">
            </div>
            <div class="pd-hero-overlay">
                <div class="pd-hero-content">
                    <span class="pd-hero-eyebrow">Mobile Island Official</span>
                    <h1>Premium Mobile Experience<br><span>Built for Pakistan</span></h1>
                    <p>Explore genuine smartphones, accessories, and wearables with trusted after-sales support.</p>
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
                    <span class="pd-hero-eyebrow">Limited Time Deals</span>
                    <h2>Exclusive Offers<br><span>Up to 40% OFF</span></h2>
                    <p>Catch this week’s best deals on smart watches, earbuds, and top mobile accessories.</p>
                    <div class="pd-hero-cta-wrap">
                        <a href="/smart-watches" class="pd-hero-btn pd-hero-btn-primary">Shop Watches</a>
                        <a href="/wireless-earbuds" class="pd-hero-btn pd-hero-btn-secondary">Shop Earbuds</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="pd-hero-slide" data-pd-slide aria-hidden="true">
            <div class="pd-hero-media">
                <img src="/public/assets/images/hero/hero-slide-3.webp" alt="Fast delivery and warranty by Mobile Island" loading="lazy">
            </div>
            <div class="pd-hero-overlay">
                <div class="pd-hero-content">
                    <span class="pd-hero-eyebrow">Trusted Since Day One</span>
                    <h2>Fast Delivery +<br><span>Warranty Promise</span></h2>
                    <p>Enjoy fast shipping, 7-day replacement, and reliable nationwide support on every order.</p>
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
<?php include_once __DIR__ . '/ad/feed1.php'; ?>

<div class="advantages-section">
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
</div>

<section class="cat-section">
    <div class="cat-inner">
        <div class="cat-header">
            <h2 class="cat-title">Shop by <span>Categories</span></h2>
        </div>
        <div class="cat-track-wrap">
            <button class="cat-arrow cat-prev" aria-label="Previous categories">&#8249;</button>
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
                    <p class="cat-label">Smartwatches</p>
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
                    <p class="cat-label">Accessories</p>
                </a>
                <a href="/power-banks" class="cat-card" style="--cat-bg:#fef9c3;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/power-banks.webp" alt="Power Banks">
                    </div>
                    <p class="cat-label">Power Banks</p>
                </a>
                <a href="/bluetooth-speakers" class="cat-card" style="--cat-bg:#fce7f3;">
                    <div class="cat-img-box">
                        <img src="/public/assets/images/bluetooth-speakers.webp" alt="Bluetooth Speakers">
                    </div>
                    <p class="cat-label">Bluetooth Speakers</p>
                </a>
            </div>
            <button class="cat-arrow cat-next" aria-label="Next categories">&#8250;</button>
        </div>
    </div>
</section>

<div class="product-section">
    <div class="category-header">
        <h2>New <span>Arrivals</span></h2>
        <a href="/shop" class="view-all-btn">View All</a>
    </div>
    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Ensure all values exist before using them
                        $product_name = !empty($product['product_name']) ? htmlspecialchars($product['product_name']) : 'Unnamed Product';
                        $category_slug = !empty($product['category_slug']) ? htmlspecialchars($product['category_slug']) : 'unknown-category';
                        $brand_slug = !empty($product['brand_slug']) ? htmlspecialchars($product['brand_slug']) : 'unknown-brand';
                        $product_slug = !empty($product['product_slug']) ? htmlspecialchars($product['product_slug']) : 'unknown-product';
                        $product_status = isset($product['product_status']) ? strtolower($product['product_status']) : '';
                        $product_image = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : '/path-to-default-image.jpg';

                        // Construct the product URL
                        $product_url = '/' . $category_slug . '/' . $brand_slug . '/' . $product_slug;

                        // Handle price
                        $product_price = '';

                        if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                            $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                                . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                        } elseif (!empty($product['regular_price'])) {
                            $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                        } elseif (!empty($product['sale_price'])) {
                            $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                        } else {
                            $product_price = '<span class="no-price">Price Not Available</span>';
                        }
                        ?>

                        <div class="product-card">
                            <div class="tagwrap">
                                <?php if ($product['is_sold_out']): ?>
                                    <div class="sold-out">
                                        <span>Sold Out</span>
                                    </div>
                                <?php elseif ($product['is_on_sale']): ?>
                                    <div class="pro-tags">
                                        <span>Sale</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo $product_url; ?>">
                                <div class="product-img-wrapper">
                                    <div class="product-img">
                                        <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>">
                                    </div>
                                </div>
                            </a>
                            <h3 class="product-title">
                                <a href="<?php echo $product_url; ?>">
                                    <?php echo $product_name; ?>
                                </a>
                            </h3>
                            <span class="prodline"></span>
                            <div class="prodprice price-line">
                            <?php echo $product_price; ?>
                           </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>
        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>



<!-- <div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Coming Soon Products</span></h2>
        <a href="/coming-soon-products" class="view-all-btn">View All</a>
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
            $product_url = '/' . htmlspecialchars($product['category_slug']) . '/' . htmlspecialchars($product['brand_slug']) . '/' . htmlspecialchars($product['product_slug']);
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
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
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

<div class="s-banner">
<a href="/smart-watches" target="_blank">
         <img src="/public/assets/images/smart-watch-banner.webp" alt="smart-watches_banner" class="banner-image">
</a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Smart Watches</span></h2>
        <a href="/smart-watches" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php
    // Fetch Smart Watches products (only category_id = 11)
    $products = $productModel->getSmartWatches(12);

    if (!empty($products)):
        foreach ($products as $product):
            // Construct product URL
            $product_url = '/smart-watches/'
                . htmlspecialchars($product['brand_slug']) . '/'
                . htmlspecialchars($product['product_slug']);

            // Format product price
            $product_price = '';

            if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                    . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } elseif (!empty($product['regular_price'])) {
                $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
            } elseif (!empty($product['sale_price'])) {
                $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } else {
                $product_price = '<span class="no-price">Price Not Available</span>';
            }
            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="sold-out">
                            <span>Sold Out</span>
                        </div>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <div class="pro-tags">
                            <span>Sale</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

            </div>
        <?php
        endforeach;
    else:
        echo '<p>No smartwatches found.</p>';
    endif;
    ?>
</div>


        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>

<div class="s-banner">
<a href="/wireless-earbuds" target="_blank">
         <img src="/public/assets/images/wireless_earbuds_banner.webp" alt="wireless_earbuds_banner" class="banner-image">
</a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Wireless Earbuds</span></h2>
        <a href="/wireless-earbuds" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php
    // Fetch products from category 13
    $products = $productModel->getCategory13Products(12);

    if (!empty($products)):
        foreach ($products as $product):
            // Updated URL structure
            $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                . htmlspecialchars($product['brand_slug']) . '/'
                . htmlspecialchars($product['product_slug']);

            // Format product price
            // Format product price
            $product_price = '';

            if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                    . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } elseif (!empty($product['regular_price'])) {
                $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
            } elseif (!empty($product['sale_price'])) {
                $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } else {
                $product_price = '<span class="no-price">Price Not Available</span>';
            }
            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="sold-out">
                            <span>Sold Out</span>
                        </div>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <div class="pro-tags">
                            <span>Sale</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

            </div>
        <?php
        endforeach;
    else:
        echo '<p>No products found in this category.</p>';
    endif;
    ?>
</div>

        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>

<div class="s-banner">
    <a href="/mobiles" target="_blank">
    <img src="/public/assets/images/mobiles_banner.webp" alt="mobiles" class="banner-image">
    </a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Mobiles</span></h2>
        <a href="/mobiles" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php
    // Fetch products from category 2
    $products = $productModel->getCategory2Products(12);

    if (!empty($products)):
        foreach ($products as $product):
            // Updated URL structure
            $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                . htmlspecialchars($product['brand_slug']) . '/'
                . htmlspecialchars($product['product_slug']);

            $product_price = '';

            if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                    . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } elseif (!empty($product['regular_price'])) {
                $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
            } elseif (!empty($product['sale_price'])) {
                $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } else {
                $product_price = '<span class="no-price">Price Not Available</span>';
            }

            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="sold-out">
                            <span>Sold Out</span>
                        </div>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <div class="pro-tags">
                            <span>Sale</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

            </div>
        <?php
        endforeach;
    else:
        echo '<p>No products found in this category.</p>';
    endif;
    ?>
</div>

        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>

<div class="s-banner">
<a href="/mobiles_accessories" target="_blank">
         <img src="/public//assets/images/mobiles_accessories_banner.webp" alt="mobiles_accessories_banner" class="banner-image">
</a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Mobiles Accessories</span></h2>
        <a href="/mobile-accessories" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php
    // Fetch products from category 9 (Mobile Accessories)
    $products = $productModel->getCategory9Products(12);

    if (!empty($products)):
        foreach ($products as $product):
            // Updated URL structure
            $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                . htmlspecialchars($product['brand_slug']) . '/'
                . htmlspecialchars($product['product_slug']);

            $product_price = '';

            if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                    . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } elseif (!empty($product['regular_price'])) {
                $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
            } elseif (!empty($product['sale_price'])) {
                $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } else {
                $product_price = '<span class="no-price">Price Not Available</span>';
            }

            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="sold-out">
                            <span>Sold Out</span>
                        </div>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <div class="pro-tags">
                            <span>Sale</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

            </div>
        <?php
        endforeach;
    else:
        echo '<p>No products found in this category.</p>';
    endif;
    ?>
</div>
        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>
<?php include_once __DIR__ . '/ad/feed2.php'; ?>

<div class="s-banner">
<a href="/power-banks" target="_blank">
         <img src="/public/assets/images/powerbanks_banner.webp" alt="power_banks_banner" class="banner-image">
</a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Power Banks</span></h2>
        <a href="/power-banks" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
        <div class="product-grid">
    <?php
    // Fetch products from category 9 (Mobile Accessories)
    $products = $productModel->getCategory10Products(12);

    if (!empty($products)):
        foreach ($products as $product):
            // Updated URL structure
            $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                . htmlspecialchars($product['brand_slug']) . '/'
                . htmlspecialchars($product['product_slug']);

            // Format product price
            $product_price = '';

            if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                    . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } elseif (!empty($product['regular_price'])) {
                $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
            } elseif (!empty($product['sale_price'])) {
                $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
            } else {
                $product_price = '<span class="no-price">Price Not Available</span>';
            }

            ?>

            <div class="product-card">
                <div class="tagwrap">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="sold-out">
                            <span>Sold Out</span>
                        </div>
                    <?php elseif (!empty($product['sale_price'])): ?>
                        <div class="pro-tags">
                            <span>Sale</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $product_url; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-img">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                    </div>
                </a>

                <h3 class="product-title">
                    <a href="<?php echo $product_url; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </a>
                </h3>

                <span class="prodline"></span>

                <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

            </div>
        <?php
        endforeach;
    else:
        echo '<p>No products found in this category.</p>';
    endif;
    ?>
</div>
        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>

<div class="s-banner">
    <a href="/bluetooth-speakers" target="_blank">
    <img src="/public/assets/images/bluetooth_speakers_banner.webp" alt="bluetooth-speakers_banner" class="banner-image">
    </a>
</div>

<div class="product-section">
    <div class="category-header">
        <h2>Latest <span>Bluetooth Speakers</span></h2>
        <a href="/bluetooth-speakers" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-wrapper">
        <button class="scroll-btn prev-btn" disabled>&lt;</button>
        <div class="product-grid-container">
            <div class="product-grid">
                <?php
                // Fetch products from category 4
                $products = $productModel->getCategory4Products(4);

                if (!empty($products)):
                    foreach ($products as $product):
                        // Construct the correct product URL
                        $product_url = '/' . htmlspecialchars($product['category_slug']) . '/' . htmlspecialchars($product['brand_slug']) . '/' . htmlspecialchars($product['product_slug']);

                        $product_price = '';

                        if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                            $product_price = '<span class="regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                                . '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                        } elseif (!empty($product['regular_price'])) {
                            $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                        } elseif (!empty($product['sale_price'])) {
                            $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                        } else {
                            $product_price = '<span class="no-price">Price Not Available</span>';
                        }

                        ?>

                        <div class="product-card">
                            <div class="tagwrap">
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <div class="sold-out">
                                        <span>Sold Out</span>
                                    </div>
                                <?php elseif (!empty($product['sale_price'])): ?>
                                    <div class="pro-tags">
                                        <span>Sale</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo $product_url; ?>">
                                <div class="product-img-wrapper">
                                    <div class="product-img">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    </div>
                                </div>
                            </a>

                            <h3 class="product-title">
                                <a href="<?php echo $product_url; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </a>
                            </h3>

                            <span class="prodline"></span>
                            <div class="prodprice price-line">
    <?php echo $product_price; ?>
</div>

                        </div>
                    <?php
                    endforeach;
                else:
                    echo '<p>No products found in this category.</p>';
                endif;
                ?>
            </div>
        </div>
        <button class="scroll-btn next-btn">&gt;</button>
    </div>
</div>


<section id="shopByUniqueBrands" class="shopByUniqueBrandsSection pd-bottom">
    <div class="container-lg">
        <div class="category-header">
            <h2>Shop <span>By Brand</span></h2>
        </div>
        <div class="brandListWrapper">
            <a href="/mobiles/samsung" target="_blank" class="brandItem brandSamsung">
                <div class="brandLogo">
                    <img src="/public/assets/images/samsung_logo.webp" alt="Samsung">
                </div>
            </a>
            <a href="/mobiles/infinix/" target="_blank" class="brandItem brandInfinix">
                <div class="brandLogo">
                    <img src="/public/assets/images/infinix_logo.webp" alt="Infinix">
                </div>
            </a>
            <a href="/mobiles/oppo" target="_blank" class="brandItem brandOppo">
                <div class="brandLogo">
                    <img src="/public/assets/images/oppo_logo.webp" alt="Oppo">
                </div>
            </a>
            <a href="/mobiles/xiaomi" target="_blank" class="brandItem brandXiaomi">
                <div class="brandLogo">
                    <img src="/public/assets/images/xiaomi_logo.webp" alt="Xiaomi">
                </div>
            </a>
            <a href="/mobiles/vivo" target="_blank" class="brandItem brandVivo">
                <div class="brandLogo">
                    <img src="/public/assets/images/vivo_logo.webp" alt="Vivo">
                </div>
            </a>
            <a href="/mobiles/tecno" target="_blank" class="brandItem brandTecno">
                <div class="brandLogo">
                    <img src="/public/assets/images/tecno_logo.webp" alt="Tecno">
                </div>
            </a>
            <a href="/mobiles/realme" target="_blank" class="brandItem brandRealme">
                <div class="brandLogo">
                    <img src="/public/assets/images/realme_logo.webp" alt="Realme">
                </div>
            </a>
            <a href="/mobiles/apple" target="_blank" class="brandItem brandApple">
                <div class="brandLogo">
                    <img src="/public/assets/images/apple_logo.webp" alt="Apple">
                </div>
            </a>
        </div>
    </div>
</section>

<div class="testimonials">
<div class="category-header">
<h2>Customer <span>Testimonials</span></h2>
</div>
    <div class="testimonials-carousel">
        <div class="testimonial-box">
            <div class="testimonial-rating">
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
            </div>
            <p>Very responsive and professional. I recommend this place! The prices are great. Fast delivery. Thank you Phones Dukan</p>
            <h3 class="testimonial-name">Muniza Saleh</h3>
        </div>
        <div class="testimonial-box">
            <div class="testimonial-rating">
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9734;</span> <!-- Empty Star -->
            </div>
            <p>Great place! I managed a remote purchase for a local. Very responsive and professional. I recommend this place! The prices are great. Fast delivery.</p>
            <h3 class="testimonial-name">Vanessa</h3>
        </div>
        <div class="testimonial-box">
            <div class="testimonial-rating">
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Empty Star -->
                <span class="star">&#9733;</span> <!-- Empty Star -->
            </div>
            <p>Their prices are low compared to the market. Additionally, they offer the best after-sale customer support. Recommended.</p>
            <h3 class="testimonial-name">Furqan Haider</h3>
        </div>
        <div class="testimonial-box">
            <div class="testimonial-rating">
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9733;</span> <!-- Filled Star -->
                <span class="star">&#9734;</span> <!-- Empty Star -->
            </div>
            <p>I like this place. It's not only a walk-in customer shop, but also an online store. That's why I give them 5 stars!</p>
            <h3 class="testimonial-name">Shadab Hussain</h3>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
