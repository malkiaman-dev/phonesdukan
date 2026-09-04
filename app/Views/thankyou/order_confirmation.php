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
               p.regular_price, p.sale_price, pi.image_url,
               oi.variation_id, oi.variation_sku, oi.variation_attributes
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
    <style>
        /* Modern Premium UI Reset and Variables */
        :root {
            --primary-color: #111111;
            --accent-color: #facc15;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #111111;
            --text-secondary: #6b7280;
            --border-radius: 12px;
            --soft-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --border-color: #e5e7eb;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
        }

        .ty-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-primary);
        }

        /* Animations */
        @keyframes ty-fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .ty-fade-in {
            animation: ty-fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .ty-delay-1 { animation-delay: 0.1s; }
        .ty-delay-2 { animation-delay: 0.2s; }
        .ty-delay-3 { animation-delay: 0.3s; }
        .ty-delay-4 { animation-delay: 0.4s; }

        /* Shared Card Styles */
        .ty-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--soft-shadow);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .ty-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .ty-card-header i {
            color: var(--primary-color);
            font-size: 1.2rem;
            background: var(--bg-color);
            padding: 10px;
            border-radius: 8px;
        }

        .ty-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        /* Success Header Hero */
        .ty-hero {
            text-align: center;
            padding: var(--spacing-xl) var(--spacing-lg);
        }

        .ty-success-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background-color: var(--primary-color);
            color: var(--accent-color);
            border-radius: 50%;
            font-size: 28px;
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 8px 16px rgba(17, 17, 17, 0.1);
        }

        .ty-hero h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 var(--spacing-sm) 0;
            letter-spacing: -0.02em;
        }

        .ty-hero-subtext {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0 0 var(--spacing-lg) 0;
        }

        .ty-order-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
        }

        .ty-copy-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 4px;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .ty-copy-btn:hover {
            color: var(--primary-color);
        }
        
        .ty-copy-btn.copied {
            color: var(--primary-color);
        }

        /* Information Grids */
        .ty-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: var(--spacing-md);
        }

        .ty-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ty-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .ty-value {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .ty-status-badge {
            display: inline-block;
            background: var(--primary-color);
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            width: fit-content;
        }

        /* 2 Column Grid for Customer & Shipping */
        .ty-split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
        }
        
        .ty-split-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Product List */
        .ty-product-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .ty-product-item {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            background: var(--bg-color);
            border-radius: 10px;
            gap: var(--spacing-md);
        }

        .ty-product-img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #fff;
            border: 1px solid var(--border-color);
            padding: 4px;
        }

        .ty-product-details {
            flex: 1;
        }

        .ty-product-name {
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 4px 0;
            font-size: 0.95rem;
        }

        .ty-product-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .ty-product-price {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        
        .ty-product-qty {
            font-size: 0.85rem;
            color: var(--text-secondary);
            background: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .ty-product-total {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Payment Summary */
        .ty-payment-summary {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ty-payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .ty-payment-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
            padding-top: var(--spacing-md);
            margin-top: 4px;
            border-top: 1px solid var(--border-color);
        }

        .ty-payment-total .ty-value {
            color: var(--primary-color);
            background: var(--accent-color);
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 1.1rem;
        }

        /* CTA Section */
        .ty-cta-section {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            margin-top: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .ty-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .ty-btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(17, 17, 17, 0.15);
        }

        .ty-btn-primary:hover {
            background-color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(17, 17, 17, 0.2);
            color: #ffffff;
        }

        /* Mobile Optimization */
        @media (max-width: 640px) {
            .ty-container {
                padding: 0 16px;
                margin: 24px auto;
            }
            
            .ty-card {
                padding: var(--spacing-md);
                border-radius: 10px;
            }

            .ty-split-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .ty-info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .ty-product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                position: relative;
            }

            .ty-product-img {
                width: 48px;
                height: 48px;
            }
            
            .ty-product-price {
                position: absolute;
                top: var(--spacing-md);
                right: var(--spacing-md);
                align-items: flex-end;
            }

            .ty-cta-section {
                flex-direction: column;
            }

            .ty-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body style="background-color: #f8fafc;">
<main class="ty-container">
    <!-- Success Header -->
    <section class="ty-card ty-hero ty-fade-in">
        <div class="ty-success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Order Placed Successfully</h1>
        <p class="ty-hero-subtext">Your order is confirmed and being processed.</p>
        <div class="ty-order-badge">
            <span class="ty-label" style="margin:0;">Order #</span>
            <span id="orderNumber"><?php echo $order['order_id']; ?></span>
            <button id="copyButton" class="ty-copy-btn" onclick="copyOrderNumber()" type="button" aria-label="Copy order number" title="Copy">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </section>

    <!-- Order Summary -->
    <section class="ty-card ty-fade-in ty-delay-1">
        <div class="ty-card-header">
            <i class="fas fa-receipt"></i>
            <h2 class="ty-card-title">Order Summary</h2>
        </div>
        <div class="ty-info-grid">
            <div class="ty-info-item">
                <span class="ty-label">Date</span>
                <span class="ty-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="ty-info-item">
                <span class="ty-label">Status</span>
                <span class="ty-status-badge">Processing</span>
            </div>
            <div class="ty-info-item">
                <span class="ty-label">Payment Method</span>
                <span class="ty-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
            </div>
            <div class="ty-info-item">
                <span class="ty-label">Total Amount</span>
                <span class="ty-value">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span>
            </div>
        </div>
    </section>

    <!-- Customer & Shipping -->
    <section class="ty-card ty-fade-in ty-delay-2">
        <div class="ty-card-header">
            <i class="fas fa-user"></i>
            <h2 class="ty-card-title">Customer &amp; Shipping</h2>
        </div>
        <div class="ty-split-grid">
            <div class="ty-split-col">
                <div class="ty-info-item">
                    <span class="ty-label">Customer Details</span>
                    <span class="ty-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    <span class="ty-value" style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    <span class="ty-value" style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                </div>
            </div>
            <div class="ty-split-col">
                <div class="ty-info-item">
                    <span class="ty-label">Shipping Address</span>
                    <span class="ty-value" style="line-height: 1.4;">
                        <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                        <?php echo htmlspecialchars($order['shipping_country']); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Ordered Products -->
    <section class="ty-card ty-fade-in ty-delay-3">
        <div class="ty-card-header">
            <i class="fas fa-box"></i>
            <h2 class="ty-card-title">Items Ordered</h2>
        </div>
        <div class="ty-product-list">
            <?php foreach ($orderItems as $item): ?>
                <div class="ty-product-item">
                    <img class="ty-product-img" src="<?php echo htmlspecialchars($item['image_url'] ?? '/public/assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <div class="ty-product-details">
                        <h3 class="ty-product-name"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                        <?php if (!empty($item['variation_attributes'])): ?>
                        <p class="ty-product-meta" style="margin-top:4px">
                            <?php foreach (explode(',', $item['variation_attributes']) as $attr):
                                $attr = trim($attr); if (!$attr) continue; ?>
                            <span style="display:inline-block;padding:1px 8px;background:#fffbeb;border:1px solid #facc15;border-radius:999px;font-size:.75rem;font-weight:700;color:#111;margin:2px"><?= htmlspecialchars($attr) ?></span>
                            <?php endforeach; ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($item['variation_sku'])): ?>
                        <p class="ty-product-meta" style="font-size:.78rem;color:#6b7280">SKU: <?= htmlspecialchars($item['variation_sku']) ?></p>
                        <?php endif; ?>
                        <p class="ty-product-meta">Rs. <?php echo number_format(($item['quantity'] > 0 ? ($item['subtotal_price'] / $item['quantity']) : 0), 2); ?> each</p>
                    </div>
                    <div class="ty-product-price">
                        <span class="ty-product-qty">Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                        <span class="ty-product-total">Rs. <?php echo number_format($item['subtotal_price'], 2); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Payment Summary -->
    <section class="ty-card ty-fade-in ty-delay-4">
        <div class="ty-card-header">
            <i class="fas fa-credit-card"></i>
            <h2 class="ty-card-title">Payment Details</h2>
        </div>
        <div class="ty-payment-summary">
            <div class="ty-payment-row">
                <span>Subtotal</span>
                <span class="ty-value">Rs. <?php echo number_format($totalPrice, 2); ?></span>
            </div>
            <div class="ty-payment-row">
                <span>Delivery Charge</span>
                <span class="ty-value">Rs. <?php echo number_format($deliveryCharge, 2); ?></span>
            </div>
            <div class="ty-payment-total">
                <span>Total</span>
                <span class="ty-value">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="ty-cta-section ty-fade-in ty-delay-4">
        <a href="/" class="ty-btn ty-btn-primary">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </section>
</main>

<script>
    function copyOrderNumber() {
        var orderNumber = document.getElementById('orderNumber').textContent;
        var tempTextArea = document.createElement('textarea');
        tempTextArea.value = orderNumber;
        document.body.appendChild(tempTextArea);
        
        tempTextArea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextArea);

        var copyButton = document.getElementById('copyButton');
        copyButton.classList.add('copied');
        copyButton.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function () {
            copyButton.classList.remove('copied');
            copyButton.innerHTML = '<i class="fas fa-copy"></i>';
        }, 1500);
    }
</script>
</body>
</html>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>