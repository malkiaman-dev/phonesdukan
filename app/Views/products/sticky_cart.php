<?php if ($productAvailability === 'instock' && $validPrice > 0): ?>
    <div class="sticky-cart-wrapper">
        <div class="cart-form">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
            <div class="quantity-wrapper">
                <button type="button" class="quantity-btn minus">-</button>
                <input type="number" id="sticky-quantity" name="quantity" value="1" min="1" max="<?php echo isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 10; ?>" readonly>
                <button type="button" class="quantity-btn plus">+</button>
            </div>
            <button type="button" id="sticky-add-to-cart-btn" class="btn add-to-cart" 
                    data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>" 
                    data-unit-price="<?php echo $validPrice; ?>" 
                    data-attribute-value="<?php echo isset($selectedAttributeValue) ? htmlspecialchars($selectedAttributeValue) : ''; ?>">
                Add to Cart
            </button>
            <button type="button" class="btn buy-now">Buy Now</button>
        </div>
    </div>
<?php endif; ?>