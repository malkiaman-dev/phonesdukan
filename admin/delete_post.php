<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/AdminPostModel.php';

// Initifalize database connection and model
$database = new Database();
$conn = $database->getConnection();
$postModel = new AdminPostModel();

if (!$conn) {
    die('Database connection failed: ' . $conn->errorInfo()[2]);
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

// Validate post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id <= 0) {
    header('Location: manage_posts.php?error=' . urlencode('Invalid post ID'));
    exit();
}

// Begin transaction to delete post and associated data
try {
    $conn->beginTransaction();

    // Delete post images
    $query = "DELETE FROM post_images WHERE post_id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $post_id]);

    // Delete post SEO
    $query = "DELETE FROM post_seo WHERE post_id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $post_id]);

    // Delete post category mappings
    $query = "DELETE FROM post_category_mappings WHERE post_id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $post_id]);

    // Delete post
    $query = "DELETE FROM posts WHERE id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $post_id]);

    // Check if post was deleted
    if ($stmt->rowCount() === 0) {
        throw new Exception('Post not found or already deleted');
    }

    $conn->commit();
    error_log("Post deleted successfully: $post_id");
    header('Location: manage_posts.php?success=' . urlencode('Post deleted successfully'));
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Delete Post Error: " . $e->getMessage());
    header('Location: manage_posts.php?error=' . urlencode('Failed to delete post: ' . $e->getMessage()));
    exit();
}
?>