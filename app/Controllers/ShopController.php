<?php
require_once __DIR__ . '/../Models/ShopModel.php'; // ShopModel handles both products and categories

$productModel = new ProductModel(); // Assuming ShopModel is handling both products and categories

$limit = 12; // Products per page
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$paged = $paged > 0 ? $paged : 1;
$offset = ($paged - 1) * $limit;

// Collect filters from GET parameters
$rawCategories = $_GET['category'] ?? [];
if (!is_array($rawCategories)) {
    $rawCategories = array_filter(explode(',', (string) $rawCategories));
}

$filters = [
    'sort_by' => $_GET['sort_by'] ?? null,         // Sorting options (low to high, high to low)
    'price_range' => $_GET['price_range'] ?? [],   // Price range filter
    'min_price' => $_GET['min_price'] ?? null,     // Min price (slider)
    'max_price' => $_GET['max_price'] ?? null,     // Max price (slider)
    'category' => $productModel->normalizeCategoryFilterIds($rawCategories),
    'brand' => $_GET['brand'] ?? []                // Selected brands
];

$selectedCategoryIds = array_map('strval', $filters['category']);

// Get total product count (with filters applied)
$total_rows = $productModel->getTotalFilteredProductCount($filters);
$total_pages = $total_rows > 0 ? (int) ceil($total_rows / $limit) : 0;

if (function_exists('seoEnforceListingPagination')) {
    seoEnforceListingPagination($paged, $total_pages);
}
$offset = ($paged - 1) * $limit;

// Fetch filtered & paginated products based on the filters
$products = $productModel->getPaginatedProducts($limit, $offset, $filters);

// Fetch all active categories and brands for the filter dropdowns
$categories = $productModel->getAllCategories();
$brands = $productModel->getAllBrands();

// Load the view with the filtered products, categories, and brands
require_once __DIR__ . '/../Views/shop/index.php';
?>
