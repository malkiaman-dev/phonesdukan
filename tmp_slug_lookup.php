<?php
require __DIR__ . '/database/db.php';

$db = (new Database())->getConnection();
$slugs = [
    'xiaomi-poco-x6-pro',
    'samsung-galaxy-s24-fe',
    'vivo-x90-pro',
    'oppo-reno-12-pro',
    'xiaomi-13',
    'samsung-galaxy-s24',
    'xiaomi-redmi-note-13-pro',
    'vivo-v30',
    'oppo-reno-11',
];

$in = implode(',', array_fill(0, count($slugs), '?'));
$stmt = $db->prepare("SELECT product_id, product_slug, product_name FROM products WHERE product_slug IN ($in) ORDER BY product_slug");
$stmt->execute($slugs);

foreach ($stmt as $row) {
    echo $row['product_slug'] . '|' . $row['product_id'] . '|' . $row['product_name'] . PHP_EOL;
}
