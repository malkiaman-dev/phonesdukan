<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Redirect to login if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include DB connection
require_once __DIR__ . '/../database/db.php';
$database = new Database();
$conn = $database->getConnection();

// Safely get POST data
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : null;
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : null;

if (!$target_id || !$new_password) {
    die("Missing target ID or password.");
}

// Get current user's role
$logged_in_id = $_SESSION['admin_id'];
$logged_in_role = $_SESSION['admin_role'];

// Fetch the target user's role from DB
$stmt = $conn->prepare("SELECT role FROM admins WHERE id = ?");
$stmt->execute([$target_id]);
$target_role = $stmt->fetchColumn();

if (!$target_role) {
    die("Target admin not found.");
}

include __DIR__ . '/admin_header.php';

// Include the sidebar
include __DIR__ . '/admin_sidebar.php';

// Restrict admins from changing superadmin password
if ($logged_in_role !== 'superadmin' && $target_role === 'superadmin') {
    die("You are not allowed to change the Super Admin's password.");
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update the password
$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
$stmt->execute([$hashed_password, $target_id]);

if ($stmt->rowCount() === 0) {
    die("Password update query ran but no rows were affected. Possibly invalid admin ID or same password.");
}

echo "Password updated successfully.";
?>
