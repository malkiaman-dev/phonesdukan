<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include the Database class and Controller
require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Controllers/AddProductController.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    // Initialize the controller and add the product
    $controller = new ProductController();
    $controller->addProduct();
    exit(); // Prevent the rest of the script from running after the controller action
}

// Create an instance of the Database class and get the connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
// Get categories and brands for dropdowns
$categories_query = "SELECT * FROM categories"; // Adjust this query to your actual categories table
$categories_result = $conn->query($categories_query);

$brands_query = "SELECT * FROM brands"; // Adjust this query to your actual brands table
$brands_result = $conn->query($brands_query);
?>

<!-- Display Session Messages -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert <?php echo $_SESSION['message_type']; ?>">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<form action="add-product.php?action=add" method="POST" enctype="multipart/form-data">
    <h1>Add New Product</h1>

    <!-- Product Details Section -->
    <label>Product Name</label>
    <input type="text" name="product_name" required>

    <label>Product Slug</label>
    <input type="text" name="product_slug" required>

    <label>Category</label>
    <select name="category_id" required>
        <?php while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) { ?>
            <option value="<?php echo $row['category_id']; ?>"><?php echo $row['category_name']; ?></option>
        <?php } ?>
    </select>

    <label>Brand</label>
    <select name="brand_id" required>
        <?php while ($row = $brands_result->fetch(PDO::FETCH_ASSOC)) { ?>
            <option value="<?php echo $row['brand_id']; ?>"><?php echo $row['brand_name']; ?></option>
        <?php } ?>
    </select>

    <label>Product Description</label>
    <textarea name="product_description" required></textarea>

    <label>Short Description</label>
    <textarea name="short_description" required></textarea>

    <label>Product Status</label>
    <select name="product_status" required>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>

    <label>Stock Quantity</label>
    <input type="number" name="stock_quantity" required>

    <label>Regular Price</label>
    <input type="number" name="regular_price" required>

    <label>Sale Price</label>
    <input type="number" name="sale_price">

    <label>Product SKU</label>
    <input type="text" name="product_sku">

    <label>Weight (kg)</label>
    <input type="text" name="weight_kg">

    <label>Dimensions (L x W x H in cm)</label>
    <input type="text" name="length_cm" placeholder="Length (cm)">
    <input type="text" name="width_cm" placeholder="Width (cm)">
    <input type="text" name="height_cm" placeholder="Height (cm)">

    <label>Tax Class</label>
    <input type="text" name="tax_class">

    <label>Product Tags</label>
    <input type="text" name="product_tag">

    <!-- SEO Section -->
    <h2>SEO Information</h2>
    <label>SEO Title</label>
    <input type="text" name="seo_title" required>

    <label>SEO Description</label>
    <textarea name="seo_description" required></textarea>

    <label>Focus Keyword</label>
    <input type="text" name="focus_keyword">

    <h2>Product Images</h2>

<!-- Primary Image Upload -->
<label for="primary_image">Primary Image</label>
<input type="file" name="primary_image" id="primary_image" required />

<label for="primary_alt_text">Alt Text for Primary Image</label>
<input type="text" name="primary_alt_text" id="primary_alt_text" required>

<label for="primary_image_title">Title for Primary Image</label>
<input type="text" name="primary_image_title" id="primary_image_title" required>

<label for="primary_image_caption">Caption for Primary Image</label>
<input type="text" name="primary_image_caption" id="primary_image_caption" required>

<label for="primary_image_description">Description for Primary Image</label>
<input type="text" name="primary_image_description" id="primary_image_description" required>

<!-- Gallery Images Upload -->
<label for="gallery_images">Gallery Images</label>
<input type="file" name="gallery_images[]" id="gallery_images" multiple onchange="previewGalleryImages(event)" />

<!-- Container for gallery image metadata -->
<div id="gallery_images_metadata"></div>

<button type="submit">Add Product</button>
</form>

<script>
// Function to preview gallery images and create metadata fields dynamically
function previewGalleryImages(event) {
    const metadataContainer = document.getElementById('gallery_images_metadata');
    metadataContainer.innerHTML = ''; // Clear any existing metadata fields

    const files = event.target.files; // Get the selected files
    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // Create a section for each image
        const imageSection = document.createElement('div');
        imageSection.classList.add('gallery-image-section');

        // Create an image preview
        const imagePreview = document.createElement('img');
        imagePreview.src = URL.createObjectURL(file);
        imagePreview.alt = file.name;
        imagePreview.style.maxWidth = '100px'; // Adjust the size of the preview
        imageSection.appendChild(imagePreview);

        // Create fields for metadata
        const altTextInput = document.createElement('input');
        altTextInput.type = 'text';
        altTextInput.name = 'alt_text_gallery[]';
        altTextInput.placeholder = 'Alt Text for Gallery Image';

        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.name = 'image_title_gallery[]';
        titleInput.placeholder = 'Title for Gallery Image';

        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.name = 'image_caption_gallery[]';
        captionInput.placeholder = 'Caption for Gallery Image';

        const descriptionTextarea = document.createElement('textarea');
        descriptionTextarea.name = 'image_description_gallery[]';
        descriptionTextarea.placeholder = 'Description for Gallery Image';

        // Append the inputs to the section
        imageSection.appendChild(altTextInput);
        imageSection.appendChild(titleInput);
        imageSection.appendChild(captionInput);
        imageSection.appendChild(descriptionTextarea);

        // Append the image section to the container
        metadataContainer.appendChild(imageSection);
    }
}
</script>