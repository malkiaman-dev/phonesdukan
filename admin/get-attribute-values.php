<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/EditProductModel.php';

$attributeId = $_GET['attribute_id'] ?? null;

if (!$attributeId) {
    http_response_code(400);
    echo json_encode(['error' => 'No attribute_id provided']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $model = new ProductModel($db);
    $values = $model->getAttributeValues($attributeId);
    echo json_encode($values);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit;