<?php if (!empty($relatedProducts)): ?>
    <div class="related-product">
        <div class="category-header">
            <h2>Related <span>Products</span></h2>
        </div>

        <div class="product-grid-container">
            <div class="product-grid-wrapper">
                <?php foreach ($relatedProducts as $product): ?>
                    <?php
                    $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                        . htmlspecialchars($product['brand_slug']) . '/'
                        . htmlspecialchars($product['product_slug']);

                    $product_image = !empty($product['image_url'])
                        ? $product['image_url']
                        : '/public/assets/images/Phones_dukan_favicon.png';

                    $regular = !empty($product['regular_price']) ? (float)$product['regular_price'] : 0;
                    $sale    = !empty($product['sale_price'])    ? (float)$product['sale_price']    : 0;
                    $hasDiscount = $sale > 0 && $regular > $sale;
                    $discountPct = $hasDiscount ? round((($regular - $sale) / $regular) * 100) : 0;
                    ?>
                    <div class="product-card">
                        <?php if ($hasDiscount): ?>
                            <span class="na-badge"><?php echo $discountPct; ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?php echo $product_url; ?>" class="na-img-link">
                            <div class="product-img">
                                <img src="<?php echo htmlspecialchars($product_image); ?>"
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     loading="lazy">
                            </div>
                        </a>

                        <h3 class="product-title">
                            <a href="<?php echo $product_url; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h3>

                        <div class="r-product-price">
                            <?php if ($hasDiscount): ?>
                                <span class="r-regular-price old-price">Rs. <?php echo number_format($regular); ?></span>
                                <span class="r-sale-price new-price">Rs. <?php echo number_format($sale); ?></span>
                            <?php elseif ($regular > 0): ?>
                                <span class="regular-price new-price">Rs. <?php echo number_format($regular); ?></span>
                            <?php elseif ($sale > 0): ?>
                                <span class="sale-price new-price">Rs. <?php echo number_format($sale); ?></span>
                            <?php else: ?>
                                <span style="font-size:13px;color:#999;">Price not available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
