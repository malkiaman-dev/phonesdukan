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
$allowedLimits = [20, 50, 100];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 20;
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Fetch posts using the model
$posts = $postModel->getAllPosts($status_filter, $search_query);
$totalPosts = count($posts);
$totalPages = max(1, (int)ceil($totalPosts / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;
$posts = array_slice($posts, $offset, $limit);
$postCount = count($posts);

// Include the header
include __DIR__ . '/admin_header.php';
?>

<head>
    <title>Manage Posts - Phones Dukan</title>
    <style>
        :root {
            --black: #111111;
            --white: #ffffff;
            --yellow: #facc15;
            --yellow-hover: #eab308;
            --light-yellow: #fffbeb;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        .content-wrapper {
            padding: 20px;
            background-color: var(--bg);
            min-height: 100vh;
        }

        .posts-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
            padding: 18px;
        }

        .content-wrapper h2 {
            font-size: 30px;
            margin-bottom: 14px;
            color: var(--black);
            letter-spacing: -0.02em;
        }

        .custom-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            transition: color .15s ease, background .15s ease, border-color .15s ease;
            cursor: pointer;
            background: var(--black);
            color: var(--white);
            border: 1px solid var(--black);
            line-height: 1.2;
        }
        .custom-btn:link,
        .custom-btn:visited,
        .custom-btn:active {
            color: var(--white) !important;
            text-decoration: none;
        }

        .custom-btn:hover {
            color: var(--yellow);
            background: var(--black);
            border-color: var(--black);
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.12);
        }

        .custom-btn-warning,
        .custom-btn-danger,
        .custom-btn-primary,
        .custom-btn-success {
            background: var(--black);
            color: var(--white);
            border: 1px solid var(--black);
        }

        .filter-search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-section,
        .search-section {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-section label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
        }

        .search-section form,
        .filter-section form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 9px 12px;
            font-size: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
            background: var(--white);
            color: var(--black);
            transition: border-color .2s ease, box-shadow .2s ease;
            font-family: inherit;
        }

        .search-input {
            min-width: 220px;
        }

        .native-filter-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .search-input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.25);
        }

        .filter-select-wrap {
            position: relative;
            min-width: 120px;
        }

        .filter-display {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 34px 9px 12px;
            background: #fff;
            color: var(--black);
            font-size: 14px;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color .2s ease, box-shadow .2s ease;
            white-space: nowrap;
        }

        .filter-display::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid #111;
            border-bottom: 2px solid #111;
            transform: translateY(-65%) rotate(45deg);
        }

        .filter-display:hover,
        .filter-select-wrap.is-open .filter-display {
            border-color: var(--yellow);
            box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.25);
        }

        .filter-options {
            position: absolute;
            z-index: 50;
            left: 0;
            right: 0;
            margin-top: 6px;
            list-style: none;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 14px 30px rgba(17, 17, 17, 0.12);
            padding: 6px;
            display: none;
        }

        .filter-select-wrap.is-open .filter-options { display: block; }

        .filter-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--black);
            cursor: pointer;
        }

        .filter-option:hover { background: var(--light-yellow); }
        .filter-option.is-selected { background: var(--yellow); }

        .table-responsive {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .table-meta {
            font-size: 14px;
            color: var(--muted);
            margin: 8px 0 12px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--white);
            min-width: 900px;
        }

        .table th,
        .table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            vertical-align: middle;
        }

        .table th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: var(--black);
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background-color: var(--light-yellow);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .post-thumb {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--border);
            background: #f9fafb;
        }

        .post-title {
            max-width: 320px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-clamp: 2;
            word-break: break-word;
            font-weight: 600;
            color: var(--black);
        }

        .category-badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f9fafb;
            color: var(--black) !important;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f9fafb;
            color: var(--black) !important;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .action-buttons a:link,
        .action-buttons a:visited,
        .action-buttons a:active {
            color: var(--white) !important;
        }

        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .pagination-info {
            font-size: 14px;
            color: var(--muted);
        }

        .pagination {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid var(--border);
            color: var(--black);
            text-decoration: none;
            font-weight: 700;
            background: var(--white);
        }

        .page-link:hover {
            border-color: var(--yellow);
            background: var(--light-yellow);
        }

        .page-link.active {
            border-color: var(--yellow);
            background: var(--yellow);
        }

        .page-link.disabled {
            opacity: .5;
            pointer-events: none;
        }

        @media (max-width: 900px) {
            .content-wrapper { padding: 14px; }
            .posts-card { padding: 14px; }
            .filter-search-container { align-items: flex-start; }
            .search-section, .filter-section { width: 100%; }
            .search-input { min-width: 0; width: 100%; }
            .search-section form, .filter-section form { width: 100%; }
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
        <div class="posts-card">
            <h2>Manage Posts</h2>

            <div class="filter-search-container">
                <div class="filter-section">
                    <a href="add_post.php" class="custom-btn custom-btn-success">+ Add New Post</a>
                    <form method="GET" id="filterForm">
                        <label for="status">Filter by Status:</label>
                        <select class="native-filter-select" name="status" id="status">
                            <option value="all" <?php if ($status_filter === 'all') echo 'selected'; ?>>All</option>
                            <option value="published" <?php if ($status_filter === 'published') echo 'selected'; ?>>Published</option>
                            <option value="draft" <?php if ($status_filter === 'draft') echo 'selected'; ?>>Draft</option>
                        </select>
                        <div class="filter-select-wrap" data-filter-select>
                            <button type="button" class="filter-display" data-filter-display>
                                <?php echo $status_filter === 'all' ? 'All' : htmlspecialchars(ucfirst($status_filter)); ?>
                            </button>
                            <ul class="filter-options" data-filter-options>
                                <li><button type="button" class="filter-option <?php if ($status_filter === 'all') echo 'is-selected'; ?>" data-value="all">All</button></li>
                                <li><button type="button" class="filter-option <?php if ($status_filter === 'published') echo 'is-selected'; ?>" data-value="published">Published</button></li>
                                <li><button type="button" class="filter-option <?php if ($status_filter === 'draft') echo 'is-selected'; ?>" data-value="draft">Draft</button></li>
                            </ul>
                        </div>

                        <label for="limit">Posts per page:</label>
                        <select class="native-filter-select" name="limit" id="limit">
                            <option value="20" <?php if ($limit === 20) echo 'selected'; ?>>20</option>
                            <option value="50" <?php if ($limit === 50) echo 'selected'; ?>>50</option>
                            <option value="100" <?php if ($limit === 100) echo 'selected'; ?>>100</option>
                        </select>
                        <div class="filter-select-wrap" data-filter-select>
                            <button type="button" class="filter-display" data-filter-display><?php echo (int)$limit; ?></button>
                            <ul class="filter-options" data-filter-options>
                                <li><button type="button" class="filter-option <?php if ($limit === 20) echo 'is-selected'; ?>" data-value="20">20</button></li>
                                <li><button type="button" class="filter-option <?php if ($limit === 50) echo 'is-selected'; ?>" data-value="50">50</button></li>
                                <li><button type="button" class="filter-option <?php if ($limit === 100) echo 'is-selected'; ?>" data-value="100">100</button></li>
                            </ul>
                        </div>

                        <input type="hidden" name="page" value="1">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <div class="search-section">
                    <form method="GET">
                        <input class="search-input" type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="custom-btn" type="submit">Search</button>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <input type="hidden" name="limit" value="<?php echo (int)$limit; ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>

            <div class="table-meta"><strong>Total Posts:</strong> <?php echo $totalPosts; ?></div>

            <div class="table-responsive">
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
                            <?php
                                $rawImg = trim((string)($post['image_url'] ?? ''));
                                $normalized = str_replace('\\', '/', $rawImg);
                                $defaultImg = '../public/uploads/default.jpg';
                                $candidates = [];
                                if ($normalized !== '') {
                                    if (preg_match('#^(https?:)?//#i', $normalized)) {
                                        $candidates[] = $normalized;
                                    } elseif (preg_match('/^[A-Za-z]:\//', $normalized)) {
                                        // Windows filesystem path is not web-accessible; skip to fallbacks.
                                    } else {
                                        $trimmed = ltrim($normalized, './');
                                        $candidates[] = $normalized;
                                        $candidates[] = '../' . $trimmed;
                                        $candidates[] = '/' . $trimmed;
                                        $candidates[] = '/phonesdukan/' . $trimmed;
                                    }
                                }
                                $candidates[] = $defaultImg;
                                $candidates = array_values(array_unique(array_filter($candidates)));
                                $imgSrc = $candidates[0] ?? $defaultImg;
                                $imgCandidatesAttr = htmlspecialchars(json_encode($candidates), ENT_QUOTES, 'UTF-8');
                            ?>
                            <?php if (!empty($post['image_url'])): ?>
                                <img class="post-thumb post-image" src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                     data-candidates="<?php echo $imgCandidatesAttr; ?>" data-candidate-index="0"
                                     alt="<?php echo htmlspecialchars($post['alt_text'] ?? 'Post Image'); ?>" 
                                     width="50">
                            <?php else: ?>
                                <img class="post-thumb post-image" src="../public/uploads/default.jpg" alt="No Image" width="50">
                            <?php endif; ?>
                        </td>
                        <td><div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div></td>
                        <td><span class="category-badge"><?php echo htmlspecialchars($post['categories']); ?></span></td>
                        <td class="action-buttons">
                            <a href="<?php echo htmlspecialchars(url('admin/edit-post/' . (int)$post['id'])); ?>" class="custom-btn custom-btn-warning">Edit</a>
                            <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="custom-btn custom-btn-danger" onclick="return confirm('Are you sure you want to delete this post and all associated data?')">Delete</a>
                            <a href="<?php echo htmlspecialchars(url('blog/' . $post['category_slug'] . '/' . $post['slug'])); ?>" 
                               class="custom-btn custom-btn-primary" target="_blank">View</a>
                        </td>
                        <td><span class="status-pill"><?php echo htmlspecialchars(ucfirst($post['status'])); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($post['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <div class="pagination-wrap">
                <div class="pagination-info">
                    Page <strong><?php echo (int)$page; ?></strong> of <strong><?php echo (int)$totalPages; ?></strong>
                </div>
                <div class="pagination">
                    <?php
                        $baseParams = [
                            'status' => $status_filter,
                            'search' => $search_query,
                            'limit' => $limit,
                        ];
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        $prevHref = 'manage_posts.php?' . http_build_query($baseParams + ['page' => $prevPage]);
                        $nextHref = 'manage_posts.php?' . http_build_query($baseParams + ['page' => $nextPage]);
                    ?>
                    <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($prevHref); ?>">Previous</a>
                    <?php
                        $window = 2;
                        $start = max(1, $page - $window);
                        $end = min($totalPages, $page + $window);
                        for ($p = $start; $p <= $end; $p++):
                            $href = 'manage_posts.php?' . http_build_query($baseParams + ['page' => $p]);
                    ?>
                        <a class="page-link <?php echo $p === $page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo (int)$p; ?></a>
                    <?php endfor; ?>
                    <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($nextHref); ?>">Next</a>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-filter-select]').forEach(function (wrap) {
            const display = wrap.querySelector('[data-filter-display]');
            const options = Array.from(wrap.querySelectorAll('.filter-option'));
            const nativeSelect = wrap.previousElementSibling;
            if (!display || !nativeSelect || !nativeSelect.classList.contains('native-filter-select')) return;

            function setValue(value, shouldSubmit) {
                nativeSelect.value = value;
                const selectedOpt = options.find(function (opt) { return opt.dataset.value === value; });
                display.textContent = selectedOpt ? selectedOpt.textContent.trim() : value;
                options.forEach(function (opt) {
                    opt.classList.toggle('is-selected', opt.dataset.value === value);
                });
                if (shouldSubmit) {
                    nativeSelect.form?.submit();
                }
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.filter-select-wrap.is-open').forEach(function (openWrap) {
                    if (openWrap !== wrap) openWrap.classList.remove('is-open');
                });
                wrap.classList.toggle('is-open');
            });

            options.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    setValue(this.dataset.value || '', true);
                    wrap.classList.remove('is-open');
                });
            });

            setValue(nativeSelect.value || options[0]?.dataset.value || '', false);
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.filter-select-wrap.is-open').forEach(function (openWrap) {
                openWrap.classList.remove('is-open');
            });
        });

        // Image fallback resolver for inconsistent stored paths.
        document.querySelectorAll('.post-image').forEach(function (img) {
            img.addEventListener('error', function () {
                let candidates = [];
                try {
                    candidates = JSON.parse(this.dataset.candidates || '[]');
                } catch (e) {
                    candidates = [];
                }
                let idx = parseInt(this.dataset.candidateIndex || '0', 10);
                idx += 1;
                if (idx < candidates.length) {
                    this.dataset.candidateIndex = String(idx);
                    this.src = candidates[idx];
                } else {
                    this.onerror = null;
                    this.src = '../public/uploads/default.jpg';
                }
            });
        });
    });
    </script>
</body>
</html>