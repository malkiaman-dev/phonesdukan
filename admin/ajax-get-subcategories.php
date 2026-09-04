<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../app/Models/CatalogModel.php';

$parentId = (int) ($_GET['category_id'] ?? $_GET['parent_id'] ?? 0);
if ($parentId <= 0) {
    echo json_encode(['subcategories' => []]);
    exit;
}

$model = new CatalogModel();
$subs = $model->getSubcategories($parentId);

echo json_encode([
    'subcategories' => array_map(static function (array $row): array {
        return [
            'category_id' => (int) $row['category_id'],
            'category_name' => $row['category_name'],
            'slug' => $row['slug'],
        ];
    }, $subs),
]);
