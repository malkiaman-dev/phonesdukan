<?php if (!empty($relatedProducts)): ?>
    <div class="related-product">
        <div class="category-header">
            <h2>Related <span>Products</span></h2>
        </div>

        <div class="product-grid-container">
            <div class="product-grid-wrapper">
                <?php foreach ($relatedProducts as $product):
                    $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                        . htmlspecialchars($product['brand_slug']) . '/'
                        . htmlspecialchars($product['product_slug']);

                    $product_image = !empty($product['image_url'])
                        ? $product['image_url']
                        : '/public/assets/images/Phones_dukan_favicon.png';

                    $regular     = isset($product['regular_price']) && is_numeric($product['regular_price']) ? (float)$product['regular_price'] : 0;
                    $sale        = isset($product['sale_price'])    && is_numeric($product['sale_price'])    ? (float)$product['sale_price']    : 0;
                    $has_sale    = $sale > 0 && $regular > $sale;
                    $unit_price  = $has_sale ? $sale : $regular;
                    $discount_pct = $has_sale ? round((($regular - $sale) / $regular) * 100) : 0;

                    // Determine sold-out state from any available field
                    $is_sold_out = (isset($product['stock_quantity']) && (int)$product['stock_quantity'] <= 0)
                        || (!empty($product['is_sold_out']))
                        || (isset($product['product_status']) && (int)$product['product_status'] !== 1);
                ?>
                    <div class="na-card">
                        <?php if ($is_sold_out): ?>
                            <span class="na-badge na-badge--sold">Sold Out</span>
                        <?php elseif ($discount_pct > 0): ?>
                            <span class="na-badge"><?= $discount_pct ?>% OFF</span>
                        <?php endif; ?>

                        <a href="<?= $product_url ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= htmlspecialchars($product_image) ?>"
                                     alt="<?= htmlspecialchars($product['product_name']) ?>"
                                     loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product_url ?>"><?= htmlspecialchars($product['product_name']) ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($has_sale): ?>
                                    <span class="na-price--old">Rs. <?= number_format($regular) ?></span>
                                    <span class="na-price--new">Rs. <?= number_format($sale) ?></span>
                                <?php elseif ($regular > 0): ?>
                                    <span class="na-price--new">Rs. <?= number_format($regular) ?></span>
                                <?php elseif ($sale > 0): ?>
                                    <span class="na-price--new">Rs. <?= number_format($sale) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <div class="na-actions">
                                <?php if (!$is_sold_out && $unit_price > 0): ?>
                                    <button class="na-btn na-btn--cart rp-cart-btn"
                                            data-product-id="<?= (int)$product['product_id'] ?>"
                                            data-unit-price="<?= (float)$unit_price ?>">
                                        Add to Cart
                                    </button>
                                    <button class="na-btn na-btn--buy rp-buy-btn"
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
            </div>
        </div>
    </div>
<?php endif; ?>
