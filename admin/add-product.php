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
    <div class="add-product-alert <?php echo $_SESSION['message_type']; ?>">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php unset($_SESSION['message']);
    unset($_SESSION['message_type']); ?>
<?php endif; ?>

<div class="admin-page">
    <div class="page-header form-card">
        <div>
            <h1>Add New Product</h1>
            <p>Create and publish a new product in your store</p>
        </div>
        <button type="submit" form="add-product-form" class="submit-btn">Save Product</button>
    </div>

    <form id="add-product-form" action="add-product.php?action=add" method="POST" enctype="multipart/form-data" class="product-form">
        <section class="form-card">
            <div class="form-card-header"><h2>Basic Information</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input id="product_name" type="text" name="product_name" required>
                </div>
                <div class="form-group">
                    <label for="product_slug">Product Slug</label>
                    <input id="product_slug" type="text" name="product_slug" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) { ?>
                            <option value="<?php echo $row['category_id']; ?>"><?php echo $row['category_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <select id="brand_id" name="brand_id" required>
                        <?php while ($row = $brands_result->fetch(PDO::FETCH_ASSOC)) { ?>
                            <option value="<?php echo $row['brand_id']; ?>"><?php echo $row['brand_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Description</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group full-width">
                    <label for="product_description">Product Description</label>
                    <textarea id="product_description" name="product_description" required></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" required></textarea>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Pricing &amp; Inventory</h2></div>
            <div class="form-grid cols-4">
                <div class="form-group">
                    <label for="regular_price">Regular Price</label>
                    <input id="regular_price" type="number" name="regular_price" required>
                </div>
                <div class="form-group">
                    <label for="sale_price">Sale Price</label>
                    <input id="sale_price" type="number" name="sale_price">
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input id="stock_quantity" type="number" name="stock_quantity" required>
                </div>
                <div class="form-group">
                    <label for="product_sku">Product SKU</label>
                    <input id="product_sku" type="text" name="product_sku">
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Product Details</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="weight_kg">Weight (kg)</label>
                    <input id="weight_kg" type="text" name="weight_kg">
                </div>
                <div class="form-group">
                    <label for="tax_class">Tax Class</label>
                    <input id="tax_class" type="text" name="tax_class">
                </div>
                <div class="form-group full-width">
                    <label>Dimensions (L x W x H in cm)</label>
                    <div class="dimensions-group">
                        <input type="text" name="length_cm" placeholder="Length (cm)">
                        <input type="text" name="width_cm" placeholder="Width (cm)">
                        <input type="text" name="height_cm" placeholder="Height (cm)">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="product_tag">Product Tags</label>
                    <input id="product_tag" type="text" name="product_tag">
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Status</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="product_status">Product Status</label>
                    <select id="product_status" name="product_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>SEO Information</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input id="seo_title" type="text" name="seo_title" required>
                </div>
                <div class="form-group">
                    <label for="focus_keyword">Focus Keyword</label>
                    <input id="focus_keyword" type="text" name="focus_keyword">
                </div>
                <div class="form-group full-width">
                    <label for="seo_description">SEO Description</label>
                    <textarea id="seo_description" name="seo_description" required></textarea>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Product Images</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="primary_image">Primary Image</label>
                    <input type="file" name="primary_image" id="primary_image" required>
                </div>
                <div class="form-group">
                    <label for="gallery_images">Gallery Images</label>
                    <input type="file" name="gallery_images[]" id="gallery_images" multiple onchange="previewGalleryImages(event)">
                </div>
                <div class="form-group">
                    <label for="primary_alt_text">Alt Text for Primary Image</label>
                    <input type="text" name="primary_alt_text" id="primary_alt_text" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_title">Title for Primary Image</label>
                    <input type="text" name="primary_image_title" id="primary_image_title" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_caption">Caption for Primary Image</label>
                    <input type="text" name="primary_image_caption" id="primary_image_caption" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_description">Description for Primary Image</label>
                    <input type="text" name="primary_image_description" id="primary_image_description" required>
                </div>
            </div>

            <div id="gallery_images_metadata" class="gallery-metadata-grid"></div>
        </section>

        <div class="form-footer-actions">
            <button type="submit" class="submit-btn">Save Product</button>
        </div>
    </form>
</div>

<script>
function previewGalleryImages(event) {
    const metadataContainer = document.getElementById('gallery_images_metadata');
    metadataContainer.innerHTML = '';

    const files = event.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        const imageSection = document.createElement('div');
        imageSection.classList.add('gallery-image-section');

        const imagePreview = document.createElement('img');
        imagePreview.src = URL.createObjectURL(file);
        imagePreview.alt = file.name;
        imagePreview.classList.add('gallery-image-preview');
        imageSection.appendChild(imagePreview);

        const altTextInput = document.createElement('input');
        altTextInput.type = 'text';
        altTextInput.name = 'alt_text_gallery[]';
        altTextInput.placeholder = 'Alt Text for Gallery Image';
        altTextInput.classList.add('gallery-meta-input');

        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.name = 'image_title_gallery[]';
        titleInput.placeholder = 'Title for Gallery Image';
        titleInput.classList.add('gallery-meta-input');

        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.name = 'image_caption_gallery[]';
        captionInput.placeholder = 'Caption for Gallery Image';
        captionInput.classList.add('gallery-meta-input');

        const descriptionTextarea = document.createElement('textarea');
        descriptionTextarea.name = 'image_description_gallery[]';
        descriptionTextarea.placeholder = 'Description for Gallery Image';
        descriptionTextarea.classList.add('gallery-meta-textarea');

        imageSection.appendChild(altTextInput);
        imageSection.appendChild(titleInput);
        imageSection.appendChild(captionInput);
        imageSection.appendChild(descriptionTextarea);

        metadataContainer.appendChild(imageSection);
    }
}
</script>