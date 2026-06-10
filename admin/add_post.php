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

include dirname(__DIR__, 1) . '/admin/admin_header.php';
include dirname(__DIR__, 1) . '/admin/admin_sidebar.php';
?>

<style>
    :root {
        --black: #111111;
        --yellow: #facc15;
        --yellow-hover: #eab308;
        --light-yellow: #fffbeb;
        --white: #ffffff;
        --bg: #f8fafc;
        --border: #e5e7eb;
        --muted: #6b7280;
    }

    html,
    body {
        height: 100%;
        overflow: hidden !important;
    }

    .admin-page-wrapper {
        max-width: 1220px;
        margin: 0 auto;
        padding: 20px;
        background: var(--bg);
        height: calc(100vh - 56px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .page-header-card,
    .section-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
    }

    .page-header-card {
        margin-bottom: 20px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .page-header-card h1 {
        margin: 0;
        color: var(--black);
        font-size: clamp(1.5rem, 2vw, 1.75rem);
        font-weight: 600;
        line-height: 1.25;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        margin-top: 6px;
        color: var(--muted);
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
    }

    .section-card {
        margin-bottom: 20px;
        padding: 24px;
    }

    .section-title {
        margin: 0 0 16px;
        color: var(--black);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .form-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .form-group {
        margin-bottom: 0;
    }

    .span-2 {
        grid-column: 1 / -1;
    }

    .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--black);
        font-size: 0.92rem;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--white);
        color: var(--black);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
        font-family: inherit;
        font-size: 0.92rem;
    }

    .form-group input,
    .form-group select {
        height: 48px;
        padding: 0 14px;
    }

    .form-group textarea {
        padding: 14px;
        resize: vertical;
        min-height: 100px;
    }

    #content {
        min-height: 220px;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    #published_at {
        -webkit-appearance: none;
        appearance: none;
        background-image:
            linear-gradient(45deg, transparent 50%, #111 50%),
            linear-gradient(135deg, #111 50%, transparent 50%);
        background-position:
            calc(100% - 18px) 20px,
            calc(100% - 12px) 20px;
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat;
        padding-right: 36px;
    }

    .native-status-select {
        position: absolute !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 1px !important;
        height: 1px !important;
    }

    .status-select-wrap {
        position: relative;
        width: 100%;
    }

    .status-display {
        width: 100%;
        height: 48px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--white);
        color: var(--black);
        padding: 0 38px 0 14px;
        font-size: 0.92rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        position: relative;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .status-display::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-right: 2px solid var(--black);
        border-bottom: 2px solid var(--black);
        transform: translateY(-65%) rotate(45deg);
    }

    .status-display:hover,
    .status-select-wrap.is-open .status-display {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .status-options {
        position: absolute;
        left: 0;
        right: 0;
        margin-top: 6px;
        list-style: none;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--white);
        box-shadow: 0 14px 24px rgba(17, 17, 17, 0.12);
        padding: 6px;
        display: none;
        z-index: 30;
    }

    .status-select-wrap.is-open .status-options {
        display: block;
    }

    .status-option {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--black);
        padding: 8px 10px;
        font-size: 0.9rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
    }

    .status-option:hover {
        background: var(--light-yellow);
    }

    .status-option.is-selected {
        background: var(--yellow);
    }

    .category-checkboxes {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }

    .category-checkboxes label {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #f9fafb;
        color: var(--black);
        padding: 10px 12px;
        cursor: pointer;
        font-weight: 600;
        transition: border-color .15s ease, background .15s ease;
    }

    .category-checkboxes input[type="checkbox"] {
        accent-color: var(--yellow);
        width: 16px;
        height: 16px;
        margin: 0;
    }

    .category-checkboxes label:has(input:checked) {
        background: var(--light-yellow);
        border-color: var(--yellow);
    }

    .file-input-native {
        position: absolute !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 1px !important;
        height: 1px !important;
    }

    .upload-box {
        border: 1px dashed var(--border);
        border-radius: 14px;
        background: var(--white);
        padding: 16px;
        display: grid;
        gap: 4px;
        cursor: pointer;
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }

    .upload-box:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
        box-shadow: 0 10px 22px rgba(17, 17, 17, 0.06);
    }

    .upload-box span {
        background: transparent !important;
        background-color: transparent !important;
        color: inherit !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    .upload-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--yellow);
        color: var(--black);
    }

    .upload-title {
        font-weight: 700;
        color: var(--black);
        font-size: 0.9rem;
    }

    .upload-help,
    .upload-filename,
    .note {
        color: var(--muted);
        font-size: 0.82rem;
        margin: 0;
        background: transparent !important;
    }

    .media-library {
        margin-top: 0;
        border: 0;
        padding: 0;
    }

    .image-grid {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
        gap: 10px;
    }

    .image-item {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        padding: 10px;
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
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .image-item .set-main-btn,
    .image-item .delete-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 999px;
        border: 1px solid var(--black);
        background: var(--black);
        color: var(--white) !important;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
    }

    .image-item .set-main-btn.main {
        background: var(--yellow);
        border-color: var(--yellow);
        color: var(--black) !important;
    }

    .image-item .set-main-btn:hover,
    .image-item .delete-btn:hover {
        color: var(--yellow) !important;
    }

    .image-item .set-main-btn.main:hover {
        color: var(--black) !important;
    }

    .image-item input,
    .image-item textarea {
        margin-top: 6px;
    }

    .image-item .image-id {
        font-size: 0.78rem;
        color: var(--muted);
        margin-top: 6px;
        display: block;
    }

    .primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 48px;
        padding: 0 16px;
        border-radius: 12px;
        border: 1px solid var(--black);
        background: var(--black);
        color: var(--white) !important;
        font-size: 0.92rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: color .15s ease, box-shadow .15s ease, transform .12s ease;
    }

    .primary-btn:hover {
        color: var(--yellow) !important;
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(17, 17, 17, 0.14);
    }

    .bottom-submit {
        margin-top: 6px;
    }

    .error {
        margin: 0 0 16px;
        color: var(--black);
        font-weight: 700;
        border: 1px solid var(--yellow);
        background: var(--light-yellow);
        border-radius: 10px;
        padding: 10px 12px;
    }

    @media (max-width: 900px) {
        .admin-page-wrapper {
            height: calc(100vh - 56px);
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .page-header-card {
            padding: 16px;
        }

        .section-card {
            padding: 16px;
        }

        .primary-btn {
            width: 100%;
        }
    }
</style>

<div class="admin-page-wrapper">
    <div class="page-header-card">
        <div>
            <h1>Add New Post</h1>
            <p class="page-subtitle">Create and publish a new blog post</p>
        </div>
        <button type="submit" class="primary-btn" form="add-post-form">Create Post</button>
    </div>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="/admin/add-post?action=add" enctype="multipart/form-data" id="add-post-form">
        <div class="section-card">
            <h3 class="section-title">Post Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Title <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="title" id="title" placeholder="Enter post title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="slug" id="slug" placeholder="post-title-slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" required>
                </div>
                <div class="form-group span-2">
                    <label for="excerpt">Excerpt</label>
                    <textarea name="excerpt" id="excerpt" placeholder="Write a short summary"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                </div>
                <div class="form-group span-2">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" rows="10" placeholder="Write full post content"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    <p class="note">Use [image id=X] to embed non-primary images in content (IDs shown after saving).</p>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h3 class="section-title">Publishing Settings</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="native-status-select">
                        <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                    <div class="status-select-wrap" data-status-select>
                        <button type="button" class="status-display" data-status-display>
                            <?php echo ($_POST['status'] ?? 'draft') === 'published' ? 'Published' : 'Draft'; ?>
                        </button>
                        <ul class="status-options" data-status-options>
                            <li><button type="button" class="status-option <?php echo ($_POST['status'] ?? 'draft') === 'draft' ? 'is-selected' : ''; ?>" data-value="draft">Draft</button></li>
                            <li><button type="button" class="status-option <?php echo ($_POST['status'] ?? '') === 'published' ? 'is-selected' : ''; ?>" data-value="published">Published</button></li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label for="published_at">Published Date</label>
                    <input type="datetime-local" name="published_at" id="published_at" value="<?php echo htmlspecialchars($_POST['published_at'] ?? ''); ?>">
                </div>
                <div class="form-group span-2">
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
            </div>
        </div>

        <div class="section-card">
            <h3 class="section-title">Media Library</h3>
            <div class="media-library">
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="main_image">Main Image</label>
                        <input class="file-input-native" type="file" name="main_image" id="main_image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <label for="main_image" class="upload-box">
                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                            <span class="upload-title">Click to upload main image</span>
                            <span class="upload-help">JPG, PNG, GIF, WEBP up to 5MB</span>
                            <span class="upload-filename" id="main-image-filename">No file selected</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="main_image_alt_text">Main Image Alt Text</label>
                        <input type="text" name="main_image_alt_text" id="main_image_alt_text" placeholder="Describe the image" value="<?php echo htmlspecialchars($_POST['main_image_alt_text'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="main_image_caption">Main Image Caption</label>
                        <textarea name="main_image_caption" id="main_image_caption" placeholder="Image caption"><?php echo htmlspecialchars($_POST['main_image_caption'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group span-2">
                        <label for="images">Additional Images</label>
                        <input class="file-input-native" type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                        <label for="images" class="upload-box">
                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                            <span class="upload-title">Click to upload additional images</span>
                            <span class="upload-help">Select multiple images</span>
                            <span class="upload-filename" id="additional-images-filename">No files selected</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="image-grid"></div>
        </div>
        <input type="hidden" name="main_image_id" id="main_image_id" value="">

        <div class="section-card">
            <h3 class="section-title">SEO Settings</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" name="meta_title" id="meta_title" placeholder="SEO title for this post" value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="meta_keywords">Meta Keywords</label>
                    <input type="text" name="meta_keywords" id="meta_keywords" placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
                </div>
                <div class="form-group span-2">
                    <label for="meta_description">Meta Description</label>
                    <textarea name="meta_description" id="meta_description" placeholder="SEO description for search engines"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group span-2">
                    <label for="canonical_url">Canonical URL</label>
                    <input type="text" name="canonical_url" id="canonical_url" placeholder="https://example.com/post-url" value="<?php echo htmlspecialchars($_POST['canonical_url'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <button type="submit" class="primary-btn bottom-submit">Create Post</button>
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

document.querySelectorAll('[data-status-select]').forEach(function (wrap) {
    const display = wrap.querySelector('[data-status-display]');
    const options = Array.from(wrap.querySelectorAll('.status-option'));
    const nativeSelect = wrap.previousElementSibling;
    if (!display || !nativeSelect || !nativeSelect.classList.contains('native-status-select')) return;

    function setStatus(value) {
        nativeSelect.value = value;
        const selected = options.find(function (opt) { return opt.dataset.value === value; });
        display.textContent = selected ? selected.textContent.trim() : value;
        options.forEach(function (opt) {
            opt.classList.toggle('is-selected', opt.dataset.value === value);
        });
    }

    display.addEventListener('click', function (e) {
        e.stopPropagation();
        document.querySelectorAll('.status-select-wrap.is-open').forEach(function (openWrap) {
            if (openWrap !== wrap) openWrap.classList.remove('is-open');
        });
        wrap.classList.toggle('is-open');
    });

    options.forEach(function (opt) {
        opt.addEventListener('click', function () {
            setStatus(this.dataset.value || 'draft');
            wrap.classList.remove('is-open');
        });
    });

    setStatus(nativeSelect.value || 'draft');
});

document.addEventListener('click', function () {
    document.querySelectorAll('.status-select-wrap.is-open').forEach(function (wrap) {
        wrap.classList.remove('is-open');
    });
});

function updateMainImage(imageId) {
    console.log('Setting main image to: ' + imageId);
    document.querySelectorAll('.set-main-btn').forEach(btn => {
        btn.textContent = 'Set as Main';
        btn.classList.remove('main');
        btn.style.color = '#ffffff';
        btn.style.background = '#111111';
        btn.style.borderColor = '#111111';
        btn.style.fontWeight = 'normal';
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

document.getElementById('main_image').addEventListener('change', function(e) {
    let file = e.target.files[0];
    const mainNameEl = document.getElementById('main-image-filename');
    if (mainNameEl) {
        mainNameEl.textContent = file ? file.name : 'No file selected';
    }
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
    const addNameEl = document.getElementById('additional-images-filename');
    if (addNameEl) {
        if (!files || files.length === 0) {
            addNameEl.textContent = 'No files selected';
        } else if (files.length === 1) {
            addNameEl.textContent = files[0].name;
        } else {
            addNameEl.textContent = files.length + ' files selected';
        }
    }
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