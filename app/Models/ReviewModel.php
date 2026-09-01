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
            $rating = isset($data['rating']) && $data['rating'] >= 1 && $data['rating'] <= 5 ? (int) $data['rating'] : 1;
            $productId = (int) ($data['product_id'] ?? 0);
            $content = trim((string) ($data['content'] ?? ''));
            $author = trim((string) ($data['author'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $isGuest = !empty($data['is_guest']) ? 1 : 0;
            $userId = isset($data['user_id']) && $data['user_id'] !== '' && $data['user_id'] !== null
                ? (int) $data['user_id']
                : null;

            if ($productId <= 0 || $content === '' || $author === '') {
                return false;
            }

            $sql = "INSERT INTO reviews (product_id, user_id, is_guest, author, email, content, rating, status, created_at) 
                    VALUES (:product_id, :user_id, :is_guest, :author, :email, :content, :rating, 'approved', NOW())";
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            if ($userId === null) {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':is_guest', $isGuest, PDO::PARAM_INT);
            $stmt->bindValue(':author', $author, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':content', $content, PDO::PARAM_STR);
            $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);

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
