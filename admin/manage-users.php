<?php
ob_start();
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once dirname(__DIR__, 1) . '/database/db.php';

$users = [];
$loadError = null;
$toast = $_SESSION['manage_users_toast'] ?? null;
unset($_SESSION['manage_users_toast']);

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string)($_POST['action'] ?? ''));
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            if ($action === 'lock') {
                $stmt = $db->prepare('UPDATE users SET is_locked = 1 WHERE user_id = :id');
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['manage_users_toast'] = ['type' => 'success', 'text' => 'User locked successfully.'];
            } elseif ($action === 'unlock') {
                $stmt = $db->prepare('UPDATE users SET is_locked = 0, failed_attempts = 0 WHERE user_id = :id');
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['manage_users_toast'] = ['type' => 'success', 'text' => 'User unlocked successfully.'];
            } elseif ($action === 'delete') {
                $stmt = $db->prepare('DELETE FROM users WHERE user_id = :id');
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['manage_users_toast'] = ['type' => 'success', 'text' => 'User deleted successfully.'];
            } else {
                $_SESSION['manage_users_toast'] = ['type' => 'error', 'text' => 'Invalid action.'];
            }
        } else {
            $_SESSION['manage_users_toast'] = ['type' => 'error', 'text' => 'Invalid user selected.'];
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    }

    $stmt = $db->query('SELECT user_id, full_name, email, phone, user_role, is_verified, is_locked, failed_attempts, created_at FROM users ORDER BY user_id DESC');
    $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>

<style>
    :root {
        --mu-black: #111111;
        --mu-yellow: #facc15;
        --mu-white: #ffffff;
        --mu-muted: #6b7280;
        --mu-border: #e5e7eb;
        --mu-bg: #f8fafc;
        --mu-soft-yellow: #fffbeb;
    }

    .mu-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px;
        background: var(--mu-bg);
    }

    .mu-header {
        background: var(--mu-white);
        color: var(--mu-black);
        border: 1px solid var(--mu-border);
        border-radius: 18px;
        padding: 22px 24px;
        margin-bottom: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .mu-title {
        margin: 0;
        font-size: clamp(1.5rem, 2vw, 1.75rem);
        font-weight: 600;
        line-height: 1.25;
        color: var(--mu-black) !important;
        letter-spacing: -0.02em;
    }

    .mu-subtitle {
        margin: 6px 0 0;
        color: var(--mu-muted);
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
    }

    .mu-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: nowrap;
        margin-bottom: 14px;
    }

    .mu-toolbar-left,
    .mu-toolbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .mu-toolbar-right {
        margin-left: auto;
    }

    .mu-search-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .mu-search {
        width: 285px;
        height: 46px;
        border: 1px solid var(--mu-border);
        border-radius: 12px;
        padding: 0 14px;
        outline: none;
        background: #fff;
        color: var(--mu-black);
    }

    .mu-search:focus {
        border-color: var(--mu-yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .mu-btn {
        height: 44px;
        border: 1px solid var(--mu-border);
        border-radius: 12px;
        background: #fff;
        color: var(--mu-black) !important;
        padding: 0 14px;
        font-size: 0.87rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none !important;
        transition: border-color .2s ease, box-shadow .2s ease, color .15s ease, transform .15s ease;
    }

    .mu-btn:hover {
        border-color: var(--mu-yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        color: var(--mu-black) !important;
    }

    .mu-btn-search {
        height: 46px;
        border: 1px solid var(--mu-black);
        border-radius: 12px;
        background: var(--mu-black);
        color: #fff !important;
        padding: 0 16px;
        font-size: 0.87rem;
        font-weight: 800;
        cursor: pointer;
        transition: color .15s ease, transform .15s ease;
    }

    .mu-btn-search:hover {
        color: var(--mu-yellow) !important;
        transform: translateY(-1px);
    }

    .mu-native-select {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .mu-select-wrap {
        position: relative;
        min-width: 150px;
    }

    .mu-select-display {
        width: 100%;
        height: 44px;
        border: 1px solid var(--mu-border);
        border-radius: 12px;
        background: #fff;
        color: var(--mu-black);
        font-size: 0.87rem;
        font-weight: 700;
        text-align: left;
        padding: 0 34px 0 12px;
        position: relative;
        cursor: pointer;
    }

    .mu-select-display::after {
        content: "";
        position: absolute;
        right: 12px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-right: 2px solid var(--mu-black);
        border-bottom: 2px solid var(--mu-black);
        transform: translateY(-65%) rotate(45deg);
    }

    .mu-select-wrap.is-open .mu-select-display,
    .mu-select-display:hover {
        border-color: var(--mu-yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }

    .mu-select-options {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        background: #fff;
        border: 1px solid var(--mu-border);
        border-radius: 12px;
        box-shadow: 0 14px 28px rgba(17, 17, 17, 0.12);
        padding: 6px;
        display: none;
        z-index: 110;
    }

    .mu-select-wrap.is-open .mu-select-options {
        display: block;
    }

    .mu-select-option {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--mu-black);
        text-align: left;
        padding: 8px 10px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
    }

    .mu-select-option:hover {
        background: var(--mu-soft-yellow);
    }

    .mu-select-option.is-selected {
        background: var(--mu-yellow);
    }

    .mu-card {
        background: var(--mu-white);
        border: 1px solid var(--mu-border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        overflow: hidden;
    }

    .mu-table-wrap {
        overflow-x: auto;
    }

    .mu-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .mu-table thead th {
        background: #f9fafb;
        color: var(--mu-black);
        text-align: left;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 14px;
        border-bottom: 1px solid var(--mu-border);
        white-space: nowrap;
    }

    .mu-table tbody td {
        padding: 14px;
        border-bottom: 1px solid var(--mu-border);
        color: var(--mu-black);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .mu-table tbody tr:hover {
        background: var(--mu-soft-yellow);
    }

    .mu-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid var(--mu-yellow);
        background: var(--mu-soft-yellow);
        color: var(--mu-black) !important;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .mu-muted {
        color: var(--mu-muted);
    }

    .mu-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .mu-form-inline {
        margin: 0;
    }

    .mu-action-btn {
        min-width: 78px;
        height: 34px;
        border: 1px solid var(--mu-border);
        border-radius: 9px;
        background: #fff;
        color: var(--mu-black);
        font-size: 0.78rem;
        font-weight: 800;
        cursor: pointer;
        padding: 0 10px;
        transition: border-color .2s ease, color .15s ease;
    }

    .mu-action-btn:hover {
        border-color: var(--mu-yellow);
        color: #a16207;
    }

    .mu-action-btn-danger {
        border-color: #fcd34d;
        background: #fffbeb;
    }

    .mu-empty {
        padding: 22px;
        color: var(--mu-muted);
        font-weight: 600;
    }

    .mu-pagination {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .mu-page-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .mu-page-btn {
        min-width: 38px;
        height: 38px;
        border: 1px solid var(--mu-border);
        border-radius: 10px;
        background: #fff;
        color: var(--mu-black);
        font-size: 0.84rem;
        font-weight: 800;
        cursor: pointer;
    }

    .mu-page-btn:hover {
        border-color: var(--mu-yellow);
        background: var(--mu-soft-yellow);
    }

    .mu-page-btn.is-active {
        border-color: var(--mu-yellow);
        background: var(--mu-yellow);
    }

    .mu-page-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .mu-page-meta {
        color: var(--mu-muted);
        font-size: 0.85rem;
        font-weight: 700;
    }

    .mu-error {
        margin-top: 12px;
        border: 1px solid var(--mu-yellow);
        background: var(--mu-soft-yellow);
        color: var(--mu-black);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .mu-toast {
        position: fixed;
        right: 20px;
        bottom: 18px;
        min-width: 220px;
        max-width: 420px;
        background: #111;
        color: #fff;
        border: 1px solid #2b2b2f;
        border-left: 4px solid var(--mu-yellow);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: .88rem;
        font-weight: 700;
        z-index: 9999;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity .2s ease, transform .2s ease;
        pointer-events: none;
    }

    .mu-toast.is-show {
        opacity: 1;
        transform: translateY(0);
    }

    .mu-toast-error {
        border-left-color: #f59e0b;
    }

    @media (max-width: 900px) {
        .mu-toolbar {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .mu-toolbar-left,
        .mu-toolbar-right {
            flex-wrap: wrap;
        }

        .mu-search {
            width: 100%;
        }

        .mu-search-group {
            width: 100%;
        }

        .mu-toolbar-right {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

<div class="mu-wrap">
    <section class="mu-header">
        <h1 class="mu-title">Manage Users</h1>
        <p class="mu-subtitle">View user accounts, roles, verification status, and signup details.</p>
    </section>

    <div class="mu-toolbar">
        <div class="mu-toolbar-left">
            <div class="mu-select-wrap" data-mu-select>
                <select id="muRoleFilter" class="mu-native-select">
                    <option value="">All Roles</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                    <option value="undefined">Undefined</option>
                </select>
                <button type="button" class="mu-select-display" data-mu-display>All Roles</button>
                <div class="mu-select-options">
                    <button type="button" class="mu-select-option" data-value="">All Roles</button>
                    <button type="button" class="mu-select-option" data-value="user">User</button>
                    <button type="button" class="mu-select-option" data-value="admin">Admin</button>
                    <button type="button" class="mu-select-option" data-value="superadmin">Superadmin</button>
                    <button type="button" class="mu-select-option" data-value="undefined">Undefined</button>
                </div>
            </div>
            <div class="mu-select-wrap" data-mu-select>
                <select id="muStatusFilter" class="mu-native-select">
                    <option value="">All Status</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                </select>
                <button type="button" class="mu-select-display" data-mu-display>All Status</button>
                <div class="mu-select-options">
                    <button type="button" class="mu-select-option" data-value="">All Status</button>
                    <button type="button" class="mu-select-option" data-value="verified">Verified</button>
                    <button type="button" class="mu-select-option" data-value="unverified">Unverified</button>
                </div>
            </div>
            <div class="mu-select-wrap" data-mu-select>
                <select id="muLockFilter" class="mu-native-select">
                    <option value="">All Lock States</option>
                    <option value="locked">Locked</option>
                    <option value="unlocked">Unlocked</option>
                </select>
                <button type="button" class="mu-select-display" data-mu-display>All Lock States</button>
                <div class="mu-select-options">
                    <button type="button" class="mu-select-option" data-value="">All Lock States</button>
                    <button type="button" class="mu-select-option" data-value="locked">Locked</button>
                    <button type="button" class="mu-select-option" data-value="unlocked">Unlocked</button>
                </div>
            </div>
            <div class="mu-select-wrap" data-mu-select>
                <select id="muPerPageFilter" class="mu-native-select">
                    <option value="20">20 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
                <button type="button" class="mu-select-display" data-mu-display>20 / page</button>
                <div class="mu-select-options">
                    <button type="button" class="mu-select-option" data-value="20">20 / page</button>
                    <button type="button" class="mu-select-option" data-value="50">50 / page</button>
                    <button type="button" class="mu-select-option" data-value="100">100 / page</button>
                </div>
            </div>
            <button type="button" id="muExportCsv" class="mu-btn">Export CSV</button>
        </div>
        <div class="mu-toolbar-right">
            <div class="mu-search-group">
                <input id="muSearch" class="mu-search" type="search" placeholder="Search by name, email, phone or role...">
                <button type="button" id="muSearchBtn" class="mu-btn-search">Search</button>
            </div>
        </div>
    </div>

    <section class="mu-card">
        <div class="mu-table-wrap">
            <table class="mu-table" id="muTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th>Locked</th>
                        <th>Failed Attempts</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <?php $role = strtolower(trim((string)($user['user_role'] ?? ''))); $role = $role !== '' ? $role : 'undefined'; ?>
                            <?php $isVerified = ((string)($user['is_verified'] ?? '0') === '1'); ?>
                            <?php $isLocked = ((string)($user['is_locked'] ?? '0') === '1'); ?>
                            <tr data-user-row="1" data-role="<?php echo htmlspecialchars($role); ?>" data-status="<?php echo $isVerified ? 'verified' : 'unverified'; ?>" data-lock="<?php echo $isLocked ? 'locked' : 'unlocked'; ?>">
                                <td><?php echo htmlspecialchars((string)($user['user_id'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars((string)($user['full_name'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars((string)($user['email'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars((string)($user['phone'] ?? 'N/A')); ?></td>
                                <td><span class="mu-pill"><?php echo htmlspecialchars($role === 'undefined' ? 'Undefined' : $role); ?></span></td>
                                <td><span class="mu-pill"><?php echo $isVerified ? 'Verified' : 'Unverified'; ?></span></td>
                                <td>
                                    <span class="mu-pill"><?php echo $isLocked ? 'Locked' : 'Unlocked'; ?></span>
                                </td>
                                <td><?php echo (int)($user['failed_attempts'] ?? 0); ?></td>
                                <td class="mu-muted"><?php echo htmlspecialchars((string)($user['created_at'] ?? '-')); ?></td>
                                <td>
                                    <div class="mu-actions">
                                        <?php if ($isLocked): ?>
                                            <form method="POST" class="mu-form-inline">
                                                <input type="hidden" name="action" value="unlock">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                                <button type="submit" class="mu-action-btn">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="mu-form-inline">
                                                <input type="hidden" name="action" value="lock">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                                <button type="submit" class="mu-action-btn">Lock</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="mu-form-inline" onsubmit="return confirm('Delete this user account?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                            <button type="submit" class="mu-action-btn mu-action-btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="mu-empty">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="mu-pagination">
        <div class="mu-page-controls" id="muPagination"></div>
        <div class="mu-page-meta" id="muPageMeta">Page 1 of 1</div>
    </div>

    <?php if ($loadError !== null): ?>
        <div class="mu-error">Could not load users: <?php echo htmlspecialchars($loadError); ?></div>
    <?php endif; ?>
</div>

<?php if (is_array($toast) && isset($toast['text'])): ?>
    <div id="muToast" class="mu-toast <?php echo (($toast['type'] ?? '') === 'error') ? 'mu-toast-error' : ''; ?>">
        <?php echo htmlspecialchars((string)$toast['text']); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('muSearch');
    const searchBtn = document.getElementById('muSearchBtn');
    const table = document.getElementById('muTable');
    const roleFilter = document.getElementById('muRoleFilter');
    const statusFilter = document.getElementById('muStatusFilter');
    const lockFilter = document.getElementById('muLockFilter');
    const perPageFilter = document.getElementById('muPerPageFilter');
    const exportBtn = document.getElementById('muExportCsv');
    const pagination = document.getElementById('muPagination');
    const pageMeta = document.getElementById('muPageMeta');
    if (!search || !searchBtn || !table || !roleFilter || !statusFilter || !lockFilter || !perPageFilter || !exportBtn || !pagination || !pageMeta) return;

    const rows = Array.from(table.querySelectorAll('tbody tr[data-user-row="1"]'));
    let filteredRows = rows.slice();
    let currentPage = 1;

    document.querySelectorAll('[data-mu-select]').forEach((wrap) => {
        const nativeSelect = wrap.querySelector('select');
        const display = wrap.querySelector('[data-mu-display]');
        const options = Array.from(wrap.querySelectorAll('.mu-select-option'));
        if (!nativeSelect || !display || options.length === 0) return;

        function syncDisplay() {
            const selected = options.find((opt) => opt.dataset.value === nativeSelect.value);
            display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex] ? nativeSelect.options[nativeSelect.selectedIndex].text : 'Select');
            options.forEach((opt) => {
                opt.classList.toggle('is-selected', opt.dataset.value === nativeSelect.value);
            });
        }

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.mu-select-wrap.is-open').forEach((item) => {
                if (item !== wrap) item.classList.remove('is-open');
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
        document.querySelectorAll('.mu-select-wrap.is-open').forEach((wrap) => wrap.classList.remove('is-open'));
    });

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const role = roleFilter.value.toLowerCase();
        const status = statusFilter.value.toLowerCase();
        const lockState = lockFilter.value.toLowerCase();

        filteredRows = [];
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const rowRole = (row.dataset.role || '').toLowerCase();
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const rowLock = (row.dataset.lock || '').toLowerCase();
            const matchesSearch = q === '' || text.includes(q);
            const matchesRole = role === '' || rowRole === role;
            const matchesStatus = status === '' || rowStatus === status;
            const matchesLock = lockState === '' || rowLock === lockState;
            if (matchesSearch && matchesRole && matchesStatus && matchesLock) {
                filteredRows.push(row);
            }
        });
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';

        const prev = document.createElement('button');
        prev.className = 'mu-page-btn';
        prev.textContent = 'Prev';
        prev.disabled = currentPage === 1;
        prev.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                render();
            }
        });
        pagination.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'mu-page-btn' + (i === currentPage ? ' is-active' : '');
            btn.textContent = String(i);
            btn.addEventListener('click', function () {
                currentPage = i;
                render();
            });
            pagination.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'mu-page-btn';
        next.textContent = 'Next';
        next.disabled = currentPage >= totalPages;
        next.addEventListener('click', function () {
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
        const totalItems = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        rows.forEach((row) => { row.style.display = 'none'; });
        const start = (currentPage - 1) * perPage;
        filteredRows.slice(start, start + perPage).forEach((row) => {
            row.style.display = '';
        });

        pageMeta.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        renderPagination(totalPages);
    }

    function exportCsv() {
        applyFilters();
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Role', 'Verified', 'Locked', 'Failed Attempts', 'Joined'];
        const lines = [headers.join(',')];

        filteredRows.forEach((row) => {
            const cols = Array.from(row.querySelectorAll('td')).slice(0, 9).map((td) => {
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
        a.download = 'users.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    search.addEventListener('input', function () { currentPage = 1; render(); });
    searchBtn.addEventListener('click', function () { currentPage = 1; render(); });
    roleFilter.addEventListener('change', function () { currentPage = 1; render(); });
    statusFilter.addEventListener('change', function () { currentPage = 1; render(); });
    lockFilter.addEventListener('change', function () { currentPage = 1; render(); });
    perPageFilter.addEventListener('change', function () { currentPage = 1; render(); });
    exportBtn.addEventListener('click', exportCsv);

    render();

    const toast = document.getElementById('muToast');
    if (toast) {
        requestAnimationFrame(function () {
            toast.classList.add('is-show');
        });
        setTimeout(function () {
            toast.classList.remove('is-show');
        }, 3200);
    }
});
</script>
