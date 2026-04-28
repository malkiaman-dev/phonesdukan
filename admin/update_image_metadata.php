<?php
require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $image_id = $_POST['image_id'];

    if ($action === 'update') {
        // Update metadata
        $title = $_POST['title'];
        $alt_text = $_POST['alt_text'];
        $caption = $_POST['caption'];
        $description = $_POST['description'];

        $sql = "UPDATE image_metadata SET title = ?, alt_text = ?, caption = ?, description = ? WHERE image_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$title, $alt_text, $caption, $description, $image_id])) {
            echo json_encode(["success" => true, "message" => "Image metadata updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update metadata."]);
        }
    }

    if ($action === 'delete') {
        // Fetch image URL before deleting
        $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE image_id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            $imagePath = __DIR__ . '/../' . parse_url($image['image_url'], PHP_URL_PATH);
            
            // Delete metadata
            $deleteMeta = $conn->prepare("DELETE FROM image_metadata WHERE image_id = ?");
            $deleteMeta->execute([$image_id]);

            // Delete image record from product_images
            $deleteImage = $conn->prepare("DELETE FROM product_images WHERE image_id = ?");
            $deleteImage->execute([$image_id]);

            // Delete the actual image file
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            echo json_encode(["success" => true, "message" => "Image deleted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Image not found."]);
        }
    }
}
?>
