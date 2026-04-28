<h2 class="order">Your Orders</h2>
<div class="orders-table-wrapper">
<table class="orders-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Status</th>
            <th>Total Price</th>
            <th>Order Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Order ID"><?= $order['order_id']; ?></td>
                <td data-label="Customer Name"><?= $order['customer_name']; ?></td>
                <td data-label="Status"><?= $order['order_status']; ?></td>
                <td data-label="Total Price"><?= $order['total_price']; ?> <?= $order['currency']; ?></td>
                <td data-label="Order Date"><?= $order['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>
<style>
    /* Table Styling */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .orders-table th, .orders-table td {
        padding: 12px 15px;
        text-align: left;
        border: 1px solid #ddd;
    }
    
    .orders-table th {
        background-color: #3498db;
        color: white;
    }

    .orders-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .orders-table tr:hover {
        background-color: #dff9fb;
    }

    .orders-table td {
        font-size: 14px;
    }

    h2.order {
    margin-bottom: 0px;
}

    /* Style the table header for better visibility */
    .orders-table th {
        font-size: 16px;
        font-weight: bold;
        padding: 10px;
    }

    /* Style the table data for a clean look */
    .orders-table td {
        font-size: 14px;
    }

    /* Mobile Styling */
@media screen and (max-width: 768px) {
    /* Wrapper for scrollable table on small screens */
    .orders-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
    }

    /* Ensure table is 100% width on mobile */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    /* Remove table borders */
    .orders-table th, .orders-table td {
        padding: 12px 15px;
        border: none;
    }

    /* Style each table row into a block element */
    .orders-table td {
        display: flex;
        justify-content: space-between;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
        background-color: #f9f9f9;
        margin-bottom: 5px;
        border-radius: 5px;
    }

    /* Make each row look like a key-value pair with column name on the left */
    .orders-table td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #3498db;
        margin-right: 10px;
        font-size: 14px;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    /* Style header (optional: remove header on mobile, as it's redundant) */
    .orders-table th {
        display: none;
    }

    /* Optional: Style the first column */
    .orders-table td:first-child {
        background-color: #eef2f5;
        font-weight: bold;
    }

    /* Style the last row */
    .orders-table tr:last-child td {
        border-bottom: none;
    }

    /* Add spacing for the whole table */
    .orders-table-wrapper {
        padding: 0 10px;
    }

    /* Improve row appearance with background and spacing */
    .orders-table tr {
        margin-bottom: 15px;
        background-color: #fff;
        border-radius: 8px;
    }

    /* Make the table cleaner and more modern with subtle hover effect */
    .orders-table tr:hover {
        background-color: #dff9fb;
    }
}

</style>
