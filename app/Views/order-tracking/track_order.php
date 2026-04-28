<?php
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>
<div class="track-order-wrapper">
<div class="track-order-container">
<h2>Track Your Order</h2>

<form method="post" action="/track-order">
    <div class="form-group">
    <label for="email">Email:</label>
    <input type="email" name="email" placeholder="Enter your email address" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"><br>
    </div>
    <div class="form-group">
    <label>Order ID:</label>
    <input type="text" name="order_id" placeholder="Enter your order ID" required value="<?= isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : '' ?>"><br>
    </div>
    <div class="form-group">
    <button type="submit">Track Order</button>
</div>
</form>
<style>
.track-order-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 100%;
    margin: 20px 0;
}

.track-order-container {
    background-color: #fff;
    padding: 30px;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    width: 400px;
    border-radius: 8px;
}

.track-order-container h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.form-group {
    margin-bottom: 15px; /* Reduced space */
}

.form-group label {
    display: block;
    font-size: 14px;
    margin-bottom: 3px; /* Reduced margin between label and input */
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 8px; /* Adjust padding for less space inside input */
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 0; /* Ensure no additional margin added here */
}

.form-group input:focus {
    outline: none;
    border-color: #4CAF50;
}

.form-group button {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

/* output */
.order-wrapper {
    margin-top: 30px;
    display: flex;
    justify-content: center;
}

.order-card {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 6px solid #4CAF50;
    border-radius: 10px;
    padding: 30px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.order-title {
    font-size: 22px;
    font-weight: 600;
    color: #2c2c2c;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #eaeaea;
}

.order-details {
    list-style: none;
    padding: 0;
    margin: 0;
}

.order-details li {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px dashed #e5e5e5;
    margin: 0px;
}

.order-details li:last-child {
    border-bottom: none;
}

.order-details strong {
    color: #555;
    font-weight: 600;
    min-width: 140px;
    font-size: 15px;
}

.order-details span {
    color: #333;
    font-size: 15px;
    text-align: right;
    word-break: break-word;
    flex: 1;
    margin: 0px;
    font-weight: normal;
    font-style: normal;
}

.order-details pre {
    margin: 0;
    padding: 0;
    background: none;
    border: none;
    font-family: inherit;
    font-size: 15px;
    white-space: pre-wrap;
    word-wrap: break-word;
    color: #333;
}

@media only screen and (max-width: 600px) {
    .track-order-wrapper {
    margin: 0px;
}

.track-order-container {
    padding: 15px;
}
.order-card {
    padding: 15px;
}
.order-details li {
    padding: 5px;
}
h3.order-title {
    margin: 0px;
}
}
</style>

<?php if (isset($error)) : ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (isset($order) && $order) : ?>
    <div class="order-wrapper">
  <div class="order-card">
    <h3 class="order-title">Order Details</h3>
    <ul class="order-details">
        <li><strong>Status:</strong> <span><?= htmlspecialchars($order['order_status']) ?></span></li>
        <li><strong>Created At:</strong> <span><?= htmlspecialchars($order['created_at']) ?></span></li>
        <li><strong>Total Price:</strong> <span><?= htmlspecialchars($order['total_price']) . ' ' . htmlspecialchars($order['currency']) ?></span></li>
        <li><strong>Customer Name:</strong> <span><?= htmlspecialchars($order['customer_name']) ?></span></li>
        <li><strong>Email:</strong> <span><?= htmlspecialchars($order['customer_email']) ?></span></li>
        <li><strong>Shipping:</strong> 
            <span><?= htmlspecialchars($order['shipping_address']) ?>, 
            <?= htmlspecialchars($order['shipping_city']) ?>, 
            <?= htmlspecialchars($order['shipping_country']) ?></span>
        </li>
        <li><strong>Payment Method:</strong> <span><?= htmlspecialchars($order['payment_method_title']) ?></span></li>
        <li><strong>Ordered Products:</strong> <span><pre><?= htmlspecialchars($order['ordered_product']) ?></pre></span></li>
    </ul>
  </div>
</div>


<?php endif; ?>
</div>
</div>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
