<?php
// Include the ReviewModel class
require_once __DIR__ . '/../Models/ReviewModel.php';


// ReviewController.php

class ReviewController {
    private $reviewModel;

    public function __construct() {
        // Initialize ReviewModel class
        $this->reviewModel = new ReviewModel();
    }

    // Submit Review
    public function add() {
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $review = $_POST['review'] ?? [];
        
            // Sanitize inputs from the form
            $product_id = isset($review['product_id']) ? (int) trim($review['product_id']) : null;
            $author = isset($review['author']) ? htmlspecialchars(trim($review['author'])) : 'Anonymous';
            $email = isset($review['email']) ? filter_var(trim($review['email']), FILTER_SANITIZE_EMAIL) : '';
            $content = isset($review['content']) ? htmlspecialchars(trim($review['content'])) : '';
            $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            $is_guest = $user_id ? 0 : 1;
            
            // Check if the rating exists and is valid, else set to default rating of 5
            $rating = isset($review['rating']) ? (int)$review['rating'] : 1;  // Default to 1 if not set
        
            // Ensure data is valid
            if (!$product_id || empty($content)) {
                // Flash error and redirect to product page
                flash('review_error', 'Product ID and content are required!', 'alert-danger');
                header("Location: " . URLROOT . "/products/view/" . $product_id);
                exit();
            }
    
            try {
                $data = [
                    'product_id' => $product_id,
                    'user_id' => $user_id,
                    'author' => $author,
                    'email' => $email,
                    'content' => $content,
                    'is_guest' => $is_guest,
                    'rating' => $rating  // Pass the rating to the model
                ];
    
                // Submit review
                if ($this->reviewModel->addReview($data)) {
                    flash('review_success', 'Review submitted successfully!', 'alert-success');
                    // Redirect to the "thank you" page (submit-review)
                    header("Location: " . URLROOT . "/submit-review");
                    exit();
                } else {
                    flash('review_error', 'Failed to submit review. Try again!', 'alert-danger');
                }
            } catch (Exception $e) {
                flash('review_error', 'An error occurred: ' . $e->getMessage(), 'alert-danger');
            }
    
            // Redirect back to product page in case of failure
            header("Location: " . URLROOT . "/products/view/" . $product_id);
            exit();
        } else {
            flash('review_error', 'Invalid request method!', 'alert-danger');
            header("Location: " . URLROOT . "/");
            exit();
        }
    }
    
    // ✅ Fetch approved reviews for a product
public function getApprovedByProduct($product_id) {
    return $this->reviewModel->getApprovedReviewsByProductId($product_id);
}
    
}
