<?php
ob_start();

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

// Ensure profile_photo column exists
try { $conn->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

$stmt = $conn->prepare('SELECT full_name, email, phone, profile_photo FROM users WHERE user_id = :user_id');
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name    = isset($profile['full_name'])    ? htmlspecialchars($profile['full_name'])    : 'User';
$user_email   = isset($profile['email'])        ? htmlspecialchars($profile['email'])        : '';
$user_phone   = isset($profile['phone'])        ? htmlspecialchars($profile['phone'])        : '';
$profile_photo = $profile['profile_photo'] ?? '';
$profile_complete = ($profile['full_name'] && $profile['email'] && $profile['phone']) ? 100 : 60;

$orders_stmt = $conn->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(total_price),0) AS total_spent FROM orders WHERE user_id = :user_id');
$orders_stmt->bindParam(':user_id', $user_id);
$orders_stmt->execute();
$orders_stats = $orders_stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $orders_stats['total']       ?? 0;
$total_spent  = number_format($orders_stats['total_spent'] ?? 0, 0);

$recent_stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
$recent_stmt->bindParam(':user_id', $user_id);
$recent_stmt->execute();
$orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$initials = '';
foreach (explode(' ', $user_name) as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
}
$initials = mb_substr($initials, 0, 2);

$tab = $_GET['tab'] ?? 'default';
?>

<div class="db-wrap">

    <!-- Sidebar -->
    <aside class="db-sidebar" id="dbSidebar">
        <div class="db-sidebar-toprow">
            <button class="db-sidebar-close" id="dbSidebarClose" aria-label="Close menu">
                <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Close
            </button>
        </div>
        <div class="db-avatar">
            <?php if ($profile_photo): ?>
                <img src="<?= url(htmlspecialchars($profile_photo)) ?>?v=<?= time() ?>" alt="Profile photo">
            <?php else: ?>
                <span><?= $initials ?></span>
            <?php endif; ?>
        </div>
        <p class="db-greeting">Hello,</p>
        <p class="db-uname"><?= $user_name ?></p>
        <p class="db-uemail"><?= $user_email ?></p>

        <nav class="db-nav">
            <a href="?tab=default" class="db-nav-link <?= $tab === 'default' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/></svg>
                Dashboard
            </a>
            <a href="?tab=orders" class="db-nav-link <?= $tab === 'orders' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                My Orders
            </a>
            <a href="?tab=profile" class="db-nav-link <?= $tab === 'profile' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Edit Profile
            </a>
            <a href="?tab=security" class="db-nav-link <?= $tab === 'security' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                Security
            </a>
        </nav>

        <a href="/logout" class="db-logout">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="16 17 21 12 16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Logout
        </a>
    </aside>

    <!-- Main content -->
    <main class="db-main">

        <!-- Mobile top bar -->
        <div class="db-topbar">
            <button class="db-menu-btn" id="dbMenuBtn" aria-label="Open menu">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <span class="db-topbar-title">
                <?php
                $titles = ['default'=>'Dashboard','orders'=>'My Orders','profile'=>'Edit Profile','security'=>'Security'];
                echo $titles[$tab] ?? 'Dashboard';
                ?>
            </span>
        </div>

        <?php if ($tab === 'default'): ?>

        <h1 class="db-page-title">Welcome back, <?= $user_name ?> 👋</h1>

        <!-- Stats -->
        <div class="db-stats">
            <div class="db-stat-card db-stat--blue">
                <div class="db-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <div>
                    <p class="db-stat-label">Total Orders</p>
                    <p class="db-stat-value"><?= $total_orders ?></p>
                </div>
            </div>
            <div class="db-stat-card db-stat--green">
                <div class="db-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <p class="db-stat-label">Total Spent</p>
                    <p class="db-stat-value">PKR <?= $total_spent ?></p>
                </div>
            </div>
            <div class="db-stat-card db-stat--yellow">
                <div class="db-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <p class="db-stat-label">Profile</p>
                    <p class="db-stat-value"><?= $profile_complete ?>%</p>
                </div>
            </div>
        </div>

        <!-- Profile progress bar -->
        <?php if ($profile_complete < 100): ?>
        <div class="db-profile-alert">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Your profile is incomplete. <a href="?tab=profile">Complete it now</a> to get 100%.
        </div>
        <?php endif; ?>

        <!-- Quick actions -->
        <div class="db-quick-actions">
            <a href="?tab=orders" class="db-quick-card">
                <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/></svg>
                <span>View Orders</span>
            </a>
            <a href="?tab=profile" class="db-quick-card">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Edit Profile</span>
            </a>
            <a href="?tab=security" class="db-quick-card">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                <span>Change Password</span>
            </a>
            <a href="/" class="db-quick-card">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                <span>Shop Now</span>
            </a>
        </div>

        <!-- Recent orders -->
        <div class="db-section">
            <div class="db-section-header">
                <h2>Recent Orders</h2>
                <a href="?tab=orders">View all</a>
            </div>
            <?php if (empty($orders)): ?>
            <div class="db-empty">
                <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/></svg>
                <p>No orders yet. <a href="/">Start shopping!</a></p>
            </div>
            <?php else: ?>
            <div class="db-table-wrap">
            <table class="db-table">
                <thead><tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td data-label="Order ID">#<?= htmlspecialchars($o['order_id']) ?></td>
                    <td data-label="Customer"><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td data-label="Status"><span class="db-badge db-badge--<?= strtolower($o['order_status']) ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                    <td data-label="Total"><?= htmlspecialchars($o['total_price']) ?> <?= htmlspecialchars($o['currency']) ?></td>
                    <td data-label="Date"><?= htmlspecialchars(date('d M Y', strtotime($o['created_at']))) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($tab === 'orders'): ?>
            <?php include 'user_orders.php'; ?>
        <?php elseif ($tab === 'profile'): ?>
            <?php include 'user_profile.php'; ?>
        <?php elseif ($tab === 'security'): ?>
            <?php include 'user_security.php'; ?>
        <?php endif; ?>

    </main>
</div>

<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.db-wrap {
    display: flex;
    min-height: 100vh;
    background: #f5f7fb;
    font-family: inherit;
}

/* ── Sidebar ─────────────────────────────────────────────────────────── */
.db-sidebar {
    width: 240px;
    flex-shrink: 0;
    background: #111827;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 32px 16px 24px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.db-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #facc15;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 12px;
    flex-shrink: 0;
    overflow: hidden;
}

.db-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.db-greeting {
    margin: 0;
    font-size: 12px;
    color: #9ca3af;
    text-align: center;
}

.db-uname {
    margin: 2px 0 2px;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    word-break: break-word;
}

.db-uemail {
    margin: 0 0 24px;
    font-size: 11px;
    color: #6b7280;
    text-align: center;
    word-break: break-all;
}

.db-nav {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.db-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    color: #9ca3af;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    outline: none;
}

.db-nav-link svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.db-nav-link:hover,
.db-nav-link:focus,
.db-nav-link.active {
    background: #1f2937;
    color: #facc15;
    text-decoration: none;
    outline: none;
}

.db-logout {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
    padding: 10px 14px;
    border-radius: 10px;
    color: #f87171;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    width: 100%;
    transition: background 0.2s;
}

.db-logout svg {
    width: 18px;
    height: 18px;
}

.db-logout:hover,
.db-logout:focus {
    background: #1f2937;
    text-decoration: none;
    outline: none;
}

/* ── Main ────────────────────────────────────────────────────────────── */
.db-main {
    flex: 1;
    padding: 32px 28px;
    overflow-y: auto;
    min-width: 0;
}

.db-topbar {
    display: none;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.db-menu-btn {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #374151;
}

.db-menu-btn svg {
    width: 20px;
    height: 20px;
}

.db-topbar-title {
    font-size: 17px;
    font-weight: 700;
    color: #111827;
}

.db-page-title {
    font-size: clamp(20px, 3vw, 26px);
    font-weight: 700;
    color: #111827;
    margin: 0 0 24px;
}

/* ── Stats ───────────────────────────────────────────────────────────── */
.db-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.db-stat-card {
    border-radius: 14px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    cursor: default;
}

.db-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.14);
}

.db-stat--blue   { background: linear-gradient(135deg, #111827, #1f2937); color: #ffffff; }
.db-stat--green  { background: linear-gradient(135deg, #facc15, #e8a800); color: #111827; }
.db-stat--yellow { background: linear-gradient(135deg, #374151, #1f2937); color: #ffffff; }
.db-stat--blue .db-stat-label,
.db-stat--blue .db-stat-value,
.db-stat--yellow .db-stat-label,
.db-stat--yellow .db-stat-value { color: #ffffff !important; }
.db-stat--green .db-stat-icon { background: rgba(0,0,0,0.12); }

.db-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.db-stat-icon svg { width: 22px; height: 22px; }

.db-stat-label {
    margin: 0 0 2px;
    font-size: 12px;
    opacity: 0.85;
    font-weight: 500;
}

.db-stat-value {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
}

/* ── Profile alert ───────────────────────────────────────────────────── */
.db-profile-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    color: #92400e;
    margin-bottom: 20px;
}

.db-profile-alert svg { width: 18px; height: 18px; flex-shrink: 0; }
.db-profile-alert a { color: #b45309; font-weight: 600; }

/* ── Quick actions ───────────────────────────────────────────────────── */
.db-quick-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 28px;
}

.db-quick-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 12px;
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 12px;
    text-decoration: none;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    outline: none;
}

.db-quick-card svg {
    width: 24px;
    height: 24px;
    color: #facc15;
}

.db-quick-card:hover,
.db-quick-card:focus {
    border-color: #facc15;
    box-shadow: 0 6px 20px rgba(250,204,21,0.25);
    transform: translateY(-3px);
    text-decoration: none !important;
    color: #111827 !important;
    outline: none;
    background: #fffdf0;
}

/* ── Section ─────────────────────────────────────────────────────────── */
.db-section {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 14px;
    overflow: hidden;
}

.db-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
}

.db-section-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

.db-section-header a {
    font-size: 13px;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
}

.db-section-header a:hover { color: #111827; }

/* ── Empty state ─────────────────────────────────────────────────────── */
.db-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 40px 20px;
    color: #9ca3af;
    font-size: 14px;
}

.db-empty svg { width: 40px; height: 40px; }
.db-empty a { color: #facc15; font-weight: 600; text-decoration: none; }

/* ── Table ───────────────────────────────────────────────────────────── */
.db-table-wrap { overflow-x: auto; }

.db-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.db-table th {
    background: #f9fafb;
    color: #6b7280;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.db-table td {
    padding: 14px 16px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.db-table tr:last-child td { border-bottom: none; }
.db-table tr:hover td { background: #fafafa; }

/* ── Badge ───────────────────────────────────────────────────────────── */
.db-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    background: #f3f4f6;
    color: #374151;
}

.db-badge--pending    { background: #fef3c7; color: #92400e; }
.db-badge--processing { background: #dbeafe; color: #1e40af; }
.db-badge--delivered  { background: #dcfce7; color: #166534; }
.db-badge--cancelled  { background: #fee2e2; color: #991b1b; }

/* ── Sidebar close button row ────────────────────────────────────────── */
.db-sidebar-toprow {
    display: none;
    width: 100%;
    justify-content: flex-end;
    margin-bottom: 12px;
    flex-shrink: 0;
}

.db-sidebar-close {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    background: #1f2937;
    border: 1px solid #374151;
    border-radius: 8px;
    color: #d1d5db;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}

.db-sidebar-close:hover { background: #374151; color: #fff; }
.db-sidebar-close svg   { width: 15px; height: 15px; pointer-events: none; }

/* ── Mobile overlay ──────────────────────────────────────────────────── */
.db-overlay {
    display: none;
    position: fixed;
    background: rgba(0,0,0,0.45);
    z-index: 199;
    top: 0; left: 0; right: 0; bottom: 0;
}

.db-overlay.open { display: block; }

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width: 1023px) {
    .db-quick-actions { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 767px) {
    .db-sidebar {
        position: fixed;
        left: -260px;
        top: 0;           /* overridden by JS with actual header height */
        z-index: 200;
        height: 100%;     /* overridden by JS */
        transition: left 0.28s ease;
        width: 240px;
        overflow-y: auto;
    }

    .db-sidebar.open { left: 0; }

    .db-sidebar-toprow { display: flex; }

    .db-main {
        padding: 16px;
    }

    .db-topbar { display: flex; }

    .db-stats { grid-template-columns: 1fr; }
    .db-stat-card { padding: 16px; }

    .db-quick-actions { grid-template-columns: repeat(2, 1fr); }

    .db-table th { font-size: 11px; padding: 10px 12px; }
    .db-table td { padding: 12px; }
}
</style>

<div class="db-overlay" id="dbOverlay"></div>

<script>
(function () {
    var btn        = document.getElementById('dbMenuBtn');
    var closeBtn   = document.getElementById('dbSidebarClose');
    var sidebar    = document.getElementById('dbSidebar');
    var overlay    = document.getElementById('dbOverlay');
    if (!btn) return;

    function getHeaderHeight() {
        var mobileHeader = document.getElementById('mobile-header');
        var mainHeader   = document.getElementById('main-header');
        var el = (mobileHeader && mobileHeader.offsetHeight > 0) ? mobileHeader : mainHeader;
        return el ? el.offsetHeight : 0;
    }

    function applyHeaderOffset() {
        if (window.innerWidth > 767) return;
        var h = getHeaderHeight();
        sidebar.style.top     = h + 'px';
        sidebar.style.height  = 'calc(100% - ' + h + 'px)';
        overlay.style.top     = h + 'px';
        overlay.style.height  = 'calc(100% - ' + h + 'px)';
    }

    function openSidebar() {
        applyHeaderOffset();
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', openSidebar);
    overlay.addEventListener('click', closeSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
        if (window.innerWidth > 767) closeSidebar();
        else applyHeaderOffset();
    });
})();
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
