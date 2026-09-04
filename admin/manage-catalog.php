<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Models/CatalogModel.php';

$model = new CatalogModel();
$toast = '';
$toastType = 'success';

if (isset($_SESSION['catalog_toast'])) {
    $toast = (string) ($_SESSION['catalog_toast']['message'] ?? '');
    $toastType = (string) ($_SESSION['catalog_toast']['type'] ?? 'success');
    unset($_SESSION['catalog_toast']);
}

function catalogMakeSlug(string $str): string
{
    return CatalogModel::makeSlug($str);
}

function catalogUniqueSlug(callable $existsFn, string $base): string
{
    $slug = $base;
    $n = 1;
    while ($existsFn($slug)) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

function catalogSaveHomepageImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowed[$mime])) {
        return null;
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads/category-home';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return null;
    }

    $filename = 'cat_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $destination)) {
        return null;
    }

    return 'public/uploads/category-home/' . $filename;
}

function catalogSaveBrandLogo(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowed[$mime])) {
        return null;
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads/brand-home';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return null;
    }

    $filename = 'brand_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $destination)) {
        return null;
    }

    return 'public/uploads/brand-home/' . $filename;
}

function catalogDeleteUploadedAsset(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $fullPath = dirname(__DIR__) . '/' . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function catalogDeleteHomepageImage(?string $relativePath): void
{
    catalogDeleteUploadedAsset($relativePath);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['catalog_toggle'])) {
        header('Content-Type: application/json; charset=utf-8');

        $entity = (string) ($_POST['entity'] ?? '');
        $field = (string) ($_POST['field'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $enabled = !empty($_POST['enabled']);
        $result = ['ok' => false, 'message' => 'Invalid toggle request.'];

        if ($entity === 'category' && $field === 'show_in_sidebar') {
            $result = $model->setCategoryShowInSidebar($id, $enabled);
        } elseif ($entity === 'category' && $field === 'show_on_homepage') {
            $result = $model->setCategoryShowOnHomepage($id, $enabled);
        } elseif ($entity === 'brand' && $field === 'show_on_homepage') {
            $result = $model->setBrandShowOnHomepage($id, $enabled);
        }

        if (!$result['ok'] && !empty($result['redirect'])) {
            $_SESSION['catalog_toast'] = [
                'type' => 'error',
                'message' => (string) ($result['message'] ?? 'Please upload the required image.'),
            ];
        }

        echo json_encode($result);
        exit;
    }

    if (isset($_POST['add_brand'])) {
        $name = trim($_POST['brand_name'] ?? '');
        $slug = trim($_POST['brand_slug'] ?? '') ?: catalogMakeSlug($name);
        $showOnHome = !empty($_POST['brand_show_on_homepage']);
        $logoPath = catalogSaveBrandLogo($_FILES['brand_homepage_logo'] ?? []);

        if ($name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Brand name is required.'];
        } elseif ($showOnHome && $logoPath === null) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Upload a logo when "Show on home page" is checked.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->brandSlugExists($s), catalogMakeSlug($slug));
            if ($model->addBrand($name, $slug, $showOnHome, $logoPath)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand "' . $name . '" added.'];
            } else {
                if ($logoPath !== null) {
                    catalogDeleteUploadedAsset($logoPath);
                }
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error adding brand.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_brand'])) {
        $id = (int) ($_POST['edit_brand_id'] ?? 0);
        $name = trim($_POST['edit_brand_name'] ?? '');
        $slug = trim($_POST['edit_brand_slug'] ?? '') ?: catalogMakeSlug($name);
        $showOnHome = !empty($_POST['edit_brand_show_on_homepage']);
        $existing = $id ? $model->getBrandById($id) : null;
        $logoPath = catalogSaveBrandLogo($_FILES['edit_brand_homepage_logo'] ?? []);
        $updateLogo = $logoPath !== null;

        if (!$id || $name === '' || !$existing) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid brand data.'];
        } elseif ($showOnHome && !$updateLogo && empty($existing['homepage_logo'])) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Upload a logo when "Show on home page" is checked.'];
        } else {
            if ($updateLogo && !empty($existing['homepage_logo'])) {
                catalogDeleteUploadedAsset((string) $existing['homepage_logo']);
            }

            $finalLogo = $updateLogo ? $logoPath : ($existing['homepage_logo'] ?? null);
            if (!$showOnHome) {
                if (!empty($existing['homepage_logo'])) {
                    catalogDeleteUploadedAsset((string) $existing['homepage_logo']);
                }
                $finalLogo = null;
                $updateLogo = true;
            }

            $slug = catalogUniqueSlug(fn ($s) => $model->brandSlugExists($s, $id), catalogMakeSlug($slug));
            if ($model->updateBrand($id, $name, $slug, $showOnHome, $finalLogo, $updateLogo)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand updated.'];
            } else {
                if ($updateLogo && $logoPath !== null) {
                    catalogDeleteUploadedAsset($logoPath);
                }
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error updating brand.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name'] ?? '');
        $slug = trim($_POST['category_slug'] ?? '') ?: catalogMakeSlug($name);
        $showOnHome = !empty($_POST['category_show_on_homepage']);
        $showInSidebar = !empty($_POST['category_show_in_sidebar']);
        $imagePath = catalogSaveHomepageImage($_FILES['category_homepage_image'] ?? []);

        if ($name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Category name is required.'];
        } elseif ($showOnHome && $imagePath === null) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Upload a homepage image when "Show on home page" is checked.'];
        } elseif ($model->categoryNameExists($name)) {
            if ($imagePath !== null) {
                catalogDeleteHomepageImage($imagePath);
            }
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'A category named "' . $name . '" already exists. Use a different name.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, null), catalogMakeSlug($slug));
            if ($model->addCategory($name, $slug, null, 0, 1, $showOnHome, $imagePath, $showInSidebar)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Category "' . $name . '" added.'];
            } else {
                if ($imagePath !== null) {
                    catalogDeleteHomepageImage($imagePath);
                }
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Could not add category. The name or slug may already be in use.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_category'])) {
        $id = (int) ($_POST['edit_category_id'] ?? 0);
        $name = trim($_POST['edit_category_name'] ?? '');
        $slug = trim($_POST['edit_category_slug'] ?? '') ?: catalogMakeSlug($name);
        $showOnHome = !empty($_POST['edit_category_show_on_homepage']);
        $showInSidebar = !empty($_POST['edit_category_show_in_sidebar']);
        $existing = $id ? $model->getCategoryById($id) : null;
        $imagePath = catalogSaveHomepageImage($_FILES['edit_category_homepage_image'] ?? []);
        $updateImage = $imagePath !== null;

        if (!$id || $name === '' || !$existing) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid category data.'];
        } elseif ($showOnHome && !$updateImage && empty($existing['homepage_image'])) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Upload a homepage image when "Show on home page" is checked.'];
        } elseif ($model->categoryNameExists($name, $id)) {
            if ($updateImage && $imagePath !== null) {
                catalogDeleteHomepageImage($imagePath);
            }
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'A category named "' . $name . '" already exists. Use a different name.'];
        } else {
            if ($updateImage && !empty($existing['homepage_image'])) {
                catalogDeleteHomepageImage((string) $existing['homepage_image']);
            }

            $finalImage = $updateImage ? $imagePath : ($existing['homepage_image'] ?? null);
            if (!$showOnHome) {
                if (!empty($existing['homepage_image'])) {
                    catalogDeleteHomepageImage((string) $existing['homepage_image']);
                }
                $finalImage = null;
                $updateImage = true;
            }

            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, null, $id), catalogMakeSlug($slug));
            if ($model->updateCategory($id, $name, $slug, null, 0, 1, $showOnHome, $finalImage, $updateImage, $showInSidebar)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Category updated.'];
            } else {
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Could not update category. The name or slug may already be in use.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['add_subcategory'])) {
        $parentId = (int) ($_POST['sub_parent_id'] ?? 0);
        $name = trim($_POST['subcategory_name'] ?? '');
        $slug = trim($_POST['subcategory_slug'] ?? '') ?: catalogMakeSlug($name);
        if (!$parentId || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Select a parent category and enter a subcategory name.'];
        } elseif ($model->categoryNameExists($name)) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'A category named "' . $name . '" already exists. Use a different name.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, $parentId), catalogMakeSlug($slug));
            if ($model->addCategory($name, $slug, $parentId, 0, 1)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Subcategory "' . $name . '" added.'];
            } else {
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Could not add subcategory. The name or slug may already be in use.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_subcategory'])) {
        $id = (int) ($_POST['edit_sub_id'] ?? 0);
        $parentId = (int) ($_POST['edit_sub_parent_id'] ?? 0);
        $name = trim($_POST['edit_sub_name'] ?? '');
        $slug = trim($_POST['edit_sub_slug'] ?? '') ?: catalogMakeSlug($name);
        if (!$id || !$parentId || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid subcategory data.'];
        } elseif ($model->categoryNameExists($name, $id)) {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'A category named "' . $name . '" already exists. Use a different name.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, $parentId, $id), catalogMakeSlug($slug));
            if ($model->updateCategory($id, $name, $slug, $parentId, 0, 1)) {
                $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Subcategory updated.'];
            } else {
                $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Could not update subcategory. The name or slug may already be in use.'];
            }
        }
        header('Location: manage-catalog.php');
        exit;
    }
}

if (isset($_GET['delete_brand'])) {
    $id = (int) $_GET['delete_brand'];
    $existingBrand = $model->getBrandById($id);
    if ($model->countProductsUsingBrand($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: products still use this brand.'];
    } else {
        if ($existingBrand && !empty($existingBrand['homepage_logo'])) {
            catalogDeleteUploadedAsset((string) $existingBrand['homepage_logo']);
        }
        $model->deleteBrand($id)
            ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand deleted.']
            : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error deleting brand.'];
    }
    header('Location: manage-catalog.php');
    exit;
}

if (isset($_GET['delete_category'])) {
    $id = (int) $_GET['delete_category'];
    $existingCategory = $model->getCategoryById($id);
    if ($model->countChildCategories($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: category has subcategories.'];
    } elseif ($model->countProductsUsingCategory($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: products still use this category.'];
    } else {
        if ($existingCategory && !empty($existingCategory['homepage_image'])) {
            catalogDeleteHomepageImage((string) $existingCategory['homepage_image']);
        }
        $model->deleteCategory($id)
            ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Category deleted.']
            : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error deleting category.'];
    }
    header('Location: manage-catalog.php');
    exit;
}

if (isset($_GET['delete_subcategory'])) {
    $id = (int) $_GET['delete_subcategory'];
    if ($model->countProductsUsingCategory($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: products still use this subcategory.'];
    } else {
        $model->deleteCategory($id)
            ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Subcategory deleted.']
            : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error deleting subcategory.'];
    }
    header('Location: manage-catalog.php');
    exit;
}

$editBrand = isset($_GET['edit_brand']) ? $model->getBrandById((int) $_GET['edit_brand']) : null;
$editCategory = isset($_GET['edit_category']) ? $model->getCategoryById((int) $_GET['edit_category']) : null;
$editSub = isset($_GET['edit_sub']) ? $model->getCategoryById((int) $_GET['edit_sub']) : null;
$prefillHomeOnEdit = !empty($_GET['enable_home']);

$brands = $model->getAllBrands();
$categoriesWithSubs = $model->getCategoriesWithSubcounts();
$parentCategories = $model->getParentCategories();

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
<div class="cp">

    <div class="ccard cp-header">
        <h1>Manage Catalog</h1>
        <p>Brands, parent categories, and subcategories for product permalinks (brand / category / subcategory / product).</p>
    </div>

    <div class="cp-grid">
        <div class="ccard cp-form">
            <h2>Add Brand</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="cp-field">
                    <label>Brand Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="brand_name" placeholder="e.g. Samsung, Apple" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="brand_slug" placeholder="auto-generated from name">
                </div>
                <div class="cp-field cp-check-field">
                    <label class="cp-check-label">
                        <input type="checkbox" name="brand_show_on_homepage" value="1" data-home-toggle="brand-home-fields">
                        <?= adminTooltipLabel('Show on home page', 'Display this brand in the homepage brands marquee.') ?>
                    </label>
                </div>
                <div class="cp-field cp-home-fields" id="brand-home-fields" hidden>
                    <label for="brand_homepage_logo">Brand Logo <span class="req-star" aria-hidden="true">*</span> <?= adminTooltipIcon('Transparent PNG or WEBP works best for brand logos.') ?></label>
                    <div class="cp-file-upload">
                        <input type="file" class="cp-file-native" id="brand_homepage_logo" name="brand_homepage_logo" accept="image/jpeg,image/png,image/webp,image/gif">
                        <label for="brand_homepage_logo" class="cp-file-btn">
                            <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                            <span class="cp-file-btn-text">Upload logo</span>
                            <span class="cp-file-name">No file selected</span>
                        </label>
                    </div>
                </div>
                <button class="cp-btn" type="submit" name="add_brand">Add Brand</button>
            </form>
        </div>

        <div class="ccard cp-form">
            <h2>Add Category</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="cp-field">
                    <label>Category Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="category_name" placeholder="e.g. Mobiles, Tablets" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="category_slug" placeholder="auto-generated">
                </div>
                <div class="cp-field cp-check-field">
                    <label class="cp-check-label">
                        <input type="checkbox" name="category_show_in_sidebar" value="1">
                        <?= adminTooltipLabel('Show in sidebar', 'Display this category in the homepage navigation sidebar.') ?>
                    </label>
                </div>
                <div class="cp-field cp-check-field">
                    <label class="cp-check-label">
                        <input type="checkbox" name="category_show_on_homepage" value="1" data-home-toggle="category-home-fields">
                        <?= adminTooltipLabel('Show on home page', 'Display this category in the homepage carousel.') ?>
                    </label>
                </div>
                <div class="cp-field cp-home-fields" id="category-home-fields" hidden>
                    <label for="category_homepage_image">Homepage Image <span class="req-star" aria-hidden="true">*</span> <?= adminTooltipIcon('Square image works best (PNG, JPG, WEBP, or GIF).') ?></label>
                    <div class="cp-file-upload">
                        <input type="file" class="cp-file-native" id="category_homepage_image" name="category_homepage_image" accept="image/jpeg,image/png,image/webp,image/gif">
                        <label for="category_homepage_image" class="cp-file-btn">
                            <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                            <span class="cp-file-btn-text">Upload image</span>
                            <span class="cp-file-name">No file selected</span>
                        </label>
                    </div>
                </div>
                <button class="cp-btn" type="submit" name="add_category">Add Category</button>
            </form>
        </div>

        <div class="ccard cp-form">
            <h2>Add Subcategory</h2>
            <form method="POST">
                <div class="cp-field">
                    <label>Parent Category <span class="req-star" aria-hidden="true">*</span></label>
                    <div class="custom-select" data-select-id="sub_parent_id">
                        <select class="native-select" name="sub_parent_id" id="sub_parent_id" required>
                            <option value="">Select parent category</option>
                            <?php foreach ($parentCategories as $pc): ?>
                                <option value="<?= (int) $pc['category_id'] ?>"><?= htmlspecialchars($pc['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="custom-select-btn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="custom-select-value">Select parent category</span>
                            <span class="custom-select-arrow" aria-hidden="true"></span>
                        </button>
                        <div class="custom-select-menu" role="listbox" tabindex="-1"></div>
                    </div>
                </div>
                <div class="cp-field">
                    <label>Subcategory Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="subcategory_name" placeholder="e.g. Bluetooth Speakers" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="subcategory_slug" placeholder="auto-generated">
                </div>
                <button class="cp-btn" type="submit" name="add_subcategory">Add Subcategory</button>
            </form>
        </div>
    </div>

    <div class="ccard" style="margin-bottom:20px">
        <div class="section-head"><h2>Brands</h2></div>
        <div class="ctable-wrap">
            <table class="ctable">
                <thead>
                    <tr><th>Name</th><th>Slug</th><th>Home</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($brands)): ?>
                    <tr><td colspan="4"><div class="empty-state">No brands yet.</div></td></tr>
                <?php else: foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= htmlspecialchars($brand['brand_name']) ?></td>
                        <td><code><?= htmlspecialchars($brand['slug']) ?></code></td>
                        <td>
                            <?= adminToggleSwitch(
                                (int) ($brand['show_on_homepage'] ?? 0) === 1,
                                [
                                    'catalog-toggle' => '1',
                                    'entity' => 'brand',
                                    'field' => 'show_on_homepage',
                                    'id' => (string) (int) $brand['brand_id'],
                                ],
                                'Toggle home page display for ' . (string) $brand['brand_name']
                            ) ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="cp-btn-sm" href="?edit_brand=<?= (int) $brand['brand_id'] ?>">Edit</a>
                                <a class="cp-btn-sm cp-btn-danger" href="?delete_brand=<?= (int) $brand['brand_id'] ?>" onclick="return confirm('Delete this brand?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="ccard">
        <div class="section-head"><h2>Categories &amp; Subcategories</h2></div>
        <div class="ctable-wrap">
            <table class="ctable">
                <thead>
                    <tr><th>Category</th><th>Slug</th><th>Home</th><th>Sidebar</th><th>Subcategories</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($categoriesWithSubs)): ?>
                    <tr><td colspan="6"><div class="empty-state">No categories yet.</div></td></tr>
                <?php else: foreach ($categoriesWithSubs as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                        <td>
                            <?= adminToggleSwitch(
                                (int) ($cat['show_on_homepage'] ?? 0) === 1,
                                [
                                    'catalog-toggle' => '1',
                                    'entity' => 'category',
                                    'field' => 'show_on_homepage',
                                    'id' => (string) (int) $cat['category_id'],
                                ],
                                'Toggle home page display for ' . (string) $cat['category_name']
                            ) ?>
                        </td>
                        <td>
                            <?= adminToggleSwitch(
                                (int) ($cat['show_in_sidebar'] ?? 0) === 1,
                                [
                                    'catalog-toggle' => '1',
                                    'entity' => 'category',
                                    'field' => 'show_in_sidebar',
                                    'id' => (string) (int) $cat['category_id'],
                                ],
                                'Toggle sidebar display for ' . (string) $cat['category_name']
                            ) ?>
                        </td>
                        <td>
                            <?php if (empty($cat['subcategories'])): ?>
                                <span class="cp-empty-mark">—</span>
                            <?php else: ?>
                                <div class="sub-chips">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <span class="sub-chip">
                                        <span class="sub-chip-name"><?= htmlspecialchars($sub['category_name']) ?></span>
                                        <a class="sub-chip-action sub-chip-edit" href="?edit_sub=<?= (int) $sub['category_id'] ?>" title="Edit subcategory" aria-label="Edit subcategory"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                        <a class="sub-chip-action sub-chip-delete" href="?delete_subcategory=<?= (int) $sub['category_id'] ?>" title="Delete subcategory" aria-label="Delete subcategory" onclick="return confirm('Delete subcategory?')"><i class="fas fa-xmark" aria-hidden="true"></i></a>
                                    </span>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="cp-btn-sm" href="?edit_category=<?= (int) $cat['category_id'] ?>">Edit</a>
                                <a class="cp-btn-sm cp-btn-danger" href="?delete_category=<?= (int) $cat['category_id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($editBrand): ?>
<div class="cp-overlay open" id="editBrandOverlay">
    <div class="cp-modal">
        <h3>Edit Brand</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_brand_id" value="<?= (int) $editBrand['brand_id'] ?>">
            <div class="cp-field"><label>Name</label><input type="text" name="edit_brand_name" value="<?= htmlspecialchars($editBrand['brand_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_brand_slug" value="<?= htmlspecialchars($editBrand['slug']) ?>"></div>
            <div class="cp-field cp-check-field">
                <label class="cp-check-label">
                    <input type="checkbox" name="edit_brand_show_on_homepage" value="1" data-home-toggle="edit-brand-home-fields" <?= !empty($editBrand['show_on_homepage']) || $prefillHomeOnEdit ? 'checked' : '' ?>>
                    <?= adminTooltipLabel('Show on home page', 'Display this brand in the homepage brands marquee.') ?>
                </label>
            </div>
            <div class="cp-field cp-home-fields" id="edit-brand-home-fields" <?= empty($editBrand['show_on_homepage']) && !$prefillHomeOnEdit ? 'hidden' : '' ?>>
                <label>Brand Logo<?= empty($editBrand['homepage_logo']) ? ' <span class="req-star" aria-hidden="true">*</span>' : '' ?> <?= adminTooltipIcon('Upload a new logo to replace the current one.') ?></label>
                <?php if (!empty($editBrand['homepage_logo'])): ?>
                    <div class="cp-image-preview">
                        <img src="<?= htmlspecialchars(url($editBrand['homepage_logo']), ENT_QUOTES, 'UTF-8') ?>" alt="Current brand logo">
                    </div>
                <?php endif; ?>
                <div class="cp-file-upload">
                    <input type="file" class="cp-file-native" id="edit_brand_homepage_logo" name="edit_brand_homepage_logo" accept="image/jpeg,image/png,image/webp,image/gif">
                    <label for="edit_brand_homepage_logo" class="cp-file-btn">
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                        <span class="cp-file-btn-text">Upload logo</span>
                        <span class="cp-file-name">No file selected</span>
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <a class="cp-btn cp-btn-outline" href="manage-catalog.php">Cancel</a>
                <button class="cp-btn" type="submit" name="edit_brand">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($editCategory && empty($editCategory['parent_id'])): ?>
<div class="cp-overlay open">
    <div class="cp-modal">
        <h3>Edit Category</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_category_id" value="<?= (int) $editCategory['category_id'] ?>">
            <div class="cp-field"><label>Name</label><input type="text" name="edit_category_name" value="<?= htmlspecialchars($editCategory['category_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_category_slug" value="<?= htmlspecialchars($editCategory['slug']) ?>"></div>
            <div class="cp-field cp-check-field">
                <label class="cp-check-label">
                    <input type="checkbox" name="edit_category_show_in_sidebar" value="1" <?= !empty($editCategory['show_in_sidebar']) ? 'checked' : '' ?>>
                    <?= adminTooltipLabel('Show in sidebar', 'Display this category in the homepage navigation sidebar.') ?>
                </label>
            </div>
            <div class="cp-field cp-check-field">
                <label class="cp-check-label">
                    <input type="checkbox" name="edit_category_show_on_homepage" value="1" data-home-toggle="edit-category-home-fields" <?= !empty($editCategory['show_on_homepage']) || $prefillHomeOnEdit ? 'checked' : '' ?>>
                    <?= adminTooltipLabel('Show on home page', 'Display this category in the homepage carousel.') ?>
                </label>
            </div>
            <div class="cp-field cp-home-fields" id="edit-category-home-fields" <?= empty($editCategory['show_on_homepage']) && !$prefillHomeOnEdit ? 'hidden' : '' ?>>
                <label>Homepage Image<?= empty($editCategory['homepage_image']) ? ' <span class="req-star" aria-hidden="true">*</span>' : '' ?> <?= adminTooltipIcon('Upload a new image to replace the current one.') ?></label>
                <?php if (!empty($editCategory['homepage_image'])): ?>
                    <div class="cp-image-preview">
                        <img src="<?= htmlspecialchars(url($editCategory['homepage_image']), ENT_QUOTES, 'UTF-8') ?>" alt="Current homepage image">
                    </div>
                <?php endif; ?>
                <div class="cp-file-upload">
                    <input type="file" class="cp-file-native" id="edit_category_homepage_image" name="edit_category_homepage_image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <label for="edit_category_homepage_image" class="cp-file-btn">
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                        <span class="cp-file-btn-text">Upload image</span>
                        <span class="cp-file-name">No file selected</span>
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <a class="cp-btn cp-btn-outline" href="manage-catalog.php">Cancel</a>
                <button class="cp-btn" type="submit" name="edit_category">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($editSub && !empty($editSub['parent_id'])): ?>
<div class="cp-overlay open">
    <div class="cp-modal">
        <h3>Edit Subcategory</h3>
        <form method="POST">
            <input type="hidden" name="edit_sub_id" value="<?= (int) $editSub['category_id'] ?>">
            <div class="cp-field">
                <label>Parent Category</label>
                <div class="custom-select" data-select-id="edit_sub_parent_id">
                    <select class="native-select" name="edit_sub_parent_id" id="edit_sub_parent_id" required>
                        <?php foreach ($parentCategories as $pc): ?>
                            <option value="<?= (int) $pc['category_id'] ?>" <?= (int) $pc['category_id'] === (int) $editSub['parent_id'] ? 'selected' : '' ?>><?= htmlspecialchars($pc['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="custom-select-btn" aria-haspopup="listbox" aria-expanded="false">
                        <span class="custom-select-value">Select parent category</span>
                        <span class="custom-select-arrow" aria-hidden="true"></span>
                    </button>
                    <div class="custom-select-menu" role="listbox" tabindex="-1"></div>
                </div>
            </div>
            <div class="cp-field"><label>Name</label><input type="text" name="edit_sub_name" value="<?= htmlspecialchars($editSub['category_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_sub_slug" value="<?= htmlspecialchars($editSub['slug']) ?>"></div>
            <div class="modal-actions">
                <a class="cp-btn cp-btn-outline" href="manage-catalog.php">Cancel</a>
                <button class="cp-btn" type="submit" name="edit_subcategory">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="cp-toast <?= $toastType === 'error' ? 'is-error' : '' ?> <?= $toast ? 'show' : '' ?>" id="catalogToast"><?= htmlspecialchars($toast) ?></div>
<script src="<?= htmlspecialchars(assetUrl('public/assets/js/admin/custom-select.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
if (document.getElementById('catalogToast')?.classList.contains('show')) {
    setTimeout(function() { document.getElementById('catalogToast').classList.remove('show'); }, 4000);
}

document.querySelectorAll('[data-home-toggle]').forEach(function (checkbox) {
    var targetId = checkbox.getAttribute('data-home-toggle');
    var panel = document.getElementById(targetId);
    if (!panel) return;

    function sync() {
        panel.hidden = !checkbox.checked;
    }

    checkbox.addEventListener('change', sync);
    sync();
});

document.querySelectorAll('.cp-file-native').forEach(function (input) {
    var nameEl = input.closest('.cp-file-upload')?.querySelector('.cp-file-name');
    if (!nameEl) return;

    input.addEventListener('change', function () {
        nameEl.textContent = input.files && input.files[0] ? input.files[0].name : 'No file selected';
    });
});

function showCatalogToast(message, type) {
    var toast = document.getElementById('catalogToast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.remove('is-error');
    if (type === 'error') {
        toast.classList.add('is-error');
    }
    toast.classList.add('show');
    clearTimeout(window.__catalogToastTimer);
    window.__catalogToastTimer = setTimeout(function () {
        toast.classList.remove('show');
    }, 4000);
}

function syncToggleLabel(input) {
    var label = input.closest('.ad-toggle');
    var text = label ? label.querySelector('.ad-toggle-text') : null;
    if (text) {
        text.textContent = input.checked ? 'Enabled' : 'Disabled';
    }
}

document.querySelectorAll('[data-catalog-toggle]').forEach(function (input) {
    input.addEventListener('change', function () {
        var toggle = input.closest('.ad-toggle');
        var previousChecked = !input.checked;
        var formData = new FormData();
        formData.append('catalog_toggle', '1');
        formData.append('entity', input.dataset.entity || '');
        formData.append('field', input.dataset.field || '');
        formData.append('id', input.dataset.id || '0');
        formData.append('enabled', input.checked ? '1' : '0');

        if (toggle) {
            toggle.classList.add('is-busy');
            toggle.classList.remove('is-error');
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (!data.ok) {
                    input.checked = previousChecked;
                    syncToggleLabel(input);
                    if (toggle) {
                        toggle.classList.add('is-error');
                    }
                    showCatalogToast(data.message || 'Could not update setting.', 'error');
                    return;
                }
                syncToggleLabel(input);
                showCatalogToast(data.message || 'Setting updated.', 'success');
            })
            .catch(function () {
                input.checked = previousChecked;
                syncToggleLabel(input);
                if (toggle) {
                    toggle.classList.add('is-error');
                }
                showCatalogToast('Could not update setting. Please try again.', 'error');
            })
            .finally(function () {
                if (toggle) {
                    toggle.classList.remove('is-busy');
                }
            });
    });
});

(function () {
    var overlay = document.querySelector('.cp-overlay.open');
    if (!overlay) return;

    document.body.appendChild(overlay);
    document.documentElement.classList.add('cp-modal-open');
    document.body.classList.add('cp-modal-open');
})();
</script>
</body>
</html>
