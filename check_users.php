<?php require_once 'database/db.php'; $db=(new Database())->getConnection(); foreach($db->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC) as $r) echo $r['Field'].' '$r['Type'].PHP_EOL;
