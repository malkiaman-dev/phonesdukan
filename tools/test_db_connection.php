<?php
require_once dirname(__DIR__) . '/database/db.php';
$db = (new Database())->getConnection();
$count = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
echo "OK connected. products={$count}\n";
