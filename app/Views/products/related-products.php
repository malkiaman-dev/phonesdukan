<?php if (!empty($relatedProducts)): ?>
    <div class="related-product">
        <div class="category-header">
            <h2>Related <span>Products</span></h2>
        </div>

        <div class="product-grid-container">
            <div class="product-grid-wrapper">
                <?php foreach ($relatedProducts as $row):
                    $product = prepareProductCardFromRow($row);
                ?>
                    <div class="na-card">
                        <?php include __DIR__ . '/../partials/na-card-badge.php'; ?>

                        <a href="<?= $product['product_url'] ?>" class="na-img-link">
                            <div class="na-img-box">
                                <img src="<?= $product['product_image'] ?>"
                                     alt="<?= $product['product_name'] ?>"
                                     loading="lazy">
                            </div>
                        </a>

                        <div class="na-body">
                            <h3 class="na-name">
                                <a href="<?= $product['product_url'] ?>"><?= $product['product_name'] ?></a>
                            </h3>

                            <div class="na-price">
                                <?php if ($product['has_sale']): ?>
                                    <span class="na-price--old">Rs. <?= number_format($product['regular_price']) ?></span>
                                    <span class="na-price--new">Rs. <?= number_format($product['sale_price']) ?></span>
                                <?php elseif ($product['regular_price'] > 0): ?>
                                    <span class="na-price--new">Rs. <?= number_format($product['regular_price']) ?></span>
                                <?php elseif ($product['sale_price'] > 0): ?>
                                    <span class="na-price--new">Rs. <?= number_format($product['sale_price']) ?></span>
                                <?php else: ?>
                                    <span class="na-price--na">Price N/A</span>
                                <?php endif; ?>
                            </div>

                            <?php include __DIR__ . '/../partials/na-card-actions.php'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
