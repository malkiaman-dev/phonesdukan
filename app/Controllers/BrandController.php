<?php

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once __DIR__ . '/../Models/CatalogModel.php';
require_once __DIR__ . '/../Models/ProductModel.php';

class BrandController
{
    public function showBrand(string $segmentA, string $segmentB): void
    {
        $catalogModel = new CatalogModel();
        $category = $catalogModel->getActiveParentCategoryBySlug($segmentA);
        $brand = $catalogModel->getBrandBySlug($segmentB);

        if (!$category || !$brand) {
            $legacyCategory = $catalogModel->getActiveParentCategoryBySlug($segmentB);
            $legacyBrand = $catalogModel->getBrandBySlug($segmentA);

            if ($legacyCategory && $legacyBrand) {
                header(
                    'Location: ' . url($legacyCategory['slug'] . '/' . $legacyBrand['slug']),
                    true,
                    301
                );
                exit();
            }

            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        $productModel = new ProductModel();
        $brandId = (int) $brand['brand_id'];
        $categoryId = (int) $category['category_id'];

        $limit = 16;
        $paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
        $paged = $paged > 0 ? $paged : 1;
        $offset = ($paged - 1) * $limit;

        $totalRows = $productModel->countListingProductsForBrandAndCategory($brandId, $categoryId);
        $categoryBrands = $catalogModel->getBrandsWithProductsInCategory($categoryId);

        if ($totalRows === 0) {
            header('Location: ' . url($category['slug']), true, 301);
            exit();
        }

        $totalPages = (int) ceil($totalRows / $limit);
        seoEnforceListingPagination($paged, $totalPages);
        $offset = ($paged - 1) * $limit;

        $rawProducts = $productModel->getListingProductsForBrandAndCategory($brandId, $categoryId, $limit, $offset);

        $products = [];
        foreach ($rawProducts as $row) {
            $products[] = prepareProductCardFromRow($row);
        }

        $listingPath = rawurlencode((string) $category['slug']) . '/' . rawurlencode((string) $brand['slug']);

        include __DIR__ . '/../Views/brand-dynamic.php';
    }
}
