<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
require_once __DIR__ . '/../database/db.php';

// Create a new Database instance and get the connection
$database = new Database();
$conn = $database->getConnection(); // Ensure the connection is assigned to $conn

// Check if the connection was established
if (!$conn) {
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the POST data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Ensure all required fields are present
    if (!isset($name, $email, $password, $role)) {
        echo "Invalid request.";
        exit;
    }

    // Prepare the SQL insert query
    $query = "INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    // Execute the query and check for success
    if ($stmt->execute([$name, $email, $password, $role])) {
        // Redirect or give feedback on success
        echo "Admin created successfully!";
        // header('Location: dashboard.php'); // Optionally redirect after success
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
} else {
    echo "Invalid request.";
}
?>
