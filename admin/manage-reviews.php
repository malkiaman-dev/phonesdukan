<?php
// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Include the AdminReviewController
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminReviewController.php';

// Initialize the controller
$controller = new AdminReviewController();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $valid_statuses = ['pending', 'approved', 'spam'];
    if ($review_id > 0 && in_array($new_status, $valid_statuses)) {
        if ($controller->updateStatus($review_id, $new_status)) {
            header("Location: manage-reviews.php?success=Status updated successfully");
        } else {
            header("Location: manage-reviews.php?error=Failed to update status");
        }
    } else {
        header("Location: manage-reviews.php?error=Invalid status or review ID");
    }
    exit();
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    
    if ($review_id > 0) {
        if ($controller->deleteReview($review_id)) {
            header("Location: manage-reviews.php?success=Review deleted successfully");
        } else {
            header("Location: manage-reviews.php?error=Failed to delete review");
        }
    } else {
        header("Location: manage-reviews.php?error=Invalid review ID");
    }
    exit();
}

// Fetch all reviews from the controller
$reviews = $controller->getAllReviews();

// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --yellow-hover: #eab308;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        body { background: var(--bg); color: var(--black); }

        .rev-wrap { max-width: 1320px; margin: 0 auto; padding: 20px; }

        .rev-header,
        .rev-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .rev-header {
            padding: 20px 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .rev-title { margin: 0; font-size: 1.8rem; letter-spacing: -0.02em; }
        .rev-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 0.92rem; }

        .rev-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 16px;
            margin-bottom: 14px;
            overflow: visible;
            position: relative;
            z-index: 5;
        }

        .rev-left, .rev-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .rev-left { justify-content: flex-start; }
        .rev-right { justify-content: flex-end; }

        .rev-input {
            width: 340px;
            max-width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 14px;
            outline: none;
            background: #fff;
            color: var(--black);
        }
        .rev-input:focus { border-color: var(--yellow); box-shadow: 0 0 0 3px rgba(250,204,21,0.18); }

        .rev-search-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .rev-btn {
            height: 44px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--black);
            background: var(--black);
            color: #fff !important;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none !important;
            transition: color .15s ease, transform .15s ease;
        }
        .rev-btn:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
        }

        .rev-btn-outline {
            border: 1px solid var(--border);
            background: #fff;
            color: var(--black) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .rev-btn-outline:hover {
            border-color: var(--yellow);
            background: var(--light-yellow);
            color: var(--black) !important;
            transform: translateY(-1px);
        }

        .rev-card { overflow: hidden; }
        .rev-card.rev-toolbar { overflow: visible; }
        .rev-table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { border: 0; padding: 14px; text-align: left; background: transparent; vertical-align: top; }
        thead th {
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            color: var(--black);
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        tbody td { border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        tbody tr:hover { background: var(--light-yellow); }

        .rev-content {
            max-width: 520px;
            color: var(--black);
            white-space: normal;
            word-break: break-word;
        }

        .rev-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--yellow);
            background: var(--light-yellow);
            color: var(--black) !important;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .rev-stars { white-space: nowrap; }
        .star-filled { color: var(--yellow) !important; background: transparent !important; margin: 0; }
        .star-empty { color: #d1d5db !important; background: transparent !important; margin: 0; }

        .rev-actions {
            display: grid;
            gap: 8px;
            min-width: 220px;
        }
        .rev-pagebar {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rev-page-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-left: auto;
        }

        .rev-page-btn {
            min-width: 38px;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--black);
            font-size: 0.84rem;
            font-weight: 800;
            cursor: pointer;
        }

        .rev-page-btn:hover {
            border-color: var(--yellow);
            background: var(--light-yellow);
        }

        .rev-page-btn.is-active {
            border-color: var(--yellow);
            background: var(--yellow);
            color: var(--black);
        }

        .rev-page-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .rev-page-meta {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 700;
        }
        .rev-status-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .rev-status-form .rev-select-wrap { grid-column: 1 / -1; }
        .rev-status-form .rev-btn {
            grid-column: 1 / -1;
            width: 100%;
            justify-content: center;
        }
        .rev-actions-bottom {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            align-items: stretch;
        }
        .rev-actions-bottom > * { margin: 0; }
        .rev-actions-bottom .rev-btn,
        .rev-actions-bottom .rev-btn-outline {
            width: 100%;
            justify-content: center;
        }
        .rev-delete-form {
            width: 100%;
            margin: 0;
        }
        .rev-delete-form .rev-btn { width: 100%; }

        .rev-native-select {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            overflow: hidden !important;
        }
        .rev-select-wrap { position: relative; min-width: 150px; }
        .rev-select-display {
            width: 100%;
            height: 44px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            color: var(--black);
            padding: 0 36px 0 12px;
            font-size: 0.86rem;
            font-weight: 800;
            text-align: left;
            cursor: pointer;
            position: relative;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .rev-select-display::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid var(--black);
            border-bottom: 2px solid var(--black);
            transform: translateY(-65%) rotate(45deg);
        }
        .rev-select-display:hover,
        .rev-select-wrap.is-open .rev-select-display {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }
        .rev-select-options {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            z-index: 9999;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(17,17,17,0.12);
            padding: 6px;
            display: none;
            max-height: 240px;
            overflow: auto;
        }
        .rev-select-options::-webkit-scrollbar { width: 8px; }
        .rev-select-options::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
        .rev-select-wrap.is-open .rev-select-options { display: block; }
        .rev-select-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            text-align: left;
            padding: 8px 10px;
            font-size: 0.86rem;
            font-weight: 800;
            color: var(--black);
            cursor: pointer;
        }
        .rev-select-option:hover { background: var(--light-yellow); }
        .rev-select-option.is-selected { background: var(--yellow); }

        .no-reviews { text-align: center; padding: 22px; color: var(--muted); font-weight: 700; }

        .rev-toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 4000;
            min-width: 260px;
            max-width: 380px;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 800;
            border: 1px solid var(--border);
            box-shadow: 0 16px 30px rgba(17, 17, 17, 0.15);
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }
        .rev-toast.is-show { opacity: 1; transform: translateY(0); }
        .rev-toast-success { background: #111111; color: #ffffff; border-color: #111111; }
        .rev-toast-error { background: var(--light-yellow); color: var(--black); border-color: var(--yellow); }

        .rev-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17,17,17,0.45);
            z-index: 4100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }
        .rev-modal-backdrop.is-open { display: flex; }
        .rev-modal {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(17,17,17,0.18);
            padding: 18px;
        }
        .rev-modal h3 { margin: 0; font-size: 1.1rem; }
        .rev-modal p { margin: 8px 0 0; color: var(--muted); font-weight: 600; }
        .rev-modal-actions { margin-top: 14px; display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .rev-actions { min-width: 0; }
            .rev-input { width: 100%; }
            .rev-search-group { width: 100%; }
        }
    </style>
    <div class="rev-wrap">
        <div class="rev-header">
            <div>
                <h2 class="rev-title">Manage Reviews</h2>
                <p class="rev-subtitle">Approve, flag spam, edit, or delete customer reviews.</p>
            </div>
        </div>

        <div class="rev-card rev-toolbar">
            <div class="rev-left">
                <div class="rev-select-wrap" data-rev-select data-rev-toolbar-status>
                    <select id="revStatusFilter" class="rev-native-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="spam">Spam</option>
                    </select>
                    <button type="button" class="rev-select-display" data-rev-display>All Status</button>
                    <div class="rev-select-options">
                        <button type="button" class="rev-select-option" data-value="">All Status</button>
                        <button type="button" class="rev-select-option" data-value="pending">Pending</button>
                        <button type="button" class="rev-select-option" data-value="approved">Approved</button>
                        <button type="button" class="rev-select-option" data-value="spam">Spam</button>
                    </div>
                </div>
                <div class="rev-select-wrap" data-rev-select data-rev-perpage>
                    <select id="revPerPage" class="rev-native-select">
                        <option value="20">20 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                    <button type="button" class="rev-select-display" data-rev-display>20 / page</button>
                    <div class="rev-select-options">
                        <button type="button" class="rev-select-option" data-value="20">20 / page</button>
                        <button type="button" class="rev-select-option" data-value="50">50 / page</button>
                        <button type="button" class="rev-select-option" data-value="100">100 / page</button>
                    </div>
                </div>
                <button type="button" class="rev-btn rev-btn-outline" id="revExportCsv">Export CSV</button>
            </div>
            <div class="rev-right">
                <div class="rev-search-group">
                    <input type="search" id="revSearch" class="rev-input" placeholder="Search by product, author, email or content...">
                    <button type="button" class="rev-btn" id="revSearchBtn">Search</button>
                </div>
            </div>
        </div>

        <div class="rev-card">
            <?php if (!empty($reviews) && is_array($reviews)): ?>
                <div class="rev-table-wrap">
                    <table id="revTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Author</th>
                                <th>Email</th>
                                <th>Content</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Guest</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <?php
                                    $status = (string)($review['status'] ?? 'pending');
                                    $rating = (int)($review['rating'] ?? 0);
                                    $content = (string)($review['content'] ?? '');
                                    $productId = (string)($review['product_id'] ?? '');
                                ?>
                                <tr data-status="<?= htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8'); ?>" data-search="<?= htmlspecialchars(strtolower(($review['product_name'] ?? '') . ' ' . ($review['author'] ?? '') . ' ' . ($review['email'] ?? '') . ' ' . $content), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><?php echo htmlspecialchars($review['id']); ?></td>
                                    <td><?php echo htmlspecialchars($productId !== '' ? $productId : '-'); ?></td>
                                    <td><?php echo htmlspecialchars($review['product_name'] ?? 'Unknown Product'); ?></td>
                                    <td><?php echo htmlspecialchars($review['author']); ?></td>
                                    <td><?php echo htmlspecialchars($review['email']); ?></td>
                                    <td>
                                        <div class="rev-content">
                                            <?php echo htmlspecialchars($content); ?>
                                        </div>
                                    </td>
                                    <td class="rev-stars">
                                        <?php
                                            $max_stars = 5;
                                            for ($i = 1; $i <= $max_stars; $i++) {
                                                echo $i <= $rating
                                                    ? '<span class="star-filled">★</span>'
                                                    : '<span class="star-empty">☆</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><span class="rev-pill"><?php echo htmlspecialchars($status); ?></span></td>
                                    <td><?php echo htmlspecialchars($review['created_at']); ?></td>
                                    <td><?php echo !empty($review['is_guest']) ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <div class="rev-actions">
                                            <form method="POST" action="manage-reviews.php" class="rev-status-form" data-status-form>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">

                                                <div class="rev-select-wrap" data-rev-select>
                                                    <select name="status" class="rev-native-select">
                                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                        <option value="spam" <?php echo $status === 'spam' ? 'selected' : ''; ?>>Spam</option>
                                                    </select>
                                                    <button type="button" class="rev-select-display" data-rev-display>Pending</button>
                                                    <div class="rev-select-options">
                                                        <button type="button" class="rev-select-option" data-value="pending">Pending</button>
                                                        <button type="button" class="rev-select-option" data-value="approved">Approved</button>
                                                        <button type="button" class="rev-select-option" data-value="spam">Spam</button>
                                                    </div>
                                                </div>

                                                <button type="submit" class="rev-btn">Update</button>
                                            </form>

                                            <div class="rev-actions-bottom">
                                                <a class="rev-btn rev-btn-outline" href="edit-review.php?action=edit&id=<?php echo $review['id']; ?>">Edit</a>
                                                <form id="delete-form-<?php echo $review['id']; ?>" method="POST" action="manage-reviews.php" class="rev-delete-form">
                                                    <input type="hidden" name="action" value="delete_review">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <button type="button" class="rev-btn" data-delete-review="<?php echo $review['id']; ?>">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-reviews">No reviews found.</p>
            <?php endif; ?>
        </div>
        <div class="rev-pagebar">
            <div class="rev-page-controls" id="revPagination"></div>
            <div class="rev-page-meta" id="revPageMeta">Page 1 of 1</div>
        </div>
    </div>

    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div id="revToast" class="rev-toast <?= isset($_GET['error']) ? 'rev-toast-error' : 'rev-toast-success' ?>">
            <?= htmlspecialchars(isset($_GET['error']) ? $_GET['error'] : $_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div class="rev-modal-backdrop" id="revModal">
        <div class="rev-modal" role="dialog" aria-modal="true" aria-labelledby="revModalTitle">
            <h3 id="revModalTitle">Delete review?</h3>
            <p>This will permanently remove the review. You can’t undo this action.</p>
            <div class="rev-modal-actions">
                <button type="button" class="rev-btn rev-btn-outline" id="revCancel">Cancel</button>
                <button type="button" class="rev-btn" id="revConfirm">Delete</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('revToast');
        if (toast) {
            requestAnimationFrame(() => toast.classList.add('is-show'));
            setTimeout(() => toast.classList.remove('is-show'), 3200);
        }

        const search = document.getElementById('revSearch');
        const searchBtn = document.getElementById('revSearchBtn');
        const table = document.getElementById('revTable');
        const statusFilterEl = document.getElementById('revStatusFilter');
        const perPageEl = document.getElementById('revPerPage');
        const exportBtn = document.getElementById('revExportCsv');
        const paginationEl = document.getElementById('revPagination');
        const pageMetaEl = document.getElementById('revPageMeta');
        let currentPage = 1;
        let visibleRows = [];

        if (search && searchBtn && table && statusFilterEl) {
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            function applySearch() {
                const q = (search.value || '').trim().toLowerCase();
                const statusVal = (statusFilterEl.value || '').toLowerCase();
                visibleRows = [];
                rows.forEach((tr) => {
                    const hay = (tr.dataset.search || '');
                    const rowStatus = (tr.dataset.status || '').toLowerCase();
                    const searchMatch = (q === '' || hay.includes(q));
                    const statusMatch = (statusVal === '' || rowStatus === statusVal);
                    const show = searchMatch && statusMatch;
                    tr.dataset._match = show ? '1' : '0';
                    if (show) visibleRows.push(tr);
                });
                currentPage = 1;
                applyPagination();
            }
            search.addEventListener('input', applySearch);
            searchBtn.addEventListener('click', applySearch);
            statusFilterEl.addEventListener('change', applySearch);
            // Initialize match flags
            rows.forEach((tr) => tr.dataset._match = '1');
            visibleRows = rows.slice();
            applySearch();
        }

        // Custom select (status)
        document.querySelectorAll('[data-rev-select]').forEach(function (wrap) {
            const nativeSelect = wrap.querySelector('select');
            const display = wrap.querySelector('[data-rev-display]');
            const options = Array.from(wrap.querySelectorAll('.rev-select-option'));
            if (!nativeSelect || !display || options.length === 0) return;

            function sync() {
                const selected = options.find(opt => opt.dataset.value === nativeSelect.value);
                display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Select');
                options.forEach(opt => opt.classList.toggle('is-selected', opt.dataset.value === nativeSelect.value));
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.rev-select-wrap.is-open').forEach(function (o) {
                    if (o !== wrap) o.classList.remove('is-open');
                });
                wrap.classList.toggle('is-open');
            });

            options.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    nativeSelect.value = this.dataset.value;
                    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    wrap.classList.remove('is-open');

                    if (wrap.hasAttribute('data-rev-perpage')) {
                        currentPage = 1;
                        applyPagination();
                    }
                });
            });

            sync();
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.rev-select-wrap.is-open').forEach(function (wrap) {
                wrap.classList.remove('is-open');
            });
        });

        function renderPagination(totalPages) {
            if (!paginationEl) return;
            paginationEl.innerHTML = '';
            if (totalPages <= 1) return;

            function makeBtn(label, page, disabled, active) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rev-page-btn' + (disabled ? ' is-disabled' : '') + (active ? ' is-active' : '');
                btn.textContent = label;
                btn.disabled = !!disabled;
                btn.addEventListener('click', function () {
                    if (disabled) return;
                    currentPage = page;
                    applyPagination();
                });
                return btn;
            }

            paginationEl.appendChild(makeBtn('Prev', Math.max(1, currentPage - 1), currentPage === 1, false));

            const windowSize = 2;
            const start = Math.max(1, currentPage - windowSize);
            const end = Math.min(totalPages, currentPage + windowSize);
            for (let p = start; p <= end; p++) {
                paginationEl.appendChild(makeBtn(String(p), p, false, p === currentPage));
            }

            paginationEl.appendChild(makeBtn('Next', Math.min(totalPages, currentPage + 1), currentPage === totalPages, false));
        }

        function applyPagination() {
            if (!table || !perPageEl) return;
            const allRows = Array.from(table.querySelectorAll('tbody tr'));
            const perPage = parseInt(perPageEl.value || '20', 10) || 20;

            // determine current matched rows
            const matched = allRows.filter((tr) => tr.dataset._match !== '0');
            const total = matched.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > totalPages) currentPage = totalPages;

            allRows.forEach((tr) => tr.style.display = 'none');
            const start = (currentPage - 1) * perPage;
            matched.slice(start, start + perPage).forEach((tr) => tr.style.display = '');

            if (pageMetaEl) pageMetaEl.textContent = 'Page ' + currentPage + ' of ' + totalPages;
            renderPagination(totalPages);
        }

        // Initial pagination
        applyPagination();

        // Export CSV (exports filtered rows, not just current page)
        if (exportBtn && table) {
            exportBtn.addEventListener('click', function () {
                const allRows = Array.from(table.querySelectorAll('tbody tr'));
                const matched = allRows.filter((tr) => tr.dataset._match !== '0');
                const headers = Array.from(table.querySelectorAll('thead th')).map((th) => (th.textContent || '').trim());
                const lines = [headers.join(',')];
                matched.forEach((tr) => {
                    const cols = Array.from(tr.querySelectorAll('td')).map((td) => {
                        const v = (td.textContent || '').trim().replace(/\s+/g, ' ');
                        return '"' + v.replace(/"/g, '""') + '"';
                    });
                    lines.push(cols.join(','));
                });
                const csv = lines.join('\n');
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'reviews.csv';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            });
        }

        // Delete modal
        const modal = document.getElementById('revModal');
        const cancel = document.getElementById('revCancel');
        const confirmBtn = document.getElementById('revConfirm');
        let pendingForm = null;

        function openModal(form) {
            pendingForm = form;
            modal.classList.add('is-open');
        }
        function closeModal() {
            modal.classList.remove('is-open');
            pendingForm = null;
        }

        document.querySelectorAll('[data-delete-review]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-delete-review');
                const form = document.getElementById('delete-form-' + id);
                if (form) openModal(form);
            });
        });

        if (cancel) cancel.addEventListener('click', closeModal);
        if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        if (confirmBtn) confirmBtn.addEventListener('click', () => { if (pendingForm) pendingForm.submit(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) closeModal(); });
    });
    </script>