<?php
/**
 * One-time local helper: create or reset an admin login.
 * CLI only. Delete after use if desired.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once dirname(__DIR__) . '/database/db.php';

$email = 'itsmalkii@gmail.com';
$passwordPlain = 'Malki&Aman12!';
$name = 'Malki';
$username = preg_replace('/[^a-z0-9_\.]/i', '', strstr($email, '@', true) ?: $email);
$role = 'admin';

$db = (new Database())->getConnection();
$hash = password_hash($passwordPlain, PASSWORD_DEFAULT);

$stmt = $db->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $upd = $db->prepare('UPDATE admins SET name = ?, password = ?, role = ?, username = ? WHERE id = ?');
    $upd->execute([$name, $hash, $role, $username, (int) $existing['id']]);
    $id = (int) $existing['id'];
    echo "Updated existing admin id={$id} for {$email}\n";
} else {
    $max = $db->query('SELECT MAX(id) AS m FROM admins')->fetch(PDO::FETCH_ASSOC);
    $id = ($max && $max['m']) ? (int) $max['m'] + 1 : 1;
    $ins = $db->prepare(
        'INSERT INTO admins (id, name, email, password, role, description, is_indexed, username)
         VALUES (?, ?, ?, ?, ?, NULL, 0, ?)'
    );
    $ins->execute([$id, $name, $email, $hash, $role, $username]);
    echo "Created admin id={$id} for {$email}\n";
}

$verify = $db->prepare('SELECT password FROM admins WHERE email = ? LIMIT 1');
$verify->execute([$email]);
$row = $verify->fetch(PDO::FETCH_ASSOC);
echo password_verify($passwordPlain, (string) ($row['password'] ?? '')) ? "Login verify: OK\n" : "Login verify: FAILED\n";
