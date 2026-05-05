<?php
require_once __DIR__ . '/../database/db.php';
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare('SELECT id, email, username, password FROM admins');
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($rows && count($rows) > 0) {
    foreach ($rows as $row) {
        echo "id=" . $row['id'] . "\n";
        echo "email=" . $row['email'] . "\n";
        echo "username=" . ($row['username'] ?? 'NULL') . "\n";
        echo "password=" . $row['password'] . "\n";
        echo "---\n";
    }
} else {
    echo "NO ROWS\n";
}
?>