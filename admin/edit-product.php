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

// AI SEO CSS
echo '<link rel="stylesheet" href="css/ai-seo.css?v=2.8">';
echo '<link rel="stylesheet" href="css/product-media.css?v=1.1">';

$flashMessage = null;
$flashType = 'success';
if (isset($_SESSION['message'])) {
    $flashMessage = (string) $_SESSION['message'];
    $flashType = ($_SESSION['message_type'] ?? 'success') === 'error' ? 'error' : 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

require_once dirname(__DIR__, 1) . '/app/Controllers/EditProductController.php';
$controller = new ProductController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remove_attribute_action'])) {
    error_log('POST data: ' . print_r($_POST, true));
    $productData = [
        'product_name' => $_POST['product_name'] ?? '',
        'product_slug' => trim(preg_replace('/-+/', '-', str_replace(' ', '-', trim($_POST['product_slug'] ?? ''))), '-'),
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
        'prepaid_discount_amount' => isset($_POST['prepaid_discount_amount']) && is_numeric($_POST['prepaid_discount_amount']) ? max(0, (float)$_POST['prepaid_discount_amount']) : 0,
    ];

    $seoData = [
        'focus_keyword'      => $_POST['focus_keyword'] ?? '',
        'seo_title'          => $_POST['seo_title'] ?? '',
        'seo_description'    => $_POST['seo_description'] ?? '',
        'secondary_keywords' => $_POST['secondary_keywords'] ?? '',
        'canonical_url'      => $_POST['canonical_url'] ?? '',
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
require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Services/ProductMediaService.php';
$mediaService = new ProductMediaService((new Database())->getConnection());
$productVideo = $mediaService->getModel()->getProductVideo($product['product_id']);
$galleryOrderItems = $mediaService->getModel()->getGalleryOrderItems($product['product_id']);
$productAttributes = $controller->getAssignedProductAttributes($product['product_id']);
$attributeValues = $data['attributeValues'];
$allAttributes = $data['productAttributes'];

function epImageCandidates($rawPath)
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
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
        $candidates[] = ($basePath === '' ? '' : $basePath) . '/' . $trimmed;
        $candidates[] = '/public/' . $trimmed;
        $candidates[] = ($basePath === '' ? '' : $basePath) . '/public/' . $trimmed;

        if (strpos($trimmed, 'uploads/') !== false) {
            $uploadsPart = substr($trimmed, strpos($trimmed, 'uploads/'));
            $candidates[] = '/' . $uploadsPart;
            $candidates[] = ($basePath === '' ? '' : $basePath) . '/' . $uploadsPart;
            $candidates[] = '/public/' . $uploadsPart;
            $candidates[] = ($basePath === '' ? '' : $basePath) . '/public/' . $uploadsPart;
        }
    }

    return array_values(array_unique(array_filter($candidates)));
}

// Load variation data for this product
require_once dirname(__DIR__, 1) . '/app/Models/VariationModel.php';
$vModel = new VariationModel();
$allVarTypes = $vModel->getVariationTypesWithValues();
$existingVariations = $vModel->getProductVariationsForFrontend($product['product_id']);
$currentProductType = $product['product_type'] ?? 'simple';
?>

<style>
/* ---- Variation Builder (edit page) ---- */

/* Global reset: override admin theme's span/label/p color inside the variation section */
#epVariationBuilderWrap,
#epVariationBuilderWrap * {
    background-color: transparent;
    color: #111111;
}
#epVariationBuilderWrap span,
#epVariationBuilderWrap label,
#epVariationBuilderWrap p,
#epVariationBuilderWrap th,
#epVariationBuilderWrap td {
    color: #111111 !important;
    background: transparent !important;
    background-color: transparent !important;
}
#epVariationBuilderWrap .vb-step-title { color: #111111 !important; }
#epVariationBuilderWrap .vb-type-badge { color: #6b7280 !important; }

.vb-toggle-wrap { position:relative; display:inline-block; }
.vb-toggle-track { width:46px; height:24px; border-radius:999px; background:#e5e7eb; transition:background .2s; cursor:pointer; }
input#enableVariationsEP:checked + .vb-toggle-track { background:#facc15; }
.vb-toggle-thumb { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.18); transition:left .2s; }
input#enableVariationsEP:checked ~ .vb-toggle-track .vb-toggle-thumb,
input#enableVariationsEP:checked + .vb-toggle-track .vb-toggle-thumb { left:25px; }
.vb-step { border:1px solid #e5e7eb; border-radius:12px; margin-bottom:16px; overflow:hidden; }
.vb-step-title { padding:12px 16px; background:#f8fafc !important; font-weight:800; font-size:.9rem; color:#111 !important; border-bottom:1px solid #e5e7eb; }
.vb-step-body { padding:16px; background:#fff !important; }
.vb-type-grid { display:flex; flex-wrap:wrap; gap:10px; }
.vb-type-check {
    display:inline-flex; align-items:center; gap:8px; cursor:pointer;
    padding:8px 14px; border:1.5px solid #e5e7eb; border-radius:10px;
    font-size:.88rem; font-weight:700; color:#111 !important;
    background:#fff !important; transition:border-color .15s,background .15s;
    user-select:none;
}
.vb-type-check:has(input:checked) { border-color:#facc15 !important; background:#fffbeb !important; }
.vb-type-check input { accent-color:#facc15; width:15px; height:15px; cursor:pointer; flex-shrink:0; }
.vb-type-check span { color:#111 !important; background:transparent !important; }
.vb-type-badge { font-size:.72rem; color:#6b7280 !important; margin-left:4px; }
.vb-value-pill {
    display:inline-flex; align-items:center; gap:6px; cursor:pointer;
    padding:6px 12px; border:1.5px solid #e5e7eb; border-radius:999px;
    font-size:.85rem; font-weight:700; color:#111 !important;
    background:#fff !important; transition:border-color .15s,background .15s;
    user-select:none;
}
.vb-value-pill:has(input:checked) { border-color:#facc15 !important; background:#fffbeb !important; }
.vb-value-pill input { display:none; }
.vb-value-pill span { color:#111 !important; background:transparent !important; }
.vb-swatch { width:16px; height:16px; border-radius:50%; border:1px solid rgba(0,0,0,.12); flex-shrink:0; }
.vb-btn-ep {
    display:inline-flex; align-items:center; height:40px; padding:0 16px;
    border:1px solid #111; border-radius:10px; background:#111 !important;
    color:#fff !important; font-size:.86rem; font-weight:700; cursor:pointer;
    transition:color .12s,transform .12s;
}
.vb-btn-ep:hover { color:#facc15 !important; transform:translateY(-1px); }
.vb-btn-ep-outline { background:#fff !important; color:#111 !important; border-color:#e5e7eb; }
.vb-btn-ep-outline:hover { border-color:#facc15; color:#111 !important; background:#fffbeb !important; transform:none; }
.vb-btn-ep-sm { height:30px; padding:0 10px; font-size:.78rem; border-radius:8px; }
.vb-btn-ep-del { background:#fff !important; color:#111 !important; border-color:#e5e7eb; }
.vb-btn-ep-del:hover { color:#ef4444 !important; border-color:#ef4444; background:#fff0f0 !important; transform:none; }
.vb-table-ep { width:100%; border-collapse:collapse; font-size:.85rem; }
.vb-table-ep th { background:#f8fafc !important; color:#111 !important; font-weight:800; padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.vb-table-ep td { padding:8px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; color:#111 !important; background:transparent !important; }
.vb-table-ep tr:last-child td { border-bottom:0; }
.vb-input-ep {
    height:36px; border:1px solid #e5e7eb; border-radius:8px; padding:0 10px;
    font-size:.84rem; color:#111 !important; background:#fff !important;
    outline:none; width:100%; transition:border-color .15s;
}
.vb-input-ep:focus { border-color:#facc15; }
select.vb-input-ep { cursor:pointer; }

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

    html, body {
        height: auto !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }

    .content,
    .dashboard-content,
    .admin-page-wrapper {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }

    .ep-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px;
        background: var(--bg);
        overflow: visible !important;
    }

    .ep-wrap span,
    .ep-wrap strong,
    .ep-wrap small,
    .ep-wrap p {
        background: transparent !important;
        color: inherit !important;
    }

    .ep-toast {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 4000;
        min-width: 260px;
        max-width: 380px;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.9rem;
        font-weight: 700;
        border: 1px solid var(--border);
        box-shadow: 0 16px 30px rgba(17, 17, 17, 0.15);
        opacity: 0;
        transform: translateY(10px);
        pointer-events: none;
        transition: opacity .2s ease, transform .2s ease;
    }

    .ep-toast.is-show {
        opacity: 1;
        transform: translateY(0);
    }

    .ep-toast-success {
        background: #111111;
        color: #ffffff;
        border-color: #111111;
    }

    .ep-toast-error {
        background: #fffbeb;
        color: #111111;
        border-color: #facc15;
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

    .ep-native-dropdown {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
        overflow: hidden !important;
    }

    .ep-dropdown {
        position: relative;
    }

    .ep-dropdown-display {
        width: 100%;
        height: 52px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: #fff;
        color: var(--black);
        padding: 0 44px 0 16px;
        text-align: left;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        position: relative;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .ep-dropdown-display::after {
        content: "";
        position: absolute;
        right: 16px;
        top: 50%;
        width: 8px;
        height: 8px;
        transform: translateY(-50%);
        border-right: 2px solid var(--black);
        border-bottom: 2px solid var(--black);
        transform: translateY(-65%) rotate(45deg);
    }

    .ep-dropdown-display:focus-visible {
        outline: none;
    }

    .ep-dropdown-display:hover,
    .ep-dropdown.is-open .ep-dropdown-display {
        border-color: var(--yellow);
        box-shadow: 0 0 0 4px rgba(250,204,21,0.18);
        background: #fff;
    }

    .ep-dropdown-options {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 12px 24px rgba(17, 17, 17, 0.12);
        z-index: 90;
        padding: 6px;
        max-height: 220px;
        overflow: auto;
        display: none;
    }

    .ep-dropdown-options::-webkit-scrollbar {
        width: 8px;
    }

    .ep-dropdown-options::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 999px;
    }

    .ep-dropdown.is-open .ep-dropdown-options {
        display: block;
    }

    .ep-dropdown-option {
        width: 100%;
        border: none;
        background: #fff;
        color: var(--black);
        border-radius: 14px;
        padding: 10px 12px;
        text-align: left;
        cursor: pointer;
        font-size: 0.94rem;
        font-weight: 700;
    }

    .ep-dropdown-option:hover {
        background: var(--light-yellow);
        color: var(--black);
    }

    .ep-dropdown-option.is-active {
        background: var(--yellow);
        color: var(--black);
    }

    .ep-check-row {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: flex-start;
        gap: 14px;
        min-height: 60px;
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #ffffff;
        padding: 18px 20px;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--black);
        cursor: pointer;
        transition: border-color .2s ease, background-color .2s ease;
    }

    .ep-check-row:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
    }

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
        min-width: 24px;
        width: 24px;
        height: 24px;
        border: 1px solid var(--yellow);
        border-radius: 6px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
        margin: 0;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .ep-check-custom::after {
        content: "";
        position: absolute;
        width: 11px;
        height: 7px;
        border-left: 2.8px solid #111111;
        border-bottom: 2.8px solid #111111;
        transform: rotate(-45deg) translate(1px, -1px);
        opacity: 0;
    }

    .ep-checkbox:checked + .ep-check-custom {
        border-color: var(--yellow);
        background-color: var(--yellow);
    }

    .ep-check-icon {
        display: none;
    }

    .ep-check-label {
        color: var(--black) !important;
        font-weight: 700;
        font-size: 0.95rem;
        line-height: 24px;
        margin: 0;
        background: transparent !important;
        display: inline-block !important;
        min-height: 24px;
        padding: 0;
        white-space: nowrap;
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

    /* ── Image AI buttons ── */
    .ep-ai-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
        padding: 5px 12px;
        background: #111111;
        color: #facc15;
        border: 1px solid #facc15;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, color .15s;
        font-family: inherit;
    }
    .ep-ai-btn:hover {
        background: #facc15;
        color: #111111;
    }
    .ep-ai-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .ep-ai-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #111;
        color: #facc15;
        font-size: 0.64rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        margin-left: 6px;
        vertical-align: middle;
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

    /* ---- SEO Section enhancements (Edit Product) ---- */
    .ep-seo-preview-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-left: 3px solid #4285f4;
        border-radius: 10px;
        padding: 14px 16px;
        margin: 0 0 20px;
        font-family: Arial, sans-serif;
    }
    .ep-seo-preview-label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 8px;
    }
    .ep-seo-preview-url { font-size: .78rem; color: #1e8e3e; margin-bottom: 3px; word-break: break-all; }
    .ep-seo-preview-title { font-size: 1.05rem; color: #1a0dab; margin-bottom: 4px; word-break: break-word; }
    .ep-seo-preview-desc { font-size: .85rem; color: #4d5156; line-height: 1.4; word-break: break-word; }
    .ep-seo-char-counter { font-size: .75rem; font-weight: 700; }
    .ep-seo-char-counter.good { color: #16a34a; }
    .ep-seo-char-counter.too-long { color: #f97316; }
    .ep-seo-char-counter.too-short { color: #f97316; }
    .ep-seo-field-wrap { display: flex; gap: 8px; align-items: flex-start; }
    .ep-seo-field-wrap > input, .ep-seo-field-wrap > textarea { flex: 1 1 0%; min-width: 0; }
    .ep-seo-auto-btn {
        flex-shrink: 0;
        height: 48px;
        padding: 0 12px;
        background: #111;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: color .15s;
        align-self: flex-start;
    }
    .ep-seo-auto-btn:hover { color: #facc15; }
    .ep-seo-field-hint { display: block; color: #9ca3af; font-size: .76rem; margin-top: 4px; }
    /* SEO field header: label LEFT, buttons RIGHT — on the same row directly above input */
    .ep-seo-field-hdr { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
    .ep-seo-field-hdr > label { margin-bottom: 0 !important; font-weight: 700; font-size: .9rem; color: var(--black); flex-shrink: 0; }
    .ep-seo-btn-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 0; }
    /* Image field header: label left, AI button right */
    .ep-img-field-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .ep-img-field-header label { margin-bottom: 0 !important; font-weight: 700; font-size: .9rem; color: var(--black); }
    .ep-img-field-header .ep-ai-btn { margin-top: 0; }
    .ep-seo-fill-btn {
        background: #facc15;
        color: #111;
        border: none;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: .82rem;
        font-weight: 800;
        cursor: pointer;
        transition: background .15s;
    }
    .ep-seo-fill-btn:hover { background: #eab308; }
</style>

<div class="ep-wrap">
    <div class="ep-header">
        <div>
            <h2>Edit Product</h2>
            <p>Update product information, pricing, images, SEO, and attributes</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <?php
                $isActive = !empty($product['product_status']) && $product['product_status'] == 1;
                $pillColor = $isActive ? '#22c55e' : 'tomato';
                $pillLabel = $isActive ? 'Active' : 'Inactive';
            ?>
            <span style="display:inline-flex;align-items:center;gap:7px;height:44px;padding:0 16px;border:1px solid <?= $pillColor ?>;border-radius:12px;background:<?= $pillColor ?> !important;color:#fff !important;font-size:0.9rem;font-weight:700;letter-spacing:.3px;white-space:nowrap;">
                <?= $pillLabel ?>
            </span>
            <a href="<?= htmlspecialchars('/' . ($product['category_slug'] ?? '') . '/' . ($product['brand_slug'] ?? '') . '/' . ($product['product_slug'] ?? '') . '/') ?>" target="_blank" class="ep-btn" style="text-decoration:none;">View Product</a>
            <button type="submit" class="ep-btn" form="product-form">Update Product</button>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="" id="product-form">
        <div class="ep-card">
            <h3>Basic Information</h3>
            <div class="ep-grid">
                <div class="ep-field">
                    <label style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px">
                        <span>Product Name</span>
                        <button type="button" class="ai-refine-btn" onclick="AISeo.refineField('product_name','seo')"><i class="fas fa-magic"></i> Refine with AI</button>
                    </label>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_product_name" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required oninput="AISeo.runScore()">
                    </div>
                </div>
                <div class="ep-field">
                    <div class="ep-seo-field-hdr">
                        <label for="ep_product_slug">Product Slug</label>
                        <div class="ai-slug-btn-wrap">
                            <button type="button" class="ai-slug-btn ai" onclick="AISeo.generateSlugAI(this)" title="Generate SEO-optimized slug with AI">
                                <i class="fas fa-robot" style="font-size:.7rem"></i> AI Slug
                            </button>
                            <button type="button" class="ai-slug-btn manual" onclick="AISeo.generateSlugManual()" title="Generate slug from product name">
                                <i class="fas fa-link" style="font-size:.7rem"></i> Manual
                            </button>
                        </div>
                    </div>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_product_slug" name="product_slug" value="<?= htmlspecialchars($product['product_slug']) ?>" required>
                    </div>
                </div>
                <div class="ep-field">
                    <label>Category</label>
                    <select name="category_id" required data-ep-custom-dropdown>
                        <?php foreach ($categories as $row): ?>
                            <option value="<?= $row['category_id'] ?>" <?= $product['category_id'] == $row['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ep-field">
                    <label>Brand</label>
                    <select name="brand_id" required data-ep-custom-dropdown>
                        <?php foreach ($brands as $row): ?>
                            <option value="<?= $row['brand_id'] ?>" <?= $product['brand_id'] == $row['brand_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['brand_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ep-field">
                    <label>Product Status</label>
                    <select name="product_status" required data-ep-custom-dropdown>
                        <option value="1" <?= $product['product_status'] == '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $product['product_status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="ep-field">
                    <label>B2B Available</label>
                    <label class="ep-check-row">
                        <input class="ep-checkbox" type="checkbox" name="is_b2b_available" value="1" <?= $product['is_b2b_available'] ? 'checked' : '' ?>>
                        <span class="ep-check-custom" aria-hidden="true">
                            <svg class="ep-check-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 8.3L6.2 11.2L13 4.5" stroke="#111111" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="ep-check-label">Enable B2B pricing</span>
                    </label>
                </div>
                <div class="ep-field" id="b2b-price-section" style="display: <?= $product['is_b2b_available'] ? 'block' : 'none' ?>;">
                    <label>B2B Regular Price</label>
                    <input type="number" name="b2b_regular_price" value="<?= htmlspecialchars($product['b2b_regular_price'] ?? '') ?>" step="0.01">
                </div>
            </div>
        </div>

        <div class="ep-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                <h3 style="margin:0">Description</h3>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="button" class="ai-gen-btn ghost" style="background:#f1f5f9;color:#111;border:1px solid #e5e7eb;font-size:.78rem" onclick="AISeo.generateField('description',this)"><i class="fas fa-robot"></i> Generate Description</button>
                    <button type="button" class="ai-gen-btn ghost" style="background:#f1f5f9;color:#111;border:1px solid #e5e7eb;font-size:.78rem" onclick="AISeo.generateField('short_description',this)"><i class="fas fa-magic"></i> Generate Short Desc</button>
                </div>
            </div>
            <div class="ep-grid">
                <div class="ep-field full">
                    <label style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px">
                        <span>Product Description</span>
                        <div style="display:flex;gap:6px">
                            <button type="button" class="ai-refine-btn" data-ai-refine="ep_product_description" onclick="AISeo.refineField('product_description','seo')"><i class="fas fa-magic"></i> Refine with AI</button>
                            <div class="ai-btn-group">
                                <button type="button" class="ai-btn-group-arrow" onclick="AISeo.toggleDropdown(this)"><i class="fas fa-chevron-down"></i></button>
                                <div class="ai-dropdown-menu">
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('product_description','professional')"><i class="fas fa-briefcase"></i> Make Professional</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('product_description','sales')"><i class="fas fa-rocket"></i> Make Sales-Focused</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('product_description','shorter')">Make Shorter</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('product_description','readability')"><i class="fas fa-book"></i> Improve Readability</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.generateField('description',this)"><i class="fas fa-robot"></i> Regenerate from Scratch</button>
                                </div>
                            </div>
                        </div>
                    </label>
                    <div class="ai-field-relative">
                        <textarea id="ep_product_description" name="product_description" required oninput="AISeo.runScore()"><?= htmlspecialchars($product['product_description']) ?></textarea>
                    </div>
                </div>
                <div class="ep-field full">
                    <label style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px">
                        <span>Short Description</span>
                        <div style="display:flex;gap:6px">
                            <button type="button" class="ai-refine-btn" data-ai-refine="ep_short_description" onclick="AISeo.refineField('short_description','seo')"><i class="fas fa-magic"></i> Refine with AI</button>
                            <div class="ai-btn-group">
                                <button type="button" class="ai-btn-group-arrow" onclick="AISeo.toggleDropdown(this)"><i class="fas fa-chevron-down"></i></button>
                                <div class="ai-dropdown-menu">
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('short_description','sales')"><i class="fas fa-rocket"></i> Sales-Focused</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('short_description','shorter')">Make Shorter</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.generateField('short_description',this)"><i class="fas fa-robot"></i> Regenerate</button>
                                </div>
                            </div>
                        </div>
                    </label>
                    <div class="ai-field-relative">
                        <textarea id="ep_short_description" name="short_description" oninput="AISeo.runScore()"><?= htmlspecialchars($product['short_description']) ?></textarea>
                    </div>
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
                    <label>Prepaid Discount Amount (PKR)</label>
                    <input type="number" name="prepaid_discount_amount" value="<?= htmlspecialchars((float)($product['prepaid_discount_amount'] ?? 0)) ?>" step="0.01" min="0" placeholder="0">
                </div>
                <div class="ep-field">
                    <label>Product SKU</label>
                    <div class="ep-seo-field-wrap">
                        <input type="text" id="ep_product_sku" name="product_sku" value="<?= htmlspecialchars($product['product_sku'] ?? '') ?>" placeholder="SAM-MOB-X7K2">
                        <button type="button" class="ep-seo-auto-btn" onclick="epGenerateProductSku()" title="Auto-generate SKU from brand and category">Auto</button>
                    </div>
                    <span class="ep-seo-field-hint">Format: BRAND-CATEGORY-RANDOM e.g. SAM-MOB-X7K2</span>
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
                    <label style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px">
                        <span>Product Tags</span>
                        <button type="button" class="ai-refine-btn" onclick="AISeo.generateField('tags',this)"><i class="fas fa-tags"></i> Suggest with AI</button>
                    </label>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_product_tag" name="product_tag" value="<?= htmlspecialchars($product['product_tag'] ?? '') ?>" oninput="AISeo.runScore()">
                    </div>
                </div>
            </div>
        </div>

        <div class="ep-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                <h3 style="margin:0">SEO Details</h3>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <button type="button" class="ep-seo-fill-btn" onclick="epSeoAutoFillAll()" style="margin:0">Auto-Fill (Manual)</button>
                    <button type="button" class="ai-gen-btn primary" style="padding:8px 16px;border-radius:8px;font-size:.82rem" onclick="AISeo.generateAll(this)"><i class="fas fa-robot"></i> AI Auto-Fill All SEO</button>
                </div>
            </div>

            <!-- AI Generate Bar -->
            <div class="ai-generate-bar" style="margin-bottom:16px">
                <span class="ai-generate-bar-label"><i class="fas fa-robot"></i> AI SEO Generator</span>
                <button type="button" class="ai-gen-btn ghost" onclick="AISeo.generateField('seo_title',this)"><i class="fas fa-thumbtack"></i> Generate Title</button>
                <button type="button" class="ai-gen-btn ghost" onclick="AISeo.generateField('meta_description',this)"><i class="fas fa-file-alt"></i> Generate Meta</button>
                <button type="button" class="ai-gen-btn ghost" onclick="AISeo.generateField('focus_keyword',this)"><i class="fas fa-key"></i> Generate Keyword</button>
                <button type="button" class="ai-gen-btn ghost" onclick="AISeo.generateTagsDirect(this)"><i class="fas fa-tags"></i> Generate Tags</button>
            </div>

            <!-- Google Search Snippet Preview -->
            <div class="ep-seo-preview-box">
                <span class="ep-seo-preview-label">Google Search Preview</span>
                <div class="ep-seo-preview-url" id="ep_snippet_url"><?= htmlspecialchars('www.phonesdukan.com/' . ($product['category_slug'] ?? 'category') . '/' . ($product['brand_slug'] ?? 'brand') . '/' . ($product['product_slug'] ?? 'product-slug') . '/') ?></div>
                <div class="ep-seo-preview-title" id="ep_snippet_title"><?= htmlspecialchars($seoData['seo_title'] ?? $product['product_name'] ?? 'Product Title') ?></div>
                <div class="ep-seo-preview-desc" id="ep_snippet_desc"><?= htmlspecialchars($seoData['seo_description'] ?? 'Product meta description will appear here...') ?></div>
            </div>

            <div class="ep-grid">
                <div class="ep-field full">
                    <div class="ep-seo-field-hdr">
                        <label for="ep_seo_title">SEO Title <span id="ep_seo_title_counter" class="ep-seo-char-counter">0/60</span></label>
                        <div class="ep-seo-btn-row">
                            <button type="button" class="ai-refine-btn" data-ai-refine="ep_seo_title" onclick="AISeo.refineField('seo_title','seo')"><i class="fas fa-magic"></i> Refine with AI</button>
                            <button type="button" class="ep-seo-auto-btn" onclick="epSeoGenerateTitle()" style="height:34px;font-size:.72rem">Manual Auto</button>
                        </div>
                    </div>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_seo_title" name="seo_title"
                            value="<?= htmlspecialchars($seoData['seo_title'] ?? '') ?>"
                            placeholder="Product Name Price in Pakistan May 2026 | Phones Dukan"
                            oninput="epSeoUpdateCharCounter('ep_seo_title',60);epSeoUpdateSnippetPreview();AISeo.runScore()">
                    </div>
                    <span class="ep-seo-field-hint">Recommended: 50-60 characters. Pattern: "{Name} Price in Pakistan {Month Year} | Phones Dukan"</span>
                </div>

                <div class="ep-field">
                    <div class="ep-seo-field-hdr">
                        <label for="ep_focus_keyword">Focus Keyword</label>
                        <div class="ep-seo-btn-row">
                            <button type="button" class="ai-refine-btn" onclick="AISeo.generateField('focus_keyword',this)"><i class="fas fa-key"></i> AI Suggest</button>
                            <button type="button" class="ep-seo-auto-btn" onclick="epSeoGenerateFocusKeyword()" style="height:34px;font-size:.72rem">Manual</button>
                        </div>
                    </div>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_focus_keyword" name="focus_keyword"
                            value="<?= htmlspecialchars($seoData['focus_keyword'] ?? '') ?>"
                            placeholder="product name price in pakistan"
                            oninput="AISeo.runScore()">
                    </div>
                    <span class="ep-seo-field-hint">Primary keyword for ranking. Use: "brand model price in pakistan"</span>
                </div>

                <div class="ep-field">
                    <div class="ep-seo-field-hdr">
                        <label for="ep_secondary_keywords">Tags</label>
                        <div class="ep-seo-btn-row">
                            <button type="button" class="ai-refine-btn" onclick="AISeo.generateTagsDirect(this)"><i class="fas fa-tags"></i> Generate Tags</button>
                        </div>
                    </div>
                    <div class="ai-field-relative">
                        <input type="text" id="ep_secondary_keywords" name="secondary_keywords"
                            value="<?= htmlspecialchars($seoData['secondary_keywords'] ?? '') ?>"
                            placeholder="buy online, best price, official warranty, pakistan">
                    </div>
                    <span class="ep-seo-field-hint">Comma-separated tags for search visibility and discoverability.</span>
                </div>

                <div class="ep-field full">
                    <div class="ep-seo-field-hdr">
                        <label for="ep_seo_description">SEO Meta Description <span id="ep_seo_desc_counter" class="ep-seo-char-counter">0/160</span></label>
                        <div class="ep-seo-btn-row">
                            <button type="button" class="ai-refine-btn" data-ai-refine="ep_seo_description" onclick="AISeo.refineField('seo_description','seo')"><i class="fas fa-magic"></i> Refine with AI</button>
                            <div class="ai-btn-group">
                                <button type="button" class="ai-btn-group-arrow" onclick="AISeo.toggleDropdown(this)"><i class="fas fa-chevron-down"></i></button>
                                <div class="ai-dropdown-menu">
                                    <button class="ai-dropdown-item" onclick="AISeo.generateField('meta_description',this)"><i class="fas fa-robot"></i> Generate from Scratch</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('seo_description','sales')"><i class="fas fa-rocket"></i> Make Sales-Focused</button>
                                    <button class="ai-dropdown-item" onclick="AISeo.refineField('seo_description','shorter')">Make Shorter</button>
                                </div>
                            </div>
                            <button type="button" class="ep-seo-auto-btn" onclick="epSeoGenerateDescription()" style="height:34px;font-size:.72rem">Manual Auto</button>
                        </div>
                    </div>
                    <div class="ai-field-relative">
                        <textarea id="ep_seo_description" name="seo_description"
                            placeholder="Buy product name in Pakistan at the best price. PTA-approved with official warranty..."
                            oninput="epSeoUpdateCharCounter('ep_seo_description',160);epSeoUpdateSnippetPreview();AISeo.runScore()"><?= htmlspecialchars($seoData['seo_description'] ?? '') ?></textarea>
                    </div>
                    <span class="ep-seo-field-hint">Recommended: 140-160 characters. Auto-generates from short description or product name.</span>
                </div>

                <div class="ep-field full">
                    <label>Canonical URL <small style="color:#9ca3af;font-weight:400">(auto-regenerated on save)</small></label>
                    <input type="text" id="ep_canonical_url" name="canonical_url" readonly
                        value="<?= htmlspecialchars($seoData['canonical_url'] ?? ('https://www.phonesdukan.com/' . ($product['category_slug'] ?? '') . '/' . ($product['brand_slug'] ?? '') . '/' . ($product['product_slug'] ?? '') . '/')) ?>"
                        style="background:#f8fafc;color:#6b7280;cursor:default;font-size:.88rem">
                </div>
            </div>
        </div>

        <!-- === AI SEO SCORE & ANALYSIS === -->
        <div class="ep-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                <h3 style="margin:0"><i class="fas fa-robot"></i> AI SEO Analysis</h3>
                <button type="button" class="ep-seo-auto-btn" onclick="AISeo.runScore()" style="height:36px;font-size:.78rem"><i class="fas fa-sync-alt"></i> Refresh Score</button>
            </div>
            <div id="ai-seo-score-section"></div>
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
                                <!-- Alt Text -->
                                <!-- Alt Text -->
                                <div class="ep-field">
                                    <div class="ep-img-field-header">
                                        <label for="img_alt_<?= $image['image_id'] ?>">Alt Text</label>
                                        <button type="button" class="ep-ai-btn"
                                            onclick="AISeo.generateImageMeta(<?= $image['image_id'] ?>, 'alt_text', this)">
                                            <i class="fas fa-robot"></i> Generate
                                        </button>
                                    </div>
                                    <div class="ai-field-relative" style="position:relative">
                                        <input type="text"
                                            id="img_alt_<?= $image['image_id'] ?>"
                                            name="alt_text[<?= $image['image_id'] ?>]"
                                            value="<?= htmlspecialchars($image['alt_text'] ?? '') ?>">
                                    </div>
                                </div>
                                <!-- Title -->
                                <div class="ep-field">
                                    <div class="ep-img-field-header">
                                        <label for="img_title_<?= $image['image_id'] ?>">Title</label>
                                        <button type="button" class="ep-ai-btn"
                                            onclick="AISeo.generateImageMeta(<?= $image['image_id'] ?>, 'title', this)">
                                            <i class="fas fa-robot"></i> Generate
                                        </button>
                                    </div>
                                    <div class="ai-field-relative" style="position:relative">
                                        <input type="text"
                                            id="img_title_<?= $image['image_id'] ?>"
                                            name="title[<?= $image['image_id'] ?>]"
                                            value="<?= htmlspecialchars($image['title'] ?? '') ?>">
                                    </div>
                                </div>
                                <!-- Description -->
                                <div class="ep-field full">
                                    <div class="ep-img-field-header">
                                        <label for="img_desc_<?= $image['image_id'] ?>">Description</label>
                                        <button type="button" class="ep-ai-btn"
                                            onclick="AISeo.generateImageMeta(<?= $image['image_id'] ?>, 'description', this)">
                                            <i class="fas fa-robot"></i> Generate
                                        </button>
                                    </div>
                                    <div class="ai-field-relative" style="position:relative">
                                        <textarea
                                            id="img_desc_<?= $image['image_id'] ?>"
                                            name="description[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <!-- Caption -->
                                <div class="ep-field full">
                                    <div class="ep-img-field-header">
                                        <label for="img_caption_<?= $image['image_id'] ?>">Caption</label>
                                        <button type="button" class="ep-ai-btn"
                                            onclick="AISeo.generateImageMeta(<?= $image['image_id'] ?>, 'caption', this)">
                                            <i class="fas fa-robot"></i> Generate
                                        </button>
                                    </div>
                                    <div class="ai-field-relative" style="position:relative">
                                        <textarea
                                            id="img_caption_<?= $image['image_id'] ?>"
                                            name="caption[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['caption'] ?? '') ?></textarea>
                                    </div>
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

        <?php
        $pmWrapperClass = 'ep-card';
        $pmHeadingTag = 'h3';
        include __DIR__ . '/includes/product-media-section.php';
        ?>

        <div class="ep-card">
            <h3>Product Attributes</h3>
            <div id="product-attributes">
                <?php foreach ($productAttributes as $index => $attribute): ?>
                    <div class="attribute" data-attribute-id="<?= $attribute['attribute_id'] ?>" data-value-id="<?= $attribute['value_id'] ?>">
                        <div>
                            <label><?= htmlspecialchars($attribute['attribute_name']) ?></label>
                            <select name="attributes[<?= $index ?>][attribute_id]" required data-ep-custom-dropdown>
                                <?php foreach ($allAttributes as $attr): ?>
                                    <option value="<?= $attr['attribute_id'] ?>" <?= $attribute['attribute_id'] == $attr['attribute_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($attr['attribute_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Value</label>
                            <select name="attributes[<?= $index ?>][value_id]" required data-ep-custom-dropdown>
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

        <!-- ============================================================
             PRODUCT VARIATIONS SECTION (Edit)
             ============================================================ -->
        <div class="ep-card" style="margin-top:20px;padding:24px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px">
                <div>
                    <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:#111">Product Variations</h3>
                    <p style="margin:4px 0 0;color:#6b7280;font-size:.88rem">Enable for products with Color, RAM, Storage, Size options</p>
                </div>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <div class="vb-toggle-wrap">
                        <input type="checkbox" id="enableVariationsEP" style="display:none" <?= $currentProductType === 'variable' ? 'checked' : '' ?>>
                        <div class="vb-toggle-track"><div class="vb-toggle-thumb"></div></div>
                    </div>
                    <span id="epVarToggleText" style="display:inline-flex;align-items:center;height:36px;padding:0 14px;background:#111111 !important;color:#facc15 !important;border:1.5px solid #facc15;border-radius:10px;font-weight:700;font-size:.85rem;white-space:nowrap;"><?= $currentProductType === 'variable' ? 'Variable Product (ON)' : 'Variable Product' ?></span>
                </label>
            </div>

            <div id="epVariationBuilderWrap" style="display:<?= $currentProductType === 'variable' ? 'block' : 'none' ?>">

                <div class="vb-step">
                    <div class="vb-step-title">Step 1: Select Variation Types</div>
                    <div class="vb-step-body">
                        <div id="epTypeCheckboxes" class="vb-type-grid"></div>
                    </div>
                </div>

                <div class="vb-step" id="epStep2" style="display:none">
                    <div class="vb-step-title">Step 2: Choose Values Per Type</div>
                    <div class="vb-step-body" id="epValueSelectors"></div>
                </div>

                <div class="vb-step" id="epStep3" style="display:none">
                    <div class="vb-step-title">Step 3: Variation Combinations</div>
                    <div class="vb-step-body">
                        <div style="display:flex;gap:10px;margin-bottom:10px;flex-wrap:wrap;align-items:center">
                            <button type="button" class="vb-btn-ep" onclick="epGenerateVariations()">Generate All Combinations</button>
                            <button type="button" class="vb-btn-ep vb-btn-ep-outline" onclick="epAddBlankVariation()">+ Add Row Manually</button>
                            <button type="button" class="vb-btn-ep vb-btn-ep-outline" onclick="epGenerateAllVariationSkus()" title="Auto-fill empty SKU fields for all variations">Generate SKUs</button>
                            <button type="button" onclick="epRemoveEmptyRows()" title="Remove rows with no SKU, price, stock or image"
                                style="height:40px;padding:0 14px;border:1px solid #e5e7eb;border-radius:10px;
                                       background:#fff;color:#6b7280;font-size:.82rem;font-weight:700;cursor:pointer"
                                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280'">
                                Remove empty rows
                            </button>
                            <span id="epComboCount" style="font-size:.82rem;color:#6b7280;font-weight:700"></span>
                        </div>
                        <div id="epCombinationsWrap"></div>
                    </div>
                </div>

                <input type="hidden" name="product_type" id="epProductTypeInput" value="<?= htmlspecialchars($currentProductType) ?>">
                <input type="hidden" name="variations_json" id="epVariationsJson" value="<?= htmlspecialchars(json_encode($existingVariations)) ?>">
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;padding:16px 0 16px 20px;">
            <span style="display:inline-flex;align-items:center;gap:7px;height:44px;padding:0 16px;border:1px solid <?= $pillColor ?>;border-radius:12px;background:<?= $pillColor ?> !important;color:#fff !important;font-size:0.9rem;font-weight:700;letter-spacing:.3px;white-space:nowrap;">
                <?= $pillLabel ?>
            </span>
            <a href="<?= htmlspecialchars('/' . ($product['category_slug'] ?? '') . '/' . ($product['brand_slug'] ?? '') . '/' . ($product['product_slug'] ?? '') . '/') ?>" target="_blank" class="ep-btn" style="text-decoration:none;">View Product</a>
            <button type="submit" class="ep-btn">Update Product</button>
        </div>
    </form>
</div>

<script>
// ---- EP (Edit Product) Variation Builder ----
const EP_ADMIN_BASE = <?= json_encode(rtrim((defined('BASE_PATH') ? BASE_PATH : ''), '/')) ?>;
function epBase(path) {
    if (!path || !path.startsWith('/')) return path;
    if (EP_ADMIN_BASE && !path.startsWith(EP_ADMIN_BASE + '/')) return EP_ADMIN_BASE + path;
    return path;
}
const EP_VB_TYPES = <?= json_encode($allVarTypes, JSON_UNESCAPED_UNICODE) ?>;
let epSelectedTypes = [];
let epSelectedValues = {};
let epVariations = <?= json_encode($existingVariations, JSON_UNESCAPED_UNICODE) ?>;

function epEscHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function epBuildLabel(v) {
    return Object.entries(v.attributes||{}).map(([tid,vid]) => {
        const t = EP_VB_TYPES.find(x=>x.id==tid);
        const val = t ? (t.values||[]).find(x=>x.id==vid) : null;
        return (t?t.name:'?') + ': ' + (val?val.value:'?');
    }).join(' / ') || 'Variation';
}

// Restore type/value selections from existing variations
function epRestoreSelectionsFromVariations() {
    if (!epVariations.length) return;
    const usedTypes = new Set();
    const usedValues = {};
    epVariations.forEach(v => {
        Object.entries(v.attributes||{}).forEach(([tid,vid]) => {
            usedTypes.add(parseInt(tid));
            if (!usedValues[tid]) usedValues[tid] = new Set();
            usedValues[tid].add(parseInt(vid));
        });
    });
    usedTypes.forEach(tid => {
        const t = EP_VB_TYPES.find(x => x.id == tid);
        if (t && !epSelectedTypes.find(x=>x.id==tid)) epSelectedTypes.push(t);
        if (!epSelectedValues[tid]) epSelectedValues[tid] = [];
        (usedValues[tid]||new Set()).forEach(vid => {
            if (!epSelectedValues[tid].includes(vid)) epSelectedValues[tid].push(vid);
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    epRenderTypeCheckboxes();
    if (epVariations.length > 0) {
        epRestoreSelectionsFromVariations();
        epRenderTypeCheckboxes(); // re-render with checks
        epRenderValueSelectors();
        epRefreshStep3();
        epRenderVariationRows();
    }
    if (<?= $currentProductType === 'variable' ? 'true' : 'false' ?>) {
        document.getElementById('epVariationBuilderWrap').style.display = 'block';
    }
});

document.getElementById('enableVariationsEP').addEventListener('change', function() {
    document.getElementById('epVariationBuilderWrap').style.display = this.checked ? 'block' : 'none';
    document.getElementById('epProductTypeInput').value = this.checked ? 'variable' : 'simple';
    document.getElementById('epVarToggleText').textContent = this.checked ? 'Variable Product (ON)' : 'Variable Product';
});

function epRenderTypeCheckboxes() {
    const container = document.getElementById('epTypeCheckboxes');
    if (!EP_VB_TYPES.length) {
        container.innerHTML = '<p style="color:#9ca3af;font-size:.88rem">No variation types. <a href="manage-variations.php" target="_blank" style="color:#111;font-weight:700">Create some first.</a></p>';
        return;
    }
    container.innerHTML = EP_VB_TYPES.map(t => {
        const checked = !!epSelectedTypes.find(x=>x.id==t.id);
        return `<label class="vb-type-check">
            <input type="checkbox" value="${t.id}" ${checked?'checked':''} onchange="epOnTypeToggle(${t.id}, this.checked)">
            <span>${epEscHtml(t.name)}</span>
            <span class="vb-type-badge">${t.values.length} values</span>
        </label>`;
    }).join('');
}

function epOnTypeToggle(type_id, checked) {
    const t = EP_VB_TYPES.find(x => x.id == type_id);
    if (!t) return;
    if (checked) {
        if (!epSelectedTypes.find(x=>x.id==type_id)) epSelectedTypes.push(t);
        if (!epSelectedValues[type_id]) epSelectedValues[type_id] = [];
    } else {
        epSelectedTypes = epSelectedTypes.filter(x=>x.id!=type_id);
        delete epSelectedValues[type_id];
    }
    epRenderValueSelectors();
    epUpdateComboCount();
    epRefreshStep3();
}

function epRenderValueSelectors() {
    const step2 = document.getElementById('epStep2');
    const container = document.getElementById('epValueSelectors');
    if (!epSelectedTypes.length) { step2.style.display='none'; return; }
    step2.style.display='block';
    container.innerHTML = epSelectedTypes.map(t => `
        <div style="margin-bottom:18px">
            <div style="font-weight:700;font-size:.9rem;margin-bottom:8px">${epEscHtml(t.name)}</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                ${(t.values||[]).map(v => `
                    <label class="vb-value-pill">
                        <input type="checkbox" value="${v.id}"
                            ${(epSelectedValues[t.id]||[]).includes(v.id)?'checked':''}
                            onchange="epOnValueToggle(${t.id}, ${v.id}, this.checked)">
                        ${t.display_type==='color'&&v.color_code
                            ? `<span class="vb-swatch" style="background:${epEscHtml(v.color_code)}"></span>` : ''}
                        <span>${epEscHtml(v.value)}</span>
                    </label>
                `).join('')}
                ${!t.values.length ? '<span style="color:#9ca3af;font-size:.85rem">No values.</span>' : ''}
            </div>
        </div>
    `).join('');
}

function epOnValueToggle(type_id, value_id, checked) {
    if (!epSelectedValues[type_id]) epSelectedValues[type_id] = [];
    if (checked) { if (!epSelectedValues[type_id].includes(value_id)) epSelectedValues[type_id].push(value_id); }
    else { epSelectedValues[type_id] = epSelectedValues[type_id].filter(x=>x!==value_id); }
    epUpdateComboCount();
    epRefreshStep3();
}

//  Stylish toast notification 
function epToast(message, type) {
    // type: 'success' | 'info' | 'warning'
    var existing = document.getElementById('epToastNotif');
    if (existing) existing.remove();

    var icons = {
        success: '<span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#16a34a;color:#fff;font-size:12px;font-weight:900;margin-right:10px;flex-shrink:0"></span>',
        info:    '<span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#6b7280;color:#fff;font-size:11px;font-weight:900;margin-right:10px;flex-shrink:0">i</span>',
        warning: '<span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#f97316;color:#fff;font-size:12px;font-weight:900;margin-right:10px;flex-shrink:0">!</span>'
    };
    var borders = { success:'#16a34a', info:'#6b7280', warning:'#f97316' };

    var t = document.createElement('div');
    t.id = 'epToastNotif';
    t.innerHTML = (icons[type] || icons.info) + message;
    t.style.cssText = [
        'position:fixed', 'bottom:24px', 'right:24px', 'z-index:99999',
        'display:flex', 'align-items:center',
        'padding:13px 20px',
        'background:#111111',
        'color:#fff',
        'border-left:4px solid ' + (borders[type] || '#6b7280'),
        'border-radius:12px',
        'box-shadow:0 8px 28px rgba(0,0,0,.28)',
        'font-size:.88rem', 'font-weight:700',
        'min-width:260px', 'max-width:380px',
        'animation:epSlideIn .25s ease',
        'cursor:pointer'
    ].join(';');

    // inject keyframe once
    if (!document.getElementById('epToastKF')) {
        var s = document.createElement('style');
        s.id = 'epToastKF';
        s.textContent = '@keyframes epSlideIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(s);
    }

    t.onclick = function(){ t.remove(); };
    document.body.appendChild(t);
    setTimeout(function(){ if(t.parentNode) t.remove(); }, 3500);
}

function epRemoveEmptyRows() {
    const before = epVariations.length;
    epVariations = epVariations.filter(function(v) {
        return (v.sku && v.sku.trim()) ||
               (v.regular_price !== '' && parseFloat(v.regular_price) > 0) ||
               (parseInt(v.stock_quantity) > 0) ||
               (v.image && v.image.trim());
    });
    const removed = before - epVariations.length;
    if (removed === 0) {
        epToast('No empty rows to remove.', 'info');
        return;
    }
    epRenderVariationRows();
    epToast('Removed ' + removed + ' empty row' + (removed !== 1 ? 's' : '') + ' successfully.', 'success');
}

function epUpdateComboCount() {
    const countEl = document.getElementById('epComboCount');
    if (!countEl) return;
    const activeTypes = epSelectedTypes.filter(t => (epSelectedValues[t.id]||[]).length > 0);
    if (!activeTypes.length) { countEl.textContent = ''; return; }
    const total = activeTypes.reduce((acc, t) => acc * (epSelectedValues[t.id]||[]).length, 1);
    const color = total > 100 ? '#ef4444' : total > 50 ? '#f97316' : '#16a34a';
    countEl.innerHTML = `Will generate: <strong style="color:${color}">${total}</strong> combination${total!==1?'s':''}` +
        (total > 100 ? ' - <span style="color:#ef4444"><i class="fas fa-exclamation-triangle"></i> Too many! Reduce selection.</span>' : '');
}

function epRefreshStep3() {
    const hasSelected = epSelectedTypes.some(t => (epSelectedValues[t.id]||[]).length > 0);
    document.getElementById('epStep3').style.display = hasSelected || epVariations.length > 0 ? 'block' : 'none';
    if (epVariations.length > 0) epRenderVariationRows();
}

function epCartesian(sets) {
    return sets.reduce((acc, set) => acc.flatMap(combo => set.map(item => [...combo, item])), [[]]);
}

function epGenerateVariations() {
    const selectedTypes = epSelectedTypes.filter(t => (epSelectedValues[t.id]||[]).length > 0);
    if (!selectedTypes.length) { alert('Select at least one type and value.'); return; }

    // Count total combinations before generating
    const totalCombos = selectedTypes.reduce((acc, t) => acc * (epSelectedValues[t.id]||[]).length, 1);
    if (totalCombos > 100) {
        const ok = confirm(
            '<i class="fas fa-exclamation-triangle"></i> Warning: ' + totalCombos + ' combinations will be generated.\n\n' +
            'This is a very large number. Please reduce your selection:\n' +
            '- Only select variation types this product actually needs\n' +
            '- Only select values this product actually comes in\n\n' +
            'Recommended: max 30-50 combinations.\n\n' +
            'Continue anyway?'
        );
        if (!ok) return;
    }

    const valueSets = selectedTypes.map(t =>
        (epSelectedValues[t.id]||[]).map(vid => {
            const valObj = (t.values||[]).find(v=>v.id==vid);
            return {type_id:t.id, type_name:t.name, value_id:vid, value_name:valObj?valObj.value:vid};
        })
    );
    const combos = epCartesian(valueSets);

    // PRESERVE all existing variations that have any data filled in
    // (user may have already set SKU / price / stock / image for a previous combination)
    function epHasData(v) {
        return (v.sku && v.sku.trim()) ||
               (v.regular_price !== '' && parseFloat(v.regular_price) > 0) ||
               (parseInt(v.stock_quantity) > 0) ||
               (v.image && v.image.trim());
    }
    const preserved = epVariations.filter(v => epHasData(v));
    const seenKeys  = new Set(preserved.map(v => JSON.stringify(v.attributes)));
    const result    = [...preserved]; // start with preserved rows

    let addedCount    = 0;
    let existingCount = 0;

    combos.forEach(combo => {
        const attrs = {};
        const label = combo.map(c => { attrs[c.type_id]=c.value_id; return c.type_name+': '+c.value_name; }).join(' / ');
        const key   = JSON.stringify(attrs);
        if (seenKeys.has(key)) { existingCount++; return; }
        seenKeys.add(key);
        result.push({attributes:attrs, label, sku:'', regular_price:'', sale_price:'', prepaid_discount_amount:0, stock_quantity:0, image:'', status:1, is_default:0});
        addedCount++;
    });

    epVariations = result;
    epRenderVariationRows();

    // Informative feedback
    if (addedCount === 0 && existingCount > 0) {
        epToast(
            existingCount + ' combination' + (existingCount !== 1 ? 's' : '') +
            ' already exist' + (existingCount === 1 ? 's' : '') + '. No new rows added.',
            'info'
        );
    } else if (addedCount > 0 && existingCount > 0) {
        epToast(
            addedCount + ' new combination' + (addedCount !== 1 ? 's' : '') + ' added. ' +
            existingCount + ' already existed and were kept.',
            'success'
        );
    } else if (addedCount > 0) {
        epToast(addedCount + ' combination' + (addedCount !== 1 ? 's' : '') + ' generated successfully.', 'success');
    }
}

function epAddBlankVariation() {
    epVariations.push({attributes:{}, label:'Custom', sku:'', regular_price:'', sale_price:'', prepaid_discount_amount:0, stock_quantity:0, image:'', status:1, is_default:0});
    epRenderVariationRows();
}

function epRenderVariationRows() {
    const wrap = document.getElementById('epCombinationsWrap');
    document.getElementById('epStep3').style.display = 'block';
    if (!epVariations.length) { wrap.innerHTML='<p style="color:#9ca3af">No combinations yet.</p>'; epSyncVariationsJson(); return; }
    wrap.innerHTML = `<div style="overflow-x:auto;overflow-y:visible"><table class="vb-table-ep" style="overflow:visible" id="epVarTable">
        <thead><tr>
            <th style="width:160px">Combination</th><th>SKU</th><th>Regular Price</th><th>Sale Price</th><th>Prepaid Disc. (PKR)</th>
            <th>Stock</th><th>Image</th><th>Status</th><th>Default</th><th></th>
        </tr></thead>
        <tbody id="epVarTbody">${epVariations.map((v,i) => epRenderRow(v,i)).join('')}</tbody>
    </table></div>`;
    epSyncVariationsJson();
}

function epRenderRow(v, i) {
    const label = v.label || epBuildLabel(v);
    const imgSrc = v.image ? epBase(v.image) : '';
    const imgThumb = imgSrc
        ? `<img src="${epEscHtml(imgSrc)}" id="epThumb${i}" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;margin-bottom:4px">`
        : `<div id="epThumb${i}" style="width:48px;height:48px;border-radius:8px;border:1px dashed #e5e7eb;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:20px;margin-bottom:4px;background:#f9fafb">+</div>`;
    return `<tr id="epRow${i}" draggable="false"
        ondragstart="epDragStart(event,${i})"
        ondragover="epDragOver(event)"
        ondrop="epDrop(event,${i})"
        ondragend="epDragEnd(event)"
        style="cursor:default">
        <td style="min-width:150px;display:flex;align-items:flex-start;gap:8px;padding-top:10px">
            <span class="ep-drag-handle" title="Drag to reorder"
                style="cursor:grab;color:#9ca3af;font-size:16px;line-height:1;padding-top:2px;flex-shrink:0;user-select:none"
                onmousedown="this.closest('tr').setAttribute('draggable','true')"
                onmouseup="this.closest('tr').setAttribute('draggable','false')"><i class="fas fa-grip-vertical"></i></span>
            <span style="font-weight:700;font-size:.84rem;color:#111;display:block">${epEscHtml(label)}</span>
        </td>
        <td style="min-width:90px">
            <input class="vb-input-ep" type="text" placeholder="SKU-001" value="${epEscHtml(v.sku||'')}"
                onchange="epVariations[${i}].sku=this.value;epSyncVariationsJson()" style="width:90px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input-ep" type="number" placeholder="0" value="${v.regular_price||''}"
                onchange="epVariations[${i}].regular_price=this.value;epSyncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input-ep" type="number" placeholder="0" value="${v.sale_price||''}"
                onchange="epVariations[${i}].sale_price=this.value;epSyncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input-ep" type="number" placeholder="0" min="0" step="0.01" value="${v.prepaid_discount_amount||0}"
                onchange="epVariations[${i}].prepaid_discount_amount=parseFloat(this.value)||0;epSyncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:80px">
            <input class="vb-input-ep" type="number" placeholder="0" value="${v.stock_quantity||0}"
                onchange="epVariations[${i}].stock_quantity=this.value;epSyncVariationsJson()" style="width:75px">
        </td>
        <td style="min-width:80px">
            ${imgThumb}
            <label style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border:1px solid #111;border-radius:8px;background:#111;color:#fff;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload
                <input type="file" accept="image/*" style="display:none" onchange="epUploadVarImage(this,${i})">
            </label>
            <input type="hidden" id="epVbImg${i}" value="${epEscHtml(v.image||'')}">
        </td>
        <td style="min-width:110px">
            <div class="ep-vb-dropdown" style="position:relative;display:inline-block;width:108px">
                <button type="button"
                    onclick="epToggleDropdown(this)"
                    style="width:108px;height:36px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;
                           color:#111;font-size:.84rem;font-weight:600;padding:0 32px 0 10px;text-align:left;
                           cursor:pointer;display:flex;align-items:center;justify-content:space-between;
                           white-space:nowrap;outline:none"
                    onmouseover="this.style.borderColor='#facc15'"
                    onmouseout="if(!this.closest('.ep-vb-dropdown').classList.contains('ep-dd-open'))this.style.borderColor='#e5e7eb'">
                    <span>${v.status==1?'Active':'Inactive'}</span>
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" style="flex-shrink:0;margin-left:4px">
                        <path d="M2 3.5L5 6.5L8 3.5" stroke="#111" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="ep-dd-menu" style="display:none;position:fixed;left:0;top:0;width:108px;
                     background:#fff;border:1px solid #e5e7eb;border-radius:8px;
                     box-shadow:0 8px 20px rgba(17,17,17,.14);z-index:99999;overflow:hidden">
                    <button type="button" data-val="1"
                        onclick="epPickStatus(this,${i})"
                        style="width:100%;padding:8px 12px;text-align:left;border:0;background:${v.status==1?'#facc15':'#fff'};
                               color:#111;font-size:.84rem;font-weight:600;cursor:pointer;display:block"
                        onmouseover="if(this.dataset.val!=epVariations[${i}].status)this.style.background='#fffbeb'"
                        onmouseout="if(this.dataset.val!=epVariations[${i}].status)this.style.background='#fff'">Active</button>
                    <button type="button" data-val="0"
                        onclick="epPickStatus(this,${i})"
                        style="width:100%;padding:8px 12px;text-align:left;border:0;background:${v.status==0?'#facc15':'#fff'};
                               color:#111;font-size:.84rem;font-weight:600;cursor:pointer;display:block;border-top:1px solid #f3f4f6"
                        onmouseover="if(this.dataset.val!=epVariations[${i}].status)this.style.background='#fffbeb'"
                        onmouseout="if(this.dataset.val!=epVariations[${i}].status)this.style.background='#fff'">Inactive</button>
                </div>
            </div>
        </td>
        <td style="text-align:center;min-width:60px">
            <label style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;cursor:pointer">
                <input type="checkbox" ${v.is_default?'checked':''} onchange="epSetDefault(${i},this.checked)"
                    style="width:18px;height:18px;accent-color:#facc15;cursor:pointer">
            </label>
        </td>
        <td style="text-align:center;min-width:40px">
            <button type="button" onclick="epRemoveVariation(${i})"
                style="width:32px;height:32px;padding:0;border-radius:8px;font-size:16px;line-height:1;
                       border:1px solid #e5e7eb!important;background:#fff!important;color:#9ca3af!important;
                       cursor:pointer;display:inline-flex;align-items:center;justify-content:center"
                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444';this.style.background='#fff5f5'"
                onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#9ca3af';this.style.background='#fff'"><i class="fas fa-times"></i></button>
        </td>
    </tr>`;
}

function epSetDefault(idx, checked) {
    epVariations.forEach((v,i) => v.is_default = (i===idx&&checked)?1:0);
    epSyncVariationsJson();
}

function epRemoveVariation(idx) {
    epVariations.splice(idx,1);
    epRenderVariationRows();
}

function epSyncVariationsJson() {
    document.getElementById('epVariationsJson').value = JSON.stringify(epVariations);
}

//  Drag-and-Drop Row Reordering 
let epDragIdx = null;

function epDragStart(event, idx) {
    epDragIdx = idx;
    event.dataTransfer.effectAllowed = 'move';
    // Style the dragged row
    setTimeout(function() {
        const row = document.getElementById('epRow' + idx);
        if (row) row.style.opacity = '0.4';
    }, 0);
}

function epDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    // Highlight target row
    const tr = event.currentTarget;
    document.querySelectorAll('#epVarTbody tr').forEach(function(r) {
        r.style.borderTop = '';
        r.style.background = '';
    });
    tr.style.borderTop = '2px solid #facc15';
    tr.style.background = '#fffbeb';
}

function epDrop(event, targetIdx) {
    event.preventDefault();
    if (epDragIdx === null || epDragIdx === targetIdx) return;

    // Reorder the array
    const moved = epVariations.splice(epDragIdx, 1)[0];
    epVariations.splice(targetIdx, 0, moved);

    epDragIdx = null;
    epRenderVariationRows();
    epSyncVariationsJson();
    epToast('Row reordered successfully.', 'success');
}

function epDragEnd(event) {
    epDragIdx = null;
    document.querySelectorAll('#epVarTbody tr').forEach(function(r) {
        r.style.opacity    = '';
        r.style.borderTop  = '';
        r.style.background = '';
        r.setAttribute('draggable', 'false');
    });
}

// Custom dropdown helpers for status
function epCloseAllDropdowns() {
    document.querySelectorAll('.ep-vb-dropdown.ep-dd-open').forEach(function(d) {
        d.classList.remove('ep-dd-open');
        d.querySelector('.ep-dd-menu').style.display = 'none';
        var tb = d.querySelector(':scope > button');
        if (tb) { tb.style.borderColor = '#e5e7eb'; tb.style.boxShadow = 'none'; }
    });
}
function epToggleDropdown(btn) {
    const wrap = btn.closest('.ep-vb-dropdown');
    const isOpen = wrap.classList.contains('ep-dd-open');
    epCloseAllDropdowns();
    if (!isOpen) {
        const menu = wrap.querySelector('.ep-dd-menu');
        const rect = btn.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.top    = (rect.bottom + 4) + 'px';
        menu.style.left   = rect.left + 'px';
        menu.style.width  = rect.width + 'px';
        wrap.classList.add('ep-dd-open');
        btn.style.borderColor = '#facc15';
        btn.style.boxShadow   = '0 0 0 3px rgba(250,204,21,.18)';
    }
}

function epPickStatus(optBtn, idx) {
    const val = parseInt(optBtn.dataset.val);
    epVariations[idx].status = val;
    epSyncVariationsJson();
    // Update trigger button text
    const wrap = optBtn.closest('.ep-vb-dropdown');
    const trigger = wrap.querySelector(':scope > button > span');
    if (trigger) trigger.textContent = val === 1 ? 'Active' : 'Inactive';
    // Update option highlights
    wrap.querySelectorAll('.ep-dd-menu button').forEach(function(b) {
        b.style.background = parseInt(b.dataset.val) === val ? '#facc15' : '#fff';
    });
    // Close
    wrap.classList.remove('ep-dd-open');
    wrap.querySelector('.ep-dd-menu').style.display = 'none';
    wrap.querySelector(':scope > button').style.borderColor = '#e5e7eb';
    wrap.querySelector(':scope > button').style.boxShadow = 'none';
}

// Close dropdowns on outside click or scroll
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ep-vb-dropdown')) epCloseAllDropdowns();
});
document.addEventListener('scroll', epCloseAllDropdowns, true);

async function epUploadVarImage(input, idx) {
    const file = input.files[0];
    if (!file) return;
    const label = input.closest('label');
    if (label) { label.style.opacity='0.6'; label.textContent='Uploading...'; }
    const fd = new FormData();
    fd.append('variation_image', file);
    try {
        const res = await fetch('ajax-upload-variation-image.php', {method:'POST', body:fd});
        const data = await res.json();
        if (data.success) {
            epVariations[idx].image = data.url;
            document.getElementById('epVbImg'+idx).value = data.url;
            epSyncVariationsJson();
            // Update the thumbnail
            const thumb = document.getElementById('epThumb'+idx);
            if (thumb) {
                const img = document.createElement('img');
                img.src = epBase(data.url);
                img.id = 'epThumb'+idx;
                img.style = 'width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;margin-bottom:4px';
                thumb.parentNode.replaceChild(img, thumb);
            }
        } else { alert(data.message||'Upload failed.'); }
    } catch(e) { alert('Upload error: ' + e.message); }
    finally {
        if (label) { label.style.opacity='1'; label.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload<input type="file" accept="image/*" style="display:none" onchange="epUploadVarImage(this,'+idx+')">'; }
    }
}

// Sync before submit
document.querySelector('form').addEventListener('submit', function() { epSyncVariationsJson(); });
</script>
<?php if ($flashMessage !== null): ?>
    <div id="ep-toast" class="ep-toast <?= $flashType === 'error' ? 'ep-toast-error' : 'ep-toast-success' ?>">
        <?= htmlspecialchars($flashMessage) ?>
    </div>
<?php endif; ?>
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

    initCustomDropdowns(document);

    const toast = document.getElementById('ep-toast');
    if (toast) {
        requestAnimationFrame(() => toast.classList.add('is-show'));
        setTimeout(() => toast.classList.remove('is-show'), 3200);
    }
});

function initCustomDropdowns(scope) {
    const selects = scope.querySelectorAll('select[data-ep-custom-dropdown]');
    selects.forEach((select) => {
        if (select.dataset.epCustomReady === '1') return;
        select.dataset.epCustomReady = '1';
        select.classList.add('ep-native-dropdown');

        const wrap = document.createElement('div');
        wrap.className = 'ep-dropdown';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        const display = document.createElement('button');
        display.type = 'button';
        display.className = 'ep-dropdown-display';
        wrap.appendChild(display);

        const menu = document.createElement('div');
        menu.className = 'ep-dropdown-options';
        wrap.appendChild(menu);

        function renderOptions() {
            menu.innerHTML = '';
            Array.from(select.options).forEach((opt, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ep-dropdown-option' + (idx === select.selectedIndex ? ' is-active' : '');
                btn.textContent = opt.text;
                btn.dataset.value = opt.value;
                btn.addEventListener('click', function () {
                    select.value = this.dataset.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncDisplay();
                    renderOptions();
                    wrap.classList.remove('is-open');
                });
                menu.appendChild(btn);
            });
        }

        function syncDisplay() {
            const selected = select.options[select.selectedIndex];
            display.textContent = selected ? selected.text : 'Select';
        }

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.ep-dropdown.is-open').forEach((node) => {
                if (node !== wrap) node.classList.remove('is-open');
            });
            wrap.classList.toggle('is-open');
        });

        select.addEventListener('change', function () {
            syncDisplay();
            renderOptions();
        });

        syncDisplay();
        renderOptions();
    });

    if (!window.epDropdownCloseBound) {
        window.epDropdownCloseBound = true;
        document.addEventListener('click', function (e) {
            document.querySelectorAll('.ep-dropdown.is-open').forEach((node) => {
                if (!node.contains(e.target)) node.classList.remove('is-open');
            });
        });
    }
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
            <select name="attributes[${attributeIndex}][attribute_id]" onchange="loadAttributeValues(this)" required data-ep-custom-dropdown>
                <option value="">Select Attribute</option>
                ${attributeOptions}
            </select>
        </div>
        <div>
            <label>Value</label>
            <select name="attributes[${attributeIndex}][value_id]" required data-ep-custom-dropdown>
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
    initCustomDropdowns(newAttributeDiv);
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
        fetch('<?= htmlspecialchars(url('admin/remove-attribute.php')) ?>', {
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

// ===== Edit Product - SEO Auto-generation =====
function epSeoGetMonthYear() {
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const d = new Date();
    return months[d.getMonth()] + ' ' + d.getFullYear();
}

function epSeoGenerateTitle() {
    const nameEl = document.querySelector('input[name="product_name"]');
    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) return;
    const title = name + ' Price in Pakistan ' + epSeoGetMonthYear() + ' | Phones Dukan';
    const field = document.getElementById('ep_seo_title');
    if (field) { field.value = title; epSeoUpdateCharCounter('ep_seo_title', 60); epSeoUpdateSnippetPreview(); }
}

function epStripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
}

function epSeoGenerateDescription() {
    const nameEl = document.querySelector('input[name="product_name"]');
    const shortEl = document.querySelector('textarea[name="short_description"]');
    const name = nameEl ? nameEl.value.trim() : '';
    // Strip HTML tags from short description before using as meta description
    const shortRaw = shortEl ? shortEl.value.trim() : '';
    const shortDesc = epStripHtml(shortRaw);
    if (!name) return;
    let desc;
    if (shortDesc.length >= 50) {
        desc = shortDesc.length > 155 ? shortDesc.substring(0, 152) + '...' : shortDesc;
    } else {
        desc = 'Buy ' + name + ' in Pakistan at the best price. PTA-approved with official warranty. Fast delivery across Pakistan. Shop now at Phones Dukan.';
    }
    if (desc.length > 160) desc = desc.substring(0, 157) + '...';
    const field = document.getElementById('ep_seo_description');
    if (field) { field.value = desc; epSeoUpdateCharCounter('ep_seo_description', 160); epSeoUpdateSnippetPreview(); }
}

function epSeoGenerateFocusKeyword() {
    const nameEl = document.querySelector('input[name="product_name"]');
    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) return;
    const field = document.getElementById('ep_focus_keyword');
    if (field) field.value = name.toLowerCase() + ' price in pakistan';
}

function epSeoAutoFillAll() {
    epSeoGenerateTitle();
    epSeoGenerateDescription();
    epSeoGenerateFocusKeyword();
}

function epSeoUpdateSnippetPreview() {
    const titleField = document.getElementById('ep_seo_title');
    const descField  = document.getElementById('ep_seo_description');
    const nameEl     = document.querySelector('input[name="product_name"]');
    const title = (titleField ? titleField.value.trim() : '') || (nameEl ? nameEl.value.trim() : '') || 'Product Title';
    const desc  = (descField  ? descField.value.trim()  : '') || 'Product meta description will appear here...';
    const tEl = document.getElementById('ep_snippet_title');
    const dEl = document.getElementById('ep_snippet_desc');
    if (tEl) tEl.textContent = title.length > 60 ? title.substring(0, 60) + '...' : title;
    if (dEl) dEl.textContent = desc.length > 160  ? desc.substring(0, 160) + '...' : desc;
}

function epSeoUpdateCharCounter(fieldId, limit) {
    const field   = document.getElementById(fieldId);
    const counter = document.getElementById(fieldId.replace('ep_seo_title','ep_seo_title_counter').replace('ep_seo_description','ep_seo_desc_counter'));
    if (!field) return;
    // Derive counter ID from field ID
    let counterId = fieldId === 'ep_seo_title' ? 'ep_seo_title_counter' : 'ep_seo_desc_counter';
    const cEl = document.getElementById(counterId);
    if (!cEl) return;
    const len = field.value.length;
    cEl.textContent = len + '/' + limit;
    cEl.className = 'ep-seo-char-counter ';
    if (len === 0 || len < Math.floor(limit * 0.6)) cEl.className += 'too-short';
    else if (len > limit) cEl.className += 'too-long';
    else cEl.className += 'good';
}

// Init SEO counters and preview on page load
document.addEventListener('DOMContentLoaded', function() {
    epSeoUpdateCharCounter('ep_seo_title', 60);
    epSeoUpdateCharCounter('ep_seo_description', 160);
    epSeoUpdateSnippetPreview();
});

// ===== Edit Product - SKU Auto-generation =====
function epGenerateProductSku() {
    const catSelect   = document.querySelector('select[name="category_id"]');
    const brandSelect = document.querySelector('select[name="brand_id"]');
    const catName     = catSelect   ? (catSelect.options[catSelect.selectedIndex]?.textContent     || '').trim() : '';
    const brandName   = brandSelect ? (brandSelect.options[brandSelect.selectedIndex]?.textContent || '').trim() : '';
    const brandCode   = brandName.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '') || 'PD';
    const catCode     = catName.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '')   || 'GEN';
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let rand = '';
    for (let i = 0; i < 4; i++) rand += chars[Math.floor(Math.random() * chars.length)];
    const skuField = document.getElementById('ep_product_sku');
    if (skuField) skuField.value = brandCode + '-' + catCode + '-' + rand;
}

function epSkuAbbrev(str) {
    return String(str || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 3);
}

function epGenerateAllVariationSkus() {
    if (!epVariations.length) { alert('Generate combinations first, then generate SKUs.'); return; }
    const skuField   = document.getElementById('ep_product_sku');
    const productSku = skuField ? skuField.value.trim() : '';
    const base       = productSku || 'PD';
    let filled = 0;
    epVariations.forEach(function(v, i) {
        if (v.sku && v.sku.trim()) return; // never overwrite an existing SKU
        const attrParts = Object.entries(v.attributes || {}).map(function([tid, vid]) {
            const t   = EP_VB_TYPES.find(function(x) { return x.id == tid; });
            const val = t ? (t.values || []).find(function(x) { return x.id == vid; }) : null;
            return val ? epSkuAbbrev(val.value) : '';
        }).filter(Boolean);
        v.sku = base + (attrParts.length ? '-' + attrParts.join('-') : '-V' + (i + 1));
        filled++;
    });
    if (filled === 0) {
        epToast('All variations already have SKUs. Clear them first to regenerate.', 'info');
        return;
    }
    epRenderVariationRows();
    epToast(filled + ' variation SKU' + (filled !== 1 ? 's' : '') + ' generated.', 'success');
}

async function loadAttributeValues(selectElement) {
    let attributeId = selectElement.value;
    const attributeBlock = selectElement.closest('.attribute');
    let valueSelect = attributeBlock ? attributeBlock.querySelector('select[name$="[value_id]"]') : null;
    if (!valueSelect) {
        return;
    }
    valueSelect.innerHTML = '<option value="">Select Value</option>';

    if (attributeId) {
        try {
            let response = await fetch(`<?= htmlspecialchars(url('admin/get-attribute-values.php')) ?>?attribute_id=${attributeId}`);
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

<!-- AI SEO Assistant JS -->
<script src="js/ai-seo.js?v=2.7"></script>
<script src="js/product-media.js?v=1.2"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    AISeo.init({
        prefix:    'ep_',
        productId: <?= (int)($product['product_id'] ?? 0) ?>,
        apiUrl:    'api/ai-seo.php',
    });
    ProductMedia.init({
        mode: 'edit',
        productId: <?= (int)($product['product_id'] ?? 0) ?>,
        basePath: <?= json_encode(rtrim((defined('BASE_PATH') ? BASE_PATH : ''), '/')) ?>,
        existingVideo: <?= json_encode($productVideo ?: null, JSON_UNESCAPED_UNICODE) ?>,
        existingVideoId: <?= (int)($productVideo['video_id'] ?? 0) ?>,
        initialGalleryItems: <?= json_encode($galleryOrderItems, JSON_UNESCAPED_UNICODE) ?>,
        ajaxUploadUrl: 'ajax-upload-product-video.php',
        ajaxPreviewUrl: 'ajax-fetch-video-preview.php',
    });
});
</script>







