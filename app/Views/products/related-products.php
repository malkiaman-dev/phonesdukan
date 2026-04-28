<?php if (!empty($relatedProducts)): ?>
    <div class="related-product">
        <div class="category-header">
            <h2>Related <span>Products</span></h2>
        </div>

        <div class="product-grid-container">
            <div class="product-grid-wrapper">
                <?php foreach ($relatedProducts as $product): ?>
                    <?php
                    // Construct product URL
                    $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                        . htmlspecialchars($product['brand_slug']) . '/'
                        . htmlspecialchars($product['product_slug']);

                    // Set product image with fallback
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : '/public/assets/images/Phones_dukan_favicon.png';

                    // Format product price
                    $product_price = '';

                    if (!empty($product['sale_price']) && !empty($product['regular_price'])) {
                        $product_price = '<span class="r-regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> '
                            . '<span class="r-sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                    } elseif (!empty($product['regular_price'])) {
                        $product_price = '<span class="regular-price new-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                    } elseif (!empty($product['sale_price'])) {
                        $product_price = '<span class="sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                    } else {
                        $product_price = '<span>Price: Not available</span>';
                    }

                    ?>
                    <div class="product-card">
                        <a href="<?php echo $product_url; ?>">
                            <div class="product-img-wrapper">
                                <div class="product-img">
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </div>
                            </div>
                        </a>
                        <h3 class="product-title">
                            <a href="<?php echo $product_url; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h3>
                        <div class="r-product-price">
    <?php echo $product_price; ?>
</div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
