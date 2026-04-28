<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Controllers/ReviewController.php';

if (!class_exists('ReviewController')) {
    die("❌ ERROR: ReviewController class NOT FOUND in " . __DIR__ . '/../app/Controllers/ReviewController.php');
}

$reviewController = new ReviewController();


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($review_id > 0 && !empty($status)) {
        error_log("Updating review: ID = $review_id, Status = $status"); // Debugging ✅
        
        $success = $reviewController->updateReviewStatus($review_id, $status);

        if ($success) {
            echo "success";
        } else {
            error_log("Database update failed ❌");
            echo "error";
        }
    } else {
        error_log("Invalid input: review_id=$review_id, status=$status ❌");
        echo "invalid";
    }
} else {
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"] . " ❌");
    echo "invalid_request";
}

?>
