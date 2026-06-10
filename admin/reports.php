<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/includes/functions.php';

$range = isset($_GET['range']) ? (string)$_GET['range'] : 'all';
$allowedRanges = ['all', '7', '30'];
if (!in_array($range, $allowedRanges, true)) {
    $range = 'all';
}

$dateFilterSql = '';
$bindDate = null;
if ($range !== 'all') {
    $days = (int)$range;
    $bindDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $dateFilterSql = ' WHERE created_at >= :from_date ';
}

$stats = [
    'orders_total' => 0,
    'orders_pending' => 0,
    'orders_completed' => 0,
    'revenue_total' => 0.0,
    'customers_total' => 0,
    'products_total' => 0,
];

$topProducts = [];
$reportError = null;

try {
    $db = (new Database())->getConnection();

    $ordersTotalSql = 'SELECT COUNT(*) FROM orders' . $dateFilterSql;
    $stmt = $db->prepare($ordersTotalSql);
    if ($bindDate !== null) {
        $stmt->bindValue(':from_date', $bindDate);
    }
    $stmt->execute();
    $stats['orders_total'] = (int)$stmt->fetchColumn();

    $ordersPendingSql = "SELECT COUNT(*) FROM orders WHERE order_status = 'pending'";
    if ($bindDate !== null) {
        $ordersPendingSql .= ' AND created_at >= :from_date';
    }
    $stmt = $db->prepare($ordersPendingSql);
    if ($bindDate !== null) {
        $stmt->bindValue(':from_date', $bindDate);
    }
    $stmt->execute();
    $stats['orders_pending'] = (int)$stmt->fetchColumn();

    $ordersCompletedSql = "SELECT COUNT(*) FROM orders WHERE order_status = 'completed'";
    if ($bindDate !== null) {
        $ordersCompletedSql .= ' AND created_at >= :from_date';
    }
    $stmt = $db->prepare($ordersCompletedSql);
    if ($bindDate !== null) {
        $stmt->bindValue(':from_date', $bindDate);
    }
    $stmt->execute();
    $stats['orders_completed'] = (int)$stmt->fetchColumn();

    $revenueSql = 'SELECT COALESCE(SUM(total_price), 0) FROM orders';
    if ($bindDate !== null) {
        $revenueSql .= ' WHERE created_at >= :from_date';
    }
    $stmt = $db->prepare($revenueSql);
    if ($bindDate !== null) {
        $stmt->bindValue(':from_date', $bindDate);
    }
    $stmt->execute();
    $stats['revenue_total'] = (float)$stmt->fetchColumn();

    $stats['customers_total'] = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['products_total'] = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();

    $topProductsSql = "
        SELECT product_name, sale_price, regular_price, stock_quantity
        FROM products
        ORDER BY created_at DESC
        LIMIT 8
    ";
    $topProducts = $db->query($topProductsSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $reportError = $e->getMessage();
}

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Phones Dukan</title>
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

        body { background: var(--bg); color: var(--black); }
        .rep-wrap { max-width: 1320px; margin: 0 auto; padding: 20px; }

        .rep-header, .rep-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .rep-header {
            padding: 20px 24px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rep-title {
            margin: 0;
            font-size: clamp(1.5rem, 2vw, 1.75rem);
            font-weight: 600;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        .rep-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }

        .rep-range-form { display: flex; align-items: center; gap: 10px; }
        .rep-native-select {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            overflow: hidden !important;
        }
        .rep-select-wrap { position: relative; min-width: 145px; }
        .rep-select-display {
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
        .rep-select-display::after {
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
        .rep-select-display:hover,
        .rep-select-wrap.is-open .rep-select-display {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }
        .rep-select-options {
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
        }
        .rep-select-wrap.is-open .rep-select-options { display: block; }
        .rep-select-option {
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
        .rep-select-option:hover { background: var(--light-yellow); }
        .rep-select-option.is-selected { background: var(--yellow); }

        .rep-btn {
            height: 44px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid #e6bd00;
            background: #f7cf04;
            color: #111111 !important;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(247, 207, 4, 0.22);
            transition: background .2s ease, border-color .2s ease, box-shadow .2s ease, transform .12s ease;
        }
        .rep-btn:hover {
            background: #e6bd00;
            color: #111111 !important;
            border-color: #d4af00;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(247, 207, 4, 0.28);
        }

        .rep-btn-outline {
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--black) !important;
            box-shadow: none;
        }
        .rep-btn-outline:hover {
            background: #fffef8;
            border-color: #f7cf04;
            box-shadow: 0 0 0 3px rgba(247, 207, 4, 0.15);
            color: var(--black) !important;
            transform: translateY(-1px);
        }

        .rep-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .rep-stat {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }
        .rep-stat-label {
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .rep-stat-value {
            margin-top: 6px;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--black);
        }

        .rep-card { padding: 16px; }
        .rep-card-title {
            margin: 0 0 10px;
            font-size: 1rem;
            font-weight: 800;
        }

        .rep-table-wrap { overflow-x: auto; }
        .rep-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .rep-table th, .rep-table td {
            padding: 12px;
            border: 0;
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .rep-table thead th {
            background: #f9fafb;
            color: var(--black);
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .rep-table tbody tr:hover { background: var(--light-yellow); }

        .rep-pill {
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
            background: var(--light-yellow) !important;
        }
        .rep-pill * {
            color: var(--black) !important;
            background: transparent !important;
        }

        .rep-error {
            margin-top: 12px;
            border: 1px solid var(--yellow);
            background: var(--light-yellow);
            color: var(--black);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 700;
        }

        @media (max-width: 980px) {
            .rep-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 700px) {
            .rep-grid { grid-template-columns: 1fr; }
        }
    </style>
    <div class="rep-wrap">
        <div class="rep-header">
            <div>
                <h2 class="rep-title">Reports</h2>
                <p class="rep-subtitle">Track key store metrics and recent product performance.</p>
            </div>
            <form method="GET" action="<?php echo htmlspecialchars(url('admin/reports.php')); ?>" class="rep-range-form">
                <div class="rep-select-wrap" data-rep-select>
                    <select name="range" id="repRange" class="rep-native-select">
                        <option value="all" <?php echo $range === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="7" <?php echo $range === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30" <?php echo $range === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                    <button type="button" class="rep-select-display" data-rep-display>All Time</button>
                    <div class="rep-select-options">
                        <button type="button" class="rep-select-option" data-value="all">All Time</button>
                        <button type="button" class="rep-select-option" data-value="7">Last 7 Days</button>
                        <button type="button" class="rep-select-option" data-value="30">Last 30 Days</button>
                    </div>
                </div>
                <button type="submit" class="rep-btn">Apply</button>
                <button type="button" class="rep-btn rep-btn-outline" id="repDownloadPdf">Download PDF</button>
            </form>
        </div>

        <div class="rep-grid">
            <div class="rep-stat">
                <div class="rep-stat-label">Total Orders</div>
                <div class="rep-stat-value"><?php echo number_format($stats['orders_total']); ?></div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-label">Pending Orders</div>
                <div class="rep-stat-value"><?php echo number_format($stats['orders_pending']); ?></div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-label">Completed Orders</div>
                <div class="rep-stat-value"><?php echo number_format($stats['orders_completed']); ?></div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-label">Revenue</div>
                <div class="rep-stat-value">Rs. <?php echo number_format($stats['revenue_total'], 0); ?></div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-label">Customers</div>
                <div class="rep-stat-value"><?php echo number_format($stats['customers_total']); ?></div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-label">Products</div>
                <div class="rep-stat-value"><?php echo number_format($stats['products_total']); ?></div>
            </div>
        </div>

        <div class="rep-card">
            <h3 class="rep-card-title">Recent Products Snapshot</h3>
            <div class="rep-table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topProducts)): ?>
                            <?php foreach ($topProducts as $row): ?>
                                <?php
                                    $price = isset($row['sale_price']) && (float)$row['sale_price'] > 0
                                        ? (float)$row['sale_price']
                                        : (float)($row['regular_price'] ?? 0);
                                    $stock = (int)($row['stock_quantity'] ?? 0);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($row['product_name'] ?? 'N/A')); ?></td>
                                    <td>Rs. <?php echo number_format($price, 0); ?></td>
                                    <td><span class="rep-pill"><?php echo $stock; ?></span></td>
                                    <td><span class="rep-pill"><?php echo $stock > 0 ? 'In Stock' : 'Out of Stock'; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="padding:16px; color: var(--muted); font-weight:700;">No product data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($reportError !== null): ?>
            <div class="rep-error">Report data issue: <?php echo htmlspecialchars($reportError); ?></div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-rep-select]').forEach(function (wrap) {
            const nativeSelect = wrap.querySelector('select');
            const display = wrap.querySelector('[data-rep-display]');
            const options = Array.from(wrap.querySelectorAll('.rep-select-option'));
            if (!nativeSelect || !display || options.length === 0) return;

            function sync() {
                const selected = options.find(opt => opt.dataset.value === nativeSelect.value);
                display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Select');
                options.forEach(opt => opt.classList.toggle('is-selected', opt.dataset.value === nativeSelect.value));
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.rep-select-wrap.is-open').forEach(function (o) {
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
                });
            });
            sync();
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.rep-select-wrap.is-open').forEach(function (wrap) {
                wrap.classList.remove('is-open');
            });
        });

        const pdfBtn = document.getElementById('repDownloadPdf');
        if (pdfBtn) {
            pdfBtn.addEventListener('click', function () {
                const jsPDFLib = window.jspdf && window.jspdf.jsPDF;
                if (!jsPDFLib) return;

                const doc = new jsPDFLib({ orientation: 'p', unit: 'pt', format: 'a4' });
                const marginX = 40;
                let y = 48;

                const title = document.querySelector('.rep-title')?.textContent?.trim() || 'Reports';
                const subtitle = document.querySelector('.rep-subtitle')?.textContent?.trim() || '';
                const generatedAt = new Date().toLocaleString();

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(18);
                doc.text(title, marginX, y);
                y += 22;

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(11);
                doc.text(subtitle, marginX, y);
                y += 16;
                doc.setTextColor(107, 114, 128);
                doc.text('Generated: ' + generatedAt, marginX, y);
                doc.setTextColor(17, 17, 17);
                y += 20;

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(13);
                doc.text('Summary', marginX, y);
                y += 14;

                const stats = Array.from(document.querySelectorAll('.rep-stat')).map(function (card) {
                    const label = card.querySelector('.rep-stat-label')?.textContent?.trim() || '';
                    const value = card.querySelector('.rep-stat-value')?.textContent?.trim() || '';
                    return [label, value];
                });

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                stats.forEach(function (row) {
                    doc.text(row[0] + ':', marginX, y);
                    doc.text(row[1], marginX + 180, y);
                    y += 14;
                });

                y += 10;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(13);
                doc.text('Recent Products Snapshot', marginX, y);
                y += 16;

                const headers = ['Product', 'Price', 'Stock', 'Status'];
                const rows = Array.from(document.querySelectorAll('.rep-table tbody tr')).map(function (tr) {
                    return Array.from(tr.querySelectorAll('td')).map(function (td) {
                        return (td.textContent || '').trim().replace(/\s+/g, ' ');
                    });
                }).filter(function (r) { return r.length === 4; });

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.text(headers[0], marginX, y);
                doc.text(headers[1], marginX + 250, y);
                doc.text(headers[2], marginX + 340, y);
                doc.text(headers[3], marginX + 400, y);
                y += 10;
                doc.setLineWidth(0.6);
                doc.line(marginX, y, 555, y);
                y += 12;

                doc.setFont('helvetica', 'normal');
                rows.forEach(function (r) {
                    if (y > 780) {
                        doc.addPage();
                        y = 48;
                    }
                    doc.text(String(r[0] || '').slice(0, 42), marginX, y);
                    doc.text(String(r[1] || ''), marginX + 250, y);
                    doc.text(String(r[2] || ''), marginX + 340, y);
                    doc.text(String(r[3] || ''), marginX + 400, y);
                    y += 14;
                });

                const filename = 'reports-' + new Date().toISOString().slice(0, 10) + '.pdf';
                doc.save(filename);
            });
        }
    });
    </script>
