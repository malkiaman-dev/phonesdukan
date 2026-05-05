<?php
ob_start();

// Include the database connection and header (ROOT-RELATIVE)
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Get Order ID from $_GET (SET BY ROUTER)
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die('Invalid order ID.');
}

$order_id = (int)$_GET['order_id'];

try {
    // Fetch order details from the orders table
    $stmt = $conn->prepare('SELECT * FROM orders WHERE order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Order not found.');
    }

    // Fetch ordered items along with product details
    $stmt = $conn->prepare('
        SELECT oi.product_id, oi.quantity, oi.subtotal_price, p.product_name, 
               p.regular_price, p.sale_price, pi.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ');
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Initialize the total price variable
$totalPrice = 0;

// Loop through the ordered items to calculate the total price
foreach ($orderItems as $item) {
    // Add the subtotal_price from the order_items table to the total price
    $totalPrice += $item['subtotal_price'];  // Use subtotal_price directly
}

// Add the flat delivery charge (Rs. 149) to the total price
$deliveryCharge = 149;
$totalPriceWithDelivery = $totalPrice + $deliveryCharge;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Phones Dukan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- FontAwesome Link -->
</head>
<body>
<main class="order_container">
    <section class="hero card fade-in">
        <div class="hero-icon" aria-hidden="true">
            <i class="fas fa-check"></i>
        </div>
        <h1>Order Placed Successfully</h1>
        <p class="hero-subtext">Your order is confirmed and being processed.</p>
        <div class="hero-order-line">
            <span class="hero-order-label">Order #</span>
            <span id="orderNumber" class="hero-order-number"><?php echo $order['order_id']; ?></span>
            <button id="copyButton" class="copy-btn" onclick="copyOrderNumber()" type="button" aria-label="Copy order number">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </section>

    <section class="card fade-in section-card">
        <div class="section-title">
            <i class="fas fa-receipt"></i>
            <h2>Order Summary</h2>
        </div>
        <div class="info-grid">
            <div class="info-row"><span class="label">Order Number</span><span class="value"><?php echo $order['order_id']; ?></span></div>
            <div class="info-row"><span class="label">Date</span><span class="value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span></div>
            <div class="info-row"><span class="label">Status</span><span class="value status-processing">Processing</span></div>
            <div class="info-row"><span class="label">Payment Method</span><span class="value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span></div>
            <div class="info-row total-row"><span class="label">Total Amount</span><span class="value">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span></div>
        </div>
    </section>

    <section class="card fade-in section-card">
        <div class="section-title">
            <i class="fas fa-user"></i>
            <h2>Customer &amp; Shipping</h2>
        </div>
        <div class="split-grid">
            <div>
                <div class="info-row"><span class="label">Name</span><span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
                <div class="info-row"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($order['customer_email']); ?></span></div>
                <div class="info-row"><span class="label">Phone</span><span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span></div>
            </div>
            <div>
                <div class="section-title mini">
                    <i class="fas fa-truck"></i>
                    <h3>Shipping Address</h3>
                </div>
                <p class="address">
                    <?php echo htmlspecialchars($order['shipping_address']); ?>,
                    <?php echo htmlspecialchars($order['shipping_city']); ?>,
                    <?php echo htmlspecialchars($order['shipping_country']); ?>
                </p>
            </div>
        </div>
    </section>

    <section class="fade-in products-wrap">
        <div class="section-title">
            <i class="fas fa-box-open"></i>
            <h2>Ordered Products</h2>
        </div>
        <div class="products-list">
            <?php foreach ($orderItems as $item): ?>
                <article class="product-item card">
                    <div class="product-left">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/public/assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                    </div>
                    <div class="product-center">
                        <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                        <p>Unit Price: Rs. <?php echo number_format(($item['quantity'] > 0 ? ($item['subtotal_price'] / $item['quantity']) : 0), 2); ?></p>
                    </div>
                    <div class="product-right">
                        <span class="qty">Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                        <span class="price">Rs. <?php echo number_format($item['subtotal_price'], 2); ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card fade-in section-card">
        <div class="section-title">
            <i class="fas fa-credit-card"></i>
            <h2>Payment Summary</h2>
        </div>
        <div class="info-grid">
            <div class="info-row"><span class="label">Subtotal</span><span class="value">Rs. <?php echo number_format($totalPrice, 2); ?></span></div>
            <div class="info-row"><span class="label">Delivery</span><span class="value">Rs. <?php echo number_format($deliveryCharge, 2); ?></span></div>
            <div class="info-row total-row"><span class="label">Total</span><span class="value total-price">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span></div>
        </div>
    </section>

    <section class="cta-wrap fade-in">
        <a href="/" class="btn btn-primary"><i class="fas fa-store"></i> Continue Shopping</a>
    </section>
</main>

<script>
    function copyOrderNumber() {
        // Get the order number text
        var orderNumber = document.getElementById('orderNumber').textContent;

        // Create a temporary text area to copy the order number
        var tempTextArea = document.createElement('textarea');
        tempTextArea.value = orderNumber;
        document.body.appendChild(tempTextArea);
        
        // Select and copy the text
        tempTextArea.select();
        document.execCommand('copy');
        
        // Remove the temporary text area
        document.body.removeChild(tempTextArea);

        var copyButton = document.getElementById('copyButton');
        copyButton.classList.add('copied');
        copyButton.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function () {
            copyButton.classList.remove('copied');
            copyButton.innerHTML = '<i class="fas fa-copy"></i>';
        }, 1200);
    }
</script>
</body>
</html>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>