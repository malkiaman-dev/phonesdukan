<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/BulkInquiryModel.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $new_status = $_POST['inquiry_status'];
    $bulkInquiryModel = new BulkInquiryModel();
    $result = $bulkInquiryModel->updateB2BOrderStatus($inquiry_id, $new_status);

    header('Location: manage-b2b-orders.php?updated=' . ($result ? '1' : '0'));
    exit();
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';

$bulkInquiryModel = new BulkInquiryModel();
$b2b_orders = $bulkInquiryModel->getAllB2BOrders();
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand-black: #111111;
            --brand-yellow: #facc15;
            --brand-yellow-hover: #eab308;
            --brand-yellow-light: #fffbeb;
            --brand-white: #ffffff;
            --brand-border: #e5e7eb;
            --brand-muted: #6b7280;
            --header-bg: #f9fafb;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f3f4f6;
            color: var(--brand-black);
            overflow-x: hidden;
        }

        .ord-wrap {
            width: 100%;
            max-width: 100%;
            margin: 24px auto;
            padding: 0 20px;
        }

        .ord-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--brand-black);
            margin-bottom: 18px;
            letter-spacing: -0.2px;
        }

        .orders-page-header {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17,17,17,0.06);
            padding: 20px 24px;
            margin-bottom: 18px;
        }
        .orders-page-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-black);
            letter-spacing: -0.02em;
        }
        .orders-page-header p {
            margin: 6px 0 0;
            color: var(--brand-muted);
            font-size: 0.92rem;
        }

        .orders-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .orders-title p {
            color: var(--brand-muted);
            font-size: 0.92rem;
            margin-top: 4px;
        }

        .orders-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ui-input {
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--brand-black);
            border: 1px solid var(--brand-border);
            background: #fff;
            border-radius: 12px;
            padding: 9px 12px;
            outline: none;
            transition: box-shadow .15s ease, border-color .15s ease;
        }

        .ui-input {
            width: min(320px, 70vw);
        }

        .ui-input:focus {
            border-color: var(--brand-yellow);
            box-shadow: 0 0 0 4px rgba(250,204,21,0.25);
        }

        .native-filter-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .filter-select-wrap {
            position: relative;
            min-width: 130px;
        }

        .filter-select-wrap.limit-wrap {
            min-width: 116px;
        }

        .filter-display {
            width: 100%;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 10px 34px 10px 12px;
            background: #fff;
            color: var(--brand-black);
            font-size: 0.9rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            white-space: nowrap;
        }

        .filter-display::after {
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

        .filter-display:hover,
        .filter-select-wrap.is-open .filter-display,
        .filter-display:focus {
            outline: none;
            border-color: var(--brand-yellow);
            box-shadow: 0 0 0 4px rgba(250,204,21,0.25);
        }

        .filter-options {
            position: absolute;
            z-index: 210;
            left: 0;
            right: 0;
            margin-top: 6px;
            list-style: none;
            background: #fff;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(17, 17, 17, 0.12);
            padding: 6px;
            display: none;
        }

        .filter-select-wrap.is-open .filter-options {
            display: block;
        }

        .filter-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--brand-black);
            cursor: pointer;
        }

        .filter-option:hover {
            background: var(--brand-yellow-light);
        }

        .filter-option.is-selected {
            background: var(--brand-yellow);
        }

        .ord-table-wrap {
            background: var(--brand-white);
            border: 1px solid var(--brand-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(17, 17, 17, 0.06);
            overflow: visible;
            width: 100%;
        }

        .ord-table {
            width: 100%;
            table-layout: auto;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .ord-head tr {
            background: var(--header-bg);
        }

        .ord-head th {
            color: var(--brand-black);
            font-weight: 700;
            font-size: 0.79rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 14px 14px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid var(--brand-border);
        }

        .ord-table tbody tr {
            transition: background 0.15s ease;
        }

        .ord-table tbody tr:hover {
            background: var(--brand-yellow-light);
        }

        .ord-table tbody td {
            padding: 13px 14px;
            vertical-align: middle;
            color: var(--brand-black);
            line-height: 1.4;
            border-bottom: 1px solid var(--brand-border);
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
            word-break: break-word;
        }

        .ord-table tbody tr:last-child td {
            border-bottom: none;
        }

        .ord-table tbody td:first-child {
            font-weight: 700;
            color: var(--brand-muted);
            min-width: 56px;
        }

        .ord-table tbody td strong {
            font-weight: 700;
            color: var(--brand-black);
        }

        .ord-table tbody td i {
            color: var(--brand-muted);
            font-style: italic;
        }

        .ord-note {
            max-width: 100%;
        }

        .ord-note-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-clamp: 2;
            color: var(--brand-black) !important;
            word-break: break-word;
            background: transparent !important;
            border-radius: 0 !important;
            padding: 0 !important;
        }

        .ord-status-cell,
        .ord-action {
            overflow: visible;
            text-overflow: unset;
            white-space: nowrap;
            position: relative;
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
            width: 132px;
            max-width: 100%;
        }

        .status-display {
            width: 100%;
            border: 1px solid var(--brand-border);
            border-radius: 999px;
            padding: 8px 30px 8px 12px;
            background: var(--brand-white);
            color: var(--brand-black);
            font-size: 0.84rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            white-space: nowrap;
        }

        .status-display::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid var(--brand-muted);
            border-bottom: 2px solid var(--brand-muted);
            transform: translateY(-65%) rotate(45deg);
        }

        .status-select-wrap.is-open .status-display,
        .status-display:hover,
        .status-display:focus {
            outline: none;
            border-color: var(--brand-yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.25);
        }

        .status-options {
            position: absolute;
            left: 0;
            right: 0;
            z-index: 200;
            margin-top: 6px;
            list-style: none;
            background: var(--brand-white);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(17, 17, 17, 0.12);
            padding: 6px;
            display: none;
        }

        .status-select-wrap.is-open .status-options {
            display: block;
        }

        .status-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--brand-black);
            cursor: pointer;
        }

        .status-option:hover {
            background: var(--brand-yellow-light);
        }

        .status-option.is-selected {
            background: var(--brand-yellow);
        }

        .ord-btn {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid var(--brand-black);
            border-radius: 999px;
            background: var(--brand-black);
            color: var(--brand-white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.15s ease, background 0.15s ease;
            white-space: nowrap;
            margin: 0;
        }

        .ord-btn:hover {
            background: var(--brand-black);
            color: var(--brand-yellow);
        }

        .ord-btn:active {
            background: var(--brand-black);
        }

        .ord-btn.ghost {
            background: #fff;
            color: var(--brand-black);
            border-color: var(--brand-border);
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.08);
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
        }

        .ord-btn.ghost:hover {
            background: var(--brand-yellow-light);
            color: var(--brand-yellow);
            border-color: var(--brand-yellow);
            box-shadow: 0 12px 24px rgba(17, 17, 17, 0.14);
            transform: translateY(-1px);
        }

        .ord-btn.ghost:active {
            transform: translateY(0);
            box-shadow: 0 6px 12px rgba(17, 17, 17, 0.1);
        }

        .ord-action {
            min-width: 124px;
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
        }

        .ord-action .ord-btn {
            width: 88%;
            text-align: center;
            align-self: center;
        }

        .table-footer {
            border-top: 1px solid var(--brand-border);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .table-meta {
            color: var(--brand-muted);
            font-size: 0.9rem;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid var(--brand-border);
            background: #fff;
            color: var(--brand-black);
            font-weight: 700;
            font-size: 0.86rem;
            text-decoration: none;
            cursor: pointer;
        }

        .page-link:hover {
            border-color: var(--brand-yellow);
            background: var(--brand-yellow-light);
        }

        .page-link.is-active {
            background: var(--brand-yellow);
            border-color: var(--brand-yellow);
        }

        .page-link.is-disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .table-body {
            overflow: visible;
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
            border: 1px solid var(--brand-border);
            box-shadow: 0 20px 40px rgba(17,17,17,0.18);
            padding: 20px;
        }
        .confirm-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--brand-black);
            margin-bottom: 6px;
        }
        .confirm-text {
            color: var(--brand-muted);
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .confirm-actions {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .confirm-btn {
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 700;
            border: 1px solid var(--brand-border);
            background: #fff;
            color: var(--brand-black);
            cursor: pointer;
        }
        .confirm-btn.confirm-yes {
            background: var(--brand-black);
            color: #fff;
            border-color: var(--brand-black);
        }
        .confirm-btn.confirm-yes:hover {
            color: var(--brand-yellow);
        }

        .status-toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1300;
            background: #111;
            color: #fff;
            border: 1px solid var(--brand-yellow);
            border-left: 5px solid var(--brand-yellow);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 14px 30px rgba(17,17,17,0.3);
            display: none;
        }
        .status-toast.is-failure {
            border-left-color: var(--brand-yellow);
            border-color: var(--brand-yellow);
        }
        .status-toast.show { display: block; }

        /* ===== Modal Overlay ===== */
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
            background: none;
            border: none;
            transition: color 0.15s;
        }
        .modal-content .close:hover,
        .custom-close-modal:hover { color: #222; }

        /* ===== Responsive ===== */
        @media (max-width: 900px) {
            .ord-wrap { padding: 0 10px; margin: 16px auto; }
            .ord-title { font-size: 1.35rem; margin-bottom: 12px; }
            .ord-table { font-size: 0.8rem; }
            .ord-head th,
            .ord-table tbody td { padding: 10px 10px; }
            .status-select-wrap { width: 122px; }
            .ord-note { max-width: 170px; }
            .orders-controls { width: 100%; }
            .ui-input { width: 100%; }
        }

        @media (max-width: 768px) {
            .ord-head { display: none; }
            .ord-table,
            .ord-table tbody,
            .ord-table tr,
            .ord-table td {
                display: block;
                width: 100%;
            }
            .ord-table tbody {
                padding: 10px;
            }
            .ord-table tr {
                border: 1px solid var(--brand-border);
                border-radius: 12px;
                margin-bottom: 10px;
                background: #fff;
                overflow: hidden;
            }
            .ord-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                border-bottom: 1px solid var(--brand-border);
                padding: 10px 12px;
                white-space: normal;
            }
            .ord-table tbody td:last-child {
                border-bottom: none;
            }
            .ord-table tbody td::before {
                content: attr(data-label);
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--brand-muted);
                font-weight: 700;
                flex: 0 0 40%;
            }
            .ord-action {
                justify-content: flex-start;
                flex-wrap: nowrap;
                min-width: 0;
            }
            .orders-controls .ui-input,
            .filter-select-wrap {
                width: 100%;
            }
        }
    </style>

<div class="ord-wrap">
    <div class="orders-page-header">
        <h2>Manage B2B Orders</h2>
        <p>View and manage all business inquiry orders</p>
    </div>
    <div class="orders-head">
        <div class="orders-controls">
            <input id="b2bSearch" class="ui-input" type="search" placeholder="Search B2B orders..." autocomplete="off">
            <select id="b2bStatusFilter" class="native-filter-select" aria-label="Filter by status">
                <option value="all">All statuses</option>
                <option value="Pending">Pending</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Completed">Completed</option>
            </select>
            <div class="filter-select-wrap" data-filter-select>
                <button type="button" class="filter-display" data-filter-display>All statuses</button>
                <ul class="filter-options" data-filter-options>
                    <li><button type="button" class="filter-option is-selected" data-value="all">All statuses</button></li>
                    <li><button type="button" class="filter-option" data-value="Pending">Pending</button></li>
                    <li><button type="button" class="filter-option" data-value="Cancelled">Cancelled</button></li>
                    <li><button type="button" class="filter-option" data-value="Completed">Completed</button></li>
                </ul>
            </div>
            <select id="b2bLimitFilter" class="native-filter-select" aria-label="Rows per page">
                <option value="10">10 / page</option>
                <option value="20" selected>20 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
            </select>
            <div class="filter-select-wrap limit-wrap" data-filter-select>
                <button type="button" class="filter-display" data-filter-display>20 / page</button>
                <ul class="filter-options" data-filter-options>
                    <li><button type="button" class="filter-option" data-value="10">10 / page</button></li>
                    <li><button type="button" class="filter-option is-selected" data-value="20">20 / page</button></li>
                    <li><button type="button" class="filter-option" data-value="50">50 / page</button></li>
                    <li><button type="button" class="filter-option" data-value="100">100 / page</button></li>
                </ul>
            </div>
            <button id="b2bExport" class="ord-btn ghost" type="button">Export CSV</button>
        </div>
    </div>
    <div class="ord-table-wrap">
        <table class="ord-table" id="b2bOrdersTable">
            <colgroup>
                <col style="width:4%">
                <col style="width:11%">
                <col style="width:12%">
                <col style="width:10%">
                <col style="width:9%">
                <col style="width:9%">
                <col style="width:10%">
                <col style="width:11%">
                <col style="width:13%">
                <col style="width:11%">
            </colgroup>
            <thead class="ord-head">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Business</th>
                    <th>Phone</th>
                    <th>Tel No</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($b2b_orders as $order): ?>
                <tr class="ord-row"
                    data-status="<?= htmlspecialchars($order['status'] ?? '') ?>"
                    data-search="<?= htmlspecialchars(
                        ($order['id'] ?? '') . ' ' .
                        ($order['name'] ?? '') . ' ' .
                        ($order['business_name'] ?? '') . ' ' .
                        ($order['phone'] ?? '') . ' ' .
                        ($order['tel_no'] ?? '') . ' ' .
                        ($order['total_price'] ?? '') . ' ' .
                        ($order['status'] ?? '') . ' ' .
                        ($order['created_at'] ?? '') . ' ' .
                        ($order['message'] ?? '')
                    ) ?>">
                    <td data-label="ID"><?= htmlspecialchars($order['id']) ?></td>
                    <td data-label="Name"><?= htmlspecialchars($order['name']) ?></td>
                    <td data-label="Business"><?= htmlspecialchars($order['business_name']) ?></td>
                    <td data-label="Phone"><?= htmlspecialchars($order['phone']) ?></td>
                    <td data-label="Tel No"><?= htmlspecialchars($order['tel_no'] ?? 'N/A') ?></td>
                    <td data-label="Price"><strong>PKR <?= number_format((float)($order['total_price'] ?? 0), 0) ?></strong></td>
                    <td class="ord-status-cell" data-label="Status">
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($order['id']) ?>">
                            <select name="inquiry_status" class="native-status-select" aria-label="Inquiry status">
                                <option value="Pending"   <?= ($order['status'] === 'Pending')   ? 'selected' : '' ?>>Pending</option>
                                <option value="Cancelled" <?= ($order['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                <option value="Completed" <?= ($order['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <div class="status-select-wrap" data-status-select>
                                <button type="button" class="status-display" data-status-display>
                                    <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                                </button>
                                <ul class="status-options" data-status-options>
                                    <li><button type="button" class="status-option <?= ($order['status'] === 'Pending') ? 'is-selected' : '' ?>" data-value="Pending">Pending</button></li>
                                    <li><button type="button" class="status-option <?= ($order['status'] === 'Cancelled') ? 'is-selected' : '' ?>" data-value="Cancelled">Cancelled</button></li>
                                    <li><button type="button" class="status-option <?= ($order['status'] === 'Completed') ? 'is-selected' : '' ?>" data-value="Completed">Completed</button></li>
                                </ul>
                            </div>
                    </td>
                    <td data-label="Date"><?= date('d-M-Y H:i', strtotime($order['created_at'])) ?></td>
                    <td class="ord-note" data-label="Note">
                        <?php if (!empty($order['message'])): ?>
                            <span class="ord-note-text" title="<?= htmlspecialchars($order['message']) ?>">
                                <?= htmlspecialchars($order['message']) ?>
                            </span>
                        <?php else: ?>
                            <i>No note.</i>
                        <?php endif; ?>
                    </td>
                    <td class="ord-action" data-label="Action">
                        <button type="submit" name="update_status" class="ord-btn upd">Update</button>
                        </form>
                        <button type="button" class="ord-btn view" data-inquiry-id="<?= $order['id'] ?>">View</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="table-footer">
            <div class="table-meta">
                Showing <strong id="b2bVisibleCount"><?= count($b2b_orders) ?></strong> of <strong><?= count($b2b_orders) ?></strong> orders
            </div>
            <div class="pagination" id="b2bPagination"></div>
        </div>
    </div>
</div>

<!-- B2B Order Details Modal -->
<div id="b2b-ord-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="b2b-ord-detail-content"><p>Loading...</p></div>
    </div>
</div>
<div class="confirm-overlay" id="statusConfirmOverlay" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div id="confirmTitle" class="confirm-title">Update B2B Order Status?</div>
        <p class="confirm-text">Are you sure you want to update this B2B order status?</p>
        <div class="confirm-actions">
            <button type="button" class="confirm-btn" id="confirmCancelBtn">Cancel</button>
            <button type="button" class="confirm-btn confirm-yes" id="confirmYesBtn">Yes, Update</button>
        </div>
    </div>
</div>
<div id="statusToast" class="status-toast" role="status" aria-live="polite"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalContainerId = "custom-b2b-ord-modal";
    const searchEl = document.getElementById("b2bSearch");
    const statusFilterEl = document.getElementById("b2bStatusFilter");
    const limitFilterEl = document.getElementById("b2bLimitFilter");
    const exportBtn = document.getElementById("b2bExport");
    const visibleCountEl = document.getElementById("b2bVisibleCount");
    const paginationEl = document.getElementById("b2bPagination");
    const rows = Array.from(document.querySelectorAll("#b2bOrdersTable tbody tr"));
    const confirmOverlay = document.getElementById("statusConfirmOverlay");
    const confirmCancelBtn = document.getElementById("confirmCancelBtn");
    const confirmYesBtn = document.getElementById("confirmYesBtn");
    const toastEl = document.getElementById("statusToast");
    let currentPage = 1;
    let pendingSubmitForm = null;
    
    document.querySelectorAll("[data-status-select]").forEach(function (wrap) {
        const display = wrap.querySelector("[data-status-display]");
        const options = Array.from(wrap.querySelectorAll(".status-option"));
        const nativeSelect = wrap.parentElement.querySelector(".native-status-select");
        if (!display || !nativeSelect || options.length === 0) return;

        function setValue(value) {
            nativeSelect.value = value;
            display.textContent = value;
            options.forEach(function (opt) {
                opt.classList.toggle("is-selected", opt.dataset.value === value);
            });
            nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
            const row = wrap.closest(".ord-row");
            if (row) row.dataset.status = value;
        }

        display.addEventListener("click", function (e) {
            e.stopPropagation();
            document.querySelectorAll(".status-select-wrap.is-open").forEach(function (openWrap) {
                if (openWrap !== wrap) openWrap.classList.remove("is-open");
            });
            wrap.classList.toggle("is-open");
        });

        options.forEach(function (opt) {
            opt.addEventListener("click", function () {
                setValue(this.dataset.value || "");
                wrap.classList.remove("is-open");
            });
        });

        setValue(nativeSelect.value || options[0].dataset.value || "Pending");
    });

    document.addEventListener("click", function () {
        document.querySelectorAll(".status-select-wrap.is-open").forEach(function (openWrap) {
            openWrap.classList.remove("is-open");
        });
    });

    document.querySelectorAll("[data-filter-select]").forEach(function (wrap) {
        const display = wrap.querySelector("[data-filter-display]");
        const options = Array.from(wrap.querySelectorAll(".filter-option"));
        const nativeSelect = wrap.previousElementSibling;
        if (!display || !nativeSelect || !nativeSelect.classList.contains("native-filter-select")) return;

        function setValue(value) {
            nativeSelect.value = value;
            const selectedOpt = options.find(function (opt) {
                return opt.dataset.value === value;
            });
            display.textContent = selectedOpt ? selectedOpt.textContent.trim() : value;
            options.forEach(function (opt) {
                opt.classList.toggle("is-selected", opt.dataset.value === value);
            });
            nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }

        display.addEventListener("click", function (e) {
            e.stopPropagation();
            document.querySelectorAll(".filter-select-wrap.is-open").forEach(function (openWrap) {
                if (openWrap !== wrap) openWrap.classList.remove("is-open");
            });
            wrap.classList.toggle("is-open");
        });

        options.forEach(function (opt) {
            opt.addEventListener("click", function () {
                setValue(this.dataset.value || "");
                wrap.classList.remove("is-open");
            });
        });

        setValue(nativeSelect.value || options[0].dataset.value || "");
    });

    document.addEventListener("click", function () {
        document.querySelectorAll(".filter-select-wrap.is-open").forEach(function (openWrap) {
            openWrap.classList.remove("is-open");
        });
    });

    function normalizeText(value) {
        return String(value || "").toLowerCase().trim();
    }

    function csvEscape(value) {
        const s = String(value ?? "");
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }

    function getFilteredRows() {
        const q = normalizeText(searchEl ? searchEl.value : "");
        const status = statusFilterEl ? statusFilterEl.value : "all";
        return rows.filter(function (row) {
            const rowSearch = normalizeText(row.dataset.search || "");
            const rowStatus = row.dataset.status || "";
            const matchesSearch = q === "" ? true : rowSearch.includes(q);
            const matchesStatus = status === "all" ? true : rowStatus === status;
            return matchesSearch && matchesStatus;
        });
    }

    function renderPagination(totalPages) {
        if (!paginationEl) return;
        paginationEl.innerHTML = "";
        if (totalPages <= 1) return;

        const prevBtn = document.createElement("button");
        prevBtn.type = "button";
        prevBtn.className = "page-link" + (currentPage === 1 ? " is-disabled" : "");
        prevBtn.textContent = "Prev";
        prevBtn.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage--;
                applyFiltersAndPagination();
            }
        });
        paginationEl.appendChild(prevBtn);

        for (let p = 1; p <= totalPages; p++) {
            const pageBtn = document.createElement("button");
            pageBtn.type = "button";
            pageBtn.className = "page-link" + (p === currentPage ? " is-active" : "");
            pageBtn.textContent = String(p);
            pageBtn.addEventListener("click", function () {
                currentPage = p;
                applyFiltersAndPagination();
            });
            paginationEl.appendChild(pageBtn);
        }

        const nextBtn = document.createElement("button");
        nextBtn.type = "button";
        nextBtn.className = "page-link" + (currentPage === totalPages ? " is-disabled" : "");
        nextBtn.textContent = "Next";
        nextBtn.addEventListener("click", function () {
            if (currentPage < totalPages) {
                currentPage++;
                applyFiltersAndPagination();
            }
        });
        paginationEl.appendChild(nextBtn);
    }

    function applyFiltersAndPagination() {
        const filteredRows = getFilteredRows();
        const perPage = parseInt(limitFilterEl ? limitFilterEl.value : "20", 10) || 20;
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        rows.forEach(function (row) { row.style.display = "none"; });
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        filteredRows.slice(start, end).forEach(function (row) {
            row.style.display = "";
        });

        if (visibleCountEl) visibleCountEl.textContent = String(filteredRows.length);
        renderPagination(totalPages);
    }

    searchEl?.addEventListener("input", function () {
        currentPage = 1;
        applyFiltersAndPagination();
    });
    statusFilterEl?.addEventListener("change", function () {
        currentPage = 1;
        applyFiltersAndPagination();
    });
    limitFilterEl?.addEventListener("change", function () {
        currentPage = 1;
        applyFiltersAndPagination();
    });

    exportBtn?.addEventListener("click", function () {
        const headers = ["ID", "Name", "Business", "Phone", "Tel No", "Price", "Status", "Date", "Note"];
        const lines = [headers.join(",")];

        const filteredRows = getFilteredRows();
        filteredRows.forEach(function (row) {
            const cells = row.querySelectorAll("td");
            const id = (cells[0]?.textContent || "").trim();
            const name = (cells[1]?.textContent || "").trim();
            const business = (cells[2]?.textContent || "").trim();
            const phone = (cells[3]?.textContent || "").trim();
            const telNo = (cells[4]?.textContent || "").trim();
            const price = (cells[5]?.textContent || "").trim();
            const status = (row.querySelector('select[name="inquiry_status"]')?.value || row.dataset.status || "").trim();
            const date = (cells[7]?.textContent || "").trim();
            const note = (cells[8]?.textContent || "").trim();

            lines.push([id, name, business, phone, telNo, price, status, date, note].map(csvEscape).join(","));
        });

        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, "0");
        const d = String(now.getDate()).padStart(2, "0");
        const filename = `b2b-orders-${y}${m}${d}.csv`;

        const blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    document.querySelectorAll(".ord-btn.upd").forEach(function (btn) {
        btn.addEventListener("click", function (event) {
            event.preventDefault();
            const form = this.closest("td")?.previousElementSibling?.querySelector("form") || this.closest("tr")?.querySelector("form");
            if (!form) return;
            pendingSubmitForm = form;
            confirmOverlay?.classList.add("is-open");
        });
    });

    confirmCancelBtn?.addEventListener("click", function () {
        confirmOverlay?.classList.remove("is-open");
        pendingSubmitForm = null;
    });

    confirmYesBtn?.addEventListener("click", function () {
        if (!pendingSubmitForm) return;
        let hiddenAction = pendingSubmitForm.querySelector('input[name="update_status"]');
        if (!hiddenAction) {
            hiddenAction = document.createElement("input");
            hiddenAction.type = "hidden";
            hiddenAction.name = "update_status";
            hiddenAction.value = "1";
            pendingSubmitForm.appendChild(hiddenAction);
        } else {
            hiddenAction.value = "1";
        }
        const formToSubmit = pendingSubmitForm;
        pendingSubmitForm = null;
        confirmOverlay?.classList.remove("is-open");
        formToSubmit.submit();
    });

    confirmOverlay?.addEventListener("click", function (e) {
        if (e.target === confirmOverlay) {
            confirmOverlay.classList.remove("is-open");
            pendingSubmitForm = null;
        }
    });

    applyFiltersAndPagination();

    const params = new URLSearchParams(window.location.search);
    if (toastEl) {
        const updatedState = params.get("updated");
        if (updatedState === "1") {
            toastEl.textContent = "B2B order status updated successfully.";
            toastEl.classList.remove("is-failure");
            toastEl.classList.add("show");
        } else if (updatedState === "0") {
            toastEl.textContent = "Failed to update B2B order status. Please try again.";
            toastEl.classList.add("is-failure");
            toastEl.classList.add("show");
        }
        if (updatedState === "1" || updatedState === "0") {
            setTimeout(function () { toastEl.classList.remove("show"); }, 3200);
            params.delete("updated");
            const newQuery = params.toString();
            const newUrl = window.location.pathname + (newQuery ? ("?" + newQuery) : "");
            window.history.replaceState({}, document.title, newUrl);
        }
    }

    document.querySelectorAll(".view").forEach(function (button) {
        button.addEventListener("click", function () {
            let inquiryId = this.getAttribute("data-inquiry-id");

            fetch("get_b2b_order_details.php?inquiry_id=" + inquiryId)
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
                .catch(error => console.error("Error loading B2B order details:", error));
        });
    });

    document.addEventListener("click", function (event) {
        const modalContainer = document.getElementById(modalContainerId);
        if (modalContainer && event.target === modalContainer) {
            modalContainer.style.display = "none";
        }
    });
});
</script>
