<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

$products = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
</head>
<body>
    <h2>Manage Products</h2>
    <a href="add_product.php">Add New Product</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Action</th>
        </tr>
        <?php while ($product = $products->fetch_assoc()) { ?>
            <tr>
                <td><?= $product['id'] ?></td>
                <td><?= $product['name'] ?></td>
                <td>$<?= $product['price'] ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $product['id'] ?>">Edit</a> |
                    <a href="delete_product.php?id=<?= $product['id'] ?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
