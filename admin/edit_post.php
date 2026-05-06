<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__, 1) . '/includes/functions.php';

if (!isset($GLOBALS['__EDIT_POST_RENDER__'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: " . url('admin/login.php'));
        exit();
    }

    $matches = [];
    preg_match('#^admin/edit-post/(\d+)$#', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), $matches);
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($matches[1]) ? (int)$matches[1] : null);
    if (!$post_id) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Post Not Found</h1>";
        exit();
    }

    $target = url('admin/edit-post/' . $post_id);
    $query = [];
    if (isset($_GET['action'])) {
        $query['action'] = (string)$_GET['action'];
    }
    if (isset($_GET['success'])) {
        $query['success'] = (string)$_GET['success'];
    }
    if (isset($_GET['error'])) {
        $query['error'] = (string)$_GET['error'];
    }
    if (!empty($query)) {
        $target .= '?' . http_build_query($query);
    }
    header('Location: ' . $target);
    exit();
}

$post_id = isset($post_id) ? (int)$post_id : (int)($post['id'] ?? 0);
$post = is_array($post ?? null) ? $post : [];
$categories = is_array($categories ?? null) ? $categories : [];
$post_categories = is_array($post_categories ?? null) ? $post_categories : [];
$images = is_array($images ?? null) ? $images : [];

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
    :root {
        --black: #111111;
        --white: #ffffff;
        --yellow: #facc15;
        --light-yellow: #fffbeb;
        --bg: #f8fafc;
        --border: #e5e7eb;
        --muted: #6b7280;
    }
    .admin-page-wrapper {
        max-width: 1220px;
        margin: 0 auto;
        padding: 20px;
        background: var(--bg);
    }
    .edit-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        padding: 18px;
    }
    .admin-page-wrapper h1 {
        font-size: 2rem;
        color: var(--black);
        margin-bottom: 16px;
        letter-spacing: -0.02em;
    }
    .form-group { margin-bottom: 16px; }
    .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--black);
        font-size: 0.95rem;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.92rem;
        background: #fff;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.25);
    }
    .form-group textarea { resize: vertical; min-height: 92px; }
    .success { color: #166534; font-weight: 700; margin-bottom: 10px; }
    .error { color: #991b1b; font-weight: 700; margin-bottom: 10px; }
    .category-checkboxes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 8px;
    }
    .category-checkboxes label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
        background: #f9fafb;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 8px 10px;
        font-weight: 600;
    }
    .category-checkboxes label input {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        min-width: 16px;
        border: 1px solid var(--border);
        border-radius: 4px;
        background: #fff;
        margin: 0;
        box-shadow: none;
        cursor: pointer;
        position: relative;
        transition: border-color .15s ease, background .15s ease;
    }
    .category-checkboxes label input:checked {
        background: var(--yellow);
        border-color: var(--yellow);
    }
    .category-checkboxes label input:checked::after {
        content: "";
        position: absolute;
        left: 4px;
        top: 1px;
        width: 5px;
        height: 9px;
        border: solid var(--black);
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    .category-checkboxes label input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.25);
    }
    .media-library {
        margin-top: 20px;
        border: 1px solid var(--border);
        padding: 14px;
        border-radius: 12px;
        background: #fcfcfd;
    }
    .media-library h3 {
        margin-top: 0;
        margin-bottom: 12px;
        color: var(--black);
    }
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 10px;
    }
    .image-item {
        position: relative;
        border: 1px solid var(--border);
        padding: 10px;
        border-radius: 10px;
        background: #fff;
    }
    .image-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border);
    }
    .image-item .actions {
        margin-top: 6px;
        color: var(--muted);
        font-size: 0.85rem;
    }
    .image-item .delete-btn {
        color: var(--white);
        background: var(--black);
        border: 1px solid var(--black);
        border-radius: 999px;
        padding: 3px 8px;
        cursor: pointer;
        font-weight: 700;
        display: inline-block;
    }
    .image-item .set-main-btn {
        color: var(--white);
        background: var(--black);
        border: 1px solid var(--black);
        border-radius: 999px;
        padding: 3px 8px;
        cursor: pointer;
        font-weight: 700;
        display: inline-block;
    }
    .image-item .set-main-btn.main {
        color: var(--black);
        background: var(--yellow);
        border-color: var(--yellow);
    }
    .image-item input,
    .image-item textarea { margin-top: 6px; }
    .image-item .image-id {
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 6px;
        display: block;
    }
    .note { font-size: 0.86rem; color: var(--muted); }
    .update-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        border-radius: 10px;
        border: 1px solid var(--black);
        background: var(--black);
        color: var(--white);
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
    }
    .update-btn:hover {
        color: var(--yellow);
        transform: translateY(-1px);
    }
    input[type="date"],
    input[type="datetime-local"] {
        color-scheme: light;
        background: #fff;
    }
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        border-radius: 6px;
        padding: 2px;
        filter: brightness(0);
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover,
    input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
        background: var(--light-yellow);
    }
</style>

<div class="admin-page-wrapper">
    <div class="edit-card">
        <h1>Edit Post</h1>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (isset($error) || isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($error ?? $_GET['error']); ?></p>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars(url('admin/edit_post.php?id=' . (int)$post['id'] . '&action=update')); ?>" enctype="multipart/form-data" id="edit-post-form">
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
                <?php foreach ($categories as $category): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                               <?php echo in_array($category['id'], array_column($post_categories, 'id')) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </label>
                <?php endforeach; ?>
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
            <button type="submit" class="update-btn">Update Post</button>
        </form>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/your-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'image code',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | image code',
    images_upload_url: '<?= htmlspecialchars(url('admin/upload_post_image.php')); ?>',
    images_upload_handler: async (blobInfo, progress) => {
        let formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        try {
            let response = await fetch('<?= htmlspecialchars(url('admin/upload_post_image.php')); ?>', {
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
        btn.style.color = '#ffffff';
        btn.style.background = '#111111';
        btn.style.borderColor = '#111111';
        btn.style.fontWeight = '700';
    });
    const clickedBtn = document.querySelector(`.set-main-btn[data-image-id="${imageId}"]`);
    if (clickedBtn) {
        clickedBtn.textContent = 'Main Image';
        clickedBtn.classList.add('main');
        clickedBtn.style.color = '#111111';
        clickedBtn.style.background = '#facc15';
        clickedBtn.style.borderColor = '#facc15';
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
            let response = await fetch(`<?= htmlspecialchars(url('admin/edit_post.php?id=' . (int)$post_id . '&action=delete_image')); ?>`, {
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