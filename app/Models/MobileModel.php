<?php
require_once __DIR__ . '/../../database/db.php';

class MobileModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getProductsByIds($productIds) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $query = "SELECT p.product_id, p.product_name, p.regular_price, p.sale_price, 
                         i.image_url
                  FROM products p
                  LEFT JOIN product_images i ON p.product_id = i.product_id AND i.is_primary = 1
                  WHERE p.product_id IN ($placeholders)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($productIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
