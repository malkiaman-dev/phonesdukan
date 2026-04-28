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
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

    /* ===== Overlay ===== */
    .custom-order-popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'DM Sans', sans-serif;
    }

    /* ===== Modal Box ===== */
    .custom-order-popup {
        background: #fff;
        border-radius: 14px;
        width: 90%;
        max-width: 620px;
        max-height: 88vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.25);
        animation: popIn 0.22s ease;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .custom-order-popup::-webkit-scrollbar { width: 5px; }
    .custom-order-popup::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    @keyframes popIn {
        from { opacity: 0; transform: scale(0.95) translateY(14px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* ===== Close Button ===== */
    .custom-close-modal {
        position: absolute;
        top: 16px;
        right: 18px;
        background: none;
        border: none;
        font-size: 1.3rem;
        color: #999;
        cursor: pointer;
        line-height: 1;
        transition: color 0.15s;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .custom-close-modal:hover {
        color: #222;
        background: #f1f5f9;
    }

    /* ===== Order Title ===== */
    .pop-title {
        text-align: center;
        font-size: 1.4rem;
        font-weight: 700;
        color: #1a7fe8;
        padding: 28px 30px 14px;
        letter-spacing: -0.2px;
    }

    .pop-title-divider {
        height: 2px;
        background: linear-gradient(90deg, transparent, #1a7fe8, transparent);
        margin: 0 30px 20px;
        border: none;
    }

    /* ===== Section ===== */
    .pop-section {
        padding: 0 30px 20px;
    }

    .pop-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e8ecf0;
    }

    /* ===== Info Rows ===== */
    .pop-row {
        display: flex;
        gap: 8px;
        padding: 6px 0;
        font-size: 0.9rem;
        color: #444;
        border-bottom: 1px solid #f3f4f6;
    }
    .pop-row:last-child { border-bottom: none; }

    .pop-label {
        font-weight: 600;
        color: #1a1a2e;
        min-width: 90px;
        flex-shrink: 0;
    }

    .pop-value {
        color: #444;
        word-break: break-word;
    }

    .pop-value a {
        color: #1a7fe8;
        text-decoration: none;
        font-weight: 500;
    }
    .pop-value a:hover { text-decoration: underline; }

    /* ===== Products Table ===== */
    .pop-product-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        margin-top: 4px;
    }

    .pop-product-table thead tr {
        background: #1a7fe8;
    }

    .pop-product-table thead th {
        color: #fff;
        font-weight: 600;
        font-size: 0.78rem;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        padding: 10px 14px;
        text-align: left;
    }

    .pop-product-table tbody tr {
        border-bottom: 1px solid #e8ecf0;
        transition: background 0.12s;
    }
    .pop-product-table tbody tr:last-child { border-bottom: none; }
    .pop-product-table tbody tr:hover { background: #f5f8ff; }

    .pop-product-table tbody td {
        padding: 10px 14px;
        color: #333;
        vertical-align: middle;
    }

    .pop-product-table tfoot tr {
        background: #f8fafc;
    }
    .pop-product-table tfoot td {
        padding: 10px 14px;
        font-weight: 700;
        color: #1a1a2e;
        font-size: 0.9rem;
        border-top: 2px solid #e8ecf0;
    }

    /* ===== No products ===== */
    .pop-empty {
        text-align: center;
        color: #aaa;
        font-style: italic;
        padding: 16px 0;
        font-size: 0.88rem;
    }

    /* ===== Bottom padding ===== */
    .pop-footer { height: 10px; }
</style>

<div class="custom-order-popup-overlay">
    <div class="custom-order-popup">

        <button class="custom-close-modal" title="Close">&#x2715;</button>

        <!-- Title -->
        <div class="pop-title">Order #<?= htmlspecialchars($order['order_id'] ?? '') ?></div>
        <hr class="pop-title-divider">

        <!-- Shipping Details -->
        <div class="pop-section">
            <div class="pop-section-title">Shipping Details</div>

            <div class="pop-row">
                <span class="pop-label">Name:</span>
                <span class="pop-value"><?= htmlspecialchars($order['customer_name'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Address:</span>
                <span class="pop-value"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">City:</span>
                <span class="pop-value"><?= htmlspecialchars($order['shipping_city'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Country:</span>
                <span class="pop-value"><?= htmlspecialchars($order['shipping_country'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Email:</span>
                <span class="pop-value"><?= htmlspecialchars($order['customer_email'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Phone:</span>
                <span class="pop-value"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></span>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="pop-section">
            <div class="pop-section-title">Payment Details</div>

            <div class="pop-row">
                <span class="pop-label">Payment via:</span>
                <span class="pop-value"><?= htmlspecialchars($order['payment_method_title'] ?? '') ?></span>
            </div>
            <?php if (!empty($order['payment_method']) && $order['payment_method'] === 'prepaid' && !empty($order['payment_screenshot'])): ?>
            <div class="pop-row">
                <span class="pop-label">Screenshot:</span>
                <span class="pop-value">
                    <a href="/admin/view-screenshot.php?file=<?= htmlspecialchars($order['payment_screenshot']) ?>" target="_blank">View Payment Proof</a>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tracking Details -->
        <div class="pop-section">
            <div class="pop-section-title">Tracking Details</div>

            <div class="pop-row">
                <span class="pop-label">Customer IP:</span>
                <span class="pop-value"><?= htmlspecialchars($order['customer_ip'] ?? 'N/A') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Device Type:</span>
                <span class="pop-value"><?= htmlspecialchars($order['device_type'] ?? 'N/A') ?></span>
            </div>
        </div>

        <!-- Ordered Products -->
        <div class="pop-section">
            <div class="pop-section-title">Ordered Products</div>

            <?php if (empty($products)): ?>
                <p class="pop-empty">No products found for this order.</p>
            <?php else: ?>
                <table class="pop-product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?></td>
                            <td><?= htmlspecialchars($product['quantity'] ?? 0) ?></td>
                            <td>₨ 0</td>
                            <td>₨ <?= number_format((float)($product['subtotal_price'] ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; color:#666; font-weight:600;">Order Total:</td>
                            <td>₨ <?= number_format((float)($order['total_price'] ?? 0), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

        <div class="pop-footer"></div>
    </div>
</div>