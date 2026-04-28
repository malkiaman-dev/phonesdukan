<?php
require_once __DIR__ . '/../../Models/CartModel.php';
require_once __DIR__ . '/../../../includes/header.php';

$cartModel = new CartModel();
$sessionId = $_SESSION['session_id'] ?? session_id();  
$userId = $_SESSION['user_id'] ?? null;

$cartItems = $cartModel->fetchCartItems($sessionId, $userId);

// Compute totals for initial render
$totalPrice = 0;
$totalQty = 0;
$grandSubtotal = 0;
$discountRate = 0;
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
    <h1>Your Shopping Cart</h1>

    <?php if (empty($cartItems)) : ?>
        <p class="empty-cart-message">Your cart is empty.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Product Price</th>
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
                    <td>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="80" height="80">
                        <p><?php echo htmlspecialchars($item['product_name']); ?></p>
                        <?php if (!empty($item['attribute_value'])): ?>
                        <p class="attribute" id="attribute_<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['attribute_value']); ?></p>
                        <?php else: ?>
                        <p class="attribute" id="attribute_<?php echo $item['product_id']; ?>" style="display: none;">No attribute available</p>
                        <?php endif; ?>
                    </td>
                    <td>PKR <?php echo number_format((float) $item['unit_price'], 2); ?></td>
                    <td>
                        <div class="quantity-wrapper">
                            <button type="button" class="minus" data-id="<?php echo $item['product_id']; ?>">−</button>
                            <div class="quantity">
                                <input type="number" id="quantity_<?php echo $item['product_id']; ?>" class="input-text qty text" 
                                       name="quantity_<?php echo $item['product_id']; ?>" value="<?php echo htmlspecialchars($item['total_quantity']); ?>" 
                                       aria-label="Product quantity" min="1" step="1" placeholder="" inputmode="numeric" autocomplete="off">
                            </div>
                            <button type="button" class="plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                        </div>
                        <span id="unit-price_<?php echo $item['product_id']; ?>" style="display: none;"><?php echo $item['unit_price']; ?></span>
                    </td>
                    <td>PKR <?php echo number_format((float) $item['subtotal'], 2); ?></td>
                    <td class="discount-cell">
  <p><?php echo ($totalQty >= 10) ? '7% OFF' : (($totalQty >= 3) ? '5% OFF' : '0% OFF'); ?></p>
</td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $item['payment_method'] ?? 'cod')); ?></td>
                    <td><button class="remove-item" data-id="<?php echo $item['product_id']; ?>">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total">
            <h3>Total: <span id="total-price">PKR <?php echo number_format($totalPrice, 2); ?></span></h3>
        </div>

        <a href="/checkout">
            <button class="checkout-btn">Proceed to Checkout</button>
        </a>
    <?php endif; ?>
</div>

<script>
window.initialCartTotals = {
    subtotal: '<?php echo $grandSubtotal; ?>',
    discount: '<?php echo $discountAmount; ?>',
    total: '<?php echo $totalPrice; ?>',
    discount_rate: <?php echo $discountRate * 100; ?>
};
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>