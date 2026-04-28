<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch existing settings
$query = "SELECT * FROM site_settings LIMIT 1";
$result = $conn->query($query);
$settings = $result ? $result->fetch(PDO::FETCH_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = $_POST['site_name'];
    $contact_email = $_POST['contact_email'];
    $footer_text = $_POST['footer_text'];

    $update_query = "UPDATE site_settings SET 
                        site_name = :site_name, 
                        contact_email = :contact_email, 
                        footer_text = :footer_text 
                    WHERE id = 1";

    $stmt = $conn->prepare($update_query);

    if ($stmt->execute([
        ':site_name' => $site_name,
        ':contact_email' => $contact_email,
        ':footer_text' => $footer_text,
    ])) {
        $message = "Settings updated successfully!";
        // Refresh settings after update
        $settings = [
            'site_name' => $site_name,
            'contact_email' => $contact_email,
            'footer_text' => $footer_text
        ];
    } else {
        $error = "Error updating settings.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Phones Dukan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: #343a40;
            color: white;
            padding: 15px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
        }
        .sidebar a:hover {
            background: #495057;
            border-radius: 5px;
        }
        /* Content Styling */
        .content {
            margin-left: 260px;
            padding: 20px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h4>Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="manage-products.php">Manage Products</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="manage-orders.php">Manage Orders</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="manage-users.php">Manage Users</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="settings.php">Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2>Site Settings</h2>
        
        <?php if(isset($message)) { echo "<div class='alert alert-success'>$message</div>"; } ?>
        <?php if(isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="site_name" class="form-label">Site Name</label>
                <input type="text" class="form-control" id="site_name" name="site_name" value="<?= $settings['site_name'] ?? '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="contact_email" class="form-label">Contact Email</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= $settings['contact_email'] ?? '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="footer_text" class="form-label">Footer Text</label>
                <textarea class="form-control" id="footer_text" name="footer_text" required><?= $settings['footer_text'] ?? '' ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Settings</button>
        </form>
    </div>

</body>
</html>
