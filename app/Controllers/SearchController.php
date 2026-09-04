<?php
require_once __DIR__ . '/../Models/ProductModel.php';

class SearchController {
    private $productModel;

    public function __construct() {
        $this->productModel = new ProductModel();
    }

    public function search() {
        if (!isset($_GET['query'])) {
            echo json_encode([]); // Return empty array if no query
            return;
        }

        $searchQuery = trim($_GET['query']); // Remove extra spaces

        if (strlen($searchQuery) < 2) {
            echo json_encode([]); // Return empty array for very short queries
            return;
        }

        // Fetch results from the model (prepared statements handle SQL safety)
        $results = $this->productModel->searchProducts($searchQuery);

        // Return JSON response
        echo json_encode($results);
    }
}

// Instantiate and execute search
$searchController = new SearchController();
$searchController->search();
