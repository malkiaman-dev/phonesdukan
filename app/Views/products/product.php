<?php
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/Helpers/SeoHelper.php';

// ── SEO meta ──────────────────────────────────────────────────────────────
$productPrice = isset($product['sale_price']) && is_numeric($product['sale_price']) && (float)$product['sale_price'] > 0
    ? (float)$product['sale_price']
    : (float)($product['regular_price'] ?? 0);

$pageTitle = SeoHelper::productTitle(
    (string)($product['product_name'] ?? ''),
    $seo['seo_title'] ?? null
);
$metaDescription = SeoHelper::productDescription(
    (string)($product['product_name'] ?? ''),
    (string)($product['brand_name'] ?? ''),
    $productPrice ?: null,
    $seo['seo_description'] ?? null
);
$metaKeywords = $seo['focus_keyword'] ?? '';
$metaRobots = ($product['product_status'] == 1) ? 'index, follow' : 'noindex';
$pageUrl = rtrim(getBaseURL(), '/') . buildProductPath(
    (string) ($product['category_slug'] ?? ''),
    (string) ($product['brand_slug'] ?? ''),
    (string) ($product['product_slug'] ?? '')
);

// Determine product availability with stricter checks
$productAvailability = 'outofstock'; // Default to out of stock
$availabilityText = 'Out of Stock';
$availabilityClass = 'out-of-stock';

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

// Breadcrumbs for BreadcrumbList schema (consumed by header.php)
$breadcrumbs = SeoHelper::productBreadcrumbs(
    (string)($product['category_slug'] ?? ''),
    (string)($product['category_name'] ?? ucwords(str_replace('-', ' ', $product['category_slug'] ?? ''))),
    (string)($product['brand_slug']    ?? ''),
    (string)($product['brand_name']    ?? ucwords(str_replace('-', ' ', $product['brand_slug']    ?? ''))),
    (string)($product['product_name']  ?? ''),
    (string)($product['product_slug']  ?? '')
);

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
                // ----------------------------------------------------------------
                // For variable products: overwrite price with lowest variation price
                // ----------------------------------------------------------------
                if (!empty($isVariableProduct) && !empty($productVariations)) {
                    $activeVars = array_filter($productVariations, fn($v) => $v['status'] == 1 && $v['stock_quantity'] > 0);
                    if (!empty($activeVars)) {
                        $lowestEffective = min(array_map(fn($v) => $v['sale_price'] !== null ? $v['sale_price'] : $v['regular_price'], $activeVars));
                        $product['sale_price']    = null;
                        $product['regular_price'] = $lowestEffective;
                    }
                    $hasActiveVarStock = !empty($activeVars);
                    if (!$hasActiveVarStock) {
                        $productAvailability = 'outofstock';
                        $availabilityText    = 'Out of Stock';
                        $availabilityClass   = 'out-of-stock';
                        $stockQuantity       = 0;
                    } else {
                        $productAvailability = 'instock';
                        $availabilityText    = 'In Stock';
                        $availabilityClass   = 'in-stock';
                        $stockQuantity       = array_sum(array_column($activeVars, 'stock_quantity'));
                    }
                }

                $validPrice = ($productAvailability === 'instock' && isset($product['sale_price']) && is_numeric($product['sale_price']) && $product['sale_price'] > 0)
                    ? $product['sale_price']
                    : ($productAvailability === 'instock' && isset($product['regular_price']) && is_numeric($product['regular_price']) && $product['regular_price'] > 0 ? $product['regular_price'] : 0);
                $showLocationMessage = (isset($product['category_slug']) && strtolower($product['category_slug']) === 'mobiles') && !isset($isComingSoon);
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
                            <span class="bulk-cta-info">Corporate Deal items</span>
                            <span class="bulk-cta-value">Get Quote</span>
                            <span class="bulk-cta-price"><strong>Best bulk offers</strong></span>
                            <span class="bulk-cta-badge">BULK ORDER</span>
                        </div>
                    </div>
                <?php endif; ?>
<!-- === VARIATION SWATCHES === -->
<?php if (!empty($isVariableProduct) && !empty($productVariations)):
    $usedTypeIds = [];
    foreach ($productVariations as $_pv) {
        foreach (array_keys($_pv['attributes']) as $_tid) $usedTypeIds[(int)$_tid] = true;
    }
    $productVarTypes = array_values(array_filter($variationTypes, fn($t) => isset($usedTypeIds[(int)$t['id']])));
?>

<style>
/* ===== Variation Swatches — PhonesDukan Theme ===== */
.pdsw-wrap { margin: 20px 0 16px; }
.pdsw-group { margin-bottom: 20px; }

/* Section label */
.pdsw-label {
    font-size: .9rem; font-weight: 800; color: #111111;
    margin-bottom: 12px; display: block;
    letter-spacing: -.01em;
}
.pdsw-chosen-name { font-weight: 600; color: #6b7280; }
.pdsw-options { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start; }

/* ---- IMAGE / COLOR CARD swatches ---- */
.pdsw-card {
    display: flex; flex-direction: column; align-items: center;
    width: 90px; min-height: 110px;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 7px 6px 9px;
    cursor: pointer; outline: none;
    transition: border-color .17s, box-shadow .17s;
    position: relative;
    -webkit-user-select: none; user-select: none;
}
.pdsw-card:hover:not(:disabled) {
    border-color: #facc15;
    box-shadow: 0 2px 10px rgba(250,204,21,.25);
}
.pdsw-card.active {
    border-color: #facc15;
    box-shadow: 0 0 0 2.5px #facc15;
}
.pdsw-card:disabled,
.pdsw-card.pdsw-oos {
    opacity: .35; cursor: not-allowed;
}
/* Product image inside card */
.pdsw-card-img {
    width: 70px; height: 72px;
    object-fit: contain;
    border-radius: 8px;
    display: block;
    background: #f8f8f8;
}
/* Solid color block inside card (when no image uploaded) */
.pdsw-card-color {
    width: 70px; height: 72px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,.07);
    display: block;
}
/* Name label below visual */
.pdsw-card-name {
    font-size: .76rem; font-weight: 700; color: #111;
    margin-top: 7px; text-align: center;
    line-height: 1.3;
    max-width: 80px;
    word-break: break-word;
}
.pdsw-card.active .pdsw-card-name { font-weight: 800; }
.pdsw-card.pdsw-oos .pdsw-card-name { color: #9ca3af; }

/* OOS diagonal badge */
.pdsw-oos-x {
    position: absolute; inset: 0; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.pdsw-oos-x::before {
    content: '';
    position: absolute; top: 50%; left: 0; right: 0; height: 1.5px;
    background: rgba(200,0,0,.4); transform: rotate(-30deg);
}

/* ---- PILL / TEXT swatches ---- */
.pdsw-pill {
    display: inline-flex; align-items: center;
    padding: 8px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 999px;
    background: #fff; color: #111;
    font-size: .85rem; font-weight: 700;
    cursor: pointer; outline: none;
    transition: border-color .15s, background .15s, color .15s;
    white-space: nowrap;
    -webkit-user-select: none; user-select: none;
}
.pdsw-pill:hover:not(:disabled) {
    border-color: #facc15;
    background: #fffbeb;
}
.pdsw-pill.active {
    border-color: #facc15;
    background: #facc15;
    color: #111;
    box-shadow: 0 0 0 1px #facc15;
}
.pdsw-pill:disabled,
.pdsw-pill.pdsw-oos {
    opacity: .38; cursor: not-allowed;
    text-decoration: line-through;
}

/* Notice / SKU */
.pdsw-notice {
    display: none; margin: 10px 0 2px;
    padding: 8px 14px; border-radius: 8px;
    background: #fffbeb; border: 1px solid #facc15;
    font-size: .84rem; font-weight: 700; color: #111;
}
.pdsw-sku-line { display: none; font-size: .8rem; color: #6b7280; margin: 5px 0; }

@media (max-width: 480px) {
    .pdsw-card { width: 74px; }
    .pdsw-card-img, .pdsw-card-color { width: 56px; height: 58px; }
    .pdsw-pill { padding: 7px 13px; font-size: .8rem; }
}
</style>

<div class="pdsw-wrap" id="pdSwatchesWrap">
<?php
// Build a map: variation_type_id → value_id → product_variation image
// This lets each color swatch show the image uploaded in Step 3 of the variation builder
$varImgMap = [];
foreach ($productVariations as $_pv) {
    if (!empty($_pv['image'])) {
        foreach ($_pv['attributes'] as $_tid => $_vid) {
            // Only set if not already set (prefer first / default variation image)
            if (!isset($varImgMap[(int)$_tid][(int)$_vid])) {
                $varImgMap[(int)$_tid][(int)$_vid] = $_pv['image'];
            }
        }
    }
}
?>
<?php foreach ($productVarTypes as $vt):
    $usedValueIds = [];
    foreach ($productVariations as $_pv) {
        if (isset($_pv['attributes'][(int)$vt['id']])) {
            $usedValueIds[(int)$_pv['attributes'][(int)$vt['id']]] = true;
        }
    }
    /* Show as cards: image type OR color type */
    $isCardType = in_array($vt['display_type'], ['image','color']);
?>
<div class="pdsw-group" data-type-id="<?= $vt['id'] ?>">
  <div class="pdsw-label">
    <?= htmlspecialchars($vt['name']) ?>
    <span class="pdsw-chosen-name" id="pdSwChosen_<?= $vt['id'] ?>"></span>
  </div>
  <div class="pdsw-options">
  <?php foreach ($vt['values'] as $val):
    if (!isset($usedValueIds[(int)$val['id']])) continue;
    $isAvail = false;
    foreach ($productVariations as $_pv) {
        if (isset($_pv['attributes'][(int)$vt['id']])
            && (int)$_pv['attributes'][(int)$vt['id']] === (int)$val['id']
            && $_pv['status'] == 1 && $_pv['stock_quantity'] > 0) {
            $isAvail = true; break;
        }
    }
    $dis      = $isAvail ? '' : 'disabled';
    $oosClass = $isAvail ? '' : ' pdsw-oos';

    // Image priority:
    // 1. Product-variation image (uploaded in Step 3 — specific to this product+color)
    // 2. Global variation-value image (uploaded in Manage Variations)
    // 3. Color code block
    // 4. Grey placeholder
    $swatchImg   = $varImgMap[(int)$vt['id']][(int)$val['id']] ?? null;
    $globalImg   = $val['image'] ?? null;
    $colorCode   = $val['color_code'] ?? null;
    $displayImg  = $swatchImg ?: $globalImg;
  ?>

  <?php if ($isCardType): ?>
    <button type="button"
      class="pdsw-card<?= $oosClass ?>"
      data-type-id="<?= $vt['id'] ?>"
      data-value-id="<?= $val['id'] ?>"
      data-value-name="<?= htmlspecialchars($val['value']) ?>"
      title="<?= htmlspecialchars($val['value']) ?>"
      <?= $dis ?>>
      <?php if ($displayImg): ?>
        <img class="pdsw-card-img"
             src="<?= htmlspecialchars($displayImg) ?>"
             alt="<?= htmlspecialchars($val['value']) ?>" loading="lazy">
      <?php elseif ($colorCode): ?>
        <span class="pdsw-card-color"
              style="background:<?= htmlspecialchars($colorCode) ?>"></span>
      <?php else: ?>
        <span class="pdsw-card-color" style="background:#f1f5f9"></span>
      <?php endif; ?>
      <span class="pdsw-card-name"><?= htmlspecialchars($val['value']) ?></span>
      <?php if (!$isAvail): ?><span class="pdsw-oos-x"></span><?php endif; ?>
    </button>

  <?php else: /* pill / text swatch */ ?>
    <button type="button"
      class="pdsw-pill<?= $oosClass ?>"
      data-type-id="<?= $vt['id'] ?>"
      data-value-id="<?= $val['id'] ?>"
      data-value-name="<?= htmlspecialchars($val['value']) ?>"
      <?= $dis ?>>
      <?= htmlspecialchars($val['value']) ?>
    </button>
  <?php endif; ?>

  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<p class="pdsw-notice" id="pdSwNotice">Please select all product options first.</p>
<p class="pdsw-sku-line" id="pdSwSku">SKU: <strong id="pdSwSkuVal"></strong></p>
<input type="hidden" id="selectedVariationId" value="">
</div>

<script>
(function(){
var PD_VARS = <?= json_encode($productVariations, JSON_UNESCAPED_UNICODE) ?>;
var pdSel = {};
var withBase = window.pdWithBase || function(p){ var b=String(window.__PD_BASE_PATH__||'').replace(/\/+$/,''); return p&&p.startsWith('/')&&!p.startsWith(b+'/')?b+p:p; };

// Attach click handlers to all swatch buttons
document.querySelectorAll('.pdsw-card:not(:disabled), .pdsw-pill:not(:disabled)').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tid=parseInt(this.dataset.typeId), vid=parseInt(this.dataset.valueId), vname=this.dataset.valueName||'';
        // Deselect all buttons of this type
        document.querySelectorAll('.pdsw-card[data-type-id="'+tid+'"], .pdsw-pill[data-type-id="'+tid+'"]').forEach(function(b){ b.classList.remove('active'); });
        if(pdSel[tid]===vid){
            delete pdSel[tid];
            var chosen=document.getElementById('pdSwChosen_'+tid);
            if(chosen) chosen.textContent='';
        } else {
            pdSel[tid]=vid;
            this.classList.add('active');
            var chosen=document.getElementById('pdSwChosen_'+tid);
            if(chosen) chosen.textContent=' — '+vname;
        }
        pdMatch();
    });
});

// Product-level prepaid discount (fallback when no variation selected)
var pdProductPrepaidDiscount = <?= isset($product['prepaid_discount_amount']) ? (float)$product['prepaid_discount_amount'] : 0 ?>;

function pdSetVariationBase(reg, sale){
    window.pdCurrentRegularPrice = Math.max(0, parseFloat(reg) || 0);
    var s = parseFloat(sale) || 0;
    window.pdCurrentBasePrice = (s > 0 && s < window.pdCurrentRegularPrice) ? s : window.pdCurrentRegularPrice;
    // Also keep closure vars in sync for simple-product fallback path
    if(typeof window.pdSyncVariationPrice === 'function') window.pdSyncVariationPrice(reg, sale);
}

function pdSetPrepaidDiscount(amount){
    var btn=document.querySelector('.payment-btn.prepaid');
    if(!btn)return;
    btn.setAttribute('data-prepaid-discount', amount);
    btn.textContent = amount > 0
        ? 'Prepaid · Save Rs. ' + amount.toLocaleString('en-PK', {minimumFractionDigits:0, maximumFractionDigits:0})
        : 'Prepaid';
    if(typeof window.pdApplyPaymentDiscount === 'function') window.pdApplyPaymentDiscount();
}

function pdMatch(){
    var notice=document.getElementById('pdSwNotice');
    var allTypeIds=[...new Set(PD_VARS.flatMap(function(v){return Object.keys(v.attributes).map(Number);}))];
    var allSelected=allTypeIds.every(function(t){return pdSel[t]!==undefined;});
    if(!Object.keys(pdSel).length){pdReset();return;}
    var match=PD_VARS.find(function(v){return allTypeIds.every(function(t){return v.attributes[t]==pdSel[t];});});
    if(!allSelected){if(match){pdSetVariationBase(match.regular_price,match.sale_price||0);pdUpdatePrice(match.regular_price,match.sale_price);pdSetPrepaidDiscount(match.prepaid_discount_amount||0);} return;}
    if(!match||match.status!=1){
        notice.style.display='block'; notice.textContent=(!match)?'This combination is unavailable.':'This combination is out of stock.';
        document.getElementById('selectedVariationId').value=''; pdDisableCart(); return;
    }
    notice.style.display='none';
    document.getElementById('selectedVariationId').value=match.id;
    pdSetVariationBase(match.regular_price, match.sale_price||0);
    pdUpdatePrice(match.regular_price,match.sale_price);
    pdSetPrepaidDiscount(match.prepaid_discount_amount||0);
    pdUpdateStock(match.stock_quantity);
    if(match.sku){document.getElementById('pdSwSku').style.display='';document.getElementById('pdSwSkuVal').textContent=match.sku;}
    else{document.getElementById('pdSwSku').style.display='none';}
    if(match.image){var mi=document.getElementById('mainImage');if(mi)mi.src=withBase(match.image);}
    match.stock_quantity>0?pdEnableCart(match.sale_price||match.regular_price,match.stock_quantity):pdDisableCart();
}

function pdUpdatePrice(reg,sale){
    var eff=(sale&&sale>0&&sale<reg)?sale:reg;
    var el=document.querySelector('.new-price');if(el)el.textContent='Rs. '+eff.toLocaleString('en-PK',{minimumFractionDigits:2});
    var old=document.querySelector('.old-price');
    if(old){if(sale&&sale>0&&sale<reg){old.textContent='Rs. '+reg.toLocaleString('en-PK',{minimumFractionDigits:2});old.style.display='';}else{old.style.display='none';}}
    document.querySelectorAll('.add-to-cart').forEach(function(b){b.dataset.unitPrice=eff;});
}
function pdUpdateStock(qty){
    var el=document.querySelector('.stock-status');
    if(!el)return;
    var cls=qty>0?'in-stock':'out-of-stock',txt=qty>0?'In Stock':'Out of Stock';
    el.className='stock-status '+cls;
    var dot=el.querySelector('.stock-dot');
    el.textContent='';if(dot)el.appendChild(dot);
    el.appendChild(document.createTextNode(txt));
}
function pdEnableCart(price,maxQty){
    document.querySelectorAll('.add-to-cart,.buy-now').forEach(function(b){b.disabled=false;});
    document.querySelectorAll('.add-to-cart').forEach(function(b){b.dataset.unitPrice=price;});
    document.querySelectorAll('input[name="quantity"]').forEach(function(i){i.max=maxQty;});
}
function pdDisableCart(){document.querySelectorAll('.add-to-cart,.buy-now').forEach(function(b){b.disabled=true;});}
function pdReset(){document.getElementById('selectedVariationId').value='';document.getElementById('pdSwNotice').style.display='none';window.pdCurrentBasePrice=0;window.pdCurrentRegularPrice=0;pdSetPrepaidDiscount(pdProductPrepaidDiscount);}

// Guard: require variation selection before adding to cart
document.querySelectorAll('.add-to-cart').forEach(function(btn){
    btn.addEventListener('click',function(e){
        var vid=document.getElementById('selectedVariationId').value;
        if(!vid){e.stopImmediatePropagation();var n=document.getElementById('pdSwNotice');if(n){n.style.display='block';n.textContent='Please select all product options first.';}return false;}
        btn.dataset.variationId=vid;
    },true);
});

// Auto-select on page load:
// 1. If a variation is marked Default → use its attributes
// 2. Otherwise → click the FIRST visible (non-disabled) swatch in each type group (DOM order)
//    This always matches what the user sees first on screen, regardless of DB insertion order.
(function(){
    var defVar = PD_VARS.find(function(v){return v.is_default==1&&v.status==1&&v.stock_quantity>0;});
    if(defVar){
        // Admin explicitly set a default — respect it
        Object.entries(defVar.attributes).forEach(function(e){
            var b=document.querySelector('.pdsw-card[data-type-id="'+e[0]+'"][data-value-id="'+e[1]+'"], .pdsw-pill[data-type-id="'+e[0]+'"][data-value-id="'+e[1]+'"]');
            if(b) b.click();
        });
    } else {
        // No default set — click the first available swatch in each group (visual/DOM order)
        document.querySelectorAll('.pdsw-group').forEach(function(group){
            var first = group.querySelector('.pdsw-card:not([disabled]), .pdsw-pill:not([disabled])');
            if(first) first.click();
        });
    }
})();

// The payment button is rendered AFTER this script block, so pdSetPrepaidDiscount()
// called during auto-select above finds no button and returns early.
// Re-apply the variation's prepaid discount once the full DOM is available.
document.addEventListener('DOMContentLoaded', function(){
    var allTypeIds=[...new Set(PD_VARS.flatMap(function(v){return Object.keys(v.attributes).map(Number);}))];
    if(!allTypeIds.length) return;
    var allSelected=allTypeIds.every(function(t){return pdSel[t]!==undefined;});
    if(!allSelected) return;
    var match=PD_VARS.find(function(v){return allTypeIds.every(function(t){return v.attributes[t]==pdSel[t];});});
    if(match){pdSetVariationBase(match.regular_price,match.sale_price||0);pdSetPrepaidDiscount(match.prepaid_discount_amount||0);}
});
})();
</script>
<?php endif; // end variation swatches ?>

<!-- Cart Form -->
<?php
$cartFormCondition = $productAvailability === 'instock' && $stockQuantity > 0 && ($validPrice > 0 || !empty($productAttributes) || !empty($isVariableProduct));
$prepaidDiscountAmount = isset($product['prepaid_discount_amount']) ? (float)$product['prepaid_discount_amount'] : 0;
$prepaidBtnLabel = $prepaidDiscountAmount > 0
    ? 'Prepaid · Save Rs. ' . number_format($prepaidDiscountAmount, 0)
    : 'Prepaid';
if ($cartFormCondition): ?>
            <!-- Payment Method Selection -->
    <div class="payment-method-selection">
        <button type="button" class="payment-btn cod active" data-method="cod">COD</button>
        <button type="button" class="payment-btn prepaid" data-method="prepaid" data-prepaid-discount="<?= $prepaidDiscountAmount ?>"><?= $prepaidBtnLabel ?></button>
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
                data-variation-id=""
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