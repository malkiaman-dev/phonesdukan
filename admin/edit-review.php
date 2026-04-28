<?php
// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Ensure the controller is included
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminReviewController.php';
// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
// Initialize the controller
$controller = new AdminReviewController();

// Check if we have an id in the query string
if (isset($_GET['id'])) {
    $review = $controller->getReviewById($_GET['id']);
    if (!$review) {
        // If no review found, redirect back to manage reviews page
        header('Location: /admin/manage-reviews.php?error=Review not found');
        exit;
    }
} else {
    // If no id is provided, redirect to manage reviews page
    header('Location: /admin/manage-reviews.php?error=Invalid review ID');
    exit;
}

// Handle the form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = $_POST['author'];
    $email = $_POST['email'];
    $content = $_POST['content'];
    $rating = $_POST['rating'];

    // Update the review using the controller's update method
    if ($controller->updateReview($_POST['id'], $author, $email, $content, $rating)) {
        // On success, set success message and refresh review data
        $success_message = 'Review updated successfully';
        $review = $controller->getReviewById($_POST['id']); // Refresh review data to display updated values
    } else {
        // If update fails, set error message
        $error_message = 'Update failed';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review</title>
    <style>
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; }
        .success { color: green; }
        .error { color: red; }
        .btn { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Edit Review</h2>
    <?php if ($success_message): ?>
        <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>
    <?php if ($error_message || isset($_GET['error'])): ?>
        <p class="error"><?php echo htmlspecialchars($error_message ?: $_GET['error']); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($review['id']); ?>">
        <div class="form-group">
            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($review['author']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($review['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="5" required><?php echo htmlspecialchars($review['content']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="rating">Rating (1-5)</label>
            <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($review['rating']); ?>" required>
        </div>
        <button type="submit" class="btn">Update Review</button>
        <a href="/admin/manage-reviews.php" class="btn">Cancel</a>
    </form>
</body>
</html>