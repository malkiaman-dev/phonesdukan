<?php
require __DIR__ . '/database/db.php';

$db = (new Database())->getConnection();
$slugToId = [];
foreach ($db->query('SELECT product_slug, product_id FROM products') as $row) {
    $slugToId[$row['product_slug']] = (int) $row['product_id'];
}

$files = glob(__DIR__ . '/app/Views/mobiles-price-list/best-mobiles-under-*.php');
$total = 0;
$mismatch = 0;
$missing = 0;

foreach ($files as $file) {
    $html = file_get_contents($file);
    preg_match_all(
        '/<a href="\/mobiles\/[^"]+\/([^"\/]+)\/"[^>]*>.*?<button class="buy-button"\s+data-product-id="(\d+)"/si',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $m) {
        $slug = $m[1];
        $id = (int) $m[2];
        $total++;

        if (!isset($slugToId[$slug])) {
            $missing++;
            echo 'MISSING|' . basename($file) . '|' . $slug . '|' . $id . PHP_EOL;
            continue;
        }

        if ($slugToId[$slug] !== $id) {
            $mismatch++;
            echo 'MISMATCH|' . basename($file) . '|' . $slug . '|current=' . $id . '|expected=' . $slugToId[$slug] . PHP_EOL;
        }
    }
}

echo 'SUMMARY|total=' . $total . '|mismatch=' . $mismatch . '|missing=' . $missing . PHP_EOL;
