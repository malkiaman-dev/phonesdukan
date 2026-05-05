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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];

    $orderModel = new OrderModel($conn);
    $result = $orderModel->updateOrderStatus($order_id, $new_status);

    $pageParam = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limitParam = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    if ($pageParam < 1) {
        $pageParam = 1;
    }
    if (!in_array($limitParam, [20, 50, 100], true)) {
        $limitParam = 20;
    }

    $redirectParams = [
        'page' => $pageParam,
        'limit' => $limitParam,
        'updated' => $result ? '1' : '0',
    ];
    header('Location: manage-orders.php?' . http_build_query($redirectParams));
    exit();
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';

// Pagination (safe + minimal backend change)
$allowedLimits = [20, 50, 100];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 20;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$totalOrders = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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
        :root{
            --primary:#111111;
            --accent:#facc15;
            --accentHover:#eab308;
            --lightYellow:#fffbeb;
            --bg:#f8fafc;
            --card:#ffffff;
            --border:#e5e7eb;
            --divider:#f1f5f9;
            --text:#111111;
            --muted:#6b7280;
            --shadow: 0 12px 30px rgba(17,17,17,0.06);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        /* Match Add Product page: single normal page scrollbar */
        html, body {
            height: auto;
            overflow: auto;
            overflow-x: hidden;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: visible;
            overflow-x: hidden;
        }
        body.modal-open {
            overflow: hidden;
        }

        /* ===== Page layout ===== */
        .ord-wrap{
            max-width: 1280px;
            margin: 28px auto;
            padding: 0 20px;
        }

        /* ===== Header section ===== */
        .orders-head{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .orders-title{
            display:flex;
            flex-direction:column;
            gap: 4px;
            min-width: 240px;
        }
        .orders-title h2{
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--primary);
        }
        .orders-title p{
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.35;
        }

        .orders-controls{
            display:flex;
            gap:10px;
            flex-wrap: wrap;
            align-items:center;
            justify-content:flex-end;
        }

        .ui-input, .ui-select{
            font-family: inherit;
            font-size: 0.92rem;
            color: var(--text);
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 12px;
            padding: 10px 12px;
            outline: none;
            transition: box-shadow .15s ease, border-color .15s ease, transform .15s ease;
        }
        .ui-input::placeholder{ color: #9ca3af; }
        .ui-input:focus, .ui-select:focus{
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(250,204,21,0.25);
        }
        .ui-input{
            width: min(360px, 70vw);
        }

        /* Custom select (filter) */
        .ui-select{
            appearance:none;
            -webkit-appearance:none;
            padding-right: 40px;
            background:
                linear-gradient(45deg, transparent 50%, #111 50%) calc(100% - 18px) calc(50% - 3px) / 6px 6px no-repeat,
                linear-gradient(135deg, #111 50%, transparent 50%) calc(100% - 12px) calc(50% - 3px) / 6px 6px no-repeat,
                linear-gradient(to right, transparent, transparent) 0 0 / 100% 100% no-repeat,
                #fff;
        }

        /* ===== Card wrapper ===== */
        .table-card{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        .table-header{
            padding: 16px 18px;
            border-bottom: 1px solid var(--divider);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .table-meta{
            color: var(--muted);
            font-size: 0.9rem;
        }
        .table-meta span{
            background: transparent !important;
            color: inherit !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            font-size: inherit !important;
        }
        .table-meta-sep{
            margin: 0 8px;
            color: #cbd5e1;
            font-weight: 700;
        }
        .table-count-pill{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #111111;
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            transition: color .15s ease;
        }
        .table-count-pill:hover{
            color: #facc15;
        }
        .table-body{
            padding: 0;
        }

        /* ===== Table ===== */
        .ord-table{
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 0.88rem; /* ~14px */
        }
        .ord-table thead th{
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f9fafb;
            color: var(--primary);
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid var(--divider);
            white-space: nowrap;
        }

        .ord-table tbody tr{
            transition: background .15s ease;
        }
        .ord-table tbody tr:nth-child(even){
            background: #fcfcfd;
        }
        .ord-table tbody tr:hover{
            background: var(--lightYellow);
        }
        .ord-table tbody td{
            padding: 10px 8px;
            border-bottom: 1px solid var(--divider);
            vertical-align: middle;
            color: #111827;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        /* Status column: allow full dropdown text (no clipping/ellipsis) */
        .ord-table td.ord-status-cell{
            overflow: visible;
            text-overflow: unset;
        }
        .ord-table td.ord-update-cell{
            overflow: visible;
            text-overflow: unset;
        }
        .ord-table tbody tr:last-child td{
            border-bottom: none;
        }

        .ord-id{
            font-weight: 800;
            color: var(--primary);
            width: 86px;
        }
        .ord-customer{
            display:flex;
            flex-direction:column;
            gap: 2px;
            min-width: 0;
        }
        .ord-customer .name{
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .ord-customer .note{
            color: var(--muted);
            font-size: 0.86rem;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ord-amount{
            font-weight: 700;
            white-space: nowrap;
            overflow: visible;
            text-overflow: unset;
        }
        .ord-date{
            color: var(--muted);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        .ord-phone{
            color: #111827;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* ===== Status pill select ===== */
        .status-pill{
            display:inline-flex;
            align-items:center;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #fff;
            padding: 2px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .status-pill:hover{
            border-color: var(--accent);
        }
        .status-pill:focus-within{
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(250,204,21,0.25);
        }
        .ord-select{
            appearance:none;
            -webkit-appearance:none;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 8px 28px 8px 12px;
            border: 0;
            outline: none;
            background:
                linear-gradient(45deg, transparent 50%, #111 50%) calc(100% - 18px) calc(50% - 3px) / 6px 6px no-repeat,
                linear-gradient(135deg, #111 50%, transparent 50%) calc(100% - 12px) calc(50% - 3px) / 6px 6px no-repeat,
                transparent;
            cursor: pointer;
            border-radius: 999px;
            color: var(--primary);
            width: 125px;
            min-width: 125px;
            max-width: none;
            white-space: nowrap;
            overflow: visible;
            text-overflow: unset;
        }
        .native-status-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }
        .status-select-wrap {
            position: relative;
            width: 125px;
        }
        .status-display {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 8px 30px 8px 12px;
            background: #fff;
            color: var(--primary);
            font-size: 0.86rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .status-display::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid #111;
            border-bottom: 2px solid #111;
            transform: translateY(-65%) rotate(45deg);
        }
        .status-select-wrap.is-open .status-display,
        .status-display:hover {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.2);
        }
        .status-options {
            position: absolute;
            z-index: 30;
            left: 0;
            right: 0;
            margin-top: 6px;
            list-style: none;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(17,17,17,0.12);
            padding: 6px;
            display: none;
        }
        .status-select-wrap.is-open .status-options { display: block; }
        .status-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
        }
        .status-option:hover {
            background: var(--lightYellow);
        }
        .status-option.is-selected {
            background: var(--accent);
        }
        .status-pill[data-status="pending"]{ background: var(--lightYellow); }
        .status-pill[data-status="processing"]{ background: #f3f4f6; }
        .status-pill[data-status="completed"]{ background: #ecfdf5; } /* soft green */
        .status-pill[data-status="cancelled"]{ background: #f8fafc; }

        /* ===== Buttons (black/yellow) ===== */
        .ord-actions{
            display:flex;
            align-items:center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .ord-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 9px;
            border: 1px solid transparent;
            background: var(--primary);
            color: #fff;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 800;
            cursor: pointer;
            text-decoration:none;
            transition: transform .12s ease, box-shadow .12s ease, color .12s ease, border-color .12s ease, background .12s ease;
            white-space: nowrap;
        }
        .ord-btn:hover{
            color: var(--accent);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(17,17,17,0.12);
        }
        .ord-btn:active{
            transform: translateY(0);
        }
        .ord-btn.ghost{
            background: transparent;
            color: var(--primary);
            border-color: var(--border);
        }
        .ord-btn.ghost:hover{
            border-color: var(--accent);
            color: var(--primary);
            background: var(--lightYellow);
        }

        /* Compact form reset */
        .status-update-wrap{
            display:flex;
            align-items:center;
            gap: 8px;
            flex-wrap: nowrap;
        }
        .ord-status-form{ margin: 0; display:flex; gap: 6px; align-items:center; }
        .ord-status-form .status-pill{ width: auto; }
        .update-btn{
            padding: 8px 10px;
            font-size: 12px;
            min-width: 58px;
            white-space: nowrap;
        }

        /* ===== Pagination ===== */
        .table-footer{
            padding: 14px 18px;
            border-top: 1px solid var(--divider);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .pagination{
            display:flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items:center;
        }
        .page-link{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--primary);
            font-weight: 800;
            text-decoration:none;
            transition: border-color .15s ease, background .15s ease, transform .12s ease;
        }
        .page-link:hover{
            border-color: var(--accent);
            background: var(--lightYellow);
            transform: translateY(-1px);
        }
        .page-link.is-active{
            background: var(--accent);
            border-color: var(--accent);
            color: var(--primary);
        }
        .page-link.is-disabled{
            opacity: 0.55;
            pointer-events: none;
        }

        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17,17,17,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }
        .confirm-overlay.is-open { display: flex; }
        .confirm-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 40px rgba(17,17,17,0.18);
            padding: 20px;
        }
        .confirm-title { font-size: 1.05rem; font-weight: 800; color: var(--primary); margin-bottom: 6px; }
        .confirm-text { color: var(--muted); font-size: 0.92rem; line-height: 1.45; }
        .confirm-actions { margin-top: 18px; display: flex; justify-content: flex-end; gap: 8px; }
        .confirm-btn {
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 700;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--primary);
            cursor: pointer;
        }
        .confirm-btn.confirm-yes {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .confirm-btn.confirm-yes:hover {
            color: var(--accent);
        }

        .status-toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1300;
            background: #111;
            color: #fff;
            border: 1px solid var(--accent);
            border-left: 5px solid var(--accent);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 14px 30px rgba(17,17,17,0.3);
            display: none;
        }
        .status-toast.is-failure {
            border-left-color: #facc15;
            border-color: #facc15;
        }
        .status-toast.show { display: block; }

        /* Neutralize legacy global span styles inside fetched order modal */
        .custom-order-popup-overlay .odm-modal span,
        .custom-order-popup-overlay .odm-modal td,
        .custom-order-popup-overlay .odm-modal th {
            background: transparent !important;
            color: inherit !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            font-size: inherit !important;
        }
        .custom-order-popup-overlay .odm-table th,
        .custom-order-popup-overlay .odm-table td {
            padding: 10px 12px !important;
        }

        /* ===== Modal overlay polish (keeps existing JS working) ===== */
        #ord-modal,
        .custom-order-popup-overlay{
            position: fixed;
            inset: 0;
            background: rgba(17,17,17,0.56);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }
        .modal-content{
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            width: 100%;
            max-width: 720px;
            max-height: 86vh;
            overflow: auto;
            box-shadow: 0 24px 70px rgba(17,17,17,0.18);
        }

        /* ===== Responsive ===== */
        @media (max-width: 900px){
            .ord-wrap{ padding: 0 14px; margin: 18px auto; }
            .ui-input{ width: min(320px, 78vw); }
            .ord-table thead th{ padding: 10px 8px; }
            .ord-table tbody td{ padding: 10px 8px; }
        }

        /* Mobile: table -> cards (no horizontal overflow) */
        @media (max-width: 640px){
            .orders-controls{ width: 100%; justify-content: flex-start; }
            .ui-input{ width: 100%; }
            .ui-select{ width: 100%; }
            .table-header{ align-items:flex-start; }
            .table-footer{ align-items: stretch; }
            .pagination{ width: 100%; }
            .page-link{ flex: 1 1 auto; }

            .ord-table{ display:block; }
            .ord-table thead{ display:none; }
            .ord-table tbody{ display:block; padding: 10px; }
            .ord-table tbody tr{
                display:block;
                background: #fff;
                border: 1px solid var(--divider);
                border-radius: 14px;
                box-shadow: 0 8px 20px rgba(17,17,17,0.05);
                margin-bottom: 10px;
                overflow: hidden;
            }
            .ord-table tbody tr:nth-child(even){ background: #fff; }
            .ord-table tbody td{
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap: 12px;
                border-bottom: 1px solid var(--divider);
                padding: 12px 12px;
                white-space: normal;
            }
            .ord-table tbody td:last-child{ border-bottom: none; }
            .ord-table tbody td::before{
                content: attr(data-label);
                font-size: 0.74rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--muted);
                font-weight: 800;
                flex: 0 0 44%;
            }
            .ord-customer{ min-width: 0; }
            .ord-customer .note{ white-space: normal; overflow: visible; text-overflow: clip; }
            .ord-actions{ width: 100%; justify-content: flex-start; }
            .ord-status-form{ width: 100%; justify-content: flex-start; }
        }
    </style>
</head>
<body>

<div class="ord-wrap">
    <div class="orders-head">
        <div class="orders-title">
            <h2>Manage Orders</h2>
            <p>View and manage all customer orders</p>
        </div>
        <div class="orders-controls">
            <input id="ordersSearch" class="ui-input" type="search" placeholder="Search orders…" autocomplete="off">
            <select id="ordersStatusFilter" class="ui-select">
                <option value="all">All statuses</option>
                <option value="Pending">Pending</option>
                <option value="Processing">Processing</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <select id="ordersLimit" class="ui-select" aria-label="Orders per page">
                <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>20 / page</option>
                <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 / page</option>
                <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 / page</option>
            </select>
            <button id="ordersExport" class="ord-btn ghost" type="button">Export CSV</button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-meta">
                <strong><?= number_format($totalOrders) ?></strong> total orders
                <span class="table-meta-sep" aria-hidden="true">•</span>
                Showing <span id="ordersCount" class="table-count-pill"><?= count($orders) ?></span>
            </div>
        </div>
        <div class="table-body">
            <table class="ord-table" id="ordersTable">
                <colgroup>
                    <col style="width:7%">
                    <col style="width:20%">
                    <col style="width:14%">
                    <col style="width:18%">
                    <col style="width:10%">
                    <col style="width:13%">
                    <col style="width:10%">
                    <col style="width:8%">
                </colgroup>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Update</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr class="ord-row"
                            data-status="<?= htmlspecialchars($order['order_status'] ?? '') ?>"
                            data-search="<?= htmlspecialchars(
                                ($order['order_id'] ?? '') . ' ' .
                                ($order['customer_name'] ?? '') . ' ' .
                                ($order['customer_phone'] ?? '') . ' ' .
                                ($order['order_status'] ?? '') . ' ' .
                                ($order['total_price'] ?? '') . ' ' .
                                ($order['created_at'] ?? '') . ' ' .
                                ($order['customer_note'] ?? '')
                            ) ?>">
                            <td class="ord-id" data-label="Order ID">#<?= htmlspecialchars($order['order_id']) ?></td>
                            <td data-label="Customer">
                                <div class="ord-customer">
                                    <div class="name"><?= htmlspecialchars($order['customer_name'] ?? '') ?></div>
                                    <div class="note">
                                        <?= !empty($order['customer_note'])
                                            ? htmlspecialchars($order['customer_note'])
                                            : '—' ?>
                                    </div>
                                </div>
                            </td>
                            <td class="ord-phone" data-label="Phone"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></td>
                            <?php $statusFormId = 'status-form-' . (int)($order['order_id'] ?? 0); ?>
                            <td data-label="Status" class="ord-status-cell">
                                <form method="post" class="ord-status-form" id="<?= htmlspecialchars($statusFormId) ?>">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                    <div class="status-update-wrap">
                                        <span class="status-pill">
                                            <select name="order_status" class="ord-select native-status-select" aria-label="Order status">
                                                <option value="Pending"    <?= ($order['order_status'] === 'Pending')    ? 'selected' : '' ?>>Pending</option>
                                                <option value="Processing" <?= ($order['order_status'] === 'Processing') ? 'selected' : '' ?>>Processing</option>
                                                <option value="Completed"  <?= ($order['order_status'] === 'Completed')  ? 'selected' : '' ?>>Completed</option>
                                                <option value="Cancelled"  <?= ($order['order_status'] === 'Cancelled')  ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <div class="status-select-wrap" data-status-select>
                                                <button type="button" class="status-display" data-status-display><?= htmlspecialchars($order['order_status'] ?? 'Pending') ?></button>
                                                <ul class="status-options" data-status-options>
                                                    <li><button type="button" class="status-option <?= ($order['order_status'] === 'Pending') ? 'is-selected' : '' ?>" data-value="Pending">Pending</button></li>
                                                    <li><button type="button" class="status-option <?= ($order['order_status'] === 'Processing') ? 'is-selected' : '' ?>" data-value="Processing">Processing</button></li>
                                                    <li><button type="button" class="status-option <?= ($order['order_status'] === 'Completed') ? 'is-selected' : '' ?>" data-value="Completed">Completed</button></li>
                                                    <li><button type="button" class="status-option <?= ($order['order_status'] === 'Cancelled') ? 'is-selected' : '' ?>" data-value="Cancelled">Cancelled</button></li>
                                                </ul>
                                            </div>
                                        </span>
                                    </div>
                                </form>
                            </td>
                            <td data-label="Update" class="ord-update-cell">
                                <button type="submit" name="update_status" class="ord-btn update-btn" form="<?= htmlspecialchars($statusFormId) ?>">Update</button>
                            </td>
                            <td class="ord-amount" data-label="Amount">PKR <?= number_format((float)($order['total_price'] ?? 0), 2) ?></td>
                            <td class="ord-date" data-label="Date"><?= date('d-M-Y', strtotime($order['created_at'])) ?></td>
                            <td data-label="Actions" style="text-align:right;">
                                <div class="ord-actions">
                                    <button class="ord-btn view" type="button" data-order-id="<?= $order['order_id'] ?>">View</button>
                                    <?php if (($order['payment_method'] ?? '') === 'prepaid' && !empty($order['payment_screenshot'])): ?>
                                        <a href="/admin/view-screenshot.php?file=<?= htmlspecialchars($order['payment_screenshot']) ?>"
                                           target="_blank" class="ord-btn ghost">Screenshot</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-meta">
                Page <strong><?= (int)$page ?></strong> of <strong><?= (int)$totalPages ?></strong>
            </div>
            <div class="pagination" aria-label="Pagination">
                <?php
                    $baseParams = ['limit' => $limit];
                    $prevPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);
                    $prevHref = 'manage-orders.php?' . http_build_query($baseParams + ['page' => $prevPage]);
                    $nextHref = 'manage-orders.php?' . http_build_query($baseParams + ['page' => $nextPage]);
                ?>

                <a class="page-link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($prevHref) ?>">Previous</a>

                <?php
                    $window = 2;
                    $start = max(1, $page - $window);
                    $end = min($totalPages, $page + $window);

                    if ($start > 1) {
                        $firstHref = 'manage-orders.php?' . http_build_query($baseParams + ['page' => 1]);
                        echo '<a class="page-link" href="' . htmlspecialchars($firstHref) . '">1</a>';
                        if ($start > 2) {
                            echo '<span style="color:#9ca3af; font-weight:800; padding:0 4px;">…</span>';
                        }
                    }

                    for ($p = $start; $p <= $end; $p++) {
                        $href = 'manage-orders.php?' . http_build_query($baseParams + ['page' => $p]);
                        $cls = $p === $page ? 'page-link is-active' : 'page-link';
                        echo '<a class="' . $cls . '" href="' . htmlspecialchars($href) . '">' . (int)$p . '</a>';
                    }

                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) {
                            echo '<span style="color:#9ca3af; font-weight:800; padding:0 4px;">…</span>';
                        }
                        $lastHref = 'manage-orders.php?' . http_build_query($baseParams + ['page' => $totalPages]);
                        echo '<a class="page-link" href="' . htmlspecialchars($lastHref) . '">' . (int)$totalPages . '</a>';
                    }
                ?>

                <a class="page-link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($nextHref) ?>">Next</a>
            </div>
        </div>
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

<div class="confirm-overlay" id="statusConfirmOverlay" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div id="confirmTitle" class="confirm-title">Update Order Status?</div>
        <p class="confirm-text">Are you sure you want to update this order status?</p>
        <div class="confirm-actions">
            <button type="button" class="confirm-btn" id="confirmCancelBtn">Cancel</button>
            <button type="button" class="confirm-btn confirm-yes" id="confirmYesBtn">Yes, Update</button>
        </div>
    </div>
</div>
<div id="statusToast" class="status-toast" role="status" aria-live="polite"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalContainerId = "custom-ord-modal";
    const confirmOverlay = document.getElementById('statusConfirmOverlay');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const confirmYesBtn = document.getElementById('confirmYesBtn');
    const toastEl = document.getElementById('statusToast');
    let pendingSubmitForm = null;

    // Status pill coloring (keeps backend values untouched)
    function normalizeStatus(v) {
        return String(v || '').trim().toLowerCase();
    }
    function refreshStatusPills(scope) {
        (scope || document).querySelectorAll('.status-pill').forEach(function (pill) {
            const sel = pill.querySelector('select');
            if (!sel) return;
            pill.dataset.status = normalizeStatus(sel.value);
        });
    }
    refreshStatusPills(document);
    document.querySelectorAll('.ord-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const pill = this.closest('.status-pill');
            if (pill) pill.dataset.status = normalizeStatus(this.value);
            const row = this.closest('.ord-row');
            if (row) row.dataset.status = this.value;
        });
    });

    // Custom status dropdown UI (keeps native select for backend)
    document.querySelectorAll('[data-status-select]').forEach(function (wrap) {
        const display = wrap.querySelector('[data-status-display]');
        const options = Array.from(wrap.querySelectorAll('.status-option'));
        const nativeSelect = wrap.closest('.status-pill')?.querySelector('.native-status-select');
        if (!display || !nativeSelect || options.length === 0) return;

        function setValue(value) {
            nativeSelect.value = value;
            display.textContent = value;
            options.forEach(function (opt) {
                opt.classList.toggle('is-selected', opt.dataset.value === value);
            });
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.status-select-wrap.is-open').forEach(function (openWrap) {
                if (openWrap !== wrap) openWrap.classList.remove('is-open');
            });
            wrap.classList.toggle('is-open');
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                setValue(this.dataset.value || '');
                wrap.classList.remove('is-open');
            });
        });

        setValue(nativeSelect.value || options[0].dataset.value || 'Pending');
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.status-select-wrap.is-open').forEach(function (openWrap) {
            openWrap.classList.remove('is-open');
        });
    });

    // Custom confirmation for status updates
    document.querySelectorAll('.update-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            const formId = this.getAttribute('form');
            if (!formId) return;
            const form = document.getElementById(formId);
            if (!form) return;
            pendingSubmitForm = form;
            confirmOverlay.classList.add('is-open');
            document.body.classList.add('modal-open');
        });
    });
    confirmCancelBtn?.addEventListener('click', function () {
        confirmOverlay.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        pendingSubmitForm = null;
    });
    confirmYesBtn?.addEventListener('click', function () {
        if (!pendingSubmitForm) return;
        const form = pendingSubmitForm;
        pendingSubmitForm = null;
        confirmOverlay.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        // Ensure update_status is posted in all browsers.
        let hiddenAction = form.querySelector('input[name="update_status"]');
        if (!hiddenAction) {
            hiddenAction = document.createElement('input');
            hiddenAction.type = 'hidden';
            hiddenAction.name = 'update_status';
            hiddenAction.value = '1';
            form.appendChild(hiddenAction);
        } else {
            hiddenAction.value = '1';
        }
        form.submit();
    });
    confirmOverlay?.addEventListener('click', function (e) {
        if (e.target === confirmOverlay) {
            confirmOverlay.classList.remove('is-open');
            document.body.classList.remove('modal-open');
            pendingSubmitForm = null;
        }
    });

    document.querySelectorAll(".view").forEach(function (button) {
        button.addEventListener("click", function () {
            let orderId = this.getAttribute("data-order-id");

            fetch("get_order_details.php?order_id=" + orderId + "&_t=" + Date.now())
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
                    document.body.classList.add('modal-open');

                    setTimeout(() => {
                        const closeButton = document.querySelector(".custom-close-modal");
                        if (closeButton) {
                            closeButton.addEventListener("click", function () {
                                modalContainer.style.display = "none";
                                document.body.classList.remove('modal-open');
                            });
                        }
                    }, 100);
                })
                .catch(error => console.error("Error loading order details:", error));
        });
    });

    // Search + filter (client-side only, no backend changes)
    const searchEl = document.getElementById('ordersSearch');
    const filterEl = document.getElementById('ordersStatusFilter');
    const limitEl = document.getElementById('ordersLimit');
    const countEl = document.getElementById('ordersCount');
    const rows = Array.from(document.querySelectorAll('#ordersTable tbody .ord-row'));

    function applyTableFilters() {
        const q = normalizeStatus(searchEl ? searchEl.value : '').replace(/\s+/g, ' ').trim();
        const status = filterEl ? filterEl.value : 'all';
        let visible = 0;

        rows.forEach(function (row) {
            const rowText = String(row.dataset.search || '').toLowerCase();
            const rowStatus = String(row.dataset.status || '');
            const matchesQuery = q === '' ? true : rowText.includes(q);
            const matchesStatus = status === 'all' ? true : rowStatus === status;
            const show = matchesQuery && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) countEl.textContent = String(visible);
    }

    if (searchEl) searchEl.addEventListener('input', applyTableFilters);
    if (filterEl) filterEl.addEventListener('change', applyTableFilters);
    applyTableFilters();

    // Orders per page dropdown (server-side pagination)
    if (limitEl) {
        limitEl.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    // Export visible rows as CSV (client-side only)
    const exportBtn = document.getElementById('ordersExport');
    function csvEscape(v) {
        const s = String(v ?? '');
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['Order ID', 'Customer', 'Phone', 'Status', 'Amount', 'Date', 'Note'];
            const lines = [headers.join(',')];
            rows.forEach(function (row) {
                if (row.style.display === 'none') return;
                const orderId = (row.querySelector('.ord-id')?.textContent || '').trim();
                const name = (row.querySelector('.ord-customer .name')?.textContent || '').trim();
                const phone = (row.querySelector('.ord-phone')?.textContent || '').trim();
                const status = (row.querySelector('select[name="order_status"]')?.value || '').trim();
                const amount = (row.querySelector('.ord-amount')?.textContent || '').trim();
                const date = (row.querySelector('.ord-date')?.textContent || '').trim();
                const note = (row.querySelector('.ord-customer .note')?.textContent || '').trim();
                lines.push([orderId, name, phone, status, amount, date, note].map(csvEscape).join(','));
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'orders.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        });
    }

    // Close modal on outside click
    document.addEventListener("click", function (event) {
        const modalContainer = document.getElementById(modalContainerId);
        if (modalContainer && event.target === modalContainer) {
            modalContainer.style.display = "none";
            document.body.classList.remove('modal-open');
        }
    });

    const params = new URLSearchParams(window.location.search);
    if (toastEl) {
        const updatedState = params.get('updated');
        if (updatedState === '1') {
            toastEl.textContent = 'Order status updated successfully.';
            toastEl.classList.remove('is-failure');
            toastEl.classList.add('show');
        } else if (updatedState === '0') {
            toastEl.textContent = 'Failed to update order status. Please try again.';
            toastEl.classList.add('is-failure');
            toastEl.classList.add('show');
        }
        if (updatedState === '1' || updatedState === '0') {
            setTimeout(function () { toastEl.classList.remove('show'); }, 3200);
            // Prevent toast from reappearing on page refresh.
            params.delete('updated');
            const newQuery = params.toString();
            const newUrl = window.location.pathname + (newQuery ? ('?' + newQuery) : '');
            window.history.replaceState({}, document.title, newUrl);
        }
    }
});
</script>

</body>
</html>