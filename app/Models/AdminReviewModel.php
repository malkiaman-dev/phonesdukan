<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

class AdminReviewModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch all reviews with product name
    public function getAllReviews()
    {
        $query = 'SELECT r.*, p.product_name AS product_name 
                  FROM reviews r 
                  LEFT JOIN products p ON r.product_id = p.product_id 
                  ORDER BY r.created_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch a single review by ID
    public function getReviewById($id)
    {
        $query = 'SELECT * FROM reviews WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Count pending reviews
    public function getPendingReviewsCount()
    {
        $query = 'SELECT COUNT(*) as count FROM reviews WHERE status = :status';
        $stmt = $this->db->prepare($query);
        $status = 'pending';
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function updateReviewStatus($id, $status)
    {
        $query = 'UPDATE reviews SET status = :status WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Update review details
    public function updateReview($id, $author, $email, $content, $rating)
    {
        $query = 'UPDATE reviews SET author = :author, email = :email, content = :content, rating = :rating WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Delete a review by ID
    public function deleteReview($id)
    {
        $query = 'DELETE FROM reviews WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>