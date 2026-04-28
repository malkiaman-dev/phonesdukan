<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminEditPostController.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /admin/login.php");
    exit();
}

// Get post ID from URL
$matches = [];
preg_match('#^admin/edit-post/(\d+)$#', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), $matches);
$post_id = $matches[1] ?? null;

if (!$post_id) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Post Not Found</h1>";
    exit();
}

// Handle AJAX image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_image') {
    $controller = new AdminEditPostController();
    $controller->deleteImage($post_id, $_POST['image_id']);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $controller = new AdminEditPostController();
    $controller->update($post_id);
    exit();
}

// Load post data
$controller = new AdminEditPostController();
$controller->index($post_id);

// Get categories for checkboxes
$database = new Database();
$conn = $database->getConnection();
$categories_query = "SELECT id, category_name FROM post_categories WHERE status = 1 ORDER BY category_name";
$categories_result = $conn->query($categories_query);

// Get main image
$main_image = null;
foreach ($images as $image) {
    if ($image['is_main']) {
        $main_image = $image;
        break;
    }
}

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
    .category-checkboxes label { display: block; margin-bottom: 5px; }
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
    <h1>Edit Post</h1>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (isset($error) || isset($_GET['error'])): ?>
        <p class="error"><?php echo htmlspecialchars($error ?? $_GET['error']); ?></p>
    <?php endif; ?>
    <form method="POST" action="/admin/edit-post/<?php echo htmlspecialchars($post['id']); ?>?action=update" enctype="multipart/form-data" id="edit-post-form">
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug *</label>
            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="excerpt">Excerpt</label>
            <textarea name="excerpt" id="excerpt"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea name="content" id="content" rows="10" style="width: 100%;"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
            <p class="note">Add image using [image id=X]</p>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>
        <div class="form-group">
            <label for="updated_at">Updated Date</label>
            <input type="datetime-local" name="updated_at" id="updated_at" value="<?php echo htmlspecialchars($post['updated_at'] ? date('Y-m-d\TH:i', strtotime($post['updated_at'])) : ''); ?>">
        </div>
        <div class="form-group">
            <label>Categories</label>
            <div class="category-checkboxes">
                <?php while ($category = $categories_result->fetch(PDO::FETCH_ASSOC)): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                               <?php echo in_array($category['id'], array_column($post_categories, 'id')) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="meta_title">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <textarea name="meta_description" id="meta_description"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="meta_keywords">Meta Keywords</label>
            <input type="text" name="meta_keywords" id="meta_keywords" value="<?php echo htmlspecialchars($post['meta_keywords'] ?? ''); ?>">
        </div>
        <div class="media-library">
            <h3>Media Library</h3>
            <div class="form-group">
                <label for="images">Images Upload</label>
                <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/webp,image/png,image/gif">
                <p class="note">Select multiple images (JPG, JPEG, PNG, GIF, max 5MB each).</p>
            </div>
            <div class="image-grid">
                <?php foreach ($images as $index => $image): ?>
                    <div class="image-item" data-image-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? ''); ?>">
                        <div class="actions">
                            <span class="set-main-btn <?php echo $image['is_main'] ? 'main' : ''; ?>" data-image-id="<?php echo $image['id']; ?>">
                                <?php echo $image['is_main'] ? 'Main Image' : 'Set as Main'; ?>
                            </span> |
                            <span class="delete-btn" data-image-id="<?php echo $image['id']; ?>">Delete</span>
                        </div>
                        <span class="image-id">ID: <?php echo htmlspecialchars($image['id']); ?></span>
                        <input type="text" name="existing_images[<?php echo $image['id']; ?>][alt_text]" value="<?php echo htmlspecialchars($image['alt_text'] ?? ''); ?>" placeholder="Alt Text">
                        <textarea name="existing_images[<?php echo $image['id']; ?>][caption]" placeholder="Caption"><?php echo htmlspecialchars($image['caption'] ?? ''); ?></textarea>
                        <input type="hidden" name="remove_image_ids[]" class="remove-image-id" value="">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <input type="hidden" name="main_image_id" id="main_image_id" value="<?php echo $main_image ? $main_image['id'] : ''; ?>">
        <button type="submit">Update Post</button>
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

document.querySelectorAll('.set-main-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        updateMainImage(this.dataset.imageId);
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this image?')) {
            let imageId = this.dataset.imageId;
            let response = await fetch(`/admin/edit-post/<?php echo $post_id; ?>?action=delete_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image_id=${imageId}`
            });
            let result = await response.json();
            if (result.success) {
                const imageItem = document.querySelector(`.image-item[data-image-id="${imageId}"]`);
                if (imageId === document.getElementById('main_image_id').value) {
                    document.getElementById('main_image_id').value = '';
                    console.log('main_image_id cleared due to deletion');
                }
                imageItem.remove();
            } else {
                alert('Failed to delete image: ' + result.error);
            }
        }
    });
});

document.getElementById('images').addEventListener('change', function(e) {
    let files = e.target.files;
    let grid = document.querySelector('.image-grid');
    for (let i = 0; i < files.length; i++) {
        let file = files[i];
        let reader = new FileReader();
        reader.onload = function(e) {
            let div = document.createElement('div');
            div.className = 'image-item';
            let newImageId = 'new_' + i + '_' + Date.now();
            div.dataset.imageId = newImageId;
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <div class="actions">
                    <span class="set-main-btn" data-image-id="${newImageId}">Set as Main</span> |
                    <span class="delete-btn" data-image-id="${newImageId}">Delete</span>
                </div>
                <span class="image-id">ID: New</span>
                <input type="text" name="images_alt_text[${i}]" placeholder="Alt Text">
                <textarea name="images_caption[${i}]" placeholder="Caption"></textarea>
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

document.getElementById('edit-post-form').addEventListener('submit', function() {
    console.log('Form submitted with main_image_id: ' + document.getElementById('main_image_id').value);
});
</script>