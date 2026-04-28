<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminPostController.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /admin/login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    $controller = new AdminPostController();
    $controller->create();
    exit();
}

// Get categories for checkboxes
$database = new Database();
$conn = $database->getConnection();
$categories_query = "SELECT id, category_name FROM post_categories WHERE status = 1 ORDER BY category_name";
$categories_result = $conn->query($categories_query);

include dirname(__DIR__, 1) . '/admin/admin_sidebar.php';
include dirname(__DIR__, 1) . '/admin/admin_header.php';
?>

<style>
    .admin-page-wrapper { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .form-group textarea { resize: vertical; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .category-checkboxes { display: flex; flex-wrap: wrap; gap: 10px; }
    .category-checkboxes label { margin-right: 15px; margin-bottom: 5px; }
    .media-library { margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
    .media-library h3 { margin-top: 0; }
    .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
    .image-item { position: relative; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
    .image-item img { max-width: 100%; height: auto; }
    .image-item .actions { margin-top: 5px; }
    .image-item .delete-btn { color: red; cursor: pointer; }
    .image-item .set-main-btn { color: blue; cursor: pointer; }
    .image-item .set-main-btn.main { color: green; font-weight: bold; }
    .image-item input, .image-item textarea { margin-top: 5px; }
    .image-item .image-id { font-size: 0.9em; color: #666; margin-top: 5px; display: block; }
    .note { font-size: 0.9em; color: #666; }
</style>

<div class="admin-page-wrapper">
    <h1>Add New Post</h1>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="/admin/add-post?action=add" enctype="multipart/form-data" id="add-post-form">
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug *</label>
            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="excerpt">Excerpt</label>
            <textarea name="excerpt" id="excerpt"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea name="content" id="content" rows="10"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            <p class="note">Use [image id=X] to embed non-primary images in content (IDs shown after saving).</p>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>
        <div class="form-group">
            <label for="published_at">Published Date</label>
            <input type="datetime-local" name="published_at" id="published_at" value="<?php echo htmlspecialchars($_POST['published_at'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Categories</label>
            <div class="category-checkboxes">
                <?php while ($category = $categories_result->fetch(PDO::FETCH_ASSOC)): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                               <?php echo in_array($category['id'], $_POST['categories'] ?? []) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="media-library">
            <h3>Media Library</h3>
            <div class="form-group">
                <label for="main_image">Main Image</label>
                <input type="file" name="main_image" id="main_image" accept="image/jpeg,image/png,image/gif,image/webp">
                <input type="text" name="main_image_alt_text" id="main_image_alt_text" value="<?php echo htmlspecialchars($_POST['main_image_alt_text'] ?? ''); ?>" placeholder="Main Image Alt Text">
                <textarea name="main_image_caption" id="main_image_caption" placeholder="Main Image Caption"><?php echo htmlspecialchars($_POST['main_image_caption'] ?? ''); ?></textarea>
                <p class="note">Upload the primary image (JPG, JPEG, PNG, GIF, WebP, max 5MB).</p>
            </div>
            <div class="form-group">
                <label for="images">Additional Images</label>
                <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                <p class="note">Select multiple images (JPG, JPEG, PNG, GIF, WebP, max 5MB each).</p>
            </div>
            <div class="image-grid"></div>
        </div>
        <input type="hidden" name="main_image_id" id="main_image_id" value="">
        <div class="form-group">
            <label for="meta_title">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <textarea name="meta_description" id="meta_description"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="meta_keywords">Meta Keywords</label>
            <input type="text" name="meta_keywords" id="meta_keywords" value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="canonical_url">Canonical URL</label>
            <input type="text" name="canonical_url" id="canonical_url" value="<?php echo htmlspecialchars($_POST['canonical_url'] ?? ''); ?>">
        </div>
        <button type="submit">Create Post</button>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/your-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'image code',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | image code',
    images_upload_url: '/admin/upload_post_image.php',
    images_upload_handler: async (blobInfo, progress) => {
        let formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        try {
            let response = await fetch('/admin/upload_post_image.php', {
                method: 'POST',
                body: formData
            });
            let result = await response.json();
            if (result.success) {
                return result.url;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            throw new Error('Image upload failed: ' + error.message);
        }
    }
});

document.getElementById('title').addEventListener('input', function() {
    let slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
});

function updateMainImage(imageId) {
    console.log('Setting main image to: ' + imageId);
    document.querySelectorAll('.set-main-btn').forEach(btn => {
        btn.textContent = 'Set as Main';
        btn.classList.remove('main');
        btn.style.color = 'blue';
        btn.style.fontWeight = 'normal';
    });
    const clickedBtn = document.querySelector(`.set-main-btn[data-image-id="${imageId}"]`);
    if (clickedBtn) {
        clickedBtn.textContent = 'Main Image';
        clickedBtn.classList.add('main');
        clickedBtn.style.color = 'green';
        clickedBtn.style.fontWeight = 'bold';
    }
    document.getElementById('main_image_id').value = imageId;
    console.log('main_image_id set to: ' + document.getElementById('main_image_id').value);
}

document.getElementById('main_image').addEventListener('change', function(e) {
    let file = e.target.files[0];
    if (file) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let grid = document.querySelector('.image-grid');
            let newImageId = 'new_main_' + Date.now();
            let div = document.createElement('div');
            div.className = 'image-item';
            div.dataset.imageId = newImageId;
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <div class="actions">
                    <span class="set-main-btn main" data-image-id="${newImageId}">Main Image</span> |
                    <span class="delete-btn" data-image-id="${newImageId}">Delete</span>
                </div>
                <span class="image-id">ID: New (Main)</span>
                <input type="text" name="main_image_alt_text" value="${document.getElementById('main_image_alt_text').value}" placeholder="Alt Text">
                <textarea name="main_image_caption" placeholder="Caption">${document.getElementById('main_image_caption').value}</textarea>
            `;
            // Clear previous main image preview
            const existingMain = grid.querySelector('.image-item .set-main-btn.main');
            if (existingMain) {
                existingMain.parentElement.parentElement.remove();
            }
            grid.appendChild(div);
            updateMainImage(newImageId);
            div.querySelector('.delete-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (newImageId === document.getElementById('main_image_id').value) {
                    document.getElementById('main_image_id').value = '';
                    console.log('main_image_id cleared due to main image deletion');
                }
                div.remove();
                document.getElementById('main_image').value = '';
                document.getElementById('main_image_alt_text').value = '';
                document.getElementById('main_image_caption').value = '';
            });
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('images').addEventListener('change', function(e) {
    let files = e.target.files;
    let grid = document.querySelector('.image-grid');
    for (let i = 0; i < files.length; i++) {
        let file = files[i];
        let reader = new FileReader();
        reader.onload = function(e) {
            let newImageId = 'new_' + i + '_' + Date.now();
            let div = document.createElement('div');
            div.className = 'image-item';
            div.dataset.imageId = newImageId;
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <div class="actions">
                    <span class="set-main-btn" data-image-id="${newImageId}">Set as Main</span> |
                    <span class="delete-btn" data-image-id="${newImageId}">Delete</span>
                </div>
                <span class="image-id">ID: New</span>
                <input type="text" name="images_alt_text[${newImageId}]" placeholder="Alt Text">
                <textarea name="images_caption[${newImageId}]" placeholder="Caption"></textarea>
            `;
            grid.appendChild(div);
            div.querySelector('.set-main-btn').addEventListener('click', function(e) {
                e.preventDefault();
                updateMainImage(this.dataset.imageId);
            });
            div.querySelector('.delete-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (newImageId === document.getElementById('main_image_id').value) {
                    document.getElementById('main_image_id').value = '';
                    console.log('main_image_id cleared due to new image deletion');
                }
                div.remove();
            });
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('add-post-form').addEventListener('submit', function(e) {
    console.log('Form submitted with main_image_id: ' + document.getElementById('main_image_id').value);
    // Log form data for debugging
    const formData = new FormData(this);
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + (value instanceof File ? value.name : value));
    }
});
</script>