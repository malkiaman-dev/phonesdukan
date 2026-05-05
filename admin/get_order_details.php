<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['order_id'])) {
    echo "Invalid request!";
    exit;
}

$order_id = (int)$_GET['order_id'];

$query = "
    SELECT o.*, ot.customer_ip, ot.device_type
    FROM orders o
    LEFT JOIN order_tracking ot ON o.order_id = ot.order_id
    WHERE o.order_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found!";
    exit;
}

$product_query = "
    SELECT oi.quantity, oi.subtotal_price, p.product_name 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->execute([$order_id]);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0.0;
foreach ($products as $product) {
    $subtotal += (float)($product['subtotal_price'] ?? 0);
}
$totalAmount = (float)($order['total_price'] ?? 0);
$deliveryCharges = $totalAmount - $subtotal;
if ($deliveryCharges < 0) {
    $deliveryCharges = 0;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
    .custom-order-popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(17, 17, 17, 0.58);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px;
        font-family: 'DM Sans', sans-serif;
    }
    .odm-modal {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        width: 100%;
        max-width: 900px;
        max-height: 88vh;
        overflow-y: auto;
        box-shadow: 0 24px 70px rgba(17,17,17,0.2);
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: #facc15 #fff;
    }
    .odm-modal::-webkit-scrollbar { width: 8px; }
    .odm-modal::-webkit-scrollbar-thumb { background: #facc15; border-radius: 999px; }
    .odm-modal::-webkit-scrollbar-track { background: #fff; }
    .odm-head {
        padding: 24px 26px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }
    .odm-title { font-size: 1.45rem; font-weight: 800; color: #111111; line-height: 1.2; }
    .odm-subtitle { margin-top: 4px; font-size: 0.92rem; color: #6b7280; }
    .custom-close-modal {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #111111;
        border-radius: 10px;
        width: 36px;
        height: 36px;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        transition: color .15s ease, border-color .15s ease;
    }
    .custom-close-modal:hover { color: #facc15; border-color: #facc15; }
    .odm-body {
        padding: 18px 24px 24px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .odm-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
    }
    .odm-section.odm-full { grid-column: 1 / -1; }
    .odm-section-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: #111111;
        margin-bottom: 10px;
    }
    .odm-row {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 8px;
        padding: 7px 0;
        border-top: 1px solid #f3f4f6;
        font-size: 0.9rem;
    }
    .odm-row:first-of-type { border-top: 0; }
    .odm-label { color: #6b7280; font-weight: 600; }
    .odm-value { color: #111111; font-weight: 700; word-break: break-word; background: transparent; }
    .odm-value a { color: #111111; text-decoration: underline; text-decoration-color: #facc15; }
    .odm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }
    .odm-table th {
        background: #f8fafc;
        color: #111111;
        padding: 10px 12px;
        text-align: left;
        font-size: 0.78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }
    .odm-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #111111;
        background: transparent;
    }
    .odm-table tr:last-child td { border-bottom: 0; }
    .odm-right { text-align: right; }
    .odm-summary {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
    }
    .odm-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 10px 12px;
        border-top: 1px solid #f3f4f6;
        color: #111111;
        font-size: 0.92rem;
    }
    .odm-summary-row:first-child { border-top: 0; }
    .odm-summary-row.odm-total { background: #fffbeb; font-weight: 800; }
    .odm-empty {
        text-align: center;
        color: #6b7280;
        font-size: 0.9rem;
        padding: 12px;
        border: 1px dashed #e5e7eb;
        border-radius: 10px;
    }
    @media (max-width: 860px) {
        .odm-body { grid-template-columns: 1fr; }
        .odm-section.odm-full { grid-column: auto; }
        .odm-row { grid-template-columns: 96px 1fr; }
    }
</style>

<div class="custom-order-popup-overlay">
    <div class="odm-modal">
        <div class="odm-head">
            <div>
                <div class="odm-title">Order #<?= htmlspecialchars($order['order_id'] ?? '') ?></div>
                <div class="odm-subtitle">Complete order details</div>
            </div>
            <button class="custom-close-modal" title="Close">&#x2715;</button>
        </div>

        <div class="odm-body">
            <div class="odm-section">
                <div class="odm-section-title">Shipping Details</div>
                <div class="odm-row"><span class="odm-label">Name</span><span class="odm-value"><?= htmlspecialchars($order['customer_name'] ?? '') ?></span></div>
                <div class="odm-row"><span class="odm-label">Address</span><span class="odm-value"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></span></div>
                <div class="odm-row"><span class="odm-label">City</span><span class="odm-value"><?= htmlspecialchars($order['shipping_city'] ?? '') ?></span></div>
                <div class="odm-row"><span class="odm-label">Country</span><span class="odm-value"><?= htmlspecialchars($order['shipping_country'] ?? '') ?></span></div>
                <div class="odm-row"><span class="odm-label">Email</span><span class="odm-value"><?= htmlspecialchars($order['customer_email'] ?? '') ?></span></div>
                <div class="odm-row"><span class="odm-label">Phone</span><span class="odm-value"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></span></div>
            </div>

            <div class="odm-section">
                <div class="odm-section-title">Payment Details</div>
                <div class="odm-row"><span class="odm-label">Method</span><span class="odm-value"><?= htmlspecialchars($order['payment_method_title'] ?? '') ?></span></div>
                <?php if (!empty($order['payment_method']) && $order['payment_method'] === 'prepaid' && !empty($order['payment_screenshot'])): ?>
                <div class="odm-row">
                    <span class="odm-label">Proof</span>
                    <span class="odm-value"><a href="/admin/view-screenshot.php?file=<?= htmlspecialchars($order['payment_screenshot']) ?>" target="_blank">View payment screenshot</a></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="odm-section">
                <div class="odm-section-title">Tracking Details</div>
                <div class="odm-row"><span class="odm-label">Customer IP</span><span class="odm-value"><?= htmlspecialchars($order['customer_ip'] ?? 'N/A') ?></span></div>
                <div class="odm-row"><span class="odm-label">Device Type</span><span class="odm-value"><?= htmlspecialchars($order['device_type'] ?? 'N/A') ?></span></div>
            </div>

            <div class="odm-section">
                <div class="odm-section-title">Order Summary</div>
                <div class="odm-summary">
                    <div class="odm-summary-row"><span>Subtotal</span><span>PKR <?= number_format($subtotal, 2) ?></span></div>
                    <div class="odm-summary-row"><span>Delivery Charges</span><span>PKR <?= number_format($deliveryCharges, 2) ?></span></div>
                    <div class="odm-summary-row odm-total"><span>Total Amount</span><span>PKR <?= number_format($totalAmount, 2) ?></span></div>
                </div>
            </div>

            <div class="odm-section odm-full">
                <div class="odm-section-title">Ordered Products</div>
                <?php if (empty($products)): ?>
                    <p class="odm-empty">No products found for this order.</p>
                <?php else: ?>
                    <table class="odm-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="odm-right">Quantity</th>
                                <th class="odm-right">Price</th>
                                <th class="odm-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <?php
                                $qty = (float)($product['quantity'] ?? 0);
                                $lineSubtotal = (float)($product['subtotal_price'] ?? 0);
                                $unitPrice = $qty > 0 ? ($lineSubtotal / $qty) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?></td>
                                <td class="odm-right"><?= (int)$qty ?></td>
                                <td class="odm-right">PKR <?= number_format($unitPrice, 2) ?></td>
                                <td class="odm-right">PKR <?= number_format($lineSubtotal, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>