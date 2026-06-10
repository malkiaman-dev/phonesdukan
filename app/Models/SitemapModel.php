<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

class SitemapModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getProducts() {
        $stmt = $this->conn->query(
            "SELECT product_slug AS slug, updated_at
             FROM products
             WHERE product_status = 1"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPages() {
        try {
            $check = $this->conn->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pages'"
            );
            if ((int) $check->fetchColumn() === 0) {
                return [];
            }

            $stmt = $this->conn->query("SELECT slug, updated_at FROM pages WHERE status = 1");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('SitemapModel getPages: ' . $e->getMessage());
            return [];
        }
    }
    
    // Get images from product_images table with status = 1
    public function getProductImages() {
        $stmt = $this->conn->query("SELECT image_url FROM product_images WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
