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
$categories_query = "SELECT category_id, category_name, slug FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);

$brands_query = "SELECT brand_id, brand_name, slug FROM brands ORDER BY brand_name";
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

<style>
/* ---- Variation Builder styles ---- */

/* Override admin theme's global span/label color inside the variation section */
#variationBuilderWrap,
#variationBuilderWrap * {
    background-color: transparent;
    color: #111111;
}
#variationBuilderWrap span,
#variationBuilderWrap label,
#variationBuilderWrap p,
#variationBuilderWrap th,
#variationBuilderWrap td {
    color: #111111 !important;
    background: transparent !important;
    background-color: transparent !important;
}
#variationBuilderWrap .vb-step-title { color: #111111 !important; }
#variationBuilderWrap .vb-type-badge { color: #6b7280 !important; }

.vb-toggle-wrap { position:relative; display:inline-block; }
.vb-toggle-track { width:46px; height:24px; border-radius:999px; background:#e5e7eb; transition:background .2s; cursor:pointer; }
input#enableVariations:checked + .vb-toggle-track { background:#facc15; }
.vb-toggle-thumb { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.18); transition:left .2s; }
input#enableVariations:checked ~ .vb-toggle-track .vb-toggle-thumb,
input#enableVariations:checked + .vb-toggle-track .vb-toggle-thumb { left:25px; }
.vb-toggle-label { user-select:none; }
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
.vb-btn {
    display:inline-flex; align-items:center; height:40px; padding:0 16px;
    border:1px solid #111; border-radius:10px; background:#111 !important; color:#fff !important;
    font-size:.86rem; font-weight:700; cursor:pointer; text-decoration:none;
    transition:color .12s,transform .12s,box-shadow .12s;
}
.vb-btn:hover { color:#facc15 !important; transform:translateY(-1px); box-shadow:0 6px 14px rgba(17,17,17,.14); }
.vb-btn-outline { background:#fff !important; color:#111 !important; border-color:#e5e7eb; }
.vb-btn-outline:hover { border-color:#facc15; color:#111 !important; background:#fffbeb !important; }
.vb-btn-sm { height:30px; padding:0 10px; font-size:.78rem; border-radius:8px; }
.vb-btn-del { background:#fff !important; color:#111 !important; border-color:#e5e7eb; }
.vb-btn-del:hover { color:#ef4444 !important; border-color:#ef4444; background:#fff0f0 !important; transform:none; box-shadow:none; }
.vb-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.vb-table th { background:#f8fafc !important; color:#111 !important; font-weight:800; padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.vb-table td { padding:8px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; color:#111 !important; background:transparent !important; }
.vb-table tr:last-child td { border-bottom:0; }
.vb-input {
    height:36px; border:1px solid #e5e7eb; border-radius:8px; padding:0 10px;
    font-size:.84rem; color:#111 !important; background:#fff !important; outline:none; width:100%;
    transition:border-color .15s;
}
.vb-input:focus { border-color:#facc15; }
.vb-input[type=number] { max-width:120px; }
select.vb-input { cursor:pointer; }

/* ---- SEO Section enhancements ---- */
.seo-preview-box {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-left: 3px solid #4285f4;
    border-radius: 10px;
    padding: 14px 16px;
    margin: 0 0 20px;
    font-family: Arial, sans-serif;
}
.seo-preview-label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 8px;
}
.seo-preview-url { font-size: .78rem; color: #1e8e3e; margin-bottom: 3px; word-break: break-all; }
.seo-preview-title { font-size: 1.05rem; color: #1a0dab; margin-bottom: 4px; word-break: break-word; }
.seo-preview-desc { font-size: .85rem; color: #4d5156; line-height: 1.4; word-break: break-word; }
.seo-char-counter { font-size: .75rem; font-weight: 700; }
.seo-char-counter.good { color: #16a34a; }
.seo-char-counter.too-long { color: #ef4444; }
.seo-char-counter.too-short { color: #f97316; }
.seo-field-wrap { display: flex; gap: 8px; align-items: flex-start; }
.seo-field-wrap > input, .seo-field-wrap > textarea { flex: 1 1 0%; min-width: 0; }
.seo-auto-btn {
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
.seo-auto-btn:hover { color: #facc15; }
.seo-field-hint { display: block; color: #9ca3af; font-size: .76rem; margin-top: 4px; }
.seo-fill-all-btn {
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
.seo-fill-all-btn:hover { background: #eab308; }
.discount-badge {
    display: none;
    align-items: center;
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: .76rem;
    font-weight: 700;
    margin-top: 4px;
}
</style>

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
                    <input id="product_name" type="text" name="product_name" placeholder="Enter product name" required>
                </div>
                <div class="form-group">
                    <label for="product_slug">Product Slug</label>
                    <input id="product_slug" type="text" name="product_slug" placeholder="product-name-slug" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <div class="custom-select" data-select-id="category_id">
                        <select class="native-select" id="category_id" name="category_id" required>
                            <?php while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) { ?>
                                <option value="<?php echo $row['category_id']; ?>" data-slug="<?php echo htmlspecialchars($row['slug'] ?? ''); ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                            <?php } ?>
                        </select>
                        <button class="custom-select-btn" type="button" aria-haspopup="listbox" aria-expanded="false">
                            <span class="custom-select-value">Select category</span>
                            <span class="custom-select-arrow" aria-hidden="true"></span>
                        </button>
                        <div class="custom-select-menu" role="listbox" tabindex="-1"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <div class="custom-select" data-select-id="brand_id">
                        <select class="native-select" id="brand_id" name="brand_id" required>
                            <?php while ($row = $brands_result->fetch(PDO::FETCH_ASSOC)) { ?>
                                <option value="<?php echo $row['brand_id']; ?>" data-slug="<?php echo htmlspecialchars($row['slug'] ?? ''); ?>"><?php echo htmlspecialchars($row['brand_name']); ?></option>
                            <?php } ?>
                        </select>
                        <button class="custom-select-btn" type="button" aria-haspopup="listbox" aria-expanded="false">
                            <span class="custom-select-value">Select brand</span>
                            <span class="custom-select-arrow" aria-hidden="true"></span>
                        </button>
                        <div class="custom-select-menu" role="listbox" tabindex="-1"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Description</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group full-width">
                    <label for="product_description">Product Description</label>
                    <textarea id="product_description" name="product_description" placeholder="Write full product description" required></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" placeholder="Write short product summary" required></textarea>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Pricing &amp; Inventory</h2></div>
            <div class="form-grid cols-4">
                <div class="form-group">
                    <label for="regular_price">Regular Price</label>
                    <input id="regular_price" type="number" name="regular_price" placeholder="4999" required>
                </div>
                <div class="form-group">
                    <label for="sale_price">Sale Price</label>
                    <input id="sale_price" type="number" name="sale_price" placeholder="4499">
                    <span id="discount_badge" class="discount-badge"></span>
                </div>
                <div class="form-group">
                    <label for="prepaid_discount_amount">Prepaid Discount Amount (PKR)</label>
                    <input id="prepaid_discount_amount" type="number" name="prepaid_discount_amount" step="0.01" min="0" placeholder="0" value="0">
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input id="stock_quantity" type="number" name="stock_quantity" placeholder="25" required>
                </div>
                <div class="form-group">
                    <label for="product_sku">Product SKU</label>
                    <div class="seo-field-wrap">
                        <input id="product_sku" type="text" name="product_sku" placeholder="SAM-MOB-X7K2">
                        <button type="button" class="seo-auto-btn" onclick="generateProductSku()" title="Auto-generate SKU from brand and category">Auto</button>
                    </div>
                    <span class="seo-field-hint">Format: BRAND-CATEGORY-RANDOM e.g. SAM-MOB-X7K2</span>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Product Details</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="weight_kg">Weight (kg)</label>
                    <input id="weight_kg" type="text" name="weight_kg" placeholder="0.5">
                </div>
                <div class="form-group">
                    <label for="tax_class">Tax Class</label>
                    <input id="tax_class" type="text" name="tax_class" placeholder="Standard">
                </div>
                <div class="form-group full-width">
                    <label>Dimensions (L x W x H in cm)</label>
                    <div class="dimensions-group">
                        <input type="text" name="length_cm" placeholder="15">
                        <input type="text" name="width_cm" placeholder="7">
                        <input type="text" name="height_cm" placeholder="1">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="product_tag">Product Tags</label>
                    <input id="product_tag" type="text" name="product_tag" placeholder="mobile, apple, iphone">
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Status</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="product_status">Product Status</label>
                    <div class="custom-select" data-select-id="product_status">
                        <select class="native-select" id="product_status" name="product_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button class="custom-select-btn" type="button" aria-haspopup="listbox" aria-expanded="false">
                            <span class="custom-select-value">Select status</span>
                            <span class="custom-select-arrow" aria-hidden="true"></span>
                        </button>
                        <div class="custom-select-menu" role="listbox" tabindex="-1"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <h2>SEO Information</h2>
                <button type="button" class="seo-fill-all-btn" onclick="seoAutoFillAll()">Auto-Fill All SEO</button>
            </div>

            <!-- Google Search Snippet Preview -->
            <div class="seo-preview-box">
                <span class="seo-preview-label">Google Search Preview</span>
                <div class="seo-preview-url" id="snippet_url">www.phonesdukan.com/category/brand/product-slug/</div>
                <div class="seo-preview-title" id="snippet_title">Product SEO Title</div>
                <div class="seo-preview-desc" id="snippet_desc">Product SEO description will appear here...</div>
            </div>

            <div class="form-grid cols-2">
                <div class="form-group full-width">
                    <label for="seo_title" style="display:flex;justify-content:space-between;align-items:center">
                        <span>SEO Title</span>
                        <span id="seo_title_counter" class="seo-char-counter too-short">0/60</span>
                    </label>
                    <div class="seo-field-wrap">
                        <input id="seo_title" type="text" name="seo_title"
                            placeholder="Product Name Price in Pakistan May 2026 | Phones Dukan"
                            oninput="seoUpdateCharCounter('seo_title',60);seoUpdateSnippetPreview()">
                        <button type="button" class="seo-auto-btn" onclick="seoGenerateSeoTitle()" title="Auto-generate from product name">Auto</button>
                    </div>
                    <span class="seo-field-hint">Recommended: 50–60 characters. Pattern: "{Name} Price in Pakistan {Month Year} | Phones Dukan"</span>
                </div>

                <div class="form-group">
                    <label for="focus_keyword">Focus Keyword</label>
                    <div class="seo-field-wrap">
                        <input id="focus_keyword" type="text" name="focus_keyword"
                            placeholder="product name price in pakistan">
                        <button type="button" class="seo-auto-btn" onclick="seoGenerateFocusKeyword()" title="Auto-generate from product name">Auto</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="secondary_keywords">Secondary Keywords</label>
                    <input id="secondary_keywords" type="text" name="secondary_keywords"
                        placeholder="buy online, best price, official warranty, pakistan">
                    <span class="seo-field-hint">Comma-separated. Supporting search terms for this product.</span>
                </div>

                <div class="form-group full-width">
                    <label for="seo_description" style="display:flex;justify-content:space-between;align-items:center">
                        <span>SEO Meta Description</span>
                        <span id="seo_description_counter" class="seo-char-counter too-short">0/160</span>
                    </label>
                    <div class="seo-field-wrap">
                        <textarea id="seo_description" name="seo_description"
                            placeholder="Buy product name in Pakistan at the best price. PTA-approved with official warranty. Fast delivery..."
                            oninput="seoUpdateCharCounter('seo_description',160);seoUpdateSnippetPreview()"></textarea>
                        <button type="button" class="seo-auto-btn" onclick="seoGenerateSeoDescription()" title="Auto-generate from short description" style="align-self:flex-start">Auto</button>
                    </div>
                    <span class="seo-field-hint">Recommended: 140–160 characters. Auto-generates from short description or product name.</span>
                </div>

                <div class="form-group full-width">
                    <label for="canonical_url">Canonical URL <small style="color:#9ca3af;font-weight:400">(auto-generated, read-only)</small></label>
                    <input id="canonical_url" type="text" name="canonical_url" readonly
                        placeholder="https://www.phonesdukan.com/category/brand/product-slug/"
                        style="background:#f8fafc;color:#6b7280;cursor:default;font-size:.88rem">
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><h2>Product Images</h2></div>
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label for="primary_image">Primary Image</label>
                    <label class="upload-box" for="primary_image">
                        <span class="upload-icon" aria-hidden="true">
                            <i class="fas fa-upload"></i>
                        </span>
                        <span class="upload-title">Click to upload primary image</span>
                        <span class="upload-help">PNG, JPG, WEBP up to 5MB</span>
                        <span class="upload-filename" data-for="primary_image">No file selected</span>
                    </label>
                    <input class="file-input" type="file" name="primary_image" id="primary_image" required>
                </div>
                <div class="form-group">
                    <label for="gallery_images">Gallery Images</label>
                    <label class="upload-box" for="gallery_images">
                        <span class="upload-icon" aria-hidden="true">
                            <i class="fas fa-images"></i>
                        </span>
                        <span class="upload-title">Click to upload gallery images</span>
                        <span class="upload-help">Upload multiple PNG, JPG, or WEBP images</span>
                        <span class="upload-filename" data-for="gallery_images">No files selected</span>
                    </label>
                    <input class="file-input" type="file" name="gallery_images[]" id="gallery_images" multiple onchange="previewGalleryImages(event)">
                </div>
                <div class="form-group">
                    <label for="primary_alt_text">Alt Text for Primary Image</label>
                    <input type="text" name="primary_alt_text" id="primary_alt_text" placeholder="Product image alt text" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_title">Title for Primary Image</label>
                    <input type="text" name="primary_image_title" id="primary_image_title" placeholder="Product image title" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_caption">Caption for Primary Image</label>
                    <input type="text" name="primary_image_caption" id="primary_image_caption" placeholder="Product image caption" required>
                </div>
                <div class="form-group">
                    <label for="primary_image_description">Description for Primary Image</label>
                    <input type="text" name="primary_image_description" id="primary_image_description" placeholder="Product image description" required>
                </div>
            </div>

            <div id="gallery_images_metadata" class="gallery-metadata-grid"></div>
        </section>

        <!-- ============================================================
             PRODUCT VARIATIONS SECTION
             ============================================================ -->
        <section class="form-card" id="variationsSection">
            <div class="form-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <h2 style="margin:0">Product Variations</h2>
                    <p style="margin:4px 0 0;color:#6b7280;font-size:.88rem">Enable for products with multiple options like Color, RAM, Storage</p>
                </div>
                <label class="vb-toggle-label" style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <div class="vb-toggle-wrap">
                        <input type="checkbox" id="enableVariations" style="display:none">
                        <div class="vb-toggle-track"><div class="vb-toggle-thumb"></div></div>
                    </div>
                    <span style="font-weight:700;font-size:.9rem" id="variationToggleText">Variable Product</span>
                </label>
            </div>

            <div id="variationBuilderWrap" style="display:none;padding:20px">

                <!-- Step 1: Select variation types for this product -->
                <div class="vb-step">
                    <div class="vb-step-title">Step 1: Select Variation Types</div>
                    <div class="vb-step-body">
                        <div id="vbTypeCheckboxes" class="vb-type-grid">
                            <!-- Populated by JS from AJAX -->
                            <p style="color:#9ca3af;font-size:.88rem">Loading variation types…</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Choose values per selected type -->
                <div class="vb-step" id="vbStep2" style="display:none">
                    <div class="vb-step-title">Step 2: Choose Values Per Type</div>
                    <div class="vb-step-body" id="vbValueSelectors"></div>
                </div>

                <!-- Step 3: Generate & manage combinations -->
                <div class="vb-step" id="vbStep3" style="display:none">
                    <div class="vb-step-title">Step 3: Variation Combinations</div>
                    <div class="vb-step-body">
                        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
                            <button type="button" class="vb-btn" onclick="generateVariations()">Generate All Combinations</button>
                            <button type="button" class="vb-btn vb-btn-outline" onclick="addBlankVariation()">+ Add Row Manually</button>
                            <button type="button" class="vb-btn vb-btn-outline" onclick="generateAllVariationSkus()" title="Auto-fill empty SKU fields for all variations">Generate SKUs</button>
                        </div>
                        <div id="vbCombinationsWrap"></div>
                    </div>
                </div>

                <input type="hidden" name="product_type" id="productTypeInput" value="simple">
                <input type="hidden" name="variations_json" id="variationsJson" value="[]">
            </div>
        </section>

        <div class="form-footer-actions">
            <button type="submit" class="submit-btn">Save Product</button>
        </div>
    </form>
</div>

<?php
// Load all variation types for the builder
require_once dirname(__DIR__, 1) . '/app/Models/VariationModel.php';
$vModel = new VariationModel();
$allVarTypes = $vModel->getVariationTypesWithValues();
?>

<script>
const VB_ADMIN_BASE = <?= json_encode(rtrim((defined('BASE_PATH') ? BASE_PATH : ''), '/')) ?>;
function vbBase(path) {
    if (!path || !path.startsWith('/')) return path;
    if (VB_ADMIN_BASE && !path.startsWith(VB_ADMIN_BASE + '/')) return VB_ADMIN_BASE + path;
    return path;
}
const VB_TYPES = <?= json_encode($allVarTypes, JSON_UNESCAPED_UNICODE) ?>;
let vbSelectedTypes = [];   // [{id, name, display_type, values:[]}]
let vbSelectedValues = {};  // {type_id: [value_id, …]}
let vbVariations = [];      // final combination rows

// ---- Toggle ----
document.getElementById('enableVariations').addEventListener('change', function() {
    const wrap = document.getElementById('variationBuilderWrap');
    document.getElementById('productTypeInput').value = this.checked ? 'variable' : 'simple';
    document.getElementById('variationToggleText').textContent = this.checked ? 'Variable Product (ON)' : 'Variable Product';
    if (this.checked) {
        wrap.style.display = 'block';
        renderTypeCheckboxes();
    } else {
        wrap.style.display = 'none';
    }
});

function renderTypeCheckboxes() {
    const container = document.getElementById('vbTypeCheckboxes');
    if (!VB_TYPES.length) {
        container.innerHTML = '<p style="color:#9ca3af">No variation types found. <a href="manage-variations.php" target="_blank" style="color:#111;font-weight:700">Create some first.</a></p>';
        return;
    }
    container.innerHTML = VB_TYPES.map(t => `
        <label class="vb-type-check">
            <input type="checkbox" value="${t.id}" onchange="onTypeToggle(${t.id}, this.checked)">
            <span>${escHtml(t.name)}</span>
            <span class="vb-type-badge">${t.values.length} values</span>
        </label>
    `).join('');
}

function onTypeToggle(type_id, checked) {
    const t = VB_TYPES.find(x => x.id == type_id);
    if (!t) return;
    if (checked) {
        if (!vbSelectedTypes.find(x => x.id == type_id)) vbSelectedTypes.push(t);
        if (!vbSelectedValues[type_id]) vbSelectedValues[type_id] = [];
    } else {
        vbSelectedTypes = vbSelectedTypes.filter(x => x.id != type_id);
        delete vbSelectedValues[type_id];
    }
    renderValueSelectors();
    refreshStep3();
}

function renderValueSelectors() {
    const step2 = document.getElementById('vbStep2');
    const container = document.getElementById('vbValueSelectors');
    if (!vbSelectedTypes.length) { step2.style.display='none'; return; }
    step2.style.display='block';
    container.innerHTML = vbSelectedTypes.map(t => `
        <div style="margin-bottom:18px">
            <div style="font-weight:700;font-size:.9rem;margin-bottom:8px">${escHtml(t.name)}</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                ${(t.values||[]).map(v => `
                    <label class="vb-value-pill ${t.display_type}" style="${t.display_type==='color'&&v.color_code?'--swatch:'+v.color_code:''}">
                        <input type="checkbox" value="${v.id}"
                            ${(vbSelectedValues[t.id]||[]).includes(v.id)?'checked':''}
                            onchange="onValueToggle(${t.id}, ${v.id}, this.checked)">
                        ${t.display_type==='color'&&v.color_code
                            ? `<span class="vb-swatch" style="background:${escHtml(v.color_code)}"></span>`
                            : ''}
                        <span>${escHtml(v.value)}</span>
                    </label>
                `).join('')}
                ${!t.values.length ? '<span style="color:#9ca3af;font-size:.85rem">No values. Add values in Manage Variations.</span>' : ''}
            </div>
        </div>
    `).join('');
}

function onValueToggle(type_id, value_id, checked) {
    if (!vbSelectedValues[type_id]) vbSelectedValues[type_id] = [];
    if (checked) {
        if (!vbSelectedValues[type_id].includes(value_id)) vbSelectedValues[type_id].push(value_id);
    } else {
        vbSelectedValues[type_id] = vbSelectedValues[type_id].filter(x => x !== value_id);
    }
    refreshStep3();
}

function refreshStep3() {
    const step3 = document.getElementById('vbStep3');
    const hasSelected = vbSelectedTypes.some(t => (vbSelectedValues[t.id]||[]).length > 0);
    step3.style.display = hasSelected ? 'block' : 'none';
}

function generateVariations() {
    const selectedTypes = vbSelectedTypes.filter(t => (vbSelectedValues[t.id]||[]).length > 0);
    if (!selectedTypes.length) { alert('Select at least one type and value.'); return; }

    const valueSets = selectedTypes.map(t =>
        (vbSelectedValues[t.id]||[]).map(vid => {
            const valObj = (t.values||[]).find(v => v.id == vid);
            return { type_id: t.id, type_name: t.name, value_id: vid, value_name: valObj ? valObj.value : vid };
        })
    );

    const combos = cartesian(valueSets);
    const existing = [...vbVariations];
    const newVariations = [];

    combos.forEach(combo => {
        const attrs = {};
        let label = combo.map(c => { attrs[c.type_id] = c.value_id; return c.type_name + ': ' + c.value_name; }).join(' / ');
        // Check if already exists (by attr key match)
        const key = JSON.stringify(attrs);
        const dupe = existing.find(v => JSON.stringify(v.attributes) === key);
        if (!dupe) {
            newVariations.push({ attributes: attrs, label, sku:'', regular_price:'', sale_price:'', prepaid_discount_amount:0, stock_quantity:0, image:'', status:1, is_default:0 });
        } else {
            newVariations.push(dupe);
        }
    });

    vbVariations = newVariations;
    renderVariationRows();
}

function cartesian(sets) {
    return sets.reduce((acc, set) => acc.flatMap(combo => set.map(item => [...combo, item])), [[]]);
}

function addBlankVariation() {
    vbVariations.push({ attributes:{}, label:'Custom', sku:'', regular_price:'', sale_price:'', prepaid_discount_amount:0, stock_quantity:0, image:'', status:1, is_default:0 });
    renderVariationRows();
}

function renderVariationRows() {
    const wrap = document.getElementById('vbCombinationsWrap');
    if (!vbVariations.length) { wrap.innerHTML = '<p style="color:#9ca3af">No combinations yet. Click "Generate All Combinations".</p>'; syncVariationsJson(); return; }

    wrap.innerHTML = `
        <div style="overflow-x:auto;overflow-y:visible">
        <table class="vb-table" style="overflow:visible">
            <thead><tr>
                <th>Combination</th><th>SKU</th><th>Regular Price</th><th>Sale Price</th><th>Prepaid Disc. (PKR)</th>
                <th>Stock</th><th>Image</th><th>Status</th><th>Default</th><th></th>
            </tr></thead>
            <tbody id="vbTbody">${vbVariations.map((v,i) => renderRow(v,i)).join('')}</tbody>
        </table>
        </div>
    `;
    syncVariationsJson();
}

function renderRow(v, i) {
    const label = v.label || buildLabel(v);
    const imgSrc = v.image ? vbBase(v.image) : '';
    const imgThumb = imgSrc
        ? `<img src="${escHtml(imgSrc)}" id="vbThumb${i}" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;margin-bottom:4px">`
        : `<div id="vbThumb${i}" style="width:48px;height:48px;border-radius:8px;border:1px dashed #e5e7eb;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:20px;margin-bottom:4px;background:#f9fafb">+</div>`;
    return `<tr id="vbRow${i}">
        <td style="min-width:150px">
            <span style="font-weight:700;font-size:.84rem;color:#111;display:block">${escHtml(label)}</span>
        </td>
        <td style="min-width:90px">
            <input class="vb-input" type="text" placeholder="SKU-001" value="${escHtml(v.sku||'')}"
                onchange="vbVariations[${i}].sku=this.value;syncVariationsJson()" style="width:90px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input" type="number" placeholder="0" value="${v.regular_price||''}"
                onchange="vbVariations[${i}].regular_price=this.value;syncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input" type="number" placeholder="0" value="${v.sale_price||''}"
                onchange="vbVariations[${i}].sale_price=this.value;syncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:100px">
            <input class="vb-input" type="number" placeholder="0" min="0" step="0.01" value="${v.prepaid_discount_amount||0}"
                onchange="vbVariations[${i}].prepaid_discount_amount=parseFloat(this.value)||0;syncVariationsJson()" style="width:100px">
        </td>
        <td style="min-width:80px">
            <input class="vb-input" type="number" placeholder="0" value="${v.stock_quantity||0}"
                onchange="vbVariations[${i}].stock_quantity=this.value;syncVariationsJson()" style="width:75px">
        </td>
        <td style="min-width:80px">
            ${imgThumb}
            <label style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border:1px solid #111;border-radius:8px;background:#111;color:#fff;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload
                <input type="file" accept="image/*" style="display:none" onchange="uploadVarImage(this,${i})">
            </label>
            <input type="hidden" id="vbImg${i}" value="${escHtml(v.image||'')}">
        </td>
        <td style="min-width:110px">
            <div class="vb-add-dropdown" style="position:relative;display:inline-block;width:108px">
                <button type="button"
                    onclick="vbToggleDropdown(this)"
                    style="width:108px;height:36px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;
                           color:#111;font-size:.84rem;font-weight:600;padding:0 32px 0 10px;text-align:left;
                           cursor:pointer;display:flex;align-items:center;justify-content:space-between;
                           white-space:nowrap;outline:none"
                    onmouseover="this.style.borderColor='#facc15'"
                    onmouseout="if(!this.closest('.vb-add-dropdown').classList.contains('vb-dd-open'))this.style.borderColor='#e5e7eb'">
                    <span>${v.status==1?'Active':'Inactive'}</span>
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" style="flex-shrink:0;margin-left:4px">
                        <path d="M2 3.5L5 6.5L8 3.5" stroke="#111" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="vb-dd-menu" style="display:none;position:fixed;left:0;top:0;width:108px;
                     background:#fff;border:1px solid #e5e7eb;border-radius:8px;
                     box-shadow:0 8px 20px rgba(17,17,17,.14);z-index:99999;overflow:hidden">
                    <button type="button" data-val="1"
                        onclick="vbPickStatus(this,${i})"
                        style="width:100%;padding:8px 12px;text-align:left;border:0;background:${v.status==1?'#facc15':'#fff'};
                               color:#111;font-size:.84rem;font-weight:600;cursor:pointer;display:block"
                        onmouseover="if(this.dataset.val!=vbVariations[${i}].status)this.style.background='#fffbeb'"
                        onmouseout="if(this.dataset.val!=vbVariations[${i}].status)this.style.background='#fff'">Active</button>
                    <button type="button" data-val="0"
                        onclick="vbPickStatus(this,${i})"
                        style="width:100%;padding:8px 12px;text-align:left;border:0;background:${v.status==0?'#facc15':'#fff'};
                               color:#111;font-size:.84rem;font-weight:600;cursor:pointer;display:block;border-top:1px solid #f3f4f6"
                        onmouseover="if(this.dataset.val!=vbVariations[${i}].status)this.style.background='#fffbeb'"
                        onmouseout="if(this.dataset.val!=vbVariations[${i}].status)this.style.background='#fff'">Inactive</button>
                </div>
            </div>
        </td>
        <td style="text-align:center;min-width:60px">
            <input type="checkbox" ${v.is_default?'checked':''} onchange="setDefault(${i},this.checked)"
                style="width:18px;height:18px;accent-color:#facc15;cursor:pointer">
        </td>
        <td style="text-align:center;min-width:40px">
            <button type="button" onclick="removeVariation(${i})"
                style="width:32px;height:32px;padding:0;border-radius:8px;font-size:16px;line-height:1;
                       border:1px solid #e5e7eb!important;background:#fff!important;color:#9ca3af!important;
                       cursor:pointer;display:inline-flex;align-items:center;justify-content:center"
                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444';this.style.background='#fff5f5'"
                onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#9ca3af';this.style.background='#fff'">✕</button>
        </td>
    </tr>`;
}

function buildLabel(v) {
    return Object.entries(v.attributes||{}).map(([tid,vid]) => {
        const t = VB_TYPES.find(x=>x.id==tid);
        const val = t ? (t.values||[]).find(x=>x.id==vid) : null;
        return (t?t.name:'?') + ': ' + (val?val.value:'?');
    }).join(' / ') || 'Variation';
}

function setDefault(idx, checked) {
    vbVariations.forEach((v,i) => v.is_default = (i===idx && checked) ? 1 : 0);
    syncVariationsJson();
}

function removeVariation(idx) {
    vbVariations.splice(idx, 1);
    renderVariationRows();
}

function syncVariationsJson() {
    document.getElementById('variationsJson').value = JSON.stringify(vbVariations);
}

async function uploadVarImage(input, idx) {
    const file = input.files[0];
    if (!file) return;
    const label = input.closest('label');
    if (label) { label.style.opacity='0.6'; }
    const fd = new FormData();
    fd.append('variation_image', file);
    try {
        const res = await fetch('ajax-upload-variation-image.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            vbVariations[idx].image = data.url;
            document.getElementById('vbImg'+idx).value = data.url;
            syncVariationsJson();
            const thumb = document.getElementById('vbThumb'+idx);
            if (thumb) {
                const img = document.createElement('img');
                img.src = vbBase(data.url); img.id = 'vbThumb'+idx;
                img.style = 'width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;margin-bottom:4px';
                thumb.parentNode.replaceChild(img, thumb);
            }
        } else { alert(data.message || 'Upload failed.'); }
    } catch(e) { alert('Upload error: ' + e.message); }
    finally { if (label) label.style.opacity='1'; }
}

// Save variations via AJAX before form submits (product_id comes from server redirect)
document.getElementById('add-product-form').addEventListener('submit', function() {
    syncVariationsJson();
});

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Custom dropdown helpers (add-product)
function vbCloseAllDropdowns() {
    document.querySelectorAll('.vb-add-dropdown.vb-dd-open').forEach(function(d) {
        d.classList.remove('vb-dd-open');
        d.querySelector('.vb-dd-menu').style.display = 'none';
        var tb = d.querySelector(':scope > button');
        if (tb) { tb.style.borderColor = '#e5e7eb'; tb.style.boxShadow = 'none'; }
    });
}
function vbToggleDropdown(btn) {
    const wrap = btn.closest('.vb-add-dropdown');
    const isOpen = wrap.classList.contains('vb-dd-open');
    vbCloseAllDropdowns();
    if (!isOpen) {
        const menu = wrap.querySelector('.vb-dd-menu');
        const rect = btn.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.top    = (rect.bottom + 4) + 'px';
        menu.style.left   = rect.left + 'px';
        menu.style.width  = rect.width + 'px';
        wrap.classList.add('vb-dd-open');
        btn.style.borderColor = '#facc15';
        btn.style.boxShadow   = '0 0 0 3px rgba(250,204,21,.18)';
    }
}

function vbPickStatus(optBtn, idx) {
    const val = parseInt(optBtn.dataset.val);
    vbVariations[idx].status = val;
    syncVariationsJson();
    const wrap = optBtn.closest('.vb-add-dropdown');
    const trigger = wrap.querySelector(':scope > button > span');
    if (trigger) trigger.textContent = val === 1 ? 'Active' : 'Inactive';
    wrap.querySelectorAll('.vb-dd-menu button').forEach(function(b) {
        b.style.background = parseInt(b.dataset.val) === val ? '#facc15' : '#fff';
    });
    wrap.classList.remove('vb-dd-open');
    wrap.querySelector('.vb-dd-menu').style.display = 'none';
    wrap.querySelector(':scope > button').style.borderColor = '#e5e7eb';
    wrap.querySelector(':scope > button').style.boxShadow = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.vb-add-dropdown')) vbCloseAllDropdowns();
});
document.addEventListener('scroll', vbCloseAllDropdowns, true);

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

function updateUploadFilename(inputId) {
    const input = document.getElementById(inputId);
    const label = document.querySelector('.upload-filename[data-for="' + inputId + '"]');
    if (!input || !label) return;

    input.addEventListener('change', function () {
        if (!input.files || input.files.length === 0) {
            label.textContent = input.multiple ? 'No files selected' : 'No file selected';
            return;
        }
        if (input.multiple) {
            label.textContent = input.files.length + ' file(s) selected';
        } else {
            label.textContent = input.files[0].name;
        }
    });
}

updateUploadFilename('primary_image');
updateUploadFilename('gallery_images');

function initCustomSelect(root) {
    const select = root.querySelector('select.native-select');
    const btn = root.querySelector('.custom-select-btn');
    const valueEl = root.querySelector('.custom-select-value');
    const menu = root.querySelector('.custom-select-menu');
    if (!select || !btn || !valueEl || !menu) return;

    function renderOptions() {
        menu.innerHTML = '';
        Array.from(select.options).forEach((opt, idx) => {
            const item = document.createElement('div');
            item.className = 'custom-select-option';
            item.setAttribute('role', 'option');
            item.dataset.value = opt.value;
            item.dataset.index = String(idx);
            item.textContent = opt.textContent;
            if (opt.selected) item.classList.add('is-selected');
            menu.appendChild(item);
        });
    }

    function syncFromNative() {
        const selected = select.options[select.selectedIndex];
        valueEl.textContent = selected ? selected.textContent : 'Select';
        Array.from(menu.querySelectorAll('.custom-select-option')).forEach((el) => {
            el.classList.toggle('is-selected', el.dataset.value === (selected ? selected.value : ''));
        });
    }

    function openMenu() {
        root.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        menu.focus();
        syncFromNative();
    }

    function closeMenu() {
        root.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
    }

    renderOptions();
    syncFromNative();

    btn.addEventListener('click', function () {
        if (root.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    menu.addEventListener('click', function (e) {
        const optEl = e.target.closest('.custom-select-option');
        if (!optEl) return;
        select.value = optEl.dataset.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncFromNative();
        closeMenu();
        btn.focus();
    });

    menu.addEventListener('keydown', function (e) {
        const options = Array.from(menu.querySelectorAll('.custom-select-option'));
        const selectedIndex = Math.max(0, options.findIndex((o) => o.classList.contains('is-selected')));
        let nextIndex = selectedIndex;

        if (e.key === 'Escape') {
            e.preventDefault();
            closeMenu();
            btn.focus();
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            nextIndex = Math.min(options.length - 1, selectedIndex + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            nextIndex = Math.max(0, selectedIndex - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const chosen = options[selectedIndex];
            if (chosen) chosen.click();
            return;
        } else {
            return;
        }

        const next = options[nextIndex];
        if (next) {
            options.forEach((o) => o.classList.remove('is-selected'));
            next.classList.add('is-selected');
            next.scrollIntoView({ block: 'nearest' });
        }
    });

    select.addEventListener('change', syncFromNative);

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) {
            closeMenu();
        }
    });
}

document.querySelectorAll('.custom-select').forEach(initCustomSelect);

// ===== SEO & Slug Auto-generation =====
let seoSlugAutoMode = true; // tracks if slug was auto-generated (not manually edited)

document.getElementById('product_name').addEventListener('input', function() {
    if (seoSlugAutoMode) {
        const slug = this.value.trim()
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        document.getElementById('product_slug').value = slug;
    }
    seoUpdateCanonicalUrl();
    seoUpdateSnippetPreview();
});

document.getElementById('product_slug').addEventListener('input', function() {
    seoSlugAutoMode = false;
    seoUpdateCanonicalUrl();
    seoUpdateSnippetPreview();
});

document.getElementById('product_name').addEventListener('blur', function() {
    const altField = document.getElementById('primary_alt_text');
    if (altField && !altField.value.trim()) {
        altField.value = this.value.trim();
    }
});

document.getElementById('category_id').addEventListener('change', seoUpdateCanonicalUrl);
document.getElementById('brand_id').addEventListener('change', seoUpdateCanonicalUrl);
document.getElementById('regular_price').addEventListener('input', seoUpdateDiscountBadge);
document.getElementById('sale_price').addEventListener('input', seoUpdateDiscountBadge);

function seoGetMonthYear() {
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const d = new Date();
    return months[d.getMonth()] + ' ' + d.getFullYear();
}

function seoGenerateSeoTitle() {
    const name = document.getElementById('product_name').value.trim();
    if (!name) { return; }
    const title = name + ' Price in Pakistan ' + seoGetMonthYear() + ' | Phones Dukan';
    document.getElementById('seo_title').value = title;
    seoUpdateCharCounter('seo_title', 60);
    seoUpdateSnippetPreview();
}

function seoGenerateSeoDescription() {
    const name = document.getElementById('product_name').value.trim();
    const shortDesc = document.getElementById('short_description').value.trim();
    if (!name) { return; }
    let desc;
    if (shortDesc.length >= 50) {
        desc = shortDesc.length > 155 ? shortDesc.substring(0, 152) + '...' : shortDesc;
    } else {
        desc = 'Buy ' + name + ' in Pakistan at the best price. PTA-approved with official warranty. Fast delivery across Pakistan. Shop now at Phones Dukan.';
    }
    if (desc.length > 160) desc = desc.substring(0, 157) + '...';
    document.getElementById('seo_description').value = desc;
    seoUpdateCharCounter('seo_description', 160);
    seoUpdateSnippetPreview();
}

function seoGenerateFocusKeyword() {
    const name = document.getElementById('product_name').value.trim();
    if (!name) { return; }
    document.getElementById('focus_keyword').value = name.toLowerCase() + ' price in pakistan';
}

function seoAutoFillAll() {
    seoGenerateSeoTitle();
    seoGenerateSeoDescription();
    seoGenerateFocusKeyword();
    seoUpdateCanonicalUrl();
}

function seoUpdateCanonicalUrl() {
    const slug = document.getElementById('product_slug').value.trim() || 'product-slug';
    const catSelect = document.getElementById('category_id');
    const brandSelect = document.getElementById('brand_id');
    const catSlug = catSelect.options[catSelect.selectedIndex]?.getAttribute('data-slug') || 'category';
    const brandSlug = brandSelect.options[brandSelect.selectedIndex]?.getAttribute('data-slug') || 'brand';
    const canonical = 'https://www.phonesdukan.com/' + catSlug + '/' + brandSlug + '/' + slug + '/';
    document.getElementById('canonical_url').value = canonical;
    seoUpdateSnippetPreview();
}

function seoUpdateSnippetPreview() {
    const title = document.getElementById('seo_title').value.trim() || document.getElementById('product_name').value.trim() || 'Product Title';
    const desc = document.getElementById('seo_description').value.trim() || 'Product meta description will appear here...';
    const canonical = document.getElementById('canonical_url').value || 'https://www.phonesdukan.com/category/brand/product-slug/';
    const urlDisplay = canonical.replace('https://', '').replace(/\/$/, '') + '/';
    const tEl = document.getElementById('snippet_title');
    const dEl = document.getElementById('snippet_desc');
    const uEl = document.getElementById('snippet_url');
    if (tEl) tEl.textContent = title.length > 60 ? title.substring(0, 60) + '…' : title;
    if (dEl) dEl.textContent = desc.length > 160 ? desc.substring(0, 160) + '…' : desc;
    if (uEl) uEl.textContent = urlDisplay;
}

function seoUpdateCharCounter(fieldId, limit) {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(fieldId + '_counter');
    if (!field || !counter) return;
    const len = field.value.length;
    counter.textContent = len + '/' + limit;
    counter.className = 'seo-char-counter ';
    if (len === 0 || len < Math.floor(limit * 0.6)) counter.className += 'too-short';
    else if (len > limit) counter.className += 'too-long';
    else counter.className += 'good';
}

function seoUpdateDiscountBadge() {
    const regular = parseFloat(document.getElementById('regular_price').value) || 0;
    const sale = parseFloat(document.getElementById('sale_price').value) || 0;
    const badge = document.getElementById('discount_badge');
    if (!badge) return;
    if (regular > 0 && sale > 0 && sale < regular) {
        const pct = Math.round(((regular - sale) / regular) * 100);
        badge.textContent = pct + '% OFF';
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

// Initialize SEO UI on load
seoUpdateCanonicalUrl();
seoUpdateSnippetPreview();

// ===== SKU Auto-generation =====
function generateProductSku() {
    const catSelect   = document.getElementById('category_id');
    const brandSelect = document.getElementById('brand_id');
    const catName     = (catSelect.options[catSelect.selectedIndex]?.textContent   || '').trim();
    const brandName   = (brandSelect.options[brandSelect.selectedIndex]?.textContent || '').trim();
    const brandCode   = brandName.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '') || 'PD';
    const catCode     = catName.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '')   || 'GEN';
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let rand = '';
    for (let i = 0; i < 4; i++) rand += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('product_sku').value = brandCode + '-' + catCode + '-' + rand;
}

function skuAbbrev(str) {
    return String(str || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 3);
}

function generateAllVariationSkus() {
    if (!vbVariations.length) { alert('Generate combinations first, then generate SKUs.'); return; }
    const productSku = document.getElementById('product_sku').value.trim() || 'PD';
    let filled = 0;
    vbVariations.forEach(function(v, i) {
        if (v.sku && v.sku.trim()) return; // never overwrite an existing SKU
        const attrParts = Object.entries(v.attributes || {}).map(function([tid, vid]) {
            const t   = VB_TYPES.find(function(x) { return x.id == tid; });
            const val = t ? (t.values || []).find(function(x) { return x.id == vid; }) : null;
            return val ? skuAbbrev(val.value) : '';
        }).filter(Boolean);
        v.sku = productSku + (attrParts.length ? '-' + attrParts.join('-') : '-V' + (i + 1));
        filled++;
    });
    if (filled === 0) {
        alert('All variations already have SKUs. Clear them first if you want to regenerate.');
        return;
    }
    renderVariationRows();
}
</script>