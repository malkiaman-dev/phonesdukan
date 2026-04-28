<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

class CategorySitemapModel {
    private $db;

    public function __construct($db) {
        if ($db === null) {
            throw new Exception('Database connection is not provided.');
        }
        $this->db = $db;
    }

    // Function to fetch all category slugs, only active categories
    public function getAllCategorySlugs() {
        // SQL to fetch category slugs, filtering by category status = 1
        $query = "
            SELECT c.slug AS category_slug
            FROM categories c
            WHERE c.status = 1  -- Only include active categories (status = 1)
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(); // Execute the query

        // Fetch all the results as an associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
