<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

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
$statusFilterLabels = [
    'all' => 'All',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'coming_soon' => 'Coming Soon',
];
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPageOptions = [20, 50, 100];
$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
if (!in_array($per_page, $perPageOptions, true)) {
    $per_page = 20;
}
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = $page > 0 ? $page : 1;

$whereClauses = [];
$params = [];
if ($status_filter === 'active') {
    $whereClauses[] = 'p.product_status = 1';
} elseif ($status_filter === 'inactive') {
    $whereClauses[] = 'p.product_status = 0';
} elseif ($status_filter === 'coming_soon') {
    $whereClauses[] = 'p.product_status = 2';
}

if ($search_query !== '') {
    $whereClauses[] = 'p.product_name LIKE :search';
    $params[':search'] = '%' . $search_query . '%';
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$countStmt = $conn->prepare("SELECT COUNT(*) FROM products p $whereClause");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $per_page));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $per_page;

$query = "SELECT 
            p.product_id, p.product_name, p.product_slug, p.regular_price, p.sale_price,
            p.stock_quantity, p.created_at,
            i.image_url,
            m.alt_text,
            c.slug AS category_slug,
            b.slug AS brand_slug,
            sc.slug AS subcategory_slug,
            p.product_status
          FROM products p
          LEFT JOIN product_images i
            ON i.product_id = p.product_id
           AND i.is_primary = 1
          LEFT JOIN image_metadata m
            ON m.image_id = i.image_id
          LEFT JOIN categories c
            ON p.category_id = c.category_id
          LEFT JOIN brands b
            ON p.brand_id = b.brand_id
          LEFT JOIN categories sc
            ON p.subcategory_id = sc.category_id
          $whereClause
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/admin_header.php';

$buildManageUrl = static function (array $overrides = []) use ($status_filter, $search_query, $per_page, $page): string {
    $query = array_merge([
        'status' => $status_filter,
        'search' => $search_query,
        'per_page' => $per_page,
        'page' => $page,
    ], $overrides);
    if (($query['search'] ?? '') === '') {
        unset($query['search']);
    }
    if (($query['status'] ?? 'all') === 'all') {
        // keep status for filter UI consistency
    }
    return 'manage-products.php?' . http_build_query($query);
};
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Phones Dukan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black: #111111;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
            --yellow: #facc15;
            --light-yellow: #fffbeb;
            --yellow-glow: rgba(250, 204, 21, 0.18);
        }

        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--bg);
            color: var(--black);
        }

        .prd-wrap {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 24px;
        }

        .prd-topbar {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding: 20px 22px;
            margin-bottom: 16px;
        }

        .prd-title {
            font-size: clamp(1.5rem, 2vw, 1.75rem);
            font-weight: 600;
            color: var(--black);
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin: 0;
        }

        .prd-subtitle {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f7cf04;
            color: #111111;
            border: 1px solid #e6bd00;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 12px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(247, 207, 4, 0.22);
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }
        .btn-add:hover {
            background: #e6bd00;
            color: #111111;
            border-color: #d4af00;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(247, 207, 4, 0.28);
        }

        .prd-controls {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .prd-controls-left,
        .prd-controls-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .prd-controls-left { justify-content: flex-start; }
        .prd-controls-right { justify-content: flex-end; margin-left: auto; }

        .prd-controls form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .prd-controls label {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--black);
            white-space: nowrap;
        }

        .prd-select,
        .prd-search-input {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--black);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0 18px;
            background: var(--white);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            outline: none;
            height: 52px;
        }

        .prd-select {
            appearance: none;
            -webkit-appearance: none;
            background:
                #fff
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='10' viewBox='0 0 14 10'%3E%3Cpath d='M2 2l5 6 5-6' stroke='%23111111' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")
                no-repeat right 16px center;
            padding-right: 44px;
            min-width: 170px;
        }
        .native-filter-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .filter-select-wrap {
            position: relative;
            min-width: 170px;
        }

        .filter-display {
            width: 100%;
            height: 52px;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0 44px 0 16px;
            background: var(--white);
            color: var(--black);
            font-size: 0.95rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .filter-display::after {
            content: "";
            position: absolute;
            right: 16px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid var(--black);
            border-bottom: 2px solid var(--black);
            transform: translateY(-65%) rotate(45deg);
        }

        .filter-display:hover,
        .filter-select-wrap.is-open .filter-display {
            background-color: #fcfcfd;
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px var(--yellow-glow);
        }

        .filter-options {
            position: absolute;
            left: 0;
            right: 0;
            z-index: 70;
            list-style: none;
            margin-top: 6px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(17,17,17,0.12);
            padding: 6px;
            display: none;
        }

        .filter-select-wrap.is-open .filter-options {
            display: block;
        }

        .filter-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            text-align: left;
            padding: 8px 10px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--black);
            cursor: pointer;
        }

        .filter-option:hover {
            background: var(--light-yellow);
        }

        .filter-option.is-selected {
            background: var(--yellow);
        }

        .prd-search-input {
            width: 100%;
            min-width: 0;
        }

        .prd-select:hover,
        .prd-search-input:hover {
            background-color: #fcfcfd;
        }

        .prd-select:focus,
        .prd-search-input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px var(--yellow-glow);
        }

        .btn-search {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            color: #111111;
            background: #f7cf04;
            border: 1px solid #e6bd00;
            border-radius: 12px;
            padding: 0 18px;
            height: 52px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(247, 207, 4, 0.22);
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }
        .btn-search:hover {
            background: #e6bd00;
            color: #111111;
            border-color: #d4af00;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(247, 207, 4, 0.28);
        }

        .btn-export {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--black);
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0 18px;
            height: 52px;
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
            white-space: nowrap;
        }
        .btn-export:hover {
            color: var(--black);
            border-color: var(--yellow);
            background: #fcfcfd;
            transform: translateY(-1px);
            box-shadow: 0 0 0 3px var(--yellow-glow);
        }

        .prd-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--black);
            border: 1px solid var(--black);
            border-radius: 999px;
            padding: 10px 14px;
            margin: 10px 0 14px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
            color: var(--white);
            font-weight: 700;
            width: fit-content;
        }
        .prd-count-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--yellow);
            box-shadow: 0 0 0 3px var(--yellow-glow);
        }
        .prd-count-pill span {
            color: rgba(255,255,255,0.85);
            font-weight: 800;
        }
        .prd-count-pill strong {
            color: var(--white);
            font-weight: 900;
        }

        .prd-table-wrap {
            background: var(--white);
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .prd-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.92rem;
        }

        .prd-table thead tr { background: #f9fafb; }

        .prd-table thead th {
            color: var(--black);
            font-weight: 900;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 18px 16px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        .prd-table tbody tr {
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .prd-table tbody tr:hover { background: var(--light-yellow); }

        .prd-table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            color: var(--black);
            border-bottom: 1px solid #f0f2f5;
        }
        .prd-table tbody tr:last-child td { border-bottom: 0; }

        .prd-imgbox {
            width: 84px;
            height: 84px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--bg);
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .prd-img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            background: var(--bg);
            color: transparent;
            font-size: 0;
        }

        .prd-imgbox.is-placeholder .prd-img {
            display: none;
        }

        .prd-img-placeholder {
            display: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--yellow);
            color: var(--black);
            font-weight: 900;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .prd-imgbox.is-placeholder .prd-img-placeholder {
            display: inline-flex;
        }

        .prd-name {
            font-weight: 900;
            color: var(--black);
            max-width: 320px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .prd-price {
            font-weight: 900;
            color: var(--black);
            white-space: nowrap;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 900;
        }
        /* Stock badge (palette-safe, always readable) */
        .stock-ok,
        .stock-low,
        .stock-out {
            background: var(--light-yellow) !important;
            border: 1px solid var(--yellow) !important;
            color: var(--black) !important;
            font-weight: 700 !important;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 900;
        }
        /* Status badge (same style as stock for visibility) */
        .status-active,
        .status-inactive,
        .status-coming-soon {
            background: var(--light-yellow) !important;
            border: 1px solid var(--yellow) !important;
            color: var(--black) !important;
            font-weight: 700 !important;
            text-transform: capitalize;
        }

        .prd-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            min-width: 170px;
        }

        .prd-actions .prd-btn:nth-child(3) {
            grid-column: 1 / -1;
        }

        .prd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0 14px;
            border: 1px solid var(--black);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
            overflow: visible;
            min-width: 0;
            line-height: 1;
            z-index: 1;
        }
        .prd-btn:link,
        .prd-btn:visited,
        .prd-btn:active {
            text-decoration: none !important;
        }
        .prd-btn:hover { transform: translateY(-1px); }
        .prd-btn:active { transform: translateY(0); }

        .prd-btn-edit,
        .prd-btn-view {
            background: #f7cf04;
            color: #111111;
            border-color: #e6bd00;
            box-shadow: 0 2px 8px rgba(247, 207, 4, 0.18);
        }
        .prd-btn-edit:hover,
        .prd-btn-view:hover {
            background: #e6bd00;
            color: #111111;
            border-color: #d4af00;
            box-shadow: 0 6px 16px rgba(247, 207, 4, 0.26);
        }

        .prd-btn-delete {
            background: #ffffff;
            color: #ef4444;
            border-color: #fecaca;
            box-shadow: none;
        }
        .prd-btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.22);
        }

        /* Remove any default link underline/blue in this page */
        .prd-wrap a:not(.prd-btn):not(.btn-add),
        .prd-wrap a:hover,
        .prd-wrap a:visited,
        .prd-wrap a:active {
            text-decoration: none !important;
            color: inherit;
        }

        .btn-add,
        .btn-add:link,
        .btn-add:visited,
        .btn-add:active,
        .btn-add:hover {
            text-decoration: none !important;
        }

        .prd-btn,
        .prd-btn:link,
        .prd-btn:visited,
        .prd-btn:active,
        .prd-btn:hover {
            text-decoration: none !important;
        }

        .prd-btn-edit,
        .prd-btn-edit:hover,
        .prd-btn-view,
        .prd-btn-view:hover {
            color: #111111 !important;
        }

        .prd-btn-delete:hover {
            color: #ffffff !important;
        }

        /* Ensure badges are never affected by global span rules */
        .stock-badge,
        .status-badge {
            background-image: none !important;
            padding: 6px 12px !important;
            margin: 0 !important;
            border-radius: 999px !important;
        }

        .prd-count-pill,
        .prd-count-pill span,
        .prd-count-pill strong {
            background-image: none !important;
        }
        .prd-count-pill span,
        .prd-count-pill strong {
            background-color: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        .prd-count-pill {
            background: var(--black) !important;
            color: var(--white) !important;
        }
        .prd-count-pill span {
            color: rgba(255,255,255,0.85) !important;
        }
        .prd-count-pill strong {
            color: var(--white) !important;
        }

        .prd-img-placeholder {
            color: var(--black) !important;
            background: var(--yellow) !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .prd-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .prd-page-info {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .prd-page-links {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .prd-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--black);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .prd-page-link:hover:not(.is-disabled):not(.is-active) {
            border-color: var(--yellow);
            background: var(--light-yellow);
            color: var(--yellow);
        }

        .prd-page-link.is-active {
            border-color: var(--yellow);
            background: var(--yellow);
            color: var(--black);
        }

        .prd-page-link.is-disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        @media (max-width: 900px) {
            .prd-wrap { padding: 0 12px; margin: 16px auto; }
            .prd-title { font-size: 1.45rem; }
            .prd-topbar { padding: 16px; }

            .prd-controls {
                padding: 12px;
            }
            .prd-controls-left,
            .prd-controls-right {
                justify-content: flex-start;
            }
            .prd-select { min-width: 0; width: 100%; }
            .btn-search { width: 100%; }
            .btn-export { width: 100%; }
            .filter-select-wrap { width: 100%; min-width: 0; }
            .prd-controls form { flex-wrap: wrap; width: 100%; }
        }

        @media (max-width: 768px) {
            .prd-table,
            .prd-table thead,
            .prd-table tbody,
            .prd-table tr,
            .prd-table th,
            .prd-table td {
                display: block;
                width: 100%;
            }

            .prd-table thead { display: none; }

            .prd-table tbody tr {
                border-bottom: 1px solid var(--border);
                padding: 12px 12px 6px;
            }

            .prd-table tbody td {
                border-bottom: 0;
                padding: 10px 6px;
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: center;
            }

            .prd-table tbody td::before {
                content: attr(data-label);
                flex: 0 0 42%;
                color: var(--muted);
                font-size: 0.72rem;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .prd-name { max-width: none; }
            .prd-actions {
                width: 100%;
                min-width: 0;
                grid-template-columns: 1fr;
            }
            .prd-actions .prd-btn:nth-child(3) { grid-column: auto; }
        }
    </style>

<?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
    <?php include __DIR__ . '/admin_sidebar.php'; ?>
<?php endif; ?>

<div class="prd-wrap">

    <!-- Top Bar -->
    <div class="prd-topbar">
        <div>
            <h2 class="prd-title">Manage Products</h2>
            <div class="prd-subtitle">Manage and organize your store inventory</div>
        </div>
        <a href="add-product.php" class="btn-add">+ Add New Product</a>
    </div>

    <!-- Filter & Search -->
    <div class="prd-controls">

        <!-- Status Filter -->
        <div class="prd-controls-left">
            <form method="GET">
                <label for="status">Filter:</label>
                <select name="status" id="status" class="native-filter-select">
                    <option value="all"      <?= ($status_filter === 'all')      ? 'selected' : '' ?>>All</option>
                    <option value="active"       <?= ($status_filter === 'active')       ? 'selected' : '' ?>>Active</option>
                    <option value="inactive"     <?= ($status_filter === 'inactive')     ? 'selected' : '' ?>>Inactive</option>
                    <option value="coming_soon"  <?= ($status_filter === 'coming_soon')  ? 'selected' : '' ?>>Coming Soon</option>
                </select>
                <div class="filter-select-wrap" data-filter-select>
                    <button type="button" class="filter-display" data-filter-display>
                        <?= htmlspecialchars($statusFilterLabels[$status_filter] ?? 'All') ?>
                    </button>
                    <ul class="filter-options" data-filter-options>
                        <li><button type="button" class="filter-option <?= $status_filter === 'all' ? 'is-selected' : '' ?>" data-value="all">All</button></li>
                        <li><button type="button" class="filter-option <?= $status_filter === 'active' ? 'is-selected' : '' ?>" data-value="active">Active</button></li>
                        <li><button type="button" class="filter-option <?= $status_filter === 'inactive' ? 'is-selected' : '' ?>" data-value="inactive">Inactive</button></li>
                        <li><button type="button" class="filter-option <?= $status_filter === 'coming_soon' ? 'is-selected' : '' ?>" data-value="coming_soon">Coming Soon</button></li>
                    </ul>
                </div>
                <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <?php endif; ?>
                <input type="hidden" name="per_page" value="<?= (int) $per_page ?>">
            </form>

            <select id="prdPerPage" class="native-filter-select">
                <option value="20" <?= $per_page === 20 ? 'selected' : '' ?>>20 / page</option>
                <option value="50" <?= $per_page === 50 ? 'selected' : '' ?>>50 / page</option>
                <option value="100" <?= $per_page === 100 ? 'selected' : '' ?>>100 / page</option>
            </select>
            <div class="filter-select-wrap" data-per-page-select>
                <button type="button" class="filter-display" data-per-page-display><?= (int) $per_page ?> / page</button>
                <ul class="filter-options" data-per-page-options>
                    <li><button type="button" class="filter-option <?= $per_page === 20 ? 'is-selected' : '' ?>" data-value="20">20 / page</button></li>
                    <li><button type="button" class="filter-option <?= $per_page === 50 ? 'is-selected' : '' ?>" data-value="50">50 / page</button></li>
                    <li><button type="button" class="filter-option <?= $per_page === 100 ? 'is-selected' : '' ?>" data-value="100">100 / page</button></li>
                </ul>
            </div>

            <button type="button" class="btn-export" id="prdExportBtn">Export CSV</button>
        </div>

        <!-- Search -->
        <div class="prd-controls-right">
            <form method="GET" id="prdSearchForm">
                <input type="text" name="search" class="prd-search-input"
                       placeholder="Search products..."
                       value="<?= htmlspecialchars($search_query) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <input type="hidden" name="per_page" value="<?= (int) $per_page ?>">
                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>

    </div>

    <!-- Count -->
    <!-- Table -->
    <div class="prd-table-wrap">
        <table class="prd-table" id="prdTable">
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
            <tbody id="prdTbody">
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" style="padding:24px;text-align:center;color:#6b7280;">No products found.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $row): ?>
                <?php
                    $stock = (int)$row['stock_quantity'];
                    $stockClass = $stock > 10 ? 'stock-ok' : ($stock > 0 ? 'stock-low' : 'stock-out');
                    $stockLabel = $stock > 0 ? $stock : 'Out';
                ?>
                <tr>
                    <td data-label="Image">
                        <?php
                            $rawImg = trim((string) ($row['image_url'] ?? ''));
                            $defaultImg = url('public/assets/images/Phones_dukan_favicon.png');
                            if ($rawImg !== '') {
                                $imgSrc = function_exists('normalizeMediaUrl')
                                    ? normalizeMediaUrl($rawImg)
                                    : $rawImg;
                            } else {
                                $imgSrc = $defaultImg;
                            }
                            // Lightweight fallbacks only (no localhost-hardcoded /phonesdukan paths).
                            $candidates = array_values(array_unique(array_filter([
                                $imgSrc,
                                $rawImg !== '' ? $rawImg : null,
                                $defaultImg,
                            ])));
                            $imgCandidatesAttr = htmlspecialchars(json_encode($candidates), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="prd-imgbox">
                            <img class="prd-img prd-image"
                                 src="<?= htmlspecialchars($imgSrc); ?>"
                                 data-candidates="<?= $imgCandidatesAttr; ?>"
                                 data-candidate-index="0"
                                 alt="<?= htmlspecialchars($row['alt_text'] ?? 'Product Image') ?>">
                            <span class="prd-img-placeholder" aria-hidden="true">No image</span>
                        </div>
                    </td>
                    <td class="prd-name" data-label="Name"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td class="prd-price" data-label="Price">Rs. <?= number_format((float)($row['sale_price'] ?? $row['regular_price']), 0) ?></td>
                    <td data-label="Stock"><span class="stock-badge <?= $stockClass ?>"><?= $stockLabel ?></span></td>
                    <td data-label="Status">
                        <?php $productStatusVal = (int) ($row['product_status'] ?? 0); ?>
                        <span class="status-badge <?= getProductStatusCssClass($productStatusVal) ?>">
                            <?= htmlspecialchars(getProductStatusLabel($productStatusVal)) ?>
                        </span>
                    </td>
                    <td data-label="Date"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td data-label="Actions">
                        <div class="prd-actions">
                            <a href="edit-product.php?id=<?= $row['product_id'] ?>" class="prd-btn prd-btn-edit">Edit</a>
                            <a href="delete_product.php?id=<?= $row['product_id'] ?>"
                               class="prd-btn prd-btn-delete"
                               onclick="return confirm('Are you sure you want to delete this product and all associated data?')">Delete</a>
                            <?php
                                $viewPath = ltrim(buildProductPathFromRow([
                                    'brand_slug' => (string) ($row['brand_slug'] ?? ''),
                                    'category_slug' => (string) ($row['category_slug'] ?? ''),
                                    'subcategory_slug' => (string) ($row['subcategory_slug'] ?? ''),
                                    'product_slug' => (string) ($row['product_slug'] ?? ''),
                                ]), '/');
                            ?>
                            <a href="<?= htmlspecialchars(url($viewPath), ENT_QUOTES, 'UTF-8'); ?>" class="prd-btn prd-btn-view" target="_blank" rel="noopener">View</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <div class="prd-pagination">
        <div class="prd-page-info">
            Showing <?= $totalRows === 0 ? 0 : ($offset + 1) ?>–<?= min($offset + $per_page, $totalRows) ?>
            of <?= $totalRows ?> products
            (Page <?= $page ?> of <?= $totalPages ?>)
        </div>
        <div class="prd-page-links">
            <?php if ($page > 1): ?>
                <a class="prd-btn" href="<?= htmlspecialchars($buildManageUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
            <?php endif; ?>
            <?php
            $windowStart = max(1, $page - 2);
            $windowEnd = min($totalPages, $page + 2);
            for ($p = $windowStart; $p <= $windowEnd; $p++):
            ?>
                <a class="prd-btn <?= $p === $page ? 'prd-btn-edit' : '' ?>"
                   href="<?= htmlspecialchars($buildManageUrl(['page' => $p]), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="prd-btn" href="<?= htmlspecialchars($buildManageUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const perPageEl = document.getElementById('prdPerPage');
    const exportBtn = document.getElementById('prdExportBtn');
    const rows = Array.from(document.querySelectorAll('#prdTbody tr'));

    function goWithPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    document.querySelectorAll('[data-filter-select]').forEach(function (wrap) {
        const display = wrap.querySelector('[data-filter-display]');
        const options = Array.from(wrap.querySelectorAll('.filter-option'));
        const nativeSelect = document.getElementById('status');
        if (!display || !nativeSelect) return;

        function setValue(value, submit) {
            nativeSelect.value = value;
            const selected = options.find(function (opt) { return opt.dataset.value === value; });
            display.textContent = selected ? selected.textContent.trim() : value;
            options.forEach(function (opt) {
                opt.classList.toggle('is-selected', opt.dataset.value === value);
            });
            if (submit) {
                nativeSelect.form && nativeSelect.form.submit();
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
                setValue(this.dataset.value || 'all', true);
                wrap.classList.remove('is-open');
            });
        });

        setValue(nativeSelect.value || 'all', false);
    });

    document.querySelectorAll('[data-per-page-select]').forEach(function (wrap) {
        const display = wrap.querySelector('[data-per-page-display]');
        const options = Array.from(wrap.querySelectorAll('.filter-option'));
        if (!display || !perPageEl) return;

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.filter-select-wrap.is-open').forEach(function (openWrap) {
                if (openWrap !== wrap) openWrap.classList.remove('is-open');
            });
            wrap.classList.toggle('is-open');
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                goWithPerPage(this.dataset.value || '20');
            });
        });
    });

    if (perPageEl) {
        perPageEl.addEventListener('change', function () {
            goWithPerPage(this.value || '20');
        });
    }

    document.addEventListener('click', function () {
        document.querySelectorAll('.filter-select-wrap.is-open').forEach(function (openWrap) {
            openWrap.classList.remove('is-open');
        });
    });

    document.querySelectorAll('.prd-image').forEach(function (img) {
        if (!img.getAttribute('src')) {
            var box = img.closest('.prd-imgbox');
            if (box) box.classList.add('is-placeholder');
        }
        img.addEventListener('error', function () {
            var candidates = [];
            try { candidates = JSON.parse(this.dataset.candidates || '[]'); } catch (e) { candidates = []; }
            var idx = parseInt(this.dataset.candidateIndex || '0', 10) + 1;
            if (idx < candidates.length) {
                this.dataset.candidateIndex = String(idx);
                this.src = candidates[idx];
            } else {
                var box = this.closest('.prd-imgbox');
                if (box) box.classList.add('is-placeholder');
                this.onerror = null;
            }
        });
    });

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['Name', 'Price', 'Stock', 'Status', 'Date'];
            const lines = [headers.join(',')];
            rows.forEach(function (row) {
                const name = (row.querySelector('.prd-name') && row.querySelector('.prd-name').textContent || '').trim();
                if (!name) return;
                const price = (row.querySelector('.prd-price') && row.querySelector('.prd-price').textContent || '').trim();
                const stock = (row.querySelector('.stock-badge') && row.querySelector('.stock-badge').textContent || '').trim();
                const status = (row.querySelector('.status-badge') && row.querySelector('.status-badge').textContent || '').trim();
                const dateCell = row.querySelector('td[data-label="Date"]');
                const date = (dateCell && dateCell.textContent || '').trim();
                const values = [name, price, stock, status, date].map(function (v) {
                    return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
                });
                lines.push(values.join(','));
            });
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'manage-products-export.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        });
    }
});
</script>