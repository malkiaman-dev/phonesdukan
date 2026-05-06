<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 1) . '/includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__, 1) . '/app/Models/AdminPostModel.php';
$toastMessage = '';
$toastType = 'success';
if (isset($_SESSION['cat_toast'])) {
    $toastMessage = (string)($_SESSION['cat_toast']['message'] ?? '');
    $toastType = (string)($_SESSION['cat_toast']['type'] ?? 'success');
    unset($_SESSION['cat_toast']);
}

$categoryModel = null;
if (!isset($categories) || !is_array($categories)) {
    $categoryModel = new AdminPostModel();
    $categories = $categoryModel->getAllCategories();
}

// Handle direct POST actions (base-path safe) without changing input names/routes shape.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!$categoryModel) {
        $categoryModel = new AdminPostModel();
    }

    try {
        switch ((string)$_GET['action']) {
            case 'create':
                $data = [
                    'category_name' => trim($_POST['category_name'] ?? ''),
                    'slug' => trim($_POST['slug'] ?? ''),
                    'status' => isset($_POST['status']) ? 1 : 0,
                ];
                $categoryModel->createCategory($data);
                $_SESSION['cat_toast'] = ['type' => 'success', 'message' => 'Category created successfully.'];
                break;
            case 'update':
                $category_id = $_POST['category_id'] ?? null;
                $data = [
                    'category_name' => trim($_POST['category_name'] ?? ''),
                    'slug' => trim($_POST['slug'] ?? ''),
                    'status' => isset($_POST['status']) ? 1 : 0,
                ];
                $categoryModel->updateCategory($category_id, $data);
                $_SESSION['cat_toast'] = ['type' => 'success', 'message' => 'Category updated successfully.'];
                break;
            case 'delete':
                $category_id = $_POST['category_id'] ?? null;
                $categoryModel->deleteCategory($category_id);
                $_SESSION['cat_toast'] = ['type' => 'success', 'message' => 'Category deleted successfully.'];
                break;
        }
    } catch (Exception $e) {
        $_SESSION['cat_toast'] = ['type' => 'error', 'message' => $e->getMessage()];
    }

    header('Location: ' . url('admin/categories.php'));
    exit;
}

include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        .category-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px;
            background: var(--bg);
        }

        .cat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .header-card {
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .header-card h1 {
            margin: 0;
            color: var(--black);
            font-size: 1.9rem;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .error,
        .success {
            margin: 0 0 14px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--yellow);
            background: var(--light-yellow);
            color: var(--black);
            font-weight: 700;
        }

        .form-container {
            margin-bottom: 20px;
            padding: 24px;
        }

        .form-container h2 {
            margin: 0 0 14px;
            color: var(--black);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .create-form-grid,
        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            align-items: end;
        }

        .create-form-grid input[type="text"],
        .edit-form-grid input[type="text"] {
            width: 100%;
            height: 46px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            background: #fff;
            color: var(--black);
            outline: none;
            font-size: 0.92rem;
        }

        .create-form-grid input[type="text"]:focus,
        .edit-form-grid input[type="text"]:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        }

        .check-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            background: #f9fafb;
            color: var(--black);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .check-wrap input[type="checkbox"] {
            accent-color: var(--yellow);
            width: 16px;
            height: 16px;
            margin: 0;
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            padding: 0 16px;
            border: 1px solid var(--black);
            border-radius: 10px;
            background: var(--black);
            color: #fff !important;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, color .12s ease;
        }

        button:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(17, 17, 17, 0.14);
        }

        .table-card {
            padding: 24px;
        }

        .table-head {
            margin-bottom: 14px;
        }

        .table-head h2 {
            margin: 0;
            color: var(--black);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .table-head p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .table-wrap {
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
            font-size: 0.9rem;
        }

        th {
            background: #f9fafb;
            color: var(--black);
            font-weight: 800;
        }

        tbody tr:hover {
            background: var(--light-yellow);
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--border) !important;
            background: #f9fafb !important;
            color: var(--black) !important;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .edit-actions {
            margin-top: 10px;
        }

        .edit-form {
            display: none;
            background: #fcfcfd;
        }

        .edit-form td {
            padding: 16px;
        }

        @media (max-width: 900px) {
            .category-container {
                padding: 14px;
            }

            .create-form-grid,
            .edit-form-grid {
                grid-template-columns: 1fr;
            }
        }

        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }

        .confirm-overlay.is-open { display: flex; }

        .confirm-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(17, 17, 17, 0.18);
            padding: 20px;
        }

        .confirm-title {
            margin: 0 0 8px;
            color: var(--black);
            font-size: 1.02rem;
            font-weight: 800;
        }

        .confirm-text {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .confirm-actions {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1300;
            background: #111;
            color: #fff;
            border: 1px solid var(--yellow);
            border-left: 5px solid var(--yellow);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 14px 30px rgba(17, 17, 17, 0.3);
            display: none;
        }

        .toast.is-error {
            border-color: var(--yellow);
            border-left-color: var(--yellow);
        }

        .toast.show { display: block; }
    </style>
</head>
<body>
    <div class="category-container">
        <div class="cat-card header-card">
            <h1>Manage Categories</h1>
            <p class="header-subtitle">Create, edit, and manage blog categories.</p>
        </div>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Create Category Form -->
        <div class="cat-card form-container">
            <h2>Add New Category</h2>
            <form method="POST" action="<?= htmlspecialchars(url('admin/categories.php?action=create')); ?>">
                <div class="create-form-grid">
                    <input type="text" name="category_name" placeholder="Category Name" required>
                    <input type="text" name="slug" placeholder="Slug" required>
                    <label class="check-wrap"><input type="checkbox" name="status" checked> Active</label>
                </div>
                <button type="submit" style="margin-top: 10px;">Create Category</button>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="cat-card table-card">
            <div class="table-head">
                <h2>Existing Categories</h2>
                <p>View and manage all saved categories.</p>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['id']); ?></td>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td><span class="status-pill"><?php echo $category['status'] ? 'Active' : 'Inactive'; ?></span></td>
                                <td class="action-buttons">
                                    <button type="button" onclick="showEditForm(<?php echo $category['id']; ?>)">Edit</button>
                                    <form method="POST" action="<?= htmlspecialchars(url('admin/categories.php?action=delete')); ?>" style="display:inline;" class="delete-form">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="delete-btn" data-confirm="Are you sure you want to delete this category?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Edit Category Form -->
                            <tr class="edit-form" id="edit-form-<?php echo $category['id']; ?>">
                                <td colspan="5">
                                    <form method="POST" action="<?= htmlspecialchars(url('admin/categories.php?action=update')); ?>">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <div class="edit-form-grid">
                                            <input type="text" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                                            <input type="text" name="slug" value="<?php echo htmlspecialchars($category['slug']); ?>" required>
                                            <label class="check-wrap"><input type="checkbox" name="status" <?php echo $category['status'] ? 'checked' : ''; ?>> Active</label>
                                        </div>
                                        <div class="action-buttons edit-actions">
                                            <button type="submit">Update Category</button>
                                            <button type="button" onclick="hideEditForm(<?php echo $category['id']; ?>)">Cancel</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="confirmOverlay" class="confirm-overlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <h3 id="confirmTitle" class="confirm-title">Confirm Delete</h3>
            <p id="confirmText" class="confirm-text">Are you sure you want to delete this item?</p>
            <div class="confirm-actions">
                <button type="button" id="confirmCancelBtn">Cancel</button>
                <button type="button" id="confirmYesBtn">Delete</button>
            </div>
        </div>
    </div>

    <div id="catToast" class="toast <?php echo $toastType === 'error' ? 'is-error' : ''; ?>" data-message="<?php echo htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($toastMessage); ?>
    </div>

    <script>
        function showEditForm(categoryId) {
            document.querySelectorAll('.edit-form').forEach(form => form.style.display = 'none');
            document.getElementById('edit-form-' + categoryId).style.display = 'table-row';
        }

        function hideEditForm(categoryId) {
            document.getElementById('edit-form-' + categoryId).style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('confirmOverlay');
            const textEl = document.getElementById('confirmText');
            const cancelBtn = document.getElementById('confirmCancelBtn');
            const yesBtn = document.getElementById('confirmYesBtn');
            let pendingForm = null;

            document.querySelectorAll('.delete-form .delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    pendingForm = this.closest('form');
                    if (textEl) textEl.textContent = this.getAttribute('data-confirm') || 'Are you sure?';
                    overlay?.classList.add('is-open');
                });
            });

            cancelBtn?.addEventListener('click', function () {
                overlay?.classList.remove('is-open');
                pendingForm = null;
            });

            yesBtn?.addEventListener('click', function () {
                if (pendingForm) pendingForm.submit();
            });

            overlay?.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('is-open');
                    pendingForm = null;
                }
            });

            const toast = document.getElementById('catToast');
            if (toast && toast.dataset.message) {
                toast.classList.add('show');
                setTimeout(function () { toast.classList.remove('show'); }, 3200);
            }
        });
    </script>
</body>
</html>