<?php
// ProductImageSitemapModel.php
class ProductImageSitemapModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch product images with status = 1
    public function getProductImages() {
        $stmt = $this->conn->prepare("SELECT pi.image_url, pi.product_id
                                      FROM product_images pi
                                      JOIN products p ON pi.product_id = p.product_id
                                      WHERE pi.status = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
