<?php
/**
 * Product Media – Database Setup Script
 * Run once: http://localhost/phonesdukan/admin/setup-product-media-db.php
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (PHP_SAPI !== 'cli') {
        die('Unauthorized. Please login as admin first.');
    }
}

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/app/Models/ProductMediaModel.php';

$database = new Database();
$conn = $database->getConnection();
$model = new ProductMediaModel($conn);

try {
    $model->ensureSchema();
    $message = 'Product media tables are ready.';
    $ok = true;
} catch (Exception $e) {
    $message = 'Setup failed: ' . $e->getMessage();
    $ok = false;
}

if (PHP_SAPI === 'cli') {
    echo ($ok ? 'OK: ' : 'ERROR: ') . $message . PHP_EOL;
    exit($ok ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Media DB Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; padding: 40px; color: #111; }
        .box { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
        .ok { color: #16a34a; }
        .err { color: #ef4444; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Product Media Setup</h1>
        <p class="<?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></p>
        <p><a href="add-product.php">Go to Add Product</a></p>
    </div>
</body>
</html>
