<?php
// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Include the AdminReviewController
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminReviewController.php';

// Initialize the controller
$controller = new AdminReviewController();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $valid_statuses = ['pending', 'approved', 'spam'];
    if ($review_id > 0 && in_array($new_status, $valid_statuses)) {
        if ($controller->updateStatus($review_id, $new_status)) {
            header("Location: manage-reviews.php?success=Status updated successfully");
        } else {
            header("Location: manage-reviews.php?error=Failed to update status");
        }
    } else {
        header("Location: manage-reviews.php?error=Invalid status or review ID");
    }
    exit();
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    
    if ($review_id > 0) {
        if ($controller->deleteReview($review_id)) {
            header("Location: manage-reviews.php?success=Review deleted successfully");
        } else {
            header("Location: manage-reviews.php?error=Failed to delete review");
        }
    } else {
        header("Location: manage-reviews.php?error=Invalid review ID");
    }
    exit();
}

// Fetch all reviews from the controller
$reviews = $controller->getAllReviews();

// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; background-color: white;}
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .status-active { color: green; }
        .status-approved { color: green; }
        .status-inactive { color: red; }
        .status-pending { color: orange; }
        .status-spam { color: red; }
        .status-deleted { color: grey; }
        .no-reviews { text-align: center; padding: 20px; }
        select, button { padding: 5px; margin: 2px; }
        .star-filled { color: gold !important; background-color: white; margin: 0px; }
        .star-empty { color: #ccc !important; background-color: #fff; margin: 0px; }
        .delete-btn { color: red; text-decoration: none; }
        .delete-btn:hover { text-decoration: underline; }
    </style>
    <script>
        function confirmDelete(reviewId) {
            if (confirm("Are you sure you want to delete this review?")) {
                document.getElementById('delete-form-' + reviewId).submit();
            }
        }
    </script>
</head>
<body>
    <h2>Manage Reviews</h2>
    <?php if (isset($_GET['success'])): ?>
        <p class="success"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php elseif (isset($_GET['error'])): ?>
        <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>
    <?php if (!empty($reviews) && is_array($reviews)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Author</th>
                    <th>Email</th>
                    <th>Content</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Guest</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($review['id']); ?></td>
                        <td><?php echo htmlspecialchars($review['product_name'] ?? 'Unknown Product'); ?></td>
                        <td><?php echo htmlspecialchars($review['author']); ?></td>
                        <td><?php echo htmlspecialchars($review['email']); ?></td>
                        <td><?php echo htmlspecialchars(substr($review['content'], 0, 50)) . '...'; ?></td>
                        <td>
                            <?php
                            $rating = (int)($review['rating'] ?? 0);
                            $max_stars = 5;
                            for ($i = 1; $i <= $max_stars; $i++) {
                                if ($i <= $rating) {
                                    echo '<span class="star-filled">★</span>';
                                } else {
                                    echo '<span class="star-empty">☆</span>';
                                }
                            }
                            ?>
                        </td>
                        <td class="status-<?php echo $review['status']; ?>">
                            <?php echo htmlspecialchars($review['status']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($review['created_at']); ?></td>
                        <td><?php echo $review['is_guest'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <form method="POST" action="manage-reviews.php">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <select name="status">
                                    <option value="pending" <?php echo $review['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $review['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="spam" <?php echo $review['status'] === 'spam' ? 'selected' : ''; ?>>Spam</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                            <a href="edit-review.php?action=edit&id=<?php echo $review['id']; ?>">Edit</a> |
                            <form id="delete-form-<?php echo $review['id']; ?>" method="POST" action="manage-reviews.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <a href="javascript:void(0);" class="delete-btn" onclick="confirmDelete(<?php echo $review['id']; ?>)">Delete</a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-reviews">No reviews found.</p>
    <?php endif; ?>
</body>
</html>