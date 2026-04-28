<?php
ob_start();

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if the user is logged in by checking the session variable
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare('SELECT full_name FROM users WHERE user_id = :user_id');
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'User';

// Fetch stats for orders and amount spent
$orders_stmt = $conn->prepare('SELECT COUNT(*) AS total, SUM(total_price) AS total_spent FROM orders WHERE user_id = :user_id');
$orders_stmt->bindParam(':user_id', $user_id);
$orders_stmt->execute();
$orders_stats = $orders_stmt->fetch(PDO::FETCH_ASSOC);

$total_orders = $orders_stats['total'] ?? 0;
$total_spent = $orders_stats['total_spent'] ?? 0;

// Fetch profile data
$profile_stmt = $conn->prepare('SELECT full_name, email, phone FROM users WHERE user_id = :user_id');
$profile_stmt->bindParam(':user_id', $user_id);
$profile_stmt->execute();
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
$profile_complete = ($profile['full_name'] && $profile['email'] && $profile['phone']) ? '100%' : 'Incomplete';

// Prepare orders array for user_orders.php
$orders = [];
$orders_data_stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = :user_id');
$orders_data_stmt->bindParam(':user_id', $user_id);
$orders_data_stmt->execute();
$orders = $orders_data_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
</head>
<style>
    /* Main container for sidebar and content */
    .user_container {
        display: flex;
        flex-wrap: nowrap;
    }

    /* Sidebar Styling */
    .user_sidebar {
        background-color: #2c3e50;
        color: white;
        padding: 20px;
    }

    /* Sidebar Links Styling */
    .user_sidebar a {
        display: block;
        color: white;
        text-decoration: none;
        margin: 10px 0;
        padding: 10px;
        background: #34495e;
        border-radius: 5px;
    }

    .user_sidebar a:hover {
        background: #1abc9c;
    }

    /* Content Area Styling */
    .user_content {
        margin-left: 20px;
        padding: 30px;
        background-color: #f9f9f9;
        flex-grow: 1;
        min-height: 100vh;
    }

    /* Dashboard Stats Section */
    .dashboard-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    /* Stat Cards Styling */
    .stat-card {
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 0 10px #ccc;
        flex: 1;
    }

    .dashboard-stats .stat-card:nth-child(1) {
    background: linear-gradient(135deg, #007bff, #0056b3); /* Blue */
}

.dashboard-stats .stat-card:nth-child(2) {
    background: linear-gradient(135deg, #28a745, #1e7e34); /* Green */
}

.dashboard-stats .stat-card:nth-child(3) {
    background: linear-gradient(135deg, #ffc107, #d39e00); /* Yellow */
}
    .stat-card h4 {
        margin-top: 0;
        font-size: 24px;
        color: white;
    }

    .username {
        color: #fff;
    }
    /* Responsive styles */
@media screen and (max-width: 768px) {
    .user_container {
        flex-direction: column;
    }

    .user_sidebar {
        width: 100%;
        text-align: center;
        height: auto;
        padding: 10px;
    }

    .user_wrapper {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.user_wrapper a {
    margin: 0px;
}


    .stat-card {
        flex: 1 1 100%;
    }
    .user_content {
    background-color: #ffffff;
    margin: 0px;
    padding: 10px;
    min-height: fit-content;
}

    .user_content h2 {
        font-size: 20px;
        margin-bottom: 20px;
        text-align: center;
        color: #333;
    }

    .dashboard-stats {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    gap: 10px;
}

    .stat-card {
        flex: 1;
        background: #ffffff;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 120px;
        text-align: center;
    }


    .stat-card h4 {
        font-size: 14px;
        color: white;
        margin-bottom: 6px;
    }

    .stat-card p {
        font-size: 16px;
        font-weight: bold;
        color: #0073e6;
        margin: 0;
    }

    .user_content p {
        margin-top: 25px;
        font-size: 15px;
        color: white;
        text-align: center;
    } 
    }
    p.user-info-text {
    color: #232721;
}
.user_wrapper a:last-child {
  color: #fff;
  background-color: #dc3545; /* Bootstrap-style red */
  padding: 8px 16px;
  border-radius: 4px;
  display: inline-block;
  text-align: center;
  transition: background-color 0.3s ease;
}

.user_wrapper a:last-child:hover {
  background-color: #c82333; /* Darker red on hover */
}

</style>
<body>

    <!-- Main container for sidebar and content -->
    <div class="user_container">

        <!-- Sidebar -->
        <div class="user_sidebar">
            <h2 class="username">Hello, <?= $user_name ?></h2>
            <div class="user_wrapper">
            <a href="?tab=orders">Orders</a>
            <a href="?tab=profile">Edit Profile</a>
            <a href="?tab=security">Security</a>
            <a href="/logout">Logout</a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="user_content">
            <h2>Welcome <?= $user_name ?> to Your Dashboard</h2>

            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <!-- Total Orders -->
                <div class="stat-card">
                    <h4>Total Orders</h4>
                    <p><?= $total_orders ?></p>
                </div>

                <!-- Total Spent -->
                <div class="stat-card">
                    <h4>Total Spent</h4>
                    <p><?= $total_spent ?> PKR</p>
                </div>

                <!-- Profile Completion -->
                <div class="stat-card">
                    <h4>Profile Completion</h4>
                    <p><?= $profile_complete ?></p>
                </div>
            </div>

            <!-- Dynamic Content Section -->
            <?php
            $tab = $_GET['tab'] ?? 'default';  // Default to 'default' if no tab is selected

            // Display different content based on the tab
            if ($tab == 'orders') {
                include 'user_orders.php';  // Include the orders content
            } elseif ($tab == 'profile') {
                include 'user_profile.php';  // Include the profile content
            } elseif ($tab == 'security') {
                include 'user_security.php';  // Include the security content
            } else {
                echo '<p class="user-info-text">Use the menu to manage your orders, profile, security, or log out.</p>';
            }
            ?>
        </div>
    </div>

</body>
</html>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>