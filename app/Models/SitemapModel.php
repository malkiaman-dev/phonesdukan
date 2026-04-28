<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

class SitemapModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getProducts() {
        $stmt = $this->conn->query("SELECT slug, updated_at FROM products WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPages() {
        $stmt = $this->conn->query("SELECT slug, updated_at FROM pages WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get images from product_images table with status = 1
    public function getProductImages() {
        $stmt = $this->conn->query("SELECT image_url FROM product_images WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
