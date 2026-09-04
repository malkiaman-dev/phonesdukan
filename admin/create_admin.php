<?php
require_once __DIR__ . '/../database/db.php';

// CONFIGURE: the new admin credentials
$email = 'itsmalkii@gmail.com';
$password_plain = 'Malki&Aman12!';
$name = 'Malki';
$username = preg_replace('/[^a-z0-9_\.]/i', '', strstr($email, '@', true) ?: $email);
$role = 'admin';

$database = new Database();
$conn = $database->getConnection();

// compute next id (table may not use AUTO_INCREMENT)
$max = $conn->query('SELECT MAX(id) AS m FROM admins')->fetch(PDO::FETCH_ASSOC);
$next_id = ($max && $max['m']) ? intval($max['m']) + 1 : 1;

$hash = password_hash($password_plain, PASSWORD_DEFAULT);

// avoid duplicate email
$stmt = $conn->prepare('SELECT id FROM admins WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "ERROR: an admin with that email already exists.\n";
    exit(1);
}

$insert = $conn->prepare('INSERT INTO admins (id, name, email, password, role, description, is_indexed, username) VALUES (?, ?, ?, ?, ?, NULL, 0, ?)');
$success = $insert->execute([$next_id, $name, $email, $hash, $role, $username]);
if (!$success) {
    echo "INSERT failed\n";
    exit(1);
}

echo "CREATED admin id=$next_id\n";
echo "email=$email\n";
echo "username=$username\n";
echo "password_plain=$password_plain\n";
echo "stored_hash=$hash\n";

// verify
$checkStmt = $conn->prepare('SELECT password FROM admins WHERE id = ?');
$checkStmt->execute([$next_id]);
$r = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($r && password_verify($password_plain, $r['password'])) {
    echo "PW_MATCH\n";
} else {
    echo "PW_MISMATCH\n";
}
?>