<?php
/**
 * Admin AJAX: search products for group-product picker.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/app/Models/ProductGroupModel.php';

$query = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$excludeId = (int) ($_GET['exclude'] ?? $_POST['exclude'] ?? 0);

if (mb_strlen($query) < 1) {
    echo json_encode(['status' => 'success', 'products' => []]);
    exit;
}

try {
    $model = new ProductGroupModel();
    $products = $model->searchProducts($query, $excludeId, 25);
    echo json_encode(['status' => 'success', 'products' => $products]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Search failed']);
}
