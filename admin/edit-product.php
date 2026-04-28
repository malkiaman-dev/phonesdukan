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
?>

<form method="POST" enctype="multipart/form-data" action="/admin/edit-product.php?id=<?= $product['product_id'] ?>" id="product-form">
    <h2>Edit Product</h2>

    <label>Product Name</label>
    <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>

    <label>Product Slug</label>
    <input type="text" name="product_slug" value="<?= htmlspecialchars($product['product_slug']) ?>" required>

    <label>Category</label>
    <select name="category_id" required>
        <?php foreach ($categories as $row): ?>
            <option value="<?= $row['category_id'] ?>" <?= $product['category_id'] == $row['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['category_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Brand</label>
    <select name="brand_id" required>
        <?php foreach ($brands as $row): ?>
            <option value="<?= $row['brand_id'] ?>" <?= $product['brand_id'] == $row['brand_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['brand_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Product Description</label>
    <textarea name="product_description" required><?= htmlspecialchars($product['product_description']) ?></textarea>

    <label>Short Description</label>
    <textarea name="short_description"><?= htmlspecialchars($product['short_description']) ?></textarea>

    <label>Product Status</label>
    <select name="product_status" required>
        <option value="1" <?= $product['product_status'] == '1' ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= $product['product_status'] == '0' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <label>B2B Available</label>
    <input type="checkbox" name="is_b2b_available" value="1" <?= $product['is_b2b_available'] ? 'checked' : '' ?>>
    <div id="b2b-price-section" style="display: <?= $product['is_b2b_available'] ? 'block' : 'none' ?>;">
        <label>B2B Regular Price</label>
        <input type="number" name="b2b_regular_price" value="<?= htmlspecialchars($product['b2b_regular_price'] ?? '') ?>" step="0.01">
    </div>

    <label>Stock Quantity</label>
    <input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?>" required>

    <label>Regular Price</label>
    <input type="number" name="regular_price" value="<?= $product['regular_price'] ?>" required>

    <label>Sale Price</label>
    <input type="number" name="sale_price" value="<?= $product['sale_price'] ?>">

    <label>Product SKU</label>
    <input type="text" name="product_sku" value="<?= htmlspecialchars($product['product_sku'] ?? '') ?>">

    <label>Weight (kg)</label>
    <input type="text" name="weight_kg" value="<?= htmlspecialchars($product['weight_kg'] ?? '') ?>">

    <label>Dimensions (L x W x H in cm)</label>
    <input type="text" name="length_cm" placeholder="Length" value="<?= htmlspecialchars($product['length_cm'] ?? '') ?>">
    <input type="text" name="width_cm" placeholder="Width" value="<?= htmlspecialchars($product['width_cm'] ?? '') ?>">
    <input type="text" name="height_cm" placeholder="Height" value="<?= htmlspecialchars($product['height_cm'] ?? '') ?>">

    <label>Tax Class</label>
    <input type="text" name="tax_class" value="<?= htmlspecialchars($product['tax_class'] ?? '') ?>">

    <label>Product Tags</label>
    <input type="text" name="product_tag" value="<?= htmlspecialchars($product['product_tag'] ?? '') ?>">

    <h3>SEO Details</h3>
    <label>Focus Keyword</label>
    <input type="text" name="focus_keyword" value="<?= htmlspecialchars($seoData['focus_keyword'] ?? '') ?>">

    <label>SEO Title</label>
    <input type="text" name="seo_title" value="<?= htmlspecialchars($seoData['seo_title'] ?? '') ?>">

    <label>SEO Description</label>
    <textarea name="seo_description"><?= htmlspecialchars($seoData['seo_description'] ?? '') ?></textarea>

    <h3>Product Images</h3>
    <label>Upload Images (Primary and Gallery)</label>
    <input type="file" name="primary_image[]" id="primary_image" multiple>

    <h4>Existing Images</h4>
    <?php if (!empty($productImages)): ?>
        <table>
            <tr>
                <th>Image</th>
                <th>Alt Text</th>
                <th>Title</th>
                <th>Description</th>
                <th>Caption</th>
                <th>Primary</th>
                <th>Remove</th>
            </tr>
            <?php foreach ($productImages as $image): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($image['image_url']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? '') ?>" width="100"></td>
                    <td><input type="text" name="alt_text[<?= $image['image_id'] ?>]" value="<?= htmlspecialchars($image['alt_text'] ?? '') ?>"></td>
                    <td><input type="text" name="title[<?= $image['image_id'] ?>]" value="<?= htmlspecialchars($image['title'] ?? '') ?>"></td>
                    <td><textarea name="description[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['description'] ?? '') ?></textarea></td>
                    <td><textarea name="caption[<?= $image['image_id'] ?>]"><?= htmlspecialchars($image['caption'] ?? '') ?></textarea></td>
                    <td>
                        <label for="primary_<?= $image['image_id'] ?>">Primary</label>
                        <input type="radio" name="primary_image_id" value="<?= $image['image_id'] ?>" <?= $image['is_primary'] ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <button type="button" onclick="confirmRemoval(<?= $image['image_id'] ?>)">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No images available for this product.</p>
    <?php endif; ?>

    <h3>Assign Product Attributes</h3>
<div id="product-attributes">
    <?php foreach ($productAttributes as $index => $attribute): ?>
        <div class="attribute" data-attribute-id="<?= $attribute['attribute_id'] ?>" data-value-id="<?= $attribute['value_id'] ?>">
            <label><?= htmlspecialchars($attribute['attribute_name']) ?></label>
            <select name="attributes[<?= $index ?>][attribute_id]" required>
                <?php foreach ($allAttributes as $attr): ?>
                    <option value="<?= $attr['attribute_id'] ?>" <?= $attribute['attribute_id'] == $attr['attribute_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($attr['attribute_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
            <input type="number" name="attributes[<?= $index ?>][regular_price]" value="<?= htmlspecialchars($attribute['regular_price'] ?? '') ?>" placeholder="Regular Price" required>
            <input type="number" name="attributes[<?= $index ?>][sale_price]" value="<?= htmlspecialchars($attribute['sale_price'] ?? '') ?>" placeholder="Sale Price">
            <button type="button" class="remove-attribute-btn" data-index="<?= $index ?>" data-attribute-id="<?= $attribute['attribute_id'] ?>" data-value-id="<?= $attribute['value_id'] ?>">Remove Attribute Value</button>
        </div>
    <?php endforeach; ?>
</div>
<button class="add-attribute" type="button" onclick="addNewAttribute()">Add New Attribute</button>

    <button type="submit">Update Product</button>
</form>
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
});

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
        <label>Attribute</label>
        <select name="attributes[${attributeIndex}][attribute_id]" onchange="loadAttributeValues(this)" required>
            <option value="">Select Attribute</option>
            ${attributeOptions}
        </select>
        <label>Value</label>
        <select name="attributes[${attributeIndex}][value_id]" required>
            <option value="">Select Value</option>
        </select>
        <label>Regular Price</label>
        <input type="number" name="attributes[${attributeIndex}][regular_price]" required>
        <label>Sale Price</label>
        <input type="number" name="attributes[${attributeIndex}][sale_price]">
        <button type="button" class="remove-attribute-btn" data-index="${attributeIndex}" data-attribute-id="0" data-value-id="0">Remove Attribute Value</button>
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