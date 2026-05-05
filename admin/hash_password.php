<?php
// Usage:
// 1) In browser: /admin/hash_password.php?pw=YourPassword&username=yourusername
// 2) Copy the printed HASH and SQL, then run the SQL in your database (phpMyAdmin or mysql)

$pw = isset($_GET['pw']) ? $_GET['pw'] : 'azmeryal';
$username = isset($_GET['username']) ? $_GET['username'] : 'admin';
$hash = password_hash($pw, PASSWORD_DEFAULT);

header('Content-Type: text/plain; charset=utf-8');
echo "HASH: $hash\n";
echo "\nSQL (copy & run in your DB):\n";
echo "UPDATE `admins` SET `password` = '" . $hash . "', `username` = '" . addslashes($username) . "' WHERE `id` = 1;\n";
echo "\n(Replace WHERE clause to target a different admin ID if needed.)\n";
?>
