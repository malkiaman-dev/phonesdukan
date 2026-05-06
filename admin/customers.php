<?php
ob_start();
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once dirname(__DIR__, 1) . '/database/db.php';

$customers = [];
$loadError = null;

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("SELECT * FROM users ORDER BY user_id DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';
?>

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

    .cus-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px;
        background: var(--bg);
    }

    .cus-header,
    .cus-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
    }

    .cus-header {
        margin-bottom: 18px;
        padding: 20px 24px;
    }

    .cus-title {
        margin: 0;
        font-size: 1.8rem;
        color: var(--black);
        letter-spacing: -0.02em;
    }

    .cus-subtitle {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 0.92rem;
    }

    .cus-toolbar {
        margin-bottom: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        align-items: center;
    }

    .cus-toolbar-left,
    .cus-toolbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cus-toolbar-right {
        justify-content: flex-start;
    }

    .cus-toolbar-left {
        flex-wrap: nowrap;
    }

    .cus-search {
        width: 320px;
        height: 48px;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 0 14px;
        background: #fff;
        color: var(--black);
        outline: none;
    }

    .cus-search-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .cus-search:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .cus-native-select {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
        overflow: hidden !important;
    }

    .cus-select-wrap {
        position: relative;
        min-width: 145px;
    }

    .cus-select-display {
        width: 100%;
        height: 44px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--black);
        padding: 0 36px 0 12px;
        font-size: 0.88rem;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
        position: relative;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .cus-select-display::after {
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

    .cus-select-display:hover,
    .cus-select-wrap.is-open .cus-select-display {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .cus-select-options {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 100;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 14px 28px rgba(17, 17, 17, 0.12);
        padding: 6px;
        display: none;
    }

    .cus-select-wrap.is-open .cus-select-options {
        display: block;
    }

    .cus-select-option {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--black);
        text-align: left;
        padding: 8px 10px;
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
    }

    .cus-select-option:hover {
        background: var(--light-yellow);
    }

    .cus-select-option.is-selected {
        background: var(--yellow);
    }

    .cus-btn {
        height: 44px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--black) !important;
        font-size: 0.88rem;
        font-weight: 700;
        padding: 0 14px;
        cursor: pointer;
        text-decoration: none !important;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease, color .15s ease;
    }

    .cus-btn-search {
        height: 48px;
        padding: 0 14px;
        font-size: 0.88rem;
        font-weight: 800;
        border-radius: 12px;
        border: 1px solid var(--black);
        background: var(--black);
        color: #fff !important;
        transition: color .15s ease;
    }

    .cus-btn-search:hover {
        color: var(--yellow) !important;
        border-color: var(--black) !important;
        background: var(--black) !important;
        transform: translateY(-1px);
    }

    .cus-btn:hover {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        background: #fff;
        color: var(--black) !important;
    }

    .cus-card {
        overflow: hidden;
    }

    .cus-table-wrap {
        overflow-x: auto;
    }

    .cus-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .cus-table thead th {
        background: #f9fafb;
        color: var(--black);
        text-align: left;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 14px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .cus-table tbody td {
        padding: 14px;
        border-bottom: 1px solid var(--border);
        color: var(--black);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .cus-table tbody tr:hover {
        background: var(--light-yellow);
    }

    .cus-pill {
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
        font-weight: 700;
        background-color: var(--light-yellow) !important;
    }

    .cus-pill * {
        color: var(--black) !important;
        background: transparent !important;
    }

    .cus-muted {
        color: var(--muted);
    }

    .cus-empty {
        padding: 22px;
        color: var(--muted);
        font-weight: 600;
    }

    .cus-error {
        margin-top: 12px;
        border: 1px solid var(--yellow);
        background: var(--light-yellow);
        color: var(--black);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .cus-pagination {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cus-page-controls {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .cus-page-btn {
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

    .cus-page-btn:hover {
        border-color: var(--yellow);
        background: var(--light-yellow);
    }

    .cus-page-btn.is-active {
        border-color: var(--yellow);
        background: var(--yellow);
        color: var(--black);
    }

    .cus-page-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .cus-page-meta {
        color: var(--muted);
        font-size: 0.85rem;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .cus-toolbar {
            grid-template-columns: 1fr;
        }

        .cus-toolbar-left {
            flex-wrap: wrap;
        }

        .cus-toolbar-right {
            justify-content: flex-start;
        }

        .cus-search {
            width: 100%;
        }

        .cus-search-group {
            width: 100%;
        }

        .cus-btn-search {
            width: 120px;
        }
    }

    .cus-role-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
</style>

<div class="cus-wrap">
    <section class="cus-header">
        <h1 class="cus-title">Customers</h1>
        <p class="cus-subtitle">View and manage registered customer accounts.</p>
    </section>

    <div class="cus-toolbar">
        <div class="cus-toolbar-left">
            <div class="cus-role-stack">
                <div class="cus-select-wrap" data-cus-select>
                <select id="roleFilter" class="cus-native-select">
                    <option value="">All Roles</option>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                    <option value="undefined">Undefined</option>
                </select>
                <button type="button" class="cus-select-display" data-cus-display>All Roles</button>
                <div class="cus-select-options">
                    <button type="button" class="cus-select-option" data-value="">All Roles</button>
                    <button type="button" class="cus-select-option" data-value="customer">Customer</button>
                    <button type="button" class="cus-select-option" data-value="admin">Admin</button>
                    <button type="button" class="cus-select-option" data-value="superadmin">Superadmin</button>
                    <button type="button" class="cus-select-option" data-value="undefined">Undefined</button>
                </div>
                </div>
            </div>
            <div class="cus-select-wrap" data-cus-select>
                <select id="statusFilter" class="cus-native-select">
                    <option value="">All Status</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                </select>
                <button type="button" class="cus-select-display" data-cus-display>All Status</button>
                <div class="cus-select-options">
                    <button type="button" class="cus-select-option" data-value="">All Status</button>
                    <button type="button" class="cus-select-option" data-value="verified">Verified</button>
                    <button type="button" class="cus-select-option" data-value="unverified">Unverified</button>
                </div>
            </div>
            <div class="cus-select-wrap" data-cus-select>
                <select id="perPageFilter" class="cus-native-select">
                    <option value="20">20 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
                <button type="button" class="cus-select-display" data-cus-display>20 / page</button>
                <div class="cus-select-options">
                    <button type="button" class="cus-select-option" data-value="20">20 / page</button>
                    <button type="button" class="cus-select-option" data-value="50">50 / page</button>
                    <button type="button" class="cus-select-option" data-value="100">100 / page</button>
                </div>
            </div>
            <button type="button" id="exportCustomersCsv" class="cus-btn">Export CSV</button>
        </div>
        <div class="cus-toolbar-right">
            <div class="cus-search-group">
                <input type="search" id="customerSearch" class="cus-search" placeholder="Search by name, email, phone or role...">
                <button type="button" id="customerSearchBtn" class="cus-btn-search">Search</button>
            </div>
        </div>
    </div>

    <section class="cus-card">
        <div class="cus-table-wrap">
            <table class="cus-table" id="customersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $user): ?>
                            <?php $roleValue = strtolower(trim((string) ($user['user_role'] ?? ''))); $roleValue = $roleValue !== '' ? $roleValue : 'undefined'; ?>
                            <tr data-role="<?= htmlspecialchars($roleValue) ?>" data-status="<?= ((string) ($user['is_verified'] ?? '0') === '1') ? 'verified' : 'unverified' ?>">
                                <td><?= htmlspecialchars((string) ($user['user_id'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($user['full_name'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string) ($user['email'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string) ($user['phone'] ?? 'N/A')) ?></td>
                                <td>
                                    <span class="cus-pill"><?= htmlspecialchars($roleValue !== 'undefined' ? $roleValue : 'Undefined') ?></span>
                                </td>
                                <td>
                                    <?php $verified = (string) ($user['is_verified'] ?? '0') === '1'; ?>
                                    <span class="cus-pill"><?= $verified ? 'Verified' : 'Unverified' ?></span>
                                </td>
                                <td class="cus-muted">
                                    <?= htmlspecialchars((string) ($user['created_at'] ?? '-')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="cus-empty">No customers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <div class="cus-pagination">
        <div class="cus-page-controls" id="customerPagination"></div>
        <div class="cus-page-meta" id="customerPageMeta">Page 1 of 1</div>
    </div>

    <?php if ($loadError !== null): ?>
        <div class="cus-error">Could not load customers: <?= htmlspecialchars($loadError) ?></div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('customerSearch');
    const table = document.getElementById('customersTable');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const perPageFilter = document.getElementById('perPageFilter');
    const exportBtn = document.getElementById('exportCustomersCsv');
    const searchBtn = document.getElementById('customerSearchBtn');
    const pagination = document.getElementById('customerPagination');
    const pageMeta = document.getElementById('customerPageMeta');
    if (!search || !table || !roleFilter || !statusFilter || !perPageFilter || !exportBtn || !searchBtn || !pagination || !pageMeta) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    let filteredRows = rows.slice();
    let currentPage = 1;

    document.querySelectorAll('[data-cus-select]').forEach((wrap) => {
        const nativeSelect = wrap.querySelector('select');
        const display = wrap.querySelector('[data-cus-display]');
        const options = Array.from(wrap.querySelectorAll('.cus-select-option'));
        if (!nativeSelect || !display || options.length === 0) return;

        function syncDisplay() {
            const selected = options.find((opt) => opt.dataset.value === nativeSelect.value);
            display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Select');
            options.forEach((opt) => {
                opt.classList.toggle('is-selected', opt.dataset.value === nativeSelect.value);
            });
        }

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.cus-select-wrap.is-open').forEach((other) => {
                if (other !== wrap) other.classList.remove('is-open');
            });
            wrap.classList.toggle('is-open');
        });

        options.forEach((opt) => {
            opt.addEventListener('click', function () {
                nativeSelect.value = this.dataset.value;
                nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                syncDisplay();
                wrap.classList.remove('is-open');
            });
        });

        syncDisplay();
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.cus-select-wrap.is-open').forEach((wrap) => {
            wrap.classList.remove('is-open');
        });
    });

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const role = roleFilter.value.toLowerCase();
        const status = statusFilter.value.toLowerCase();

        filteredRows = [];
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const rowRole = (row.dataset.role || '').toLowerCase();
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const searchMatch = q === '' || text.includes(q);
            const roleMatch = role === '' || rowRole === role;
            const statusMatch = status === '' || rowStatus === status;
            if (searchMatch && roleMatch && statusMatch) {
                filteredRows.push(row);
            }
        });
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';
        const prev = document.createElement('button');
        prev.className = 'cus-page-btn';
        prev.textContent = 'Prev';
        prev.disabled = currentPage === 1;
        prev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                render();
            }
        });
        pagination.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'cus-page-btn' + (i === currentPage ? ' is-active' : '');
            btn.textContent = String(i);
            btn.addEventListener('click', () => {
                currentPage = i;
                render();
            });
            pagination.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'cus-page-btn';
        next.textContent = 'Next';
        next.disabled = currentPage >= totalPages;
        next.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                render();
            }
        });
        pagination.appendChild(next);
    }

    function render() {
        applyFilters();
        const perPage = parseInt(perPageFilter.value, 10) || 20;
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        rows.forEach((row) => { row.style.display = 'none'; });
        const start = (currentPage - 1) * perPage;
        const pageItems = filteredRows.slice(start, start + perPage);
        pageItems.forEach((row) => { row.style.display = ''; });

        pageMeta.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        renderPagination(totalPages);
    }

    function exportCsv() {
        applyFilters();
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Created'];
        const lines = [headers.join(',')];

        filteredRows.forEach((row) => {
            const cols = Array.from(row.querySelectorAll('td')).map((td) => {
                const value = (td.textContent || '').trim().replace(/\s+/g, ' ');
                return '"' + value.replace(/"/g, '""') + '"';
            });
            lines.push(cols.join(','));
        });

        const csv = lines.join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'customers.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    search.addEventListener('input', () => { currentPage = 1; render(); });
    searchBtn.addEventListener('click', () => { currentPage = 1; render(); });
    roleFilter.addEventListener('change', () => { currentPage = 1; render(); });
    statusFilter.addEventListener('change', () => { currentPage = 1; render(); });
    perPageFilter.addEventListener('change', () => { currentPage = 1; render(); });
    exportBtn.addEventListener('click', exportCsv);
    render();
});
</script>
