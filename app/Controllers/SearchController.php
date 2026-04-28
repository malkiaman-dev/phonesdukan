<?php
require_once __DIR__ . '/../Models/ProductModel.php';
require_once __DIR__ . '/../../database/db.php';

class SearchController {
    private $productModel;

    public function __construct() {
        $database = new Database(); // Initialize Database class
        $this->productModel = new ProductModel($database->getConnection()); // Pass connection
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

        // Prevent XSS while allowing valid search characters
        $searchQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');

        // Fetch results from the model
        $results = $this->productModel->searchProducts($searchQuery);

        // Return JSON response
        echo json_encode($results);
    }
}

// Instantiate and execute search
$searchController = new SearchController();
$searchController->search();
