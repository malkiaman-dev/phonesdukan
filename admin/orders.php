        <!-- Recent Orders -->
        <h3>Recent Orders</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Total Price</th>
                    <th>Order Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->query("SELECT order_id, customer_name, total_price, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['order_id']) . "</td>
                            <td>" . htmlspecialchars($row['customer_name']) . "</td>
                            <td>" . htmlspecialchars($row['total_price']) . "</td>
                            <td>" . htmlspecialchars($row['order_status']) . "</td>
                            <td>" . htmlspecialchars($row['created_at']) . "</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
