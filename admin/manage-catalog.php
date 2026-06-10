<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_brand'])) {
        $name = trim($_POST['brand_name'] ?? '');
        $slug = trim($_POST['brand_slug'] ?? '') ?: catalogMakeSlug($name);
        if ($name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Brand name is required.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->brandSlugExists($s), catalogMakeSlug($slug));
            $model->addBrand($name, $slug)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand "' . $name . '" added.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error adding brand.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_brand'])) {
        $id = (int) ($_POST['edit_brand_id'] ?? 0);
        $name = trim($_POST['edit_brand_name'] ?? '');
        $slug = trim($_POST['edit_brand_slug'] ?? '') ?: catalogMakeSlug($name);
        if (!$id || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid brand data.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->brandSlugExists($s, $id), catalogMakeSlug($slug));
            $model->updateBrand($id, $name, $slug)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand updated.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error updating brand.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name'] ?? '');
        $slug = trim($_POST['category_slug'] ?? '') ?: catalogMakeSlug($name);
        $sort = (int) ($_POST['category_sort_order'] ?? 0);
        if ($name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Category name is required.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, null), catalogMakeSlug($slug));
            $model->addCategory($name, $slug, null, $sort, 1)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Category "' . $name . '" added.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error adding category.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_category'])) {
        $id = (int) ($_POST['edit_category_id'] ?? 0);
        $name = trim($_POST['edit_category_name'] ?? '');
        $slug = trim($_POST['edit_category_slug'] ?? '') ?: catalogMakeSlug($name);
        $sort = (int) ($_POST['edit_category_sort_order'] ?? 0);
        if (!$id || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid category data.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, null, $id), catalogMakeSlug($slug));
            $model->updateCategory($id, $name, $slug, null, $sort, 1)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Category updated.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error updating category.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['add_subcategory'])) {
        $parentId = (int) ($_POST['sub_parent_id'] ?? 0);
        $name = trim($_POST['subcategory_name'] ?? '');
        $slug = trim($_POST['subcategory_slug'] ?? '') ?: catalogMakeSlug($name);
        $sort = (int) ($_POST['sub_sort_order'] ?? 0);
        if (!$parentId || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Select a parent category and enter a subcategory name.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, $parentId), catalogMakeSlug($slug));
            $model->addCategory($name, $slug, $parentId, $sort, 1)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Subcategory "' . $name . '" added.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error adding subcategory.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }

    if (isset($_POST['edit_subcategory'])) {
        $id = (int) ($_POST['edit_sub_id'] ?? 0);
        $parentId = (int) ($_POST['edit_sub_parent_id'] ?? 0);
        $name = trim($_POST['edit_sub_name'] ?? '');
        $slug = trim($_POST['edit_sub_slug'] ?? '') ?: catalogMakeSlug($name);
        $sort = (int) ($_POST['edit_sub_sort_order'] ?? 0);
        if (!$id || !$parentId || $name === '') {
            $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Invalid subcategory data.'];
        } else {
            $slug = catalogUniqueSlug(fn ($s) => $model->categorySlugExists($s, $parentId, $id), catalogMakeSlug($slug));
            $model->updateCategory($id, $name, $slug, $parentId, $sort, 1)
                ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Subcategory updated.']
                : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error updating subcategory.'];
        }
        header('Location: manage-catalog.php');
        exit;
    }
}

if (isset($_GET['delete_brand'])) {
    $id = (int) $_GET['delete_brand'];
    if ($model->countProductsUsingBrand($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: products still use this brand.'];
    } else {
        $model->deleteBrand($id)
            ? $_SESSION['catalog_toast'] = ['type' => 'success', 'message' => 'Brand deleted.']
            : $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Error deleting brand.'];
    }
    header('Location: manage-catalog.php');
    exit;
}

if (isset($_GET['delete_category'])) {
    $id = (int) $_GET['delete_category'];
    if ($model->countChildCategories($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: category has subcategories.'];
    } elseif ($model->countProductsUsingCategory($id) > 0) {
        $_SESSION['catalog_toast'] = ['type' => 'error', 'message' => 'Cannot delete: products still use this category.'];
    } else {
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

$brands = $model->getAllBrands();
$categoriesWithSubs = $model->getCategoriesWithSubcounts();
$parentCategories = $model->getParentCategories();

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Catalog</title>
<link rel="stylesheet" href="/public/assets/css/admin/manage-catalog.css">
</head>
<body>
<div class="cp">

    <div class="ccard cp-header">
        <h1>Manage Catalog</h1>
        <p>Brands, parent categories, and subcategories for product permalinks (brand / category / subcategory / product).</p>
    </div>

    <div class="cp-grid">
        <div class="ccard cp-form">
            <h2>Add Brand</h2>
            <form method="POST">
                <div class="cp-field">
                    <label>Brand Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="brand_name" placeholder="e.g. Samsung, Apple" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="brand_slug" placeholder="auto-generated from name">
                </div>
                <button class="cp-btn" type="submit" name="add_brand">Add Brand</button>
            </form>
        </div>

        <div class="ccard cp-form">
            <h2>Add Category</h2>
            <form method="POST">
                <div class="cp-field">
                    <label>Category Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="category_name" placeholder="e.g. Mobiles, Tablets" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="category_slug" placeholder="auto-generated">
                </div>
                <div class="cp-field">
                    <label>Sort Order</label>
                    <input type="number" name="category_sort_order" value="0" min="0">
                </div>
                <button class="cp-btn" type="submit" name="add_category">Add Category</button>
            </form>
        </div>

        <div class="ccard cp-form">
            <h2>Add Subcategory</h2>
            <form method="POST">
                <div class="cp-field">
                    <label>Parent Category <span class="req-star" aria-hidden="true">*</span></label>
                    <select name="sub_parent_id" required>
                        <option value="">Select parent category</option>
                        <?php foreach ($parentCategories as $pc): ?>
                            <option value="<?= (int) $pc['category_id'] ?>"><?= htmlspecialchars($pc['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cp-field">
                    <label>Subcategory Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="subcategory_name" placeholder="e.g. Bluetooth Speakers" required>
                </div>
                <div class="cp-field">
                    <label>Slug (optional)</label>
                    <input type="text" name="subcategory_slug" placeholder="auto-generated">
                </div>
                <div class="cp-field">
                    <label>Sort Order</label>
                    <input type="number" name="sub_sort_order" value="0" min="0">
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
                    <tr><th>Name</th><th>Slug</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($brands)): ?>
                    <tr><td colspan="3"><div class="empty-state">No brands yet.</div></td></tr>
                <?php else: foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= htmlspecialchars($brand['brand_name']) ?></td>
                        <td><code><?= htmlspecialchars($brand['slug']) ?></code></td>
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
                    <tr><th>Category</th><th>Slug</th><th>Subcategories</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($categoriesWithSubs)): ?>
                    <tr><td colspan="4"><div class="empty-state">No categories yet.</div></td></tr>
                <?php else: foreach ($categoriesWithSubs as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                        <td>
                            <?php if (empty($cat['subcategories'])): ?>
                                <span style="color:var(--muted)">—</span>
                            <?php else: ?>
                                <div class="sub-chips">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <span class="sub-chip">
                                        <?= htmlspecialchars($sub['category_name']) ?>
                                        <a href="?edit_sub=<?= (int) $sub['category_id'] ?>" style="color:inherit">✎</a>
                                        <a href="?delete_subcategory=<?= (int) $sub['category_id'] ?>" onclick="return confirm('Delete subcategory?')" style="color:inherit">×</a>
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
        <form method="POST">
            <input type="hidden" name="edit_brand_id" value="<?= (int) $editBrand['brand_id'] ?>">
            <div class="cp-field"><label>Name</label><input type="text" name="edit_brand_name" value="<?= htmlspecialchars($editBrand['brand_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_brand_slug" value="<?= htmlspecialchars($editBrand['slug']) ?>"></div>
            <div class="modal-actions">
                <a class="cp-btn-sm" href="manage-catalog.php">Cancel</a>
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
        <form method="POST">
            <input type="hidden" name="edit_category_id" value="<?= (int) $editCategory['category_id'] ?>">
            <div class="cp-field"><label>Name</label><input type="text" name="edit_category_name" value="<?= htmlspecialchars($editCategory['category_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_category_slug" value="<?= htmlspecialchars($editCategory['slug']) ?>"></div>
            <div class="cp-field"><label>Sort Order</label><input type="number" name="edit_category_sort_order" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>" min="0"></div>
            <div class="modal-actions">
                <a class="cp-btn-sm" href="manage-catalog.php">Cancel</a>
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
                <select name="edit_sub_parent_id" required>
                    <?php foreach ($parentCategories as $pc): ?>
                        <option value="<?= (int) $pc['category_id'] ?>" <?= (int) $pc['category_id'] === (int) $editSub['parent_id'] ? 'selected' : '' ?>><?= htmlspecialchars($pc['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cp-field"><label>Name</label><input type="text" name="edit_sub_name" value="<?= htmlspecialchars($editSub['category_name']) ?>" required></div>
            <div class="cp-field"><label>Slug</label><input type="text" name="edit_sub_slug" value="<?= htmlspecialchars($editSub['slug']) ?>"></div>
            <div class="cp-field"><label>Sort Order</label><input type="number" name="edit_sub_sort_order" value="<?= (int) ($editSub['sort_order'] ?? 0) ?>" min="0"></div>
            <div class="modal-actions">
                <a class="cp-btn-sm" href="manage-catalog.php">Cancel</a>
                <button class="cp-btn" type="submit" name="edit_subcategory">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="cp-toast <?= $toastType === 'error' ? 'is-error' : '' ?> <?= $toast ? 'show' : '' ?>" id="catalogToast"><?= htmlspecialchars($toast) ?></div>
<script>
if (document.getElementById('catalogToast')?.classList.contains('show')) {
    setTimeout(function() { document.getElementById('catalogToast').classList.remove('show'); }, 4000);
}
</script>
</body>
</html>
