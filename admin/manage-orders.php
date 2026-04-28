<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/OrderModel.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];

    $orderModel = new OrderModel($conn);
    $result = $orderModel->updateOrderStatus($order_id, $new_status);

    if ($result) {
        echo "<script>alert('Order status updated successfully!'); window.location.href='manage-orders.php';</script>";
    } else {
        echo "<script>alert('Failed to update order status.');</script>";
    }
}

$query = "SELECT * FROM orders ORDER BY created_at DESC";
$stmt = $conn->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Phones Dukan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== Reset & Base ===== */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f0f2f5;
            color: #222;
        }

        /* ===== Wrapper ===== */
        .ord-wrap {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 24px;
        }

        /* ===== Title ===== */
        .ord-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: -0.3px;
        }

        /* ===== Table Wrapper ===== */
        .ord-table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* ===== Table ===== */
        .ord-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        /* ===== Header ===== */
        .ord-head tr {
            background: #1a7fe8;
        }

        .ord-head th {
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 14px 18px;
            text-align: left;
            white-space: nowrap;
        }

        /* ===== Body Rows ===== */
        .ord-table tbody tr {
            border-bottom: 1px solid #e8ecf0;
            transition: background 0.15s ease;
        }

        .ord-table tbody tr:last-child {
            border-bottom: none;
        }

        .ord-table tbody tr:hover {
            background: #f5f8ff;
        }

        .ord-table tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            color: #333;
            line-height: 1.5;
        }

        /* ID column */
        .ord-table tbody td:first-child {
            font-weight: 700;
            color: #555;
            width: 55px;
        }

        /* Price bold */
        .ord-table tbody td strong {
            font-weight: 700;
            color: #1a1a2e;
        }

        /* Note italic */
        .ord-table tbody td i {
            color: #aaa;
            font-style: italic;
        }

        /* ===== Status Dropdown ===== */
        .ord-select {
            appearance: none;
            -webkit-appearance: none;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23666' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 10px center;
            border: 1.5px solid #d0d7e2;
            border-radius: 6px;
            padding: 7px 30px 7px 10px;
            font-size: 0.85rem;
            font-family: 'DM Sans', sans-serif;
            color: #333;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 130px;
        }

        .ord-select:focus {
            outline: none;
            border-color: #1a7fe8;
            box-shadow: 0 0 0 3px rgba(26,127,232,0.12);
        }

        /* ===== Buttons ===== */
        .ord-btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.1s, box-shadow 0.15s, opacity 0.15s;
            white-space: nowrap;
            margin: 2px 2px;
        }

        .ord-btn:hover {
            transform: translateY(-1px);
        }

        .ord-btn:active {
            transform: translateY(0);
            opacity: 0.9;
        }

        /* Green - Update */
        .ord-btn.upd {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 2px 6px rgba(34,197,94,0.3);
        }
        .ord-btn.upd:hover {
            background: #16a34a;
            box-shadow: 0 4px 12px rgba(34,197,94,0.35);
        }

        /* Blue - View */
        .ord-btn.view {
            background: #1a7fe8;
            color: #fff;
            box-shadow: 0 2px 6px rgba(26,127,232,0.3);
        }
        .ord-btn.view:hover {
            background: #1565c0;
            box-shadow: 0 4px 12px rgba(26,127,232,0.35);
        }

        /* Teal - Screenshot */
        .ord-btn.screenshot {
            background: #0ea5e9;
            color: #fff;
            box-shadow: 0 2px 6px rgba(14,165,233,0.3);
        }
        .ord-btn.screenshot:hover {
            background: #0284c7;
            box-shadow: 0 4px 12px rgba(14,165,233,0.35);
        }

        /* Action cell layout */
        .ord-table tbody td:last-child {
            white-space: nowrap;
        }

        /* ===== Modal Overlay ===== */
        #ord-modal,
        .custom-order-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-content .close,
        .custom-close-modal {
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: 1.4rem;
            cursor: pointer;
            color: #888;
            line-height: 1;
            transition: color 0.15s;
            background: none;
            border: none;
        }

        .modal-content .close:hover,
        .custom-close-modal:hover {
            color: #222;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .ord-wrap { padding: 0 12px; margin: 16px auto; }
            .ord-title { font-size: 1.4rem; }
            .ord-table { font-size: 0.8rem; }
            .ord-head th,
            .ord-table tbody td { padding: 10px 10px; }
            .ord-select { min-width: 110px; }
        }
    </style>
</head>
<body>

<div class="ord-wrap">
    <h2 class="ord-title">Manage Orders</h2>
    <div class="ord-table-wrap">
        <table class="ord-table">
            <thead class="ord-head">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><strong>PKR <?= number_format($order['total_price'], 2) ?></strong></td>
                    <td>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                            <select name="order_status" class="ord-select">
                                <option value="Pending"    <?= ($order['order_status'] === 'Pending')    ? 'selected' : '' ?>>Pending</option>
                                <option value="Processing" <?= ($order['order_status'] === 'Processing') ? 'selected' : '' ?>>Processing</option>
                                <option value="Completed"  <?= ($order['order_status'] === 'Completed')  ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled"  <?= ($order['order_status'] === 'Cancelled')  ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                    </td>
                    <td><?= date('d-M-Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <?= !empty($order['customer_note'])
                            ? htmlspecialchars($order['customer_note'])
                            : '<i>No note.</i>' ?>
                    </td>
                    <td>
                        <button type="submit" name="update_status" class="ord-btn upd">Update</button>
                        </form>
                        <button class="ord-btn view" data-order-id="<?= $order['order_id'] ?>">View</button>
                        <?php if ($order['payment_method'] === 'prepaid' && !empty($order['payment_screenshot'])): ?>
                            <a href="/admin/view-screenshot.php?file=<?= htmlspecialchars($order['payment_screenshot']) ?>"
                               target="_blank" class="ord-btn screenshot">Screenshot</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div id="ord-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="ord-detail-content">
            <p>Loading...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalContainerId = "custom-ord-modal";

    document.querySelectorAll(".view").forEach(function (button) {
        button.addEventListener("click", function () {
            let orderId = this.getAttribute("data-order-id");

            fetch("get_order_details.php?order_id=" + orderId)
                .then(response => response.text())
                .then(data => {
                    let modalContainer = document.getElementById(modalContainerId);
                    if (!modalContainer) {
                        modalContainer = document.createElement("div");
                        modalContainer.id = modalContainerId;
                        modalContainer.className = "custom-order-popup-overlay";
                        document.body.appendChild(modalContainer);
                    }

                    modalContainer.innerHTML = data;
                    modalContainer.style.display = "flex";

                    setTimeout(() => {
                        const closeButton = document.querySelector(".custom-close-modal");
                        if (closeButton) {
                            closeButton.addEventListener("click", function () {
                                modalContainer.style.display = "none";
                            });
                        }
                    }, 100);
                })
                .catch(error => console.error("Error loading order details:", error));
        });
    });

    // Close modal on outside click
    document.addEventListener("click", function (event) {
        const modalContainer = document.getElementById(modalContainerId);
        if (modalContainer && event.target === modalContainer) {
            modalContainer.style.display = "none";
        }
    });
});
</script>

</body>
</html>