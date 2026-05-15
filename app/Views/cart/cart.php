<?php
require_once __DIR__ . '/../../Models/CartModel.php';
require_once __DIR__ . '/../../../includes/header.php';

$cartModel = new CartModel();
$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;

$cartItems = $cartModel->fetchCartItems($sessionId, $userId);

// Compute totals for initial render
$totalPrice = 0;
$totalQty = 0;
$grandSubtotal = 0;
$discountRate = 0;
$discountAmount = 0;
if (!empty($cartItems)) {
    foreach ($cartItems as $item) {
        $totalQty += (int)$item['total_quantity'];
        $grandSubtotal += (float)$item['subtotal'];
    }
    $discountRate = ($totalQty >= 10) ? 0.07 : ($totalQty >= 3 ? 0.05 : 0);
    $discountAmount = $grandSubtotal * $discountRate;
    $totalPrice = $grandSubtotal - $discountAmount;
}
?>

<div class="cart-container">
    <h1 class="cart-heading">Your Shopping Cart</h1>

    <?php if (empty($cartItems)) : ?>
        <div class="empty-cart-wrapper">
            <svg class="empty-cart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <p class="empty-cart-message">Your cart is empty.</p>
            <a href="/shop" class="continue-shopping-btn">Continue Shopping</a>
        </div>
    <?php else : ?>

    <div class="cart-layout">

        <!-- LEFT: Cart Items -->
        <div class="cart-items-col">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item) : ?>
                    <tr>
                        <td class="td-product">
                            <img src="<?php echo htmlspecialchars(!empty($item['image_url']) ? $item['image_url'] : '/public/assets/images/Phones_dukan_favicon.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <div class="product-info">
                                <p class="product-name-text"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                <?php if (!empty($item['variation_attributes'])): ?>
                                <div class="cart-var-pills" id="attribute_<?php echo $item['product_id']; ?>">
                                    <?php foreach (explode(',', $item['variation_attributes']) as $attr):
                                        $attr = trim($attr); if (!$attr) continue;
                                        $val = trim(strpos($attr, ':') !== false ? substr($attr, strpos($attr, ':') + 1) : $attr);
                                    ?>
                                    <span class="cart-var-pill"><?= htmlspecialchars($val) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php elseif (!empty($item['attribute_value'])): ?>
                                <p class="attribute" id="attribute_<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['attribute_value']); ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="td-price" data-label="Price">PKR <?php echo number_format((float) $item['unit_price'], 2); ?></td>
                        <td class="td-qty">
                            <div class="quantity-wrapper">
                                <button type="button" class="minus" data-id="<?php echo $item['product_id']; ?>">−</button>
                                <div class="quantity">
                                    <input type="number" id="quantity_<?php echo $item['product_id']; ?>" class="input-text qty text"
                                           name="quantity_<?php echo $item['product_id']; ?>" value="<?php echo htmlspecialchars($item['total_quantity']); ?>"
                                           aria-label="Product quantity" min="1" step="1" placeholder="" inputmode="numeric" autocomplete="off">
                                </div>
                                <button type="button" class="plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                            </div>
                            <span id="unit-price_<?php echo $item['product_id']; ?>" style="display:none;"><?php echo $item['unit_price']; ?></span>
                        </td>
                        <td class="td-subtotal" data-label="Subtotal">PKR <?php echo number_format((float) $item['subtotal'], 2); ?></td>
                        <td class="td-discount discount-cell">
                            <p><?php echo ($totalQty >= 10) ? '7% OFF' : (($totalQty >= 3) ? '5% OFF' : '0% OFF'); ?></p>
                        </td>
                        <td class="td-payment">
                            <span class="payment-badge"><?php echo ucfirst(str_replace('_', ' ', $item['payment_method'] ?? 'cod')); ?></span>
                        </td>
                        <td class="td-action">
                            <button class="remove-item" data-id="<?php echo $item['product_id']; ?>" aria-label="Remove item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="cart-total">
            <div class="summary-card">
                <h2 class="summary-title">Order Summary</h2>

                <div class="summary-lines">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-val" id="summary-subtotal-val">PKR <?php echo number_format($grandSubtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" id="summary-discount-label">Discount<?php if ($discountRate > 0): ?> (<?php echo ($discountRate * 100); ?>%)<?php endif; ?></span>
                        <?php if ($discountRate > 0): ?>
                        <span class="summary-val summary-val--discount" id="summary-discount-val">− PKR <?php echo number_format($discountAmount, 2); ?></span>
                        <?php else: ?>
                        <span class="summary-val" id="summary-discount-val">PKR 0.00</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Delivery</span>
                        <span class="summary-val summary-val--free">Free</span>
                    </div>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-total-row">
                    <span class="summary-total-label">Total</span>
                    <span id="total-price">PKR <?php echo number_format($totalPrice, 2); ?></span>
                </div>

                <a href="/checkout" class="checkout-link">
                    <button class="checkout-btn">Proceed to Checkout</button>
                </a>

                <a href="/shop" class="continue-shopping-link">← Continue Shopping</a>
            </div>
        </div>

    </div><!-- /.cart-layout -->

    <?php endif; ?>
</div><!-- /.cart-container -->

<script>
window.initialCartTotals = {
    subtotal: '<?php echo $grandSubtotal; ?>',
    discount: '<?php echo $discountAmount; ?>',
    total: '<?php echo $totalPrice; ?>',
    discount_rate: <?php echo $discountRate * 100; ?>
};
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
