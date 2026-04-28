<?php
require_once __DIR__ . '/../database/db.php';

// Admin Credentials
$admin_email = "admin@phonesdukan.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Securely hash password

// Insert Query
$query = "INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)";

try {
    $stmt = $conn->prepare($query);
    $created = $stmt->execute([
        ':name' => 'Admin',
        ':email' => $admin_email,
        ':password' => $admin_password,
    ]);

    echo $created ? "Admin created successfully!" : "Error creating admin.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
