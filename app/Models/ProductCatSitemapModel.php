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

    // Function to fetch all category slugs that have at least one active product
    public function getAllCategorySlugs() {
        $query = "
            SELECT c.slug AS category_slug
            FROM categories c
            WHERE c.status = 1
              AND EXISTS (
                  SELECT 1
                  FROM products p
                  WHERE p.category_id = c.category_id
                    AND p.product_status = 1
              )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
