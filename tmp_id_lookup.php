<?php
require __DIR__ . '/database/db.php';

$db = (new Database())->getConnection();
$ids = [14,15,17,22,44,55,57,59,60,77,141,142,157,158,163,165,166,170,171,172,175,176,180,181,182];

$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("SELECT product_id, product_slug, product_name FROM products WHERE product_id IN ($in) ORDER BY product_id");
$stmt->execute($ids);

foreach ($stmt as $row) {
    echo $row['product_id'] . '|' . $row['product_slug'] . '|' . $row['product_name'] . PHP_EOL;
}
