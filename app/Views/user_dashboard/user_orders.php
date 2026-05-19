<div class="ord-wrap">
    <h1 class="db-page-title">My Orders</h1>

    <?php if (empty($orders)): ?>
    <div class="ord-empty">
        <div class="ord-empty-icon">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <h2>No orders yet</h2>
        <p>You haven't placed any orders. Start shopping to see your orders here.</p>
        <a href="/" class="ord-shop-btn">Browse Products</a>
    </div>
    <?php else: ?>
    <div class="ord-summary">
        <span><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found</span>
    </div>
    <div class="db-table-wrap">
    <table class="db-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td data-label="Order ID"><strong>#<?= htmlspecialchars($o['order_id']) ?></strong></td>
                <td data-label="Customer"><?= htmlspecialchars($o['customer_name']) ?></td>
                <td data-label="Status"><span class="db-badge db-badge--<?= strtolower($o['order_status']) ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                <td data-label="Total"><strong><?= htmlspecialchars($o['total_price']) ?> <?= htmlspecialchars($o['currency']) ?></strong></td>
                <td data-label="Date"><?= htmlspecialchars(date('d M Y', strtotime($o['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<style>
.ord-wrap { }

.ord-empty {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 14px;
    padding: 60px 20px;
    text-align: center;
}

.ord-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: #9ca3af;
}

.ord-empty-icon svg { width: 32px; height: 32px; }

.ord-empty h2 { margin: 0 0 8px; font-size: 18px; color: #111827; }
.ord-empty p  { margin: 0 0 20px; font-size: 14px; color: #6b7280; }

.ord-shop-btn {
    display: inline-block;
    padding: 12px 28px;
    background: #facc15;
    color: #111827;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
}

.ord-shop-btn:hover { background: #e8b900; }

.ord-summary {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 12px;
}

/* Reuse db-table styles from dashboard.php */
.db-table-wrap {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 14px;
    overflow: hidden;
}

@media (max-width: 600px) {
    .db-table thead { display: none; }

    .db-table tr {
        display: block;
        border-bottom: 1px solid #f3f4f6;
        padding: 12px 16px;
    }

    .db-table tr:last-child { border-bottom: none; }

    .db-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        border-bottom: none;
        font-size: 13px;
    }

    .db-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
}
</style>
