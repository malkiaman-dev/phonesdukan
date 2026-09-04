<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/admin_header.php';

// Include the sidebar
include __DIR__ . '/admin_sidebar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Admin</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <!-- Adjust the path -->
</head>
<body>
    <div class="content-wrapper">
        <div class="admin-container">
        <h2>Add New Admin</h2>
        <form action="insert_admin.php" method="POST">
            <div class="form-group">
            <label>Name:</label><br>
            <input type="text" name="name" required><br><br>
            </div>
            <div class="form-group">
            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>
</div>
<div class="form-group">

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>
</div>
<div class="form-group">
            <label>Role:</label><br>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="superadmin">Super Admin</option>
            </select><br><br>
</div>
            <button type="submit">Create Admin</button>
        </form>
        </div>
    </div>
</body>
</html>
