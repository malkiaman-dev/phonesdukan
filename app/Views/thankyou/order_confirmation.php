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
<div class="order_container">
    <h1><i class="fas fa-check-circle"></i> Order Placed Successfully!</h1>
    <div class="thank-you-message">
        <div class="thank-you-icon">
            <i class="fas fa-gift"></i> <!-- Icon to represent the purchase -->
        </div>
        <h1 class="thank-you-title">Thank You for Your Purchase!</h1>
        <p class="thank-you-text">
            <span class="highlighted"><?php echo htmlspecialchars($order['customer_name']); ?></span>, your order is being processed.
        </p>
        <p class="thank-you-subtext">We appreciate your business. You will receive an email confirmation soon.</p>
    </div>

    <div class="order-details card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i>
            <h2>Order Details</h2>
        </div>
        <div class="card-body">
            <p>
                <strong>Order Number:</strong> 
                <span id="orderNumber"><?php echo $order['order_id']; ?></span>
                <!-- Copy Icon -->
                <button id="copyButton" class="copy-btn" onclick="copyOrderNumber()">
                    <i class="fas fa-copy"></i> <!-- FontAwesome copy icon -->
                </button>
            </p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
            <p><strong>Status:</strong> <span class="status-processing">Processing</span></p>
        </div>
    </div>

    <div class="customer-info card">
        <div class="card-header">
            <i class="fas fa-user"></i>
            <h2>Customer Information</h2>
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
        </div>
    </div>

    <div class="shipping-info card">
        <div class="card-header">
            <i class="fas fa-truck"></i>
            <h2>Shipping Information</h2>
        </div>
        <div class="card-body">
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_country']); ?></p>
        </div>
    </div>

    <div class="order-items">
        <h2>Ordered Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            foreach ($orderItems as $item): 
                $itemTotalPrice = $item['subtotal_price']; // Price of the item
            ?>
            <tr>
                <td data-label="Product Image">
                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/public/assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="80" height="80">
                </td>
                <td data-label="Product Name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td data-label="Quantity"><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td data-label="Subtotal">Rs. <?php echo number_format($item['subtotal_price'], 2); ?></td>  <!-- Show subtotal_price -->
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="summary card">
        <div class="card-header">
            <i class="fas fa-credit-card"></i>
            <h2>Payment Summary</h2>
        </div>
        <div class="card-body">
            <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
            <!-- Add delivery charge to the summary -->
            <p><strong>Delivery Charges:</strong> <span class="delivery-price">Rs. <?php echo number_format($deliveryCharge, 2); ?></span></p>
            <!-- Show total amount including the delivery charge -->
            <p><strong>Total Amount (including Delivery):</strong> <span class="total-price">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span></p>
        </div>
    </div>

    <a href="/" class="btn"><i class="fas fa-home"></i> Back to Home</a>
</div>

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

        // Optionally show an alert or visual confirmation
        alert('Order number copied to clipboard!');
    }
</script>
</body>
</html>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>