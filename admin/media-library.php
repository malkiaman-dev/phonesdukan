<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

// Include the header
include __DIR__ . '/admin_header.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Fetch images with metadata and product details
$sql = "
    SELECT i.image_id, i.image_url, i.product_id, im.alt_text, im.title, im.description, im.caption, 
           COALESCE(p.product_name, 'Not Assigned') AS product_name
    FROM product_images i
    LEFT JOIN image_metadata im ON i.image_id = im.image_id
    LEFT JOIN products p ON i.product_id = p.product_id
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete request
if (isset($_POST['image_id']) && !empty($_POST['image_id'])) {
    $imageId = (int)$_POST['image_id'];

    // Delete the image and metadata
    try {
        // Delete from product_images
        $deleteSql = "DELETE FROM product_images WHERE image_id = :image_id";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bindParam(':image_id', $imageId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete metadata from image_metadata
        $deleteMetadataSql = "DELETE FROM image_metadata WHERE image_id = :image_id";
        $stmt = $conn->prepare($deleteMetadataSql);
        $stmt->bindParam(':image_id', $imageId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit(); // Terminate the script after deletion
}

?>

<?php
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    include __DIR__ . '/admin_sidebar.php';
}
?>

<div class="content-wrapper">
    <h2 class="dashboard-title">Media Library</h2>
    <button id="openUploadModal">Upload New Image</button>
    <div class="image-grid">
        <?php foreach ($images as $image) : ?>
            <div class="image-card" data-image-id="<?= $image['image_id'] ?>">
    <img src="<?= htmlspecialchars($image['image_url']) ?>" 
        alt="<?= htmlspecialchars($image['alt_text'] ?? '') ?>"
        onclick="openEditModal(
            <?= $image['image_id'] ?>, 
            '<?= addslashes(htmlspecialchars($image['title'] ?? 'No Title')) ?>', 
            '<?= addslashes(htmlspecialchars($image['alt_text'] ?? 'No Alt Text')) ?>', 
            '<?= addslashes(htmlspecialchars($image['caption'] ?? 'No Caption')) ?>', 
            '<?= addslashes(htmlspecialchars($image['description'] ?? 'No Description')) ?>'
        )">
</div>

        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Image Details</h2>
        <form id="editForm">
            <input type="hidden" id="editImageId">

            <label for="editTitle">Title:</label>
            <input type="text" id="editTitle" class="input-field">

            <label for="editAltText">Alt Text:</label>
            <input type="text" id="editAltText" class="input-field">

            <label for="editCaption">Caption:</label>
            <input type="text" id="editCaption" class="input-field">

            <label for="editDescription">Description:</label>
            <textarea id="editDescription" class="input-field"></textarea>

            <div class="button-group">
                <button type="button" class="update-btn" onclick="updateImageMetadata()">Update</button>
                <button type="button" id="deleteImageBtn" class="delete-btn">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" id="closeUploadModal">&times;</span>
        <iframe id="uploadFrame" src="upload_image.php" width="100%" height="400px" style="border:none;"></iframe>
    </div>
</div>

<script>
// Function to open the edit modal
function openEditModal(imageId, title, altText, caption, description) {
    document.getElementById('editImageId').value = imageId;
    document.getElementById('editTitle').value = title;
    document.getElementById('editAltText').value = altText;
    document.getElementById('editCaption').value = caption;
    document.getElementById('editDescription').value = description;
    document.getElementById('editModal').style.display = 'block';
}

// Function to close the edit modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

/// Function to handle the delete button click
document.getElementById('deleteImageBtn').addEventListener('click', function() {
    const imageId = document.getElementById('editImageId').value;
    
    // Confirm if the user really wants to delete the image
    if (confirm('Are you sure you want to delete this image?')) {
        deleteImageMetadata(imageId);  // Call function to delete image
    }
});

// Function to delete the image by sending an AJAX request
function deleteImageMetadata(imageId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);  // Sends the request to the same page (current URL)
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    // This function triggers when the request completes
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            const response = JSON.parse(xhr.responseText);  // Parse the JSON response from the server
            
            if (response.success) {
                alert('Image deleted successfully!');
                
                // Remove the image card from the DOM immediately
                const imageCard = document.querySelector(`.image-card[data-image-id='${imageId}']`);
                if (imageCard) {
                    imageCard.remove();  // Remove the image card from the page
                }

            } else {
                alert('Error deleting image: ' + response.message);  // Show error if deletion failed
            }
        }
    };

    // Send the request to delete the image with the image ID
    xhr.send('image_id=' + imageId);  // The image ID is passed as part of the POST data
}

</script>

