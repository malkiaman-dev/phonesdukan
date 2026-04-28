<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['inquiry_id'])) {
    echo "Invalid request!";
    exit;
}

$inquiry_id = (int)$_GET['inquiry_id'];

$query = "
    SELECT id, name, email, phone, tel_no, business_name, address, message, total_price, status, created_at
    FROM bulk_inquiries
    WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$inquiry_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "B2B Order not found!";
    exit;
}

$product_query = "
    SELECT bii.quantity, bii.product_id, p.product_name, p.b2b_regular_price
    FROM bulk_inquiry_items bii
    LEFT JOIN products p ON bii.product_id = p.product_id
    WHERE bii.inquiry_id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->execute([$inquiry_id]);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

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

    .custom-order-popup {
        background: #fff;
        border-radius: 14px;
        width: 90%;
        max-width: 640px;
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
        transition: color 0.15s, background 0.15s;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .custom-close-modal:hover {
        color: #222;
        background: #f1f5f9;
    }

    .pop-title {
        text-align: center;
        font-size: 1.4rem;
        font-weight: 700;
        color: #1a7fe8;
        padding: 28px 30px 14px;
        letter-spacing: -0.2px;
    }

    .pop-badge {
        display: inline-block;
        background: #1a7fe8 !important;
        color: #fff !important;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        padding: 4px 11px;
        border-radius: 20px;
        border: none !important;
        margin-left: 8px;
        vertical-align: middle;
    }

    .pop-title-divider {
        height: 2px;
        background: linear-gradient(90deg, transparent, #1a7fe8, transparent);
        margin: 0 30px 20px;
        border: none;
    }

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

    .custom-order-popup .pop-row {
        display: flex;
        gap: 8px;
        padding: 6px 0;
        font-size: 0.9rem;
        color: #444 !important;
        border-bottom: 1px solid #f3f4f6 !important;
        background: transparent !important;
    }
    .custom-order-popup .pop-row:last-child { border-bottom: none !important; }

    .custom-order-popup .pop-label {
        font-weight: 600 !important;
        color: #1a1a2e !important;
        min-width: 115px;
        flex-shrink: 0;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
        display: inline !important;
        font-size: 0.9rem !important;
    }

    .custom-order-popup .pop-value {
        color: #444 !important;
        word-break: break-word;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
        display: inline !important;
        font-size: 0.9rem !important;
    }

    .custom-order-popup .pop-value strong {
        color: #1a1a2e !important;
        background: transparent !important;
    }

    .status-pill {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.4px;
    }
    .status-pill.pending   { background: #f59e0b !important; color: #fff !important; }
    .status-pill.completed { background: #22c55e !important; color: #fff !important; }
    .status-pill.cancelled { background: #ef4444 !important; color: #fff !important; }

    .pop-product-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        margin-top: 4px;
    }

    .pop-product-table thead tr { background: #1a7fe8; }

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

    .pop-product-table tfoot tr { background: #f8fafc; }
    .pop-product-table tfoot td {
        padding: 10px 14px;
        font-weight: 700;
        color: #1a1a2e;
        font-size: 0.9rem;
        border-top: 2px solid #e8ecf0;
    }

    .pop-empty {
        text-align: center;
        color: #aaa;
        font-style: italic;
        padding: 16px 0;
        font-size: 0.88rem;
    }

    .pop-footer { height: 10px; }
</style>

<div class="custom-order-popup-overlay">
    <div class="custom-order-popup">

        <button class="custom-close-modal" title="Close">&#x2715;</button>

        <!-- Title -->
        <div class="pop-title">
            B2B Order #<?= htmlspecialchars($order['id'] ?? '') ?>
            <span class="pop-badge">B2B</span>
        </div>
        <hr class="pop-title-divider">

        <!-- Customer Details -->
        <div class="pop-section">
            <div class="pop-section-title">Customer Details</div>

            <div class="pop-row">
                <span class="pop-label">Name:</span>
                <span class="pop-value"><?= htmlspecialchars($order['name'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Business Name:</span>
                <span class="pop-value"><?= htmlspecialchars($order['business_name'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Email:</span>
                <span class="pop-value"><?= htmlspecialchars($order['email'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Phone:</span>
                <span class="pop-value"><?= htmlspecialchars($order['phone'] ?? '') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Tel No:</span>
                <span class="pop-value"><?= htmlspecialchars($order['tel_no'] ?? 'N/A') ?></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Address:</span>
                <span class="pop-value"><?= htmlspecialchars($order['address'] ?? '') ?></span>
            </div>
            <?php if (!empty($order['message'])): ?>
            <div class="pop-row">
                <span class="pop-label">Message:</span>
                <span class="pop-value"><?= htmlspecialchars($order['message']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Details -->
        <div class="pop-section">
            <div class="pop-section-title">Order Details</div>

            <div class="pop-row">
                <span class="pop-label">Total Price:</span>
                <span class="pop-value"><strong>PKR <?= number_format((float)($order['total_price'] ?? 0), 0) ?></strong></span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Status:</span>
                <span class="pop-value">
                    <?php $s = strtolower($order['status'] ?? 'pending'); ?>
                    <span class="status-pill <?= $s ?>"><?= ucfirst($s) ?></span>
                </span>
            </div>
            <div class="pop-row">
                <span class="pop-label">Date:</span>
                <span class="pop-value"><?= date('d-M-Y H:i', strtotime($order['created_at'] ?? 'now')) ?></span>
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
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product):
                            $unit  = (float)($product['b2b_regular_price'] ?? 0);
                            $qty   = (int)($product['quantity'] ?? 0);
                            $total = $unit * $qty;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?></td>
                            <td><?= $qty ?></td>
                            <td>PKR <?= number_format($unit, 0) ?></td>
                            <td>PKR <?= number_format($total, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; color:#666; font-weight:600;">Order Total:</td>
                            <td>PKR <?= number_format((float)($order['total_price'] ?? 0), 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

        <div class="pop-footer"></div>
    </div>
</div>