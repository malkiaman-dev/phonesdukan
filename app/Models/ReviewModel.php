<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct database connection

class ReviewModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch reviews by product_id
    public function getApprovedReviewsByProductId($product_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE product_id = :product_id AND status = 'approved' ORDER BY created_at DESC");
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    // Insert new review
    public function addReview($data) {
        try {
            // Ensure the rating value is valid (1-5)
            $rating = isset($data['rating']) && $data['rating'] >= 1 && $data['rating'] <= 5 ? $data['rating'] : 1;
    
            $sql = "INSERT INTO reviews (product_id, user_id, is_guest, author, email, content, rating, status, created_at) 
                    VALUES (:product_id, :user_id, :is_guest, :author, :email, :content, :rating, 'approved', NOW())";
            $stmt = $this->db->prepare($sql);
    
            // Bind parameters
            $stmt->bindParam(":product_id", $data['product_id'], PDO::PARAM_INT);
            $stmt->bindValue(":user_id", $data['user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindParam(":is_guest", $data['is_guest'], PDO::PARAM_BOOL);
            $stmt->bindParam(":author", $data['author'], PDO::PARAM_STR);
            $stmt->bindParam(":email", $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(":content", $data['content'], PDO::PARAM_STR);
            $stmt->bindParam(":rating", $rating, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("❌ Error adding review: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProductNameById($product_id) {
        $sql = "SELECT product_name FROM products WHERE product_id = :product_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ? $product['product_name'] : null;
    }
    
}    
?>
