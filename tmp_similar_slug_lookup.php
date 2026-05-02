<?php
require __DIR__ . '/database/db.php';

$db = (new Database())->getConnection();
$patterns = [
    'poco-x6',
    'poco',
    's24',
    'samsung-galaxy-s24',
    'x90',
    'vivo-x90',
    'reno-12',
    'reno12',
    'xiaomi-13',
    'xiaomi 13',
    'redmi-note-13-pro',
    'redmi note 13 pro',
    'v30',
    'vivo v30',
    'reno-11',
    'reno11',
    'v40e',
    'vivo v40e',
    't3-pro',
    'vivo t3 pro',
    'realme-13',
    'realme 13',
    'a55-5g',
    'a55 5g',
    'y28',
    'vivo y28',
    'y19s',
    'vivo y19s',
    'redmi-14c',
    'redmi 14c',
    'note-50',
    'note 50',
];

$conditions = [];
$params = [];
foreach ($patterns as $p) {
    $conditions[] = '(product_slug LIKE ? OR product_name LIKE ?)';
    $params[] = '%' . $p . '%';
    $params[] = '%' . $p . '%';
}

$sql = "SELECT product_id, product_slug, product_name FROM products WHERE " . implode(' OR ', $conditions) . " ORDER BY product_id";
$stmt = $db->prepare($sql);
$stmt->execute($params);

foreach ($stmt as $row) {
    echo $row['product_id'] . '|' . $row['product_slug'] . '|' . $row['product_name'] . PHP_EOL;
}
