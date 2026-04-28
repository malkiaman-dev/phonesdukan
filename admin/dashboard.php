<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

// Include the header
include __DIR__ . '/admin_header.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in by checking both session variables
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Fetch admin details
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];

    $sql = "SELECT name FROM admins WHERE id = :admin_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":admin_id", $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $admin_name = $admin ? htmlspecialchars($admin['name']) : "Admin";
} else {
    $admin_name = "Admin";
}
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Phones Dukan</title>
    <!-- Main Content -->
    <?php // Only include the sidebar if the admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    include __DIR__ . '/admin_sidebar.php';
}?>

<div class="content">
<h2 class="dashboard-title">Welcome, <span class="admin-name"><?= htmlspecialchars($admin_name) ?></span> to Admin Dashboard</h2>
    <div class="dashboard-stats">
        <!-- Products Count -->
        <div class="stat-card bg-primary">
            <div class="stat-header">Total Products</div>
            <div class="stat-body">
                <h4>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) AS total FROM products");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo htmlspecialchars($row['total']);
                    ?>
                </h4>
            </div>
        </div>

        <!-- Orders Count -->
        <div class="stat-card bg-success">
            <div class="stat-header">Total Orders</div>
            <div class="stat-body">
                <h4>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) AS total FROM orders");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo htmlspecialchars($row['total']);
                    ?>
                </h4>
            </div>
        </div>

        <!-- Users Count -->
        <div class="stat-card bg-warning">
            <div class="stat-header">Total Users</div>
            <div class="stat-body">
                <h4>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) AS total FROM users");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo htmlspecialchars($row['total']);
                    ?>
                </h4>
            </div>
        </div>
    </div>
</div>
