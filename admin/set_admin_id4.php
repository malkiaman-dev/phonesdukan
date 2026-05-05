<?php
require_once __DIR__ . '/../database/db.php';
$database = new Database();
$conn = $database->getConnection();
$pw = 'Admin@12345';
$hash = password_hash($pw, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE admins SET email = ?, password = ?, username = ? WHERE id = ?');
$stmt->execute(['admin@phonesdukan.com', $hash, 'admin', 4]);
echo "UPDATED\n";
$stmt2 = $conn->prepare('SELECT id, email, username, password FROM admins WHERE id = 4');
$stmt2->execute();
$row = $stmt2->fetch(PDO::FETCH_ASSOC);
print_r($row);
?>