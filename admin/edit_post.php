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
        -webkit-appearance: none !important;
        appearance: none !important;
        width: 16px;
        height: 16px;
        min-width: 16px;
        border: 1px solid var(--border) !important;
        border-radius: 4px !important;
        background: #fff !important;
        margin: 0;
        box-shadow: none !important;
        cursor: pointer;
        position: relative;
        transition: border-color .15s ease, background .15s ease;
        vertical-align: middle;
    }
    .category-checkboxes label input:checked {
        background: var(--yellow) !important;
        border-color: var(--yellow) !important;
    }
    .category-checkboxes label input:checked::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 5px;
        height: 9px;
        border: solid var(--black);
        border-width: 0 2px 2px 0;
        transform: translate(-50%, -58%) rotate(45deg);
    }
    .category-checkboxes label input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.25) !important;
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
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .image-item .delete-btn {
        color: var(--white) !important;
        background: var(--black) !important;
        border: 1px solid var(--black) !important;
        border-radius: 999px !important;
        padding: 3px 8px !important;
        cursor: pointer;
        font-weight: 700;
        display: inline-block;
        line-height: 1.1;
    }
    .image-item .set-main-btn {
        color: var(--white) !important;
        background: var(--black) !important;
        border: 1px solid var(--black) !important;
        border-radius: 999px !important;
        padding: 3px 8px !important;
        cursor: pointer;
        font-weight: 700;
        display: inline-block;
        line-height: 1.1;
    }
    .image-item .set-main-btn.main {
        color: var(--black) !important;
        background: var(--yellow) !important;
        border-color: var(--yellow) !important;
    }
    .image-item input,
    .image-item textarea { margin-top: 6px; }
    .image-item .image-id {
        font-size: 0.8rem;
        color: var(--muted) !important;
        margin-top: 6px;
        display: block;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }
    .image-item .actions .delete-btn:hover,
    .image-item .actions .set-main-btn:hover {
        color: var(--yellow) !important;
    }
    .image-item .actions .set-main-btn.main:hover {
        color: var(--black) !important;
    }
    .note { font-size: 0.86rem; color: var(--muted); }
    .file-input-wrap {
        display: inline-block;
        width: 100%;
    }
    .form-group input.file-input-native {
        position: absolute !important;
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 0 !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        overflow: hidden !important;
    }
    .file-input-btn {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        padding: 14px 14px;
        width: 100%;
        border-radius: 14px;
        border: 1px dashed var(--border);
        background: #f9fafb;
        color: var(--black) !important;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        line-height: 1.2;
        text-decoration: none;
    }
    .file-input-btn:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
    }
    .file-input-name {
        color: var(--muted) !important;
        font-size: 0.84rem;
        font-weight: 600;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        margin-top: 4px;
    }

    /* Upload box (match add-product.php UI) */
    .upload-box {
        display: grid;
        gap: 6px;
        padding: 20px;
        border: 1px dashed #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        cursor: pointer;
        user-select: none;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .upload-box:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
        box-shadow: 0 10px 22px rgba(17, 17, 17, 0.05);
    }
    .upload-box:hover span {
        color: var(--yellow) !important;
    }

    .upload-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--yellow);
        color: var(--black);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .upload-title {
        font-weight: 700;
        color: var(--black);
        font-size: 14px;
    }

    .upload-help {
        font-size: 12px;
        color: var(--muted);
    }

    .upload-box span {
        /* Reset the global red pill span styling inside upload box */
        background: transparent !important;
        background-color: transparent !important;
        color: var(--black) !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    .upload-filename {
        font-size: 12px;
        opacity: 0.85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .update-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        padding: 0 18px;
        border-radius: 10px;
        border: 1px solid var(--black);
        background: var(--black);
        color: var(--white);
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        box-sizing: border-box;
        text-decoration: none;
        line-height: 1;
        white-space: nowrap;
        vertical-align: middle;
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
        filter: none;
        opacity: .85;
        background: transparent;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover,
    input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
        background: var(--light-yellow);
    }

    /* ---- Post header card ---- */
    .post-header-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17,17,17,.06);
        padding: 20px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .post-header-card h1 { margin:0; font-size:1.85rem; color:var(--black); letter-spacing:-.02em; }
    .post-header-card p  { margin:4px 0 0; color:var(--muted); font-size:.92rem; }

    /* ---- SEO Section enhancements (Posts) ---- */
    .post-seo-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .post-seo-preview-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-left: 3px solid #4285f4;
        border-radius: 10px;
        padding: 14px 16px;
        margin: 0 0 16px;
        font-family: Arial, sans-serif;
    }
    .post-seo-preview-label { display:block; font-size:.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; }
    .post-seo-preview-url  { font-size:.78rem; color:#1e8e3e; margin-bottom:3px; word-break:break-all; }
    .post-seo-preview-title{ font-size:1.05rem; color:#1a0dab; margin-bottom:4px; word-break:break-word; }
    .post-seo-preview-desc { font-size:.85rem; color:#4d5156; line-height:1.4; word-break:break-word; }
    .post-seo-char-counter { font-size:.75rem; font-weight:700; }
    .post-seo-char-counter.good     { color:#16a34a; }
    .post-seo-char-counter.too-long { color:#ef4444; }
    .post-seo-char-counter.too-short{ color:#f97316; }
    .post-seo-field-wrap { display:flex; gap:8px; align-items:flex-start; }
    .post-seo-field-wrap > input,
    .post-seo-field-wrap > textarea { flex:1 1 0%; min-width:0; }
    .post-seo-auto-btn {
        flex-shrink:0; height:42px; padding:0 12px;
        background:#111; color:#fff; border:none; border-radius:10px;
        font-size:.78rem; font-weight:700; cursor:pointer;
        white-space:nowrap; transition:color .15s; align-self:flex-start;
    }
    .post-seo-auto-btn:hover { color:#facc15; }
    .post-seo-field-hint { display:block; color:#9ca3af; font-size:.76rem; margin-top:4px; }
    .post-seo-fill-btn {
        background:#facc15; color:#111; border:none; border-radius:8px;
        padding:7px 14px; font-size:.82rem; font-weight:800;
        cursor:pointer; transition:background .15s;
    }
    .post-seo-fill-btn:hover { background:#eab308; }
    .post-seo-section-head {
        display:flex; align-items:center; justify-content:space-between;
        flex-wrap:wrap; gap:10px; margin-bottom:14px;
    }
    .post-seo-section-head strong { font-size:.95rem; color:#111; }

    /* ---- Hard reset: strip admin theme's global red from every element inside the SEO box ---- */
    .post-seo-box *,
    .post-seo-preview-box * {
        background-color: transparent !important;
        background-image: none !important;
    }
    .post-seo-box label,
    .post-seo-box span,
    .post-seo-box strong,
    .post-seo-box small,
    .post-seo-box p,
    .post-seo-preview-box span,
    .post-seo-preview-box label {
        background: transparent !important;
        color: #111111 !important;
        padding: 0 !important;
        border-radius: 0 !important;
        font-size: inherit !important;
        line-height: inherit !important;
    }
    /* Restore the specific colors we actually want */
    .post-seo-preview-url   { color: #1e8e3e !important; font-size: .78rem !important; }
    .post-seo-preview-title { color: #1a0dab !important; font-size: 1.05rem !important; }
    .post-seo-preview-desc  { color: #4d5156 !important; font-size: .85rem !important; }
    .post-seo-preview-label { color: #6b7280 !important; font-size: .72rem !important; text-transform: uppercase; letter-spacing: .06em; }
    .post-seo-field-hint    { color: #9ca3af !important; font-size: .76rem !important; display: block; margin-top: 4px; }
    /* Character counters — styled with a subtle pill, no red */
    .post-seo-char-counter {
        font-size: .75rem !important;
        font-weight: 700 !important;
        padding: 2px 7px !important;
        border-radius: 6px !important;
        background: #f3f4f6 !important;
        color: #6b7280 !important;
    }
    .post-seo-char-counter.good     { background: #dcfce7 !important; color: #16a34a !important; }
    .post-seo-char-counter.too-long { background: #fee2e2 !important; color: #ef4444 !important; }
    .post-seo-char-counter.too-short{ background: #fff7ed !important; color: #f97316 !important; }
    /* Auto button — keep its own colors */
    .post-seo-auto-btn { background: #111111 !important; color: #ffffff !important; }
    .post-seo-auto-btn:hover { color: #facc15 !important; }
    /* Fill-all button */
    .post-seo-fill-btn { background: #facc15 !important; color: #111111 !important; }
    .post-seo-fill-btn:hover { background: #eab308 !important; }
    /* Restore textarea/input backgrounds */
    .post-seo-box input,
    .post-seo-box textarea { background: #ffffff !important; color: #111111 !important; }
    /* Restore preview box left-border color */
    .post-seo-preview-box { border-left: 3px solid #4285f4 !important; }
    /* Section label inside the header */
    .post-seo-section-head strong { color: #111111 !important; background: transparent !important; }
</style>

<?php
// Build post view URL using first assigned category slug if available
$_postFirstCatSlug = '';
foreach ($post_categories as $_pc) {
    if (!empty($_pc['slug'])) { $_postFirstCatSlug = $_pc['slug']; break; }
}
$_postViewUrl = 'https://www.phonesdukan.com/blog/'
    . ($_postFirstCatSlug ? $_postFirstCatSlug . '/' : '')
    . ($post['slug'] ?? '') . '/';
?>
<div class="admin-page-wrapper">

    <!-- Top header with View + Update buttons -->
    <div class="post-header-card">
        <div>
            <h1>Edit Post</h1>
            <p>Update post content, SEO, and media</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <a href="<?= htmlspecialchars($_postViewUrl) ?>" target="_blank" class="update-btn" style="text-decoration:none">View Post</a>
            <button type="submit" form="edit-post-form" class="update-btn">Update Post</button>
        </div>
    </div>

    <div class="edit-card">
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (isset($error) || isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($error ?? $_GET['error']); ?></p>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars(url('admin/edit_post.php?id=' . (int)$post['id'] . '&action=update')); ?>" enctype="multipart/form-data" id="edit-post-form">
        <div class="form-group">
            <label for="title">Title <span class="req-star" aria-hidden="true">*</span></label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug <span class="req-star" aria-hidden="true">*</span></label>
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
        <!-- ── SEO Section ─────────────────────────────────────────── -->
        <div class="post-seo-box">
            <div class="post-seo-section-head">
                <strong>SEO Information</strong>
                <button type="button" class="post-seo-fill-btn" onclick="postSeoAutoFillAll()">Auto-Fill All SEO</button>
            </div>

            <!-- Google Search Snippet Preview -->
            <div class="post-seo-preview-box">
                <!-- <span class="post-seo-preview-label">Google Search Preview</span> -->
                <div class="post-seo-preview-url" id="post_snippet_url"><?= htmlspecialchars(str_replace('https://', '', $_postViewUrl)) ?></div>
                <div class="post-seo-preview-title" id="post_snippet_title"><?= htmlspecialchars($post['meta_title'] ?? $post['title'] ?? 'Post Title') ?></div>
                <div class="post-seo-preview-desc" id="post_snippet_desc"><?= htmlspecialchars($post['meta_description'] ?? 'Post meta description will appear here...') ?></div>
            </div>

            <div class="form-group">
                <label for="meta_title" style="display:flex;justify-content:space-between;align-items:center">
                    <span>Meta Title</span>
                    <span id="meta_title_counter" class="post-seo-char-counter too-short">0/60</span>
                </label>
                <div class="post-seo-field-wrap">
                    <input type="text" name="meta_title" id="meta_title"
                        value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>"
                        placeholder="Post Title | Phones Dukan"
                        oninput="postSeoUpdateCharCounter('meta_title',60);postSeoUpdateSnippetPreview()">
                    <button type="button" class="post-seo-auto-btn" onclick="postSeoGenerateTitle()" title="Auto-generate from post title">Auto</button>
                </div>
                <span class="post-seo-field-hint">Recommended: 50–60 characters. Pattern: "{Post Title} | Phones Dukan"</span>
            </div>

            <div class="form-group">
                <label for="meta_description" style="display:flex;justify-content:space-between;align-items:center">
                    <span>Meta Description</span>
                    <span id="meta_description_counter" class="post-seo-char-counter too-short">0/160</span>
                </label>
                <div class="post-seo-field-wrap">
                    <textarea name="meta_description" id="meta_description"
                        placeholder="Brief summary of the post for Google search results..."
                        oninput="postSeoUpdateCharCounter('meta_description',160);postSeoUpdateSnippetPreview()"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
                    <button type="button" class="post-seo-auto-btn" onclick="postSeoGenerateDescription()" title="Auto-generate from excerpt" style="align-self:flex-start">Auto</button>
                </div>
                <span class="post-seo-field-hint">Recommended: 140–160 characters. Auto-generates from the Excerpt field.</span>
            </div>

            <div class="form-group" style="margin-bottom:0">
                <label for="meta_keywords">Meta Keywords <small style="color:#9ca3af;font-weight:400">(comma-separated)</small></label>
                <div class="post-seo-field-wrap">
                    <input type="text" name="meta_keywords" id="meta_keywords"
                        value="<?php echo htmlspecialchars($post['meta_keywords'] ?? ''); ?>"
                        placeholder="mobile phones pakistan, earbuds review, best price">
                    <button type="button" class="post-seo-auto-btn" onclick="postSeoGenerateKeyword()" title="Auto-generate from post title">Auto</button>
                </div>
            </div>
        </div>
        <div class="media-library">
            <h3>Media Library</h3>
            <div class="form-group">
                <label for="images">Images Upload</label>
                <div class="file-input-wrap">
                    <input class="file-input-native file-input" type="file" name="images[]" id="images" multiple accept="image/jpeg,image/webp,image/png,image/gif">
                    <label for="images" class="upload-box">
                        <span class="upload-icon" aria-hidden="true"><i class="fas fa-upload"></i></span>
                        <span class="upload-title">Click to upload images</span>
                        <span class="upload-help">PNG, JPG, WEBP up to 5MB</span>
                        <span class="upload-filename" data-for="images">No file selected</span>
                    </label>
                </div>
                <p class="note">You can select multiple images at once.</p>
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
            <div style="display:flex;gap:10px;align-items:center;margin-top:20px">
                <a href="<?= htmlspecialchars($_postViewUrl) ?>" target="_blank" class="update-btn" style="text-decoration:none">View Post</a>
                <button type="submit" class="update-btn">Update Post</button>
            </div>
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
    const fileNameEl = document.querySelector('.upload-filename[data-for="images"]');
    if (fileNameEl) {
        if (!files || files.length === 0) {
            fileNameEl.textContent = 'No file selected';
        } else if (files.length === 1) {
            fileNameEl.textContent = files[0].name;
        } else {
            fileNameEl.textContent = files.length + ' files selected';
        }
    }
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

// ===== Post SEO Auto-generation =====
function postSeoGenerateTitle() {
    const title = (document.getElementById('title')?.value || '').trim();
    if (!title) return;
    const suffix     = ' | Phones Dukan';
    const maxTitle   = 60 - suffix.length; // 44 chars available for the title part
    let titlePart    = title;
    if (titlePart.length > maxTitle) {
        // Trim at last word boundary — no ellipsis, clean cut
        titlePart = titlePart.substring(0, maxTitle).replace(/\s+\S*$/, '').trimEnd();
    }
    const field = document.getElementById('meta_title');
    if (field) {
        field.value = titlePart + suffix;
        postSeoUpdateCharCounter('meta_title', 60);
        postSeoUpdateSnippetPreview();
    }
}

function postSeoGenerateDescription() {
    const excerpt = document.getElementById('excerpt')?.value.trim() || '';
    const title   = document.getElementById('title')?.value.trim()   || '';
    if (!title && !excerpt) return;
    let desc = excerpt
        || ('Read about ' + title + ' on Phones Dukan. Expert insights, comparisons, and guides for mobile phones in Pakistan.');
    if (desc.length > 160) desc = desc.substring(0, 157) + '...';
    const field = document.getElementById('meta_description');
    if (field) { field.value = desc; postSeoUpdateCharCounter('meta_description', 160); postSeoUpdateSnippetPreview(); }
}

function postSeoGenerateKeyword() {
    const title   = (document.getElementById('title')?.value   || '').trim();
    const excerpt = (document.getElementById('excerpt')?.value || '').trim();
    if (!title) return;

    const stopWords = new Set([
        'a','an','the','and','or','but','in','on','at','to','for','of','with','by',
        'from','is','it','its','this','that','are','was','be','as','so','if','vs',
        'versus','how','why','what','when','where','which','who','your','our','their',
        'ultimate','complete','top','guide','between','about','into','over','after'
    ]);

    function extractWords(text) {
        return text.toLowerCase()
            .replace(/[–—\/]/g, ' ')
            .replace(/[^a-z0-9\s\-]/g, ' ')
            .split(/\s+/)
            .filter(w => w.length > 2 && !stopWords.has(w));
    }

    const titleWords   = extractWords(title);
    const excerptWords = extractWords(excerpt).slice(0, 20);
    const keywords     = [];

    function add(kw) {
        kw = kw.trim();
        if (kw && keywords.length < 10 && !keywords.includes(kw)) keywords.push(kw);
    }

    // 1. Cleaned full title
    add(title.toLowerCase().replace(/[^a-z0-9\s\-]/g, ' ').replace(/\s+/g, ' ').trim());

    // 2. Two-word phrases from title
    for (let i = 0; i < titleWords.length - 1; i++) {
        add(titleWords[i] + ' ' + titleWords[i + 1]);
    }

    // 3. Three-word phrases from title
    for (let i = 0; i < titleWords.length - 2; i++) {
        add(titleWords[i] + ' ' + titleWords[i + 1] + ' ' + titleWords[i + 2]);
    }

    // 4. Individual title words
    titleWords.forEach(w => add(w));

    // 5. Top title word + "pakistan"
    if (titleWords[0]) add(titleWords[0] + ' pakistan');
    if (titleWords[1]) add(titleWords[1] + ' pakistan');

    // 6. Fill remaining slots from excerpt words
    excerptWords.forEach(w => add(w));

    const field = document.getElementById('meta_keywords');
    if (field) field.value = keywords.join(', ');
}

function postSeoAutoFillAll() {
    postSeoGenerateTitle();
    postSeoGenerateDescription();
    postSeoGenerateKeyword();
}

function postSeoUpdateSnippetPreview() {
    const titleField = document.getElementById('meta_title');
    const descField  = document.getElementById('meta_description');
    const titleFallback = document.getElementById('title');
    const title = (titleField?.value.trim())   || (titleFallback?.value.trim()) || 'Post Title';
    const desc  = (descField?.value.trim())    || 'Post meta description will appear here...';
    const tEl = document.getElementById('post_snippet_title');
    const dEl = document.getElementById('post_snippet_desc');
    if (tEl) tEl.textContent = title.length > 60  ? title.substring(0, 60)  + '…' : title;
    if (dEl) dEl.textContent = desc.length  > 160 ? desc.substring(0, 160)  + '…' : desc;
}

function postSeoUpdateCharCounter(fieldId, limit) {
    const field   = document.getElementById(fieldId);
    const counter = document.getElementById(fieldId + '_counter');
    if (!field || !counter) return;
    const len = field.value.length;
    counter.textContent = len + '/' + limit;
    counter.className = 'post-seo-char-counter ';
    if (len === 0 || len < Math.floor(limit * 0.6)) counter.className += 'too-short';
    else if (len > limit)                            counter.className += 'too-long';
    else                                             counter.className += 'good';
}

document.addEventListener('DOMContentLoaded', function() {
    postSeoUpdateCharCounter('meta_title', 60);
    postSeoUpdateCharCounter('meta_description', 160);
    postSeoUpdateSnippetPreview();
    document.getElementById('title')?.addEventListener('input', postSeoUpdateSnippetPreview);
    document.getElementById('excerpt')?.addEventListener('input', postSeoUpdateSnippetPreview);
});
</script>