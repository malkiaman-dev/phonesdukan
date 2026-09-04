<div class="na-actions<?= !empty($product['is_coming_soon']) ? ' na-actions--single' : '' ?>">
    <?php if (!empty($product['is_coming_soon'])): ?>
        <a href="<?= htmlspecialchars($product['product_url'] ?? '#') ?>" class="na-btn na-btn--details">Show Details</a>
    <?php elseif (empty($product['is_sold_out']) && (float) ($product['unit_price'] ?? 0) > 0): ?>
        <button class="na-btn na-btn--cart"
            data-product-id="<?= (int) ($product['product_id'] ?? 0) ?>"
            data-unit-price="<?= (float) ($product['unit_price'] ?? 0) ?>">
            Add to Cart
        </button>
        <button class="na-btn na-btn--buy buy-button"
            data-product-id="<?= (int) ($product['product_id'] ?? 0) ?>"
            data-unit-price="<?= (float) ($product['unit_price'] ?? 0) ?>">
            Buy Now
        </button>
    <?php else: ?>
        <span class="na-btn na-btn--soldout">Sold Out</span>
    <?php endif; ?>
</div>
