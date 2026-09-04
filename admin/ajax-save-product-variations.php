<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../app/Models/VariationModel.php';
require_once __DIR__ . '/../database/db.php';

$product_id  = (int)($_POST['product_id'] ?? 0);
$product_type = trim($_POST['product_type'] ?? 'simple');
$variationsJson = $_POST['variations'] ?? '[]';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

// Update product_type on the products table
$db = (new Database())->getConnection();
$pt = in_array($product_type, ['simple','variable']) ? $product_type : 'simple';
$db->prepare("UPDATE products SET product_type=? WHERE product_id=?")->execute([$pt, $product_id]);

// If variable, save variations
if ($pt === 'variable') {
    $variations = json_decode($variationsJson, true);
    if (!is_array($variations)) {
        echo json_encode(['success' => false, 'message' => 'Invalid variation data']);
        exit();
    }
    $model = new VariationModel();
    $ok = $model->saveProductVariations($product_id, $variations);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Variations saved.' : 'Save failed.']);
} else {
    // Simple product — delete any existing variations
    $db->prepare(
        "DELETE pv FROM product_variations pv
         LEFT JOIN product_variation_values pvv ON pvv.product_variation_id = pv.id
         WHERE pv.product_id = ?"
    )->execute([$product_id]);
    echo json_encode(['success' => true, 'message' => 'Product set to simple.']);
}
