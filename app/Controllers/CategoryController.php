<?php

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once __DIR__ . '/../Models/CatalogModel.php';
require_once __DIR__ . '/../Models/ProductModel.php';

class CategoryController
{
    public function showCategory(string $category_slug): void
    {
        $view_path = __DIR__ . '/../Views/' . $category_slug . '.php';

        if (file_exists($view_path)) {
            include $view_path;
            return;
        }

        $catalogModel = new CatalogModel();
        $category = $catalogModel->getActiveParentCategoryBySlug($category_slug);

        if (!$category) {
            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        $productModel = new ProductModel();
        $limit = 16;
        $paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
        $paged = $paged > 0 ? $paged : 1;
        $offset = ($paged - 1) * $limit;

        $totalRows = $productModel->countListingProductsForCategory((int) $category['category_id']);
        $rawProducts = $productModel->getListingProductsForCategory((int) $category['category_id'], $limit, $offset);

        $products = [];
        foreach ($rawProducts as $row) {
            $products[] = prepareProductCardFromRow($row);
        }

        $totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 0;

        include __DIR__ . '/../Views/category-dynamic.php';
    }
}
