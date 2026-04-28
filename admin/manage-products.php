<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die('Database connection failed: ' . $conn->errorInfo()[2]);
}

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClauses = [];
if ($status_filter === 'active') {
    $whereClauses[] = 'p.product_status = 1';
} elseif ($status_filter === 'inactive') {
    $whereClauses[] = 'p.product_status = 0';
}

if (!empty($search_query)) {
    $safe_search    = $conn->quote('%' . $search_query . '%');
    $whereClauses[] = "p.product_name LIKE $safe_search";
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

include __DIR__ . '/admin_header.php';

$query = "SELECT 
            p.product_id, p.product_name, p.product_slug, p.regular_price, p.sale_price,
            p.stock_quantity, p.created_at,
            i.image_url, m.alt_text,
            c.slug AS category_slug,
            b.slug AS brand_slug,
            p.product_status
          FROM products p
          LEFT JOIN product_images i  ON p.product_id = i.product_id AND i.is_primary = 1
          LEFT JOIN image_metadata m  ON i.image_id = m.image_id
          LEFT JOIN categories c      ON p.category_id = c.category_id
          LEFT JOIN brands b          ON p.brand_id = b.brand_id
          $whereClause
          ORDER BY p.created_at DESC";

$result       = $conn->query($query);
$productCount = $result->rowCount();

if (!$result) {
    die('Error fetching products: ' . $conn->errorInfo()[2]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Phones Dukan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f0f2f5;
            color: #222;
        }

        /* ===== Wrapper ===== */
        .prd-wrap {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 24px;
        }

        /* ===== Top Bar ===== */
        .prd-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .prd-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -0.3px;
        }

        /* ===== Add Button ===== */
        .btn-add {
            display: inline-block;
            background: #22c55e;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(34,197,94,0.3);
            transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
            white-space: nowrap;
        }
        .btn-add:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34,197,94,0.35);
        }

        /* ===== Filter & Search Bar ===== */
        .prd-controls {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .prd-controls form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .prd-controls label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            white-space: nowrap;
        }

        .prd-select,
        .prd-search-input {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            color: #333;
            border: 1.5px solid #d0d7e2;
            border-radius: 7px;
            padding: 8px 12px;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .prd-select {
            appearance: none;
            -webkit-appearance: none;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23666' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 10px center;
            padding-right: 30px;
            min-width: 130px;
        }

        .prd-select:focus,
        .prd-search-input:focus {
            border-color: #1a7fe8;
            box-shadow: 0 0 0 3px rgba(26,127,232,0.12);
        }

        .prd-search-input { min-width: 220px; }

        .btn-search {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            background: #1a7fe8;
            border: none;
            border-radius: 7px;
            padding: 8px 18px;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }
        .btn-search:hover {
            background: #1565c0;
            transform: translateY(-1px);
        }

        /* ===== Count Label ===== */
        .prd-count {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
        }
        .prd-count strong { color: #1a1a2e; }

        /* ===== Table Wrapper ===== */
        .prd-table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* ===== Table ===== */
        .prd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .prd-table thead tr { background: #1a7fe8; }

        .prd-table thead th {
            color: #fff;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 14px 16px;
            text-align: left;
            white-space: nowrap;
        }

        .prd-table tbody tr {
            border-bottom: 1px solid #e8ecf0;
            transition: background 0.15s;
        }
        .prd-table tbody tr:last-child { border-bottom: none; }
        .prd-table tbody tr:hover { background: #f5f8ff; }

        .prd-table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            color: #333;
        }

        /* Product image */
        .prd-img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e8ecf0;
        }

        /* Product name */
        .prd-name {
            font-weight: 600;
            color: #1a1a2e;
            max-width: 220px;
        }

        /* Price */
        .prd-price {
            font-weight: 700;
            color: #1a1a2e;
            white-space: nowrap;
        }

        /* Stock badge */
        .stock-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .stock-ok  { background: #dcfce7; color: #166534; }
        .stock-low { background: #fef9c3; color: #854d0e; }
        .stock-out { background: #fee2e2; color: #991b1b; }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .status-active   { background: #22c55e; color: #fff; }
        .status-inactive { background: #94a3b8; color: #fff; }

        /* ===== Action Buttons ===== */
        .prd-btn {
            display: inline-block;
            padding: 6px 13px;
            border: none;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.1s, box-shadow 0.15s;
            white-space: nowrap;
            margin: 2px 1px;
        }
        .prd-btn:hover { transform: translateY(-1px); }
        .prd-btn:active { transform: translateY(0); }

        .prd-btn-edit   { background: #f59e0b; color: #fff; box-shadow: 0 2px 6px rgba(245,158,11,0.3); }
        .prd-btn-edit:hover { background: #d97706; box-shadow: 0 4px 10px rgba(245,158,11,0.35); }

        .prd-btn-delete { background: #ef4444; color: #fff; box-shadow: 0 2px 6px rgba(239,68,68,0.3); }
        .prd-btn-delete:hover { background: #dc2626; box-shadow: 0 4px 10px rgba(239,68,68,0.35); }

        .prd-btn-view   { background: #1a7fe8; color: #fff; box-shadow: 0 2px 6px rgba(26,127,232,0.3); }
        .prd-btn-view:hover { background: #1565c0; box-shadow: 0 4px 10px rgba(26,127,232,0.35); }

        /* ===== Responsive ===== */
        @media (max-width: 900px) {
            .prd-wrap { padding: 0 12px; margin: 16px auto; }
            .prd-title { font-size: 1.4rem; }
            .prd-table { font-size: 0.8rem; }
            .prd-table thead th,
            .prd-table tbody td { padding: 10px 10px; }
            .prd-search-input { min-width: 150px; }
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
    <?php include __DIR__ . '/admin_sidebar.php'; ?>
<?php endif; ?>

<div class="prd-wrap">

    <!-- Top Bar -->
    <div class="prd-topbar">
        <h2 class="prd-title">Manage Products</h2>
        <a href="add-product.php" class="btn-add">+ Add New Product</a>
    </div>

    <!-- Filter & Search -->
    <div class="prd-controls">

        <!-- Status Filter -->
        <form method="GET">
            <label for="status">Filter:</label>
            <select name="status" id="status" class="prd-select" onchange="this.form.submit()">
                <option value="all"      <?= ($status_filter === 'all')      ? 'selected' : '' ?>>All</option>
                <option value="active"   <?= ($status_filter === 'active')   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($status_filter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
            <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
            <?php endif; ?>
        </form>

        <!-- Search -->
        <form method="GET">
            <input type="text" name="search" class="prd-search-input"
                   placeholder="Search products..."
                   value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="btn-search">Search</button>
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
        </form>

    </div>

    <!-- Count -->
    <p class="prd-count"><strong><?= $productCount ?></strong> product<?= $productCount !== 1 ? 's' : '' ?> found</p>

    <!-- Table -->
    <div class="prd-table-wrap">
        <table class="prd-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                <?php
                    $stock = (int)$row['stock_quantity'];
                    $stockClass = $stock > 10 ? 'stock-ok' : ($stock > 0 ? 'stock-low' : 'stock-out');
                    $stockLabel = $stock > 0 ? $stock : 'Out';
                ?>
                <tr>
                    <td>
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="<?= htmlspecialchars($row['image_url']) ?>"
                                 alt="<?= htmlspecialchars($row['alt_text'] ?? 'Product Image') ?>"
                                 class="prd-img">
                        <?php else: ?>
                            <img src="../public/uploads/default.jpg" alt="No Image" class="prd-img">
                        <?php endif; ?>
                    </td>
                    <td class="prd-name"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td class="prd-price">Rs. <?= number_format((float)($row['sale_price'] ?? $row['regular_price']), 0) ?></td>
                    <td><span class="stock-badge <?= $stockClass ?>"><?= $stockLabel ?></span></td>
                    <td>
                        <span class="status-badge <?= $row['product_status'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $row['product_status'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="edit-product.php?id=<?= $row['product_id'] ?>" class="prd-btn prd-btn-edit">Edit</a>
                        <a href="delete_product.php?id=<?= $row['product_id'] ?>"
                           class="prd-btn prd-btn-delete"
                           onclick="return confirm('Are you sure you want to delete this product and all associated data?')">Delete</a>
                        <a href="/<?= htmlspecialchars($row['category_slug'] . '/' . $row['brand_slug'] . '/' . $row['product_slug']) ?>"
                           class="prd-btn prd-btn-view" target="_blank">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>