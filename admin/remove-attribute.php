<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/EditProductModel.php';

$productId = $_POST['product_id'] ?? null;
$attributeId = $_POST['attribute_id'] ?? null;
$valueId = $_POST['value_id'] ?? null;

error_log("Received in remove-attribute.php: product_id=$productId, attribute_id=$attributeId, value_id=$valueId");

if (!$productId || !is_numeric($productId) || !$attributeId || !is_numeric($attributeId) || !$valueId || !is_numeric($valueId)) {
    http_response_code(400);
    error_log("Invalid input: product_id=$productId, attribute_id=$attributeId, value_id=$valueId");
    echo json_encode(['error' => 'Invalid product_id, attribute_id, or value_id']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Log the state before deletion
    $stmt = $db->prepare('SELECT * FROM product_attribute_prices WHERE product_id = ?');
    $stmt->execute([$productId]);
    $before = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Attributes before deletion for product_id=$productId: " . print_r($before, true));
    
    // Delete the specific attribute value
    $stmt = $db->prepare('DELETE FROM product_attribute_prices WHERE product_id = ? AND attribute_id = ? AND value_id = ?');
    $success = $stmt->execute([$productId, $attributeId, $valueId]);
    
    error_log("DELETE query executed: product_id=$productId, attribute_id=$attributeId, value_id=$valueId, success=" . ($success ? 'true' : 'false'));
    
    if (!$success) {
        error_log("Error removing attribute value: " . print_r($stmt->errorInfo(), true));
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove attribute value']);
        exit;
    }
    
    // Log the state after deletion
    $stmt = $db->prepare('SELECT * FROM product_attribute_prices WHERE product_id = ?');
    $stmt->execute([$productId]);
    $after = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Attributes after deletion for product_id=$productId: " . print_r($after, true));
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Exception in remove-attribute.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit;