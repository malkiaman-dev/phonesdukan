<?php
require_once dirname(__DIR__, 3) . '/includes/functions.php';

$pageTitle = $seo['seo_title'] ?? $product['product_name'];
$metaDescription = $seo['seo_description'] ?? $product['product_description'];
$metaKeywords = $seo['focus_keyword'] ?? '';
$metaRobots = ($product['product_status'] == 1) ? 'index, follow' : 'noindex';
$pageUrl = rtrim(getBaseURL(), '/') . buildProductPath(
    (string) ($product['category_slug'] ?? ''),
    (string) ($product['brand_slug'] ?? ''),
    (string) ($product['product_slug'] ?? '')
);

// Use regular_price and sale_price instead of product_price
$productPrice = isset($product['sale_price']) && is_numeric($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : (isset($product['regular_price']) && is_numeric($product['regular_price']) ? $product['regular_price'] : 0);

// Determine product availability with stricter checks
$productAvailability = 'outofstock'; // Default to out of stock
$availabilityText = 'Out of Stock';
$availabilityClass = 'out-of-stock';

// Log initial product data for debugging
error_log("Initial Product Data - ID: {$product['product_id']}, Stock: " . ($product['stock_quantity'] ?? 'unset') . ", Status: " . ($product['product_status'] ?? 'unset') . ", Coming Soon: " . (isset($isComingSoon) ? ($isComingSoon ? 'true' : 'false') : 'unset'));

// Explicitly check stock_quantity and handle NULL or non-numeric cases
$stockQuantity = isset($product['stock_quantity']) && is_numeric($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
$productStatus = isset($product['product_status']) ? (int)$product['product_status'] : 0;

if (isset($isComingSoon) && $isComingSoon === true) {
    $productAvailability = 'comingsoon';
    $availabilityText = 'Coming Soon';
    $availabilityClass = 'coming-soon';
} elseif ($productStatus == 1 && $stockQuantity > 0) {
    $productAvailability = 'instock';
    $availabilityText = 'In Stock';
    $availabilityClass = 'in-stock';
} else {
    $productAvailability = 'outofstock';
    $availabilityText = 'Out of Stock';
    $availabilityClass = 'out-of-stock';
}

// Debug: Log availability decision
error_log("Availability Decision - Product ID: {$product['product_id']}, Availability: $productAvailability, Stock Quantity: $stockQuantity, Status: $productStatus, Coming Soon: " . (isset($isComingSoon) ? ($isComingSoon ? 'true' : 'false') : 'unset'));

// Set the product image URL (fallback to default favicon image if no image is available)
$productImage = !empty($images[0]['image_url'])
    ? normalizeMediaUrl((string) $images[0]['image_url'])
    : getBaseURL() . 'public/assets/images/Phones_dukan_favicon.png';
$productImageAlt = $product['product_name'];

$productAttributes = $productModel->getProductAttributes($product['product_id']);  // Ensure you have initialized $productModel properly

// Load reviews for rating calculation
require_once dirname(__DIR__, 3) . '/app/Controllers/ReviewController.php';
$reviewController = new ReviewController();
$reviews = $reviewController->getApprovedByProduct($product['product_id']);

// Calculate average rating
$averageRating = 0;
$reviewCount = count($reviews);
if ($reviewCount > 0) {
    $totalRating = array_sum(array_column($reviews, 'rating'));
    $averageRating = round($totalRating / $reviewCount, 1);
}

// Display attributes as buttons or dropdown
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>
<div class="main-wrapper">
    <div class="nav-path">
        <span>
            <a href="<?php echo getBaseURL(); ?>">Home</a> > 
            <?php if (isset($product['category_slug']) && !empty($product['category_slug'])): ?>
                <a href="<?php echo getBaseURL() . encodeUrlPath((string) ($product['category_slug'] ?? '')); ?>">
                    <?php 
                    $categoryDisplay = !empty($product['category_name']) ? $product['category_name'] : ucwords(str_replace('-', ' ', $product['category_slug']));
                    echo htmlspecialchars($categoryDisplay);
                    ?>
                </a> > 
            <?php endif; ?>
            <?php if (isset($product['brand_slug']) && !empty($product['brand_slug'])): ?>
                <a href="<?php echo getBaseURL() . buildProductPath((string) ($product['category_slug'] ?? ''), (string) ($product['brand_slug'] ?? ''), ''); ?>">
                    <?php echo htmlspecialchars($product['brand_name'] ?? ucwords(str_replace('-', ' ', $product['brand_slug']))); ?>
                </a> > 
            <?php endif; ?>
            <a href="<?php echo getBaseURL() . buildProductPath((string) ($product['category_slug'] ?? ''), (string) ($product['brand_slug'] ?? ''), (string) ($product['product_slug'] ?? '')); ?>">
                <?php echo htmlspecialchars($product['product_name']); ?>
            </a>
        </span>
        <h1 class="product-heading">Buy <?php echo htmlspecialchars($product['product_name']); ?> Price in Pakistan</h1>
    </div>
</div>
<?php
$description = $product['product_description'];

// Split by paragraph tags to inject after 2nd paragraph
$paragraphs = explode('</p>', $description);
$modifiedDescription = '';
foreach ($paragraphs as $index => $para) {
    if (trim($para) == '')
        continue;

    $modifiedDescription .= $para . '</p>';

    // Inject ad after 2nd paragraph
    if ($index == 1) {
        ob_start();
        include_once (__DIR__ . '/../ad/display1.php');
        $adContent = ob_get_clean();
        $modifiedDescription .= $adContent;
    }
}
?>

<div class="p-page-container">
    <div class="product-container">
        <div class="product-content">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <?php if (!empty($images)): ?>
                    <?php
                    $seenUrls = [];
                    $mainImage = $images[0];
                    $mainImageUrl = normalizeMediaUrl((string) ($mainImage['image_url'] ?? ''));
                    $seenUrls[] = $mainImageUrl;
                    ?>

                    <!-- Main Image -->
                    <div class="image-wrapper">
                        <img src="<?= htmlspecialchars($mainImageUrl) ?>" 
                             alt="<?= htmlspecialchars($mainImage['alt_text'] ?? '') ?>" 
                             title="<?= htmlspecialchars($mainImage['title'] ?? '') ?>"
                             data-description="<?= htmlspecialchars($mainImage['description'] ?? '') ?>"
                             data-caption="<?= htmlspecialchars($mainImage['caption'] ?? '') ?>"
                             class="primary-image" id="mainImage">
                    </div>

                    <!-- Thumbnails -->
                    <div class="thumbnail-container">
                        <button class="thumbnail-nav left" onclick="scrollThumbnails('left')">❮</button>

                        <div class="thumbnail-gallery">
                            <!-- Main Image as First Thumbnail -->
                            <img src="<?= htmlspecialchars(normalizeMediaUrl((string) ($mainImage['image_url'] ?? '')) ) ?>" 
                                 alt="<?= htmlspecialchars($mainImage['alt_text'] ?? '') ?>" 
                                 title="<?= htmlspecialchars($mainImage['title'] ?? '') ?>"
                                 data-description="<?= htmlspecialchars($mainImage['description'] ?? '') ?>"
                                 data-caption="<?= htmlspecialchars($mainImage['caption'] ?? '') ?>"
                                 class="thumbnail active" 
                                 onclick="updateMainImage(this)">

                            <!-- Gallery Images -->
                            <?php
                            foreach ($images as $img):
                                $imageUrl = normalizeMediaUrl((string) ($img['image_url'] ?? ''));
                                if (in_array($imageUrl, $seenUrls, true)) {
                                    continue;
                                }
                                $seenUrls[] = $imageUrl;
                                ?>
                                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                     alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>" 
                                     title="<?= htmlspecialchars($img['title'] ?? '') ?>"
                                     data-description="<?= htmlspecialchars($img['description'] ?? '') ?>"
                                     data-caption="<?= htmlspecialchars($img['caption'] ?? '') ?>"
                                     class="thumbnail" 
                                     onclick="updateMainImage(this)">
                            <?php endforeach; ?>
                        </div>

                        <button class="thumbnail-nav right" onclick="scrollThumbnails('right')">❯</button>
                    </div>
                <?php else: ?>
                    <img src="default-image.jpg" alt="No image available">
                <?php endif; ?>
            </div>

            <!-- Product Summary -->
            <div class="product-summary" data-availability="<?php echo htmlspecialchars($productAvailability); ?>">
                <h2 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                
                <!-- Star Rating Section -->
                <div class="rating-section" onclick="scrollToReviews()">
                    <div class="rating-star-group">
                        <?php
                        $fullStars = floor($averageRating);
                        $halfStar = ($averageRating - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;

                        // Full stars
                        for ($i = 0; $i < $fullStars; $i++):
                            ?>
                            <div class="star-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#FFD700" stroke="none">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                            </div>
                        <?php endfor;

                        // Half star
                        if ($halfStar): ?>
                            <div class="star-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFD700" stroke-width="2">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    <path fill="#FFD700" d="M12 17.27L5.82 21l1.64-7.03L2 9.24l7.19-.61L12 2V17.27z"/>
                                </svg>
                            </div>
                        <?php endif;

                        // Empty stars
                        for ($i = 0; $i < $emptyStars; $i++): ?>
                            <div class="star-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFD700" stroke-width="2">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                            </div>
                        <?php endfor; ?>
                        <div class="rating-points"><?php echo $reviewCount > 0 ? number_format($averageRating, 1) : '0.0'; ?></div>
                    </div>
                    <span class="line"> | </span>
                    <div class="rating-count"><?php echo $reviewCount > 0 ? $reviewCount . ' Review' . ($reviewCount > 1 ? 's' : '') : 'No Reviews'; ?></div>
                </div>

                <div class="single-product-price">
                    <div class="price-content">
                        <?php
                        $regularPrice = isset($product['regular_price']) && is_numeric($product['regular_price']) ? (float) $product['regular_price'] : 0;
                        $salePrice = isset($product['sale_price']) && is_numeric($product['sale_price']) ? (float) $product['sale_price'] : 0;
                        $productPrice = ($salePrice > 0 && $salePrice < $regularPrice) ? $salePrice : $regularPrice;
                        ?>
                        <?php if ($productPrice > 0): ?>
                            <div class="product-price">
                                <span class="price-label">Phones Dukan Price</span>
                                <span class="price-amount new-price">
                                    Rs. <?php echo number_format($productPrice, 2); ?>
                                </span>
                            </div>
                            <?php if ($salePrice > 0 && $regularPrice > $salePrice): ?>
                                <div class="price-discount">
                                    <span class="price-amount old-price line-through">
                                        Rs. <?php echo number_format($regularPrice, 2); ?>
                                    </span>
                                    <div class="discount-section">
                                        <span class="discount-percentage">
                                            <?php
                                            $discount = round((($regularPrice - $salePrice) / $regularPrice) * 100);
                                            echo $discount . '% OFF';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="product-price">
                                <span class="price-label">Phones Dukan Price</span>
                                <span class="error-price">Price not available</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="availability-section">
                        <div class="stock-info">
                            <span class="availability-label">Availability</span>
                            <span class="stock-status <?php echo htmlspecialchars($availabilityClass); ?>">
                                <span class="stock-dot"></span><?php echo htmlspecialchars($availabilityText); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php
                $validPrice = ($productAvailability === 'instock' && isset($product['sale_price']) && is_numeric($product['sale_price']) && $product['sale_price'] > 0)
                    ? $product['sale_price']
                    : ($productAvailability === 'instock' && isset($product['regular_price']) && is_numeric($product['regular_price']) && $product['regular_price'] > 0 ? $product['regular_price'] : 0);
                $showLocationMessage = (isset($product['category_slug']) && strtolower($product['category_slug']) === 'mobiles') && !isset($isComingSoon);
                error_log("Location Message Check - Product ID: {$product['product_id']}, Category: " . ($product['category_slug'] ?? 'unset') . ", Coming Soon: " . (isset($isComingSoon) ? ($isComingSoon ? 'true' : 'false') : 'unset'));
                if ($showLocationMessage): ?>
                    <p class="location-restriction-msg">
                        This product is available for delivery only in Rawalpindi and Islamabad.
                    </p>
                <?php endif; ?>
                <?php if ($productAvailability === 'instock' && $stockQuantity > 0 && $validPrice > 0): ?>
                    <div class="bulk-cta-grid slider slider--mobile">
                        <div class="bulk-cta-item bulk-quantity" data-quantity="3">
                            <span class="bulk-cta-info">More than 3 Items</span>
                            <span class="bulk-cta-value">Get 5% Off</span>
                            <span class="bulk-cta-price"><strong>Rs. <?php echo number_format($validPrice * 0.95, 2); ?>/Item</strong></span>
                            <span class="bulk-cta-badge">MOST POPULAR</span>
                        </div>
                        <div class="bulk-cta-item bulk-quantity" data-quantity="10">
                            <span class="bulk-cta-info">More than 10 Items</span>
                            <span class="bulk-cta-value">Get 7% Off</span>
                            <span class="bulk-cta-price"><strong>Rs. <?php echo number_format($validPrice * 0.93, 2); ?>/Item</strong></span>
                            <span class="bulk-cta-badge">BEST VALUE</span>
                        </div>
                        <div class="bulk-cta-item bulk-inquiry">
                            <span class="bulk-cta-info">Corporate Deal</span>
                            <span class="bulk-cta-value">Click to Enquire</span>
                            <span class="bulk-cta-badge">BULK ORDER</span>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Cart Form -->
<!-- Cart Form -->
<?php
$cartFormCondition = $productAvailability === 'instock' && $stockQuantity > 0 && ($validPrice > 0 || !empty($productAttributes));
error_log("Cart form condition: " . ($cartFormCondition ? 'true' : 'false') . ", Availability: $productAvailability, Stock: $stockQuantity, Valid Price: $validPrice, Attributes: " . (empty($productAttributes) ? 'none' : 'present'));
if ($cartFormCondition): ?>
            <!-- Payment Method Selection -->
    <div class="payment-method-selection">
        <button type="button" class="payment-btn cod active" data-method="cod">COD</button>
        <button type="button" class="payment-btn prepaid" data-method="prepaid">Prepaid · Save 4%</button>
    </div>

    <!-- Desktop Cart Form -->
    <div class="cart-form desktop-cart-form">
        <div class="quantity-container">
            <button type="button" class="quantity-btn minus">−</button>
            <input type="number" id="quantity-desktop" name="quantity" value="1" min="1" max="<?php echo $stockQuantity; ?>">
            <button type="button" class="quantity-btn plus">+</button>
        </div>

        <button class="add-to-cart"
                data-product-id="<?php echo $product['product_id']; ?>"
                data-unit-price="<?php echo $validPrice; ?>"
                data-attribute-value=""
                data-payment-method="cod"
                id="add-to-cart-btn-desktop">
            Add to Cart
        </button>

        <button class="buy-now" id="buy-now-desktop">Buy Now</button>
    </div>

    <!-- Trust Signals -->
    <div class="trust-signals">
        <div class="trust-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Fast Delivery</span>
        </div>
        <div class="trust-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Warranty</span>
        </div>
        <div class="trust-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/>
            </svg>
            <span>Easy Returns</span>
        </div>
    </div>

    <!-- Sticky Cart for Mobile -->
    <?php if ($productAvailability === 'instock' && $validPrice > 0): ?>
        <div class="sticky-cart-wrapper">
            <div class="cart-form mobile-cart-form">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                <div class="quantity-wrapper">
                    <button type="button" class="quantity-btn minus">−</button>
                    <input type="number" id="quantity-mobile" name="quantity" value="1" min="1" max="<?php echo $stockQuantity; ?>" readonly>
                    <button type="button" class="quantity-btn plus">+</button>
                </div>
                <button type="button" id="add-to-cart-btn-mobile" class="btn add-to-cart"
                        data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"
                        data-unit-price="<?php echo $validPrice; ?>"
                        data-attribute-value="<?php echo isset($selectedAttributeValue) ? htmlspecialchars($selectedAttributeValue) : ''; ?>"
                        data-payment-method="cod">
                    Add to Cart
                </button>
                <button type="button" id="buy-now-mobile" class="btn buy-now">Buy Now</button>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <?php if ($productAvailability !== 'instock'): ?>
        <p class="status-msg">
            <?php echo htmlspecialchars($availabilityText); ?>
        </p>
    <?php endif; ?>
<?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include_once (__DIR__ . '/../ad/display1.php'); ?>

<div class="custom-tabs">
    <ul class="custom-tab-titles">
        <li class="custom-tab-title description active" data-tab="tab-description">Description</li>
        <li class="custom-tab-title specification" data-tab="tab-specification">Specification</li>
        <li class="custom-tab-title reviews" data-tab="tab-reviews">Reviews (<?php echo $reviewCount; ?>)</li>
    </ul>

    <div class="custom-tab-content">
        <div id="tab-description" class="custom-tab description active">
            <?php echo $modifiedDescription; ?>
        </div>
        <div id="tab-specification" class="custom-tab specification" style="display: none;">
            <?php echo !empty($product['short_description']) ? $product['short_description'] : '<p>No specification available</p>'; ?>
        </div>
        <div id="tab-reviews" class="custom-tab reviews" style="display: none;">
            <div class="reviews-section-wrapper">
                <div class="review-form-box">
                    <?php
                    $product_id = $product['product_id'] ?? 0;
                    require_once __DIR__ . '/reviews_form.php';
                    ?>
                </div>
                <div class="review-display-box">
                    <?php require_once __DIR__ . '/reviews_display.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once (__DIR__ . '/../ad/display3.php'); ?>

<!-- Bulk Inquiry Popup -->
<div class="bulk-inquiry-overlay" aria-hidden="true">
    <div class="close-inquiry" role="button" aria-label="Close popup">
        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" class="icon icon-close icon--medium" fill="none" viewBox="0 0 24 24">
            <path d="M18.75 5.13496L5.25 18.8544" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M18.75 18.8544L5.25 5.13496" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </div>
    <form id="bulk-inquiry-form" class="inquiry-form">
        <h3>Exclusive Discount On Your Bulk Orders!</h3>
        <div class="form-content">
            <p>We deal in both Chinese products and Pakistani brands in bulk. To know the retailer prices, click the button below.</p>
            <p>ہم چینی مصنوعات اور پاکستانی برانڈز بلک میں فراہم کرتے ہیں۔ ریٹیلر قیمتوں کی معلومات کے لیے نیچے دیے گئے بٹن پر کلک کریں۔</p>
            <button type="button" class="chat-button" data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                <img src="/public/assets/images/whatsapp.svg" alt="WhatsApp" class="whatsapp-icon">
                Chat Us
            </button>
        </div>
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">

        <div class="form-group flex-group">
            <div>
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Type Here" required>
            </div>
            <div>
                <label>Email *</label>
                <input type="email" name="email" placeholder="Type Here" required>
            </div>
        </div>

        <div class="form-group flex-group">
            <div>
                <label>Phone *</label>
                <input type="tel" name="phone" placeholder="Type Here" pattern="[0-9]{1,12}" maxlength="12" required title="Phone must contain only digits (up to 12).">
            </div>
            <div>
                <label>Tel No (Optional)</label>
                <input type="tel" name="tel_no" placeholder="Type Here" pattern="[0-9]{0,12}" maxlength="12" title="Tel No must contain only digits (up to 12).">
            </div>
        </div>

        <div class="form-group flex-group">
            <div>
                <label>Business Name *</label>
                <input type="text" name="business_name" placeholder="Type Here" required>
            </div>
            <div>
                <label>Product Quantity *</label>
                <input type="number" name="product_quantity" placeholder="Type Here" min="1" required>
            </div>
        </div>

        <div class="form-group">
            <div>
                <label>Address *</label>
                <input type="text" name="address" placeholder="Type Here" required>
            </div>
        </div>

        <div class="form-group">
            <div>
                <label>Your Message (Optional)</label>
                <textarea name="message" placeholder="Type Here"></textarea>
            </div>
        </div>

        <button type="submit">Submit</button>
    </form>
</div>

<?php require_once __DIR__ . '/related-products.php'; ?>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>