<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';

if (isset($_SESSION['message'])) {
    $messageType = $_SESSION['message_type'] ?? 'success';
    $messageClass = $messageType === 'success' ? 'success-message' : 'error-message';
    echo '<div class="' . $messageClass . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

require_once dirname(__DIR__, 1) . '/app/Controllers/EditProductController.php';
$controller = new ProductController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remove_attribute_action'])) {
    error_log('POST data: ' . print_r($_POST, true));
    $productData = [
        'product_name' => $_POST['product_name'] ?? '',
        'product_slug' => $_POST['product_slug'] ?? '',
        'product_description' => $_POST['product_description'] ?? '',
        'short_description' => $_POST['short_description'] ?? '',
        'product_status' => $_POST['product_status'] ?? '0',
        'stock_quantity' => $_POST['stock_quantity'] ?? 0,
        'regular_price' => $_POST['regular_price'] ?? 0,
        'sale_price' => $_POST['sale_price'] ?? null,
        'product_sku' => $_POST['product_sku'] ?? null,
        'weight_kg' => $_POST['weight_kg'] ?? null,
        'length_cm' => $_POST['length_cm'] ?? null,
        'width_cm' => $_POST['width_cm'] ?? null,
        'height_cm' => $_POST['height_cm'] ?? null,
        'tax_class' => $_POST['tax_class'] ?? null,
        'product_tag' => $_POST['product_tag'] ?? null,
        'category_id' => $_POST['category_id'] ?? null,
        'brand_id' => $_POST['brand_id'] ?? null,
        'is_b2b_available' => isset($_POST['is_b2b_available']) ? 1 : 0,
        'b2b_regular_price' => $_POST['b2b_regular_price'] ?? null,
    ];

    $seoData = [
        'focus_keyword' => $_POST['focus_keyword'] ?? '',
        'seo_title' => $_POST['seo_title'] ?? '',
        'seo_description' => $_POST['seo_description'] ?? ''
    ];

    $primaryImage = $_FILES['primary_image'] ?? null;
    $galleryImages = $_FILES['gallery_images'] ?? null;
    $imageMetadata = [
        'alt_text' => $_POST['alt_text'] ?? [],
        'title' => $_POST['title'] ?? [],
        'description' => $_POST['description'] ?? [],
        'caption' => $_POST['caption'] ?? []
    ];
    $primaryImageId = $_POST['primary_image_id'] ?? null;

    $controller->updateProduct($_GET['id'], $productData, $seoData, $primaryImage, $galleryImages, $imageMetadata, $primaryImageId);
}

$data = $controller->getProductDetails($_GET['id']);
if (!isset($data['product']) || !$data['product']) {
    echo 'Product not found.';
    exit;
}

$product = $data['product'];
$categories = $data['categories'];
$brands = $data['brands'];
$seoData = $controller->getSeoData($product['product_id']);
$productImages = $controller->getProductImages($product['product_id']);
$productAttributes = $controller->getAssignedProductAttributes($product['product_id']);
$attributeValues = $data['attributeValues'];
$allAttributes = $data['productAttributes'];

function epImageCandidates($rawPath)
{
    $raw = trim((string) $rawPath);
    if ($raw === '') {
        return [];
    }

    $normalized = str_replace('\\', '/', $raw);
    $candidates = [$normalized];

    if (preg_match('/^https?:\/\//i', $normalized)) {
        return array_values(array_unique($candidates));
    }

    $trimmed = ltrim($normalized, './');
    $trimmed = ltrim($trimmed, '/');
    if ($trimmed !== '') {
        $candidates[] = '/' . $trimmed;
        $candidates[] = '/phonesdukan/' . $trimmed;
        $candidates[] = '/public/' . $trimmed;
        $candidates[] = '/phonesdukan/public/' . $trimmed;

        if (strpos($trimmed, 'uploads/') !== false) {
            $uploadsPart = substr($trimmed, strpos($trimmed, 'uploads/'));
            $candidates[] = '/' . $uploadsPart;
            $candidates[] = '/phonesdukan/' . $uploadsPart;
            $candidates[] = '/public/' . $uploadsPart;
            $candidates[] = '/phonesdukan/public/' . $uploadsPart;
        }
    }

    return array_values(array_unique(array_filter($candidates)));
}
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

    .ep-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px;
        background: var(--bg);
    }

    .ep-wrap span,
    .ep-wrap strong,
    .ep-wrap small,
    .ep-wrap p {
        background: transparent !important;
    }

    .ep-header,
    .ep-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
    }

    .ep-header {
        padding: 20px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .ep-header h2 {
        margin: 0;
        color: var(--black);
        font-size: 1.85rem;
        letter-spacing: -0.02em;
    }

    .ep-header p {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 0.92rem;
    }

    .ep-card {
        padding: 24px;
        margin-bottom: 20px;
    }

    .ep-card h3 {
        margin: 0 0 14px;
        color: var(--black);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .ep-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .ep-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .ep-field {
        margin-bottom: 0;
    }

    .ep-field.full {
        grid-column: 1 / -1;
    }

    .ep-field label {
        display: block;
        margin-bottom: 6px;
        color: var(--black);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .ep-field input,
    .ep-field select,
    .ep-field textarea {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        color: var(--black);
        font-family: inherit;
        font-size: 0.92rem;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ep-field input,
    .ep-field select {
        height: 48px;
        padding: 0 14px;
    }

    .ep-field textarea {
        min-height: 110px;
        padding: 14px;
        resize: vertical;
    }

    .ep-field input:focus,
    .ep-field select:focus,
    .ep-field textarea:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .ep-field select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 38px;
        background:
            #fff
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='10' viewBox='0 0 14 10'%3E%3Cpath d='M2 2l5 6 5-6' stroke='%23111111' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")
            no-repeat right 14px center;
    }

    .ep-native-status-select {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
        overflow: hidden !important;
    }

    .ep-status-select {
        position: relative;
    }

    .ep-status-display {
        width: 100%;
        height: 48px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        color: var(--black);
        padding: 0 40px 0 14px;
        text-align: left;
        font-size: 0.92rem;
        font-weight: 600;
        cursor: pointer;
        position: relative;
    }

    .ep-status-display::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        width: 12px;
        height: 8px;
        transform: translateY(-50%);
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='10' viewBox='0 0 14 10'%3E%3Cpath d='M2 2l5 6 5-6' stroke='%23111111' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat center center;
    }

    .ep-status-display:focus-visible {
        outline: none;
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .ep-status-options {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: 0 12px 24px rgba(17, 17, 17, 0.12);
        z-index: 20;
        padding: 6px;
        max-height: 220px;
        overflow: auto;
        display: none;
    }

    .ep-status-select.is-open .ep-status-options {
        display: block;
    }

    .ep-status-option {
        width: 100%;
        border: none;
        background: #fff;
        color: var(--black);
        border-radius: 8px;
        padding: 9px 10px;
        text-align: left;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .ep-status-option:hover,
    .ep-status-option.is-active {
        background: var(--light-yellow);
        color: var(--black);
    }

    .ep-check-row {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 48px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #f9fafb;
        padding: 0 12px;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--black);
    }

    .ep-check-row input[type="checkbox"],
    .ep-images-wrap input[type="radio"] {
        accent-color: var(--yellow);
    }

    .ep-checkbox {
        position: absolute !important;
        opacity: 0 !important;
        width: 1px !important;
        height: 1px !important;
        margin: 0 !important;
    }

    .ep-check-custom {
        width: 24px;
        height: 24px;
        min-width: 24px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .ep-checkbox:checked + .ep-check-custom {
        border-color: var(--yellow);
        background-color: var(--yellow);
    }

    .ep-check-custom::after {
        content: "";
        width: 14px;
        height: 14px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath d='M3 7.2l2.2 2.2L11 3.8' stroke='%23111111' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat center center;
        opacity: 0;
    }

    .ep-checkbox:checked + .ep-check-custom::after {
        opacity: 1;
    }

    .ep-checkbox:focus-visible + .ep-check-custom {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .ep-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 44px;
        padding: 0 16px;
        border: 1px solid var(--black);
        border-radius: 12px;
        background: var(--black);
        color: #fff !important;
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none !important;
        cursor: pointer;
        transition: color .15s ease, box-shadow .15s ease, transform .12s ease;
    }

    .ep-btn:hover {
        color: var(--yellow) !important;
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(17, 17, 17, 0.14);
    }

    .ep-upload-input {
        position: absolute !important;
        opacity: 0 !important;
        width: 1px !important;
        height: 1px !important;
        pointer-events: none !important;
    }

    .ep-upload-box {
        border: 1px dashed var(--border);
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
        cursor: pointer;
        transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
    }

    .ep-upload-box:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
        box-shadow: 0 10px 22px rgba(17, 17, 17, 0.06);
    }

    .ep-upload-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: var(--yellow);
        color: var(--black);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
    }

    .ep-upload-title {
        font-weight: 800;
        color: var(--black);
        font-size: 0.9rem;
    }

    .ep-upload-help,
    .ep-upload-name {
        color: var(--muted);
        font-size: 0.82rem;
    }

    .ep-upload-box span,
    .ep-upload-box strong,
    .ep-upload-box small {
        background: transparent !important;
        color: var(--black) !important;
    }

    .ep-images-wrap {
        display: grid;
        gap: 12px;
    }

    .ep-image-row {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        padding: 12px;
        display: grid;
        grid-template-columns: 96px 1fr auto;
        gap: 12px;
        align-items: start;
    }

    .ep-thumb {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        border: 1px solid var(--border);
        object-fit: cover;
        background: #f8fafc;
    }

    .ep-thumb-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        border: 1px solid var(--border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--light-yellow);
        color: var(--black);
        font-size: 0.78rem;
        font-weight: 800;
    }

    .ep-image-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .ep-image-fields .ep-field.full {
        grid-column: 1 / -1;
    }

    .ep-image-actions {
        display: grid;
        gap: 8px;
        min-width: 130px;
    }

    #product-attributes {
        display: grid;
        gap: 12px;
        margin-bottom: 12px;
    }

    .attribute {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px;
        background: #fff;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
    }

    .attribute label {
        display: block;
        margin-bottom: 6px;
        color: var(--black);
        font-size: 0.85rem;
        font-weight: 700;
    }

    .attribute input,
    .attribute select {
        width: 100%;
        height: 44px;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0 12px;
        font-family: inherit;
        outline: none;
        background: #fff;
    }

    .attribute select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 36px;
        background:
            #fff
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='10' viewBox='0 0 14 10'%3E%3Cpath d='M2 2l5 6 5-6' stroke='%23111111' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")
            no-repeat right 12px center;
    }

    .attribute input:focus,
    .attribute select:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .attribute .remove-attribute-btn {
        grid-column: 1 / -1;
    }

    @media (max-width: 960px) {
        .ep-grid,
        .ep-grid-3,
        .ep-image-fields,
        .attribute {
            grid-template-columns: 1fr;
        }
        .ep-image-row {
            grid-template-columns: 1fr;
        }
        .ep-image-actions {
            min-width: 0;
        }
        .ep-btn {
            width: 100%;
        }
    }
</style>

<div class="ep-wrap">
    <div class="ep-header">
        <div>
            <h2>Edit Product</h2>
            <p>Update product information, pricing, images, SEO, and attributes</p>
        </div>
        <button type="submit" class="ep-btn" form="product-form">Update Product</button>
    </div>

    <form method="POST" enctype="multipart/form-data" action="/admin/edit-product.php?id=<?= $product['product_id'] ?>" id="product-form">
        <div class="ep-card">
            <h3>Basic Information</h3>
            <div class="ep-grid">
                <div class="ep-field">
                    <label>Product Name</label>
                    <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                </div>
                <div class="ep-field">
                    <label>Product Slug</label>
                    <input type="text" name="product_slug" value="<?= htmlspecialchars($product['product_slug']) ?>" required>
                </div>
                <div class="ep-field">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $row): ?>
                            <option value="<?= $row['category_id'] ?>" <?= $product['category_id'] == $row['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ep-field">
                    <label>Brand</label>
                    <select name="brand_id" required>
                        <?php foreach ($brands as $row): ?>
                            <option value="<?= $row['brand_id'] ?>" <?= $product['brand_id'] == $row['brand_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['brand_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ep-field">
                    <label>Product Status</label>
                    <div class="ep-status-select" data-status-select>
                        <select name="product_status" id="product_status" class="ep-native-status-select" required>
                            <option value="1" <?= $product['product_status'] == '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $product['product_status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <button type="button" class="ep-status-display" data-status-display>Active</button>
                        <div class="ep-status-options">
                            <button type="button" class="ep-status-option" data-value="1">Active</button>
                            <button type="button" class="ep-status-option" data-value="0">Inactive</button>
                        </div>
                    </div>
                </div>
                <div class="ep-field">
                    <label>B2B Available</label>
                    <label class="ep-check-row">
                        <input class="ep-checkbox" type="checkbox" name="is_b2b_available" value="1" <?= $product['is_b2b_available'] ? 'checked' : '' ?>>
                        <span class="ep-check-custom" aria-hidden="true"></span>
                        Enable B2B pricing
                    </label>
                </div>
                <div class="ep-field" id="b2b-price-section" style="display: <?= $product['is_b2b_available'] ? 'block' : 'none' ?>;">
                    <label>B2B Regular Price</label>
                    <input type="number" name="b2b_regular_price" value="<?= htmlspecialchars($product['b2b_regular_price'] ?? '') ?>" step="0.01">
                </div>
            </div>
        </div>

        <div class="ep-card">
            <h3>Description</h3>
            <div class="ep-grid">
                <div class="ep-field full">
                    <label>Product Description</label>
                    <textarea name="product_description" required><?= htmlspecialchars($product['product_description']) ?></textarea>
                </div>
                <div class="ep-field full">
                    <label>Short Description</label>
                    <textarea name="short_description"><?= htmlspecialchars($product['short_description']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="ep-card">
            <h3>Pricing & Inventory</h3>
            <div class="ep-grid">
                <div class="ep-field">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?>" required>
                </div>
                <div class="ep-field">
                    <label>Regular Price</label>
                    <input type="number" name="regular_price" value="<?= $product['regular_price'] ?>" required>
                </div>
                <div class="ep-field">
                    <label>Sale Price</label>
                    <input type="number" name="sale_price" value="<?= $product['sale_price'] ?>">
                </div>
                <div class="ep-field">
                    <label>Product SKU</label>
                    <input type="text" name="product_sku" value="<?= htmlspecialchars($product['product_sku'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="ep-card">
            <h3>Product Details</h3>
            <div class="ep-grid-3">
                <div class="ep-field">
                    <label>Weight (kg)</label>
                    <input type="text" name="weight_kg" value="<?= htmlspecialchars($product['weight_kg'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>Length (cm)</label>
                    <input type="text" name="length_cm" placeholder="Length" value="<?= htmlspecialchars($product['length_cm'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>Width (cm)</label>
                    <input type="text" name="width_cm" placeholder="Width" value="<?= htmlspecialchars($product['width_cm'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>Height (cm)</label>
                    <input type="text" name="height_cm" placeholder="Height" value="<?= htmlspecialchars($product['height_cm'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>Tax Class</label>
                    <input type="text" name="tax_class" value="<?= htmlspecialchars($product['tax_class'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>Product Tags</label>
                    <input type="text" name="product_tag" value="<?= htmlspecialchars($product['product_tag'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="ep-card">
            <h3>SEO Details</h3>
            <div class="ep-grid">
                <div class="ep-field">
                    <label>Focus Keyword</label>
                    <input type="text" name="focus_keyword" value="<?= htmlspecialchars($seoData['focus_keyword'] ?? '') ?>">
                </div>
                <div class="ep-field">
                    <label>SEO Title</label>
                    <input type="text" name="seo_title" value="<?= htmlspecialchars($seoData['seo_title'] ?? '') ?>">
                </div>
                <div class="ep-field full">
                    <label>SEO Description</label>
                    <textarea name="seo_description"><?= htmlspecialchars($seoData['seo_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="ep-card">
            <h3>Product Images</h3>
            <div class="ep-field full">
                <input class="ep-upload-input" type="file" name="primary_image[]" id="primary_image" multiple>
                <label for="primary_image" class="ep-upload-box">
                    <strong class="ep-upload-icon">+</strong>
                    <strong class="ep-upload-title">Click to upload product images</strong>
                    <small class="ep-upload-help">Upload primary and gallery images</small>
                    <small class="ep-upload-name" id="ep-upload-name">No files selected</small>
                </label>
            </div>

            <h3 style="margin-top: 14px;">Existing Images</h3>
            <?php if (!empty($productImages)): ?>
                <div class="ep-images-wrap">
                    <?php foreach ($productImages as $image): ?>
                        <div class="ep-image-row">
                            <div>
                                <?php if (!empty($image['image_url'])): ?>
                                    <?php $imgCandidates = epImageCandidates($image['image_url']); ?>
                                    <img
                                        class="ep-thumb js-ep-thumb"
                                        src="<?= htmlspecialchars($imgCandidates[0] ?? $image['image_url']) ?>"
                                        data-candidates="<?= htmlspecialchars(json_encode($imgCandidates), ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($image['alt_text'] ?? '') ?>"
                                        onerror="epHandleImageFallback(this);"
                                    >
                                    <span class="ep-thumb-placeholder" style="display:none;">No image</span>
                                <?php else: ?>
                                    <span class="ep-thumb-placeholder">No image</span>
                                <?php endif; ?>
                            </div>
                            <div class="ep-image-fields">
                                <div class="ep-field">
                                    <label>Alt Text</label>
                                    <input type="text" name="alt_text[<?= $image['image_id'] ?>]" value="<?= htmlspecialchars($image['alt_text'] ?? '') ?>">
                                </div>
                                <div class="ep-field">
                                    <label>Title</label>
                                    <input type="text" name="title[<?= $image['image_id'] ?>]" value="<?= htmlspecialchars($image['title'] ?? '') ?>">
                                </div>
                                <div class="ep-field full">
                                    <label>Description</label>
                                    <textarea name="description[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['description'] ?? '') ?></textarea>
                                </div>
                                <div class="ep-field full">
                                    <label>Caption</label>
                                    <textarea name="caption[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['caption'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="ep-image-actions">
                                <label class="ep-check-row" style="min-height:44px;">
                                    <input type="radio" name="primary_image_id" value="<?= $image['image_id'] ?>" <?= $image['is_primary'] ? 'checked' : '' ?>>
                                    Primary
                                </label>
                                <button class="ep-btn" type="button" onclick="confirmRemoval(<?= $image['image_id'] ?>)">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--muted);">No images available for this product.</p>
            <?php endif; ?>
        </div>

        <div class="ep-card">
            <h3>Product Attributes</h3>
            <div id="product-attributes">
                <?php foreach ($productAttributes as $index => $attribute): ?>
                    <div class="attribute" data-attribute-id="<?= $attribute['attribute_id'] ?>" data-value-id="<?= $attribute['value_id'] ?>">
                        <div>
                            <label><?= htmlspecialchars($attribute['attribute_name']) ?></label>
                            <select name="attributes[<?= $index ?>][attribute_id]" required>
                                <?php foreach ($allAttributes as $attr): ?>
                                    <option value="<?= $attr['attribute_id'] ?>" <?= $attribute['attribute_id'] == $attr['attribute_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($attr['attribute_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Value</label>
                            <select name="attributes[<?= $index ?>][value_id]" required>
                                <?php if (isset($attributeValues[$attribute['attribute_id']])): ?>
                                    <?php foreach ($attributeValues[$attribute['attribute_id']] as $value): ?>
                                        <option value="<?= $value['value_id'] ?>" <?= $value['value_id'] == $attribute['value_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($value['value']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No values available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label>Regular Price</label>
                            <input type="number" name="attributes[<?= $index ?>][regular_price]" value="<?= htmlspecialchars($attribute['regular_price'] ?? '') ?>" placeholder="Regular Price" required>
                        </div>
                        <div>
                            <label>Sale Price</label>
                            <input type="number" name="attributes[<?= $index ?>][sale_price]" value="<?= htmlspecialchars($attribute['sale_price'] ?? '') ?>" placeholder="Sale Price">
                        </div>
                        <button type="button" class="ep-btn remove-attribute-btn" data-index="<?= $index ?>" data-attribute-id="<?= $attribute['attribute_id'] ?>" data-value-id="<?= $attribute['value_id'] ?>">Remove Attribute Value</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="ep-btn add-attribute" type="button" onclick="addNewAttribute()">Add New Attribute</button>
        </div>

        <button type="submit" class="ep-btn">Update Product</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners to existing remove buttons
    document.querySelectorAll('.remove-attribute-btn').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const attributeId = this.getAttribute('data-attribute-id');
            const valueId = this.getAttribute('data-value-id');
            removeAttribute(index, this.closest('.attribute'), attributeId, valueId);
        });
    });

    // B2B checkbox toggle
    const b2bCheckbox = document.querySelector('input[name="is_b2b_available"]');
    if (b2bCheckbox) {
        b2bCheckbox.addEventListener('change', function() {
            const b2bSection = document.getElementById('b2b-price-section');
            if (b2bSection) {
                b2bSection.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    const uploadInput = document.getElementById('primary_image');
    const uploadNameEl = document.getElementById('ep-upload-name');
    if (uploadInput && uploadNameEl) {
        uploadInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (!files || files.length === 0) {
                uploadNameEl.textContent = 'No files selected';
            } else if (files.length === 1) {
                uploadNameEl.textContent = files[0].name;
            } else {
                uploadNameEl.textContent = files.length + ' files selected';
            }
        });
    }

    initStatusSelect();
});

function initStatusSelect() {
    const wrap = document.querySelector('[data-status-select]');
    if (!wrap) return;

    const select = wrap.querySelector('#product_status');
    const display = wrap.querySelector('[data-status-display]');
    const options = Array.from(wrap.querySelectorAll('.ep-status-option'));
    if (!select || !display || !options.length) return;

    function sync() {
        const selected = options.find((btn) => btn.dataset.value === String(select.value));
        display.textContent = selected ? selected.textContent.trim() : (select.options[select.selectedIndex]?.text || 'Select');
        options.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.value === String(select.value)));
    }

    display.addEventListener('click', function (e) {
        e.stopPropagation();
        wrap.classList.toggle('is-open');
    });

    options.forEach((btn) => {
        btn.addEventListener('click', function () {
            select.value = this.dataset.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            wrap.classList.remove('is-open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            wrap.classList.remove('is-open');
        }
    });

    sync();
}

function epHandleImageFallback(img) {
    let candidates = [];
    try {
        candidates = JSON.parse(img.getAttribute('data-candidates') || '[]');
    } catch (e) {
        candidates = [];
    }

    const currentSrc = img.getAttribute('src');
    const currentIndex = candidates.indexOf(currentSrc);
    const nextIndex = currentIndex + 1;
    if (nextIndex < candidates.length) {
        img.setAttribute('src', candidates[nextIndex]);
        return;
    }

    img.style.display = 'none';
    if (img.nextElementSibling) {
        img.nextElementSibling.style.display = 'inline-flex';
    }
}

function confirmRemoval(imageId) {
    if (confirm('Are you sure you want to remove this image?')) {
        let form = document.getElementById('product-form');
        let removeInput = document.createElement('input');
        removeInput.type = 'hidden';
        removeInput.name = `remove_image[${imageId}]`;
        removeInput.value = '1';
        form.appendChild(removeInput);
        form.submit();
    }
}

let attributeIndex = <?= count($productAttributes) ?>;

function addNewAttribute() {
    let attributes = <?= json_encode($allAttributes) ?>;
    let newAttributeDiv = document.createElement('div');
    newAttributeDiv.classList.add('attribute');

    let attributeOptions = attributes.map(attr => 
        `<option value="${attr.attribute_id}">${attr.attribute_name}</option>`
    ).join('');

    newAttributeDiv.innerHTML = `
        <div>
            <label>Attribute</label>
            <select name="attributes[${attributeIndex}][attribute_id]" onchange="loadAttributeValues(this)" required>
                <option value="">Select Attribute</option>
                ${attributeOptions}
            </select>
        </div>
        <div>
            <label>Value</label>
            <select name="attributes[${attributeIndex}][value_id]" required>
                <option value="">Select Value</option>
            </select>
        </div>
        <div>
            <label>Regular Price</label>
            <input type="number" name="attributes[${attributeIndex}][regular_price]" required>
        </div>
        <div>
            <label>Sale Price</label>
            <input type="number" name="attributes[${attributeIndex}][sale_price]">
        </div>
        <button type="button" class="ep-btn remove-attribute-btn" data-index="${attributeIndex}" data-attribute-id="0" data-value-id="0">Remove Attribute Value</button>
    `;
    document.getElementById('product-attributes').appendChild(newAttributeDiv);
    // Attach event listener to the new button
    const newButton = newAttributeDiv.querySelector('.remove-attribute-btn');
    newButton.addEventListener('click', function() {
        const index = this.getAttribute('data-index');
        const attributeId = this.getAttribute('data-attribute-id');
        const valueId = this.getAttribute('data-value-id');
        removeAttribute(index, newAttributeDiv, attributeId, valueId);
    });

    attributeIndex++;
}

function removeAttribute(index, element, attributeId, valueId) {
    if (!confirm('Are you sure you want to remove this attribute value?')) {
        return;
    }
    if (element) {
        element.remove();
    }
    if (attributeId && attributeId !== '0' && valueId && valueId !== '0') {
        fetch('/admin/remove-attribute.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=<?= $product['product_id'] ?>&attribute_id=${attributeId}&value_id=${valueId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    } else {
        window.location.reload();
    }
}

async function loadAttributeValues(selectElement) {
    let attributeId = selectElement.value;
    let valueSelect = selectElement.parentElement.querySelector('select[name$="[value_id]"]');
    valueSelect.innerHTML = '<option value="">Select Value</option>';

    if (attributeId) {
        try {
            let response = await fetch(`/admin/get-attribute-values.php?attribute_id=${attributeId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            let values = await response.json();
            values.forEach(value => {
                let option = document.createElement('option');
                option.value = value.value_id;
                option.textContent = value.value;
                valueSelect.appendChild(option);
            });
        } catch (error) {
            // Silent error handling
        }
    }
}
</script>