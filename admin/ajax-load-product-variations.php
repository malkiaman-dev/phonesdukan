<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../app/Models/VariationModel.php';
require_once __DIR__ . '/../database/db.php';

$product_id = (int)($_GET['product_id'] ?? 0);
if (!$product_id) {
    echo json_encode(['product_type' => 'simple', 'variations' => []]);
    exit();
}

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT product_type FROM products WHERE product_id=?");
$stmt->execute([$product_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$product_type = $row['product_type'] ?? 'simple';

$model      = new VariationModel();
$variations = $model->getProductVariationsForFrontend($product_id);
$types      = $model->getVariationTypesWithValues();

echo json_encode([
    'product_type' => $product_type,
    'variations'   => $variations,
    'types'        => $types,
]);
