<?php
session_start();

// Include the database connection file
require_once __DIR__ . '/../database/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection(); // Get PDO connection

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Start a transaction to ensure all data is deleted successfully (PDO transaction handling)
    $conn->beginTransaction();

    try {
        // Step 1: Delete metadata associated with the images
        $stmt = $conn->prepare("DELETE FROM image_metadata WHERE image_id IN (SELECT image_id FROM product_images WHERE product_id = ?)");
        $stmt->execute([$product_id]);

        // Step 2: Delete product videos (table may not exist on older installs)
        try {
            $stmt = $conn->prepare("DELETE FROM product_videos WHERE product_id = ?");
            $stmt->execute([$product_id]);
        } catch (Exception $videoEx) {
            // Ignore if product_videos table is not installed yet
        }

        // Step 3: Delete the images from product_images
        $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Step 4: Delete SEO data for the product
        $stmt = $conn->prepare("DELETE FROM product_seo WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Step 5: Delete the product itself from products table
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Commit the transaction
        $conn->commit();

        // Set a session message for successful deletion
        $_SESSION['message'] = 'Product and all associated data have been deleted successfully.';
        $_SESSION['message_type'] = 'success';

        // Redirect to the manage products page
        header("Location: /admin/manage-products.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $conn->rollBack();
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header("Location: /admin/manage-products.php");
        exit();
    }
}
?>
