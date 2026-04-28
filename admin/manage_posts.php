<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/AdminPostModel.php';

// Initialize database connection and model
$database = new Database();
$conn = $database->getConnection();
$postModel = new AdminPostModel();

if (!$conn) {
    die('Database connection failed: ' . $conn->errorInfo()[2]);
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

// Handle status filter and search query
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch posts using the model
$posts = $postModel->getAllPosts($status_filter, $search_query);
$postCount = count($posts);

// Include the header
include __DIR__ . '/admin_header.php';
?>

<head>
    <title>Manage Posts - Phones Dukan</title>
    <style>
        /* General layout for content wrapper */
.content-wrapper {
    padding: 20px;
    background-color: #f8f9fa;
    min-height: 100vh;
}

/* Heading */
.content-wrapper h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

/* Button styles */
.custom-btn {
    display: inline-block;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s, color 0.3s;
    cursor: pointer;
}

.custom-btn-success {
    background-color: #28a745;
    color: #fff;
    border: 1px solid #28a745;
}

.custom-btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.custom-btn-warning {
    background-color: #ffc107;
    color: #212529;
    border: 1px solid #ffc107;
}

.custom-btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}

.custom-btn-danger {
    background-color: #dc3545;
    color: #fff;
    border: 1px solid #dc3545;
}

.custom-btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.custom-btn-primary {
    background-color: #007bff;
    color: #fff;
    border: 1px solid #007bff;
}

.custom-btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

/* Filter and search container */
.filter-search-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.filter-section, .search-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-section label {
    font-size: 14px;
    color: #555;
}

.filter-section select, .search-section input[type="text"] {
    padding: 8px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.3s;
}

.filter-section select:focus, .search-section input[type="text"]:focus {
    border-color: #007bff;
}

.search-section button {
    padding: 8px 16px;
    font-size: 14px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-section button:hover {
    background-color: #0069d9;
}

/* Table styles */
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table th, .table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
    font-size: 14px;
}

.table th {
    background-color: #f1f1f1;
    font-weight: 600;
    color: #333;
}

.table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.table tbody tr:hover {
    background-color: #f5f5f5;
}

.table img {
    max-width: 50px;
    height: auto;
    border-radius: 4px;
    object-fit: cover;
}

.action-buttons a {
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
}

/* Total posts count */
.table-responsive p {
    font-size: 14px;
    color: #555;
    margin-bottom: 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
        padding: 15px;
    }

    .filter-search-container {
        flex-direction: column;
        align-items: flex-start;
    }

    .filter-section, .search-section {
        width: 100%;
    }

    .search-section input[type="text"] {
        width: calc(100% - 90px);
    }

    .search-section button {
        width: 80px;
    }

    .table th, .table td {
        font-size: 12px;
        padding: 8px;
    }

    .action-buttons a {
        padding: 6px 12px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .table th, .table td {
        font-size: 10px;
        padding: 6px;
    }

    .action-buttons a {
        display: block;
        margin-bottom: 5px;
    }

    .custom-btn {
        font-size: 12px;
        padding: 6px 12px;
    }
}
    </style>
</head>
<body>
    <?php
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        include __DIR__ . '/admin_sidebar.php';
    }
    ?>

    <div class="content-wrapper">
        <h2>Manage Posts</h2>
        <a href="add_post.php" class="custom-btn custom-btn-success">+ Add New Post</a>

        <div class="filter-search-container">
            <!-- Status Filter -->
            <div class="filter-section">
                <form method="GET">
                    <label for="status">Filter by Status:</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="all" <?php if ($status_filter === 'all') echo 'selected'; ?>>All</option>
                        <option value="published" <?php if ($status_filter === 'published') echo 'selected'; ?>>Published</option>
                        <option value="draft" <?php if ($status_filter === 'draft') echo 'selected'; ?>>Draft</option>
                    </select>
                    <!-- Preserve search query in filter form -->
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                </form>
            </div>

            <!-- Search Box -->
            <div class="search-section">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Search</button>
                    <!-- Preserve status filter in search form -->
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <p><strong>Total Posts:</strong> <?php echo $postCount; ?></p>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Categories</th>
                        <th>Actions</th>
                        <th>Status</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <?php if (!empty($post['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['alt_text'] ?? 'Post Image'); ?>" 
                                     width="50">
                            <?php else: ?>
                                <img src="../public/uploads/default.jpg" alt="No Image" width="50">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td><?php echo htmlspecialchars($post['categories']); ?></td>
                        <td class="action-buttons">
                            <a href="/admin/edit-post/<?php echo $post['id']; ?>?" class="custom-btn custom-btn-warning">Edit</a>
                            <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="custom-btn custom-btn-danger" onclick="return confirm('Are you sure you want to delete this post and all associated data?')">Delete</a>
                            <a href="/blog/<?php echo htmlspecialchars($post['category_slug'] . '/' . $post['slug']); ?>" 
                               class="custom-btn custom-btn-primary" target="_blank">View</a>
                        </td>
                        <td><?php echo htmlspecialchars(ucfirst($post['status'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($post['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>