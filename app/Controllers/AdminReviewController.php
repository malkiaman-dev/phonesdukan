<?php
require_once dirname(__DIR__, 2) . '/app/Models/AdminReviewModel.php';

class AdminReviewController
{
    private $model;

    public function __construct()
    {
        $this->model = new AdminReviewModel();
    }

    // Fetch all reviews and return data
    public function getAllReviews()
    {
        return $this->model->getAllReviews();
    }

    // Update the review status
    public function updateStatus($id, $status)
    {
        return $this->model->updateReviewStatus($id, $status);
    }

    // Get single review by ID
    public function getReviewById($id)
    {
        return $this->model->getReviewById($id);
    }

    // Update review details
    public function updateReview($id, $author, $email, $content, $rating)
    {
        return $this->model->updateReview($id, $author, $email, $content, $rating);
    }

    // Delete a review
    public function deleteReview($id)
    {
        return $this->model->deleteReview($id);
    }
}
?>