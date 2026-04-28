<?php
require_once __DIR__ . '/../Models/MobileModel.php';

class MobilePriceController {
    private $mobileModel;

    public function __construct() {
        $this->mobileModel = new MobileModel();
    }

    public function getProductById($productId) {
        $db = new Database();
        $conn = $db->getConnection();
    
        $query = "SELECT p.product_id, p.product_name, p.regular_price, p.sale_price, 
                         (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) AS image_url
                  FROM products p 
                  WHERE p.product_id = ?";
    
        $stmt = $conn->prepare($query);
        $stmt->execute([$productId]);
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
}
?>
