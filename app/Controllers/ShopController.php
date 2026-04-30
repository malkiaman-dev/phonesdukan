<?php
require_once __DIR__ . '/../Models/ShopModel.php'; // ShopModel handles both products and categories

$productModel = new ProductModel(); // Assuming ShopModel is handling both products and categories

$limit = 12; // Products per page
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

// Collect filters from GET parameters
$filters = [
    'sort_by' => $_GET['sort_by'] ?? null,         // Sorting options (low to high, high to low)
    'price_range' => $_GET['price_range'] ?? [],   // Price range filter
    'min_price' => $_GET['min_price'] ?? null,     // Min price (slider)
    'max_price' => $_GET['max_price'] ?? null,     // Max price (slider)
    'category' => $_GET['category'] ?? [],         // Selected categories
    'brand' => $_GET['brand'] ?? []                // Selected brands
];

// Fetch filtered & paginated products based on the filters
$products = $productModel->getPaginatedProducts($limit, $offset, $filters);

// Get total product count (with filters applied)
$total_rows = $productModel->getTotalFilteredProductCount($filters);
$total_pages = ceil($total_rows / $limit);

// Fetch all active categories and brands for the filter dropdowns
$categories = $productModel->getAllCategories();
$brands = $productModel->getAllBrands();

// Load the view with the filtered products, categories, and brands
require_once __DIR__ . '/../Views/shop/index.php';
?>
