<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/app/Models/CatalogModel.php';
require_once dirname(__DIR__) . '/app/Models/ProductModel.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $ok): void
{
    global $passed, $failed;
    if ($ok) {
        echo "[PASS] $label\n";
        $passed++;
    } else {
        echo "[FAIL] $label\n";
        $failed++;
    }
}

try {
    $db = (new Database())->getConnection();
    check('DB connection', $db instanceof PDO);

    $catalog = new CatalogModel();
    check('CatalogModel ensureSchema', true);

    $cols = $db->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->fetch();
    check('categories.parent_id exists', !empty($cols));

    $cols = $db->query("SHOW COLUMNS FROM products LIKE 'subcategory_id'")->fetch();
    check('products.subcategory_id exists', !empty($cols));

    $path3 = buildProductPath('login', 'bluetooth-speakers', 'login-l-274-vybeon');
    check('3-segment path order', $path3 === '/login/bluetooth-speakers/login-l-274-vybeon');

    $path4 = buildProductPath('login', 'bluetooth-speakers', 'login-l-274-vybeon', 'portable-speakers');
    check('4-segment path includes subcategory', $path4 === '/login/bluetooth-speakers/portable-speakers/login-l-274-vybeon');

    $row = [
        'brand_slug' => 'samsung',
        'category_slug' => 'mobiles',
        'subcategory_slug' => 'flagship',
        'product_slug' => 'galaxy-s24',
    ];
    check('buildProductPathFromRow', buildProductPathFromRow($row) === '/samsung/mobiles/flagship/galaxy-s24');

    $pm = new ProductModel();
    check('ProductModel getProductByLegacyPermalink method', method_exists($pm, 'getProductByLegacyPermalink'));
    check('ProductModel getProductByPermalinkNoSub method', method_exists($pm, 'getProductByPermalinkNoSub'));

    echo "\nResult: $passed passed, $failed failed\n";
    exit($failed > 0 ? 1 : 0);
} catch (Throwable $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . "\n";
    exit(1);
}
