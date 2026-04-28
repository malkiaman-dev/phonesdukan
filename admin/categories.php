<?php
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
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
        .category-container { max-width: 1200px; margin: auto; }
        h1 { text-align: center; }
        .error { color: red; }
        .success { color: green; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-container { margin-bottom: 20px; }
        .form-container input, .form-container select { margin: 5px; padding: 5px; width: 200px; }
        .form-container button { padding: 5px 10px; margin-right: 5px; }
        .edit-form { display: none; }
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <div class="category-container">
        <h1>Manage Categories</h1>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Create Category Form -->
        <div class="form-container">
            <h2>Add New Category</h2>
            <form method="POST" action="/admin/categories?action=create">
                <input type="text" name="category_name" placeholder="Category Name" required>
                <input type="text" name="slug" placeholder="Slug" required>
                <label><input type="checkbox" name="status" checked> Active</label>
                <button type="submit">Create Category</button>
            </form>
        </div>

        <!-- Categories Table -->
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
                        <td><?php echo $category['status'] ? 'Active' : 'Inactive'; ?></td>
                        <td class="action-buttons">
                            <button onclick="showEditForm(<?php echo $category['id']; ?>)">Edit</button>
                            <form method="POST" action="/admin/categories?action=delete" style="display:inline;">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <!-- Edit Category Form -->
                    <tr class="edit-form" id="edit-form-<?php echo $category['id']; ?>">
                        <td colspan="5">
                            <form method="POST" action="/admin/categories?action=update">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <input type="text" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                                <input type="text" name="slug" value="<?php echo htmlspecialchars($category['slug']); ?>" required>
                                <label><input type="checkbox" name="status" <?php echo $category['status'] ? 'checked' : ''; ?>> Active</label>
                                <button type="submit">Update Category</button>
                                <button type="button" onclick="hideEditForm(<?php echo $category['id']; ?>)">Cancel</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function showEditForm(categoryId) {
            document.querySelectorAll('.edit-form').forEach(form => form.style.display = 'none');
            document.getElementById('edit-form-' + categoryId).style.display = 'table-row';
        }

        function hideEditForm(categoryId) {
            document.getElementById('edit-form-' + categoryId).style.display = 'none';
        }
    </script>
</body>
</html>