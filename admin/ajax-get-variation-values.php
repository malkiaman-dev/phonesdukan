<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../app/Models/VariationModel.php';

$type_id = (int)($_GET['type_id'] ?? 0);
if (!$type_id) {
    echo json_encode([]);
    exit();
}

$model  = new VariationModel();
$values = $model->getValuesByTypeId($type_id);
echo json_encode($values);
