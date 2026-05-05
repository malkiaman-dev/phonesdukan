<?php
require_once __DIR__ . '/../database/db.php';
$db=new Database();
$c=$db->getConnection();
$stmt=$c->prepare('SELECT id,email,password FROM admins WHERE email = ?');
$stmt->execute(['admin@phonesdukan.com']);
$r=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$r) {
    echo "NO USER\n";
    exit;
}
$check = password_verify('Admin@12345', $r['password']);
echo "id={$r['id']} email={$r['email']}\n";
echo $check ? "PW_MATCH\n" : "PW_MISMATCH\n";
?>