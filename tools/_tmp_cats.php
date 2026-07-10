<?php
require dirname(__DIR__) . '/database/db.php';
$db = (new Database())->getConnection();
foreach ($db->query('SELECT category_name, slug FROM categories WHERE parent_id IS NULL ORDER BY category_name') as $r) {
    echo $r['slug'] . ' | ' . $r['category_name'] . PHP_EOL;
}
