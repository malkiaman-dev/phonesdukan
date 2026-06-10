<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

class ProductSitemapModel {
    private $db;

    public function __construct($db) {
        if ($db === null) {
            throw new Exception('Database connection is not provided.');
        }
        $this->db = $db;
    }

    // Function to fetch product slugs with category and brand slugs, only active products
    public function getAllProductSlugs() {
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                p.product_slug, 
                c.slug AS category_slug, 
                b.slug AS brand_slug,
                sc.slug AS subcategory_slug
            FROM products p
            INNER JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN categories sc ON p.subcategory_id = sc.category_id
            INNER JOIN brands b ON p.brand_id = b.brand_id
            WHERE p.product_status = 1
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getProductImages($product_id) {
        $sql = "
            SELECT 
                image_url, 
                is_primary 
            FROM 
                product_images 
            WHERE 
                product_id = :product_id 
                AND status = 1
            ORDER BY is_primary DESC
        ";
    
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
}
?>