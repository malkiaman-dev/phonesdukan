<?php
/**
 * AI SEO Assistant – Database Setup Script
 * Run once: http://localhost/phonesdukan/admin/setup-ai-seo-db.php
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Allow CLI execution
    if (PHP_SAPI !== 'cli') {
        die('Unauthorized. Please login as admin first.');
    }
}

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/database_migrations.php';
$database = new Database();
$conn = $database->getConnection();

$results = [];

if (function_exists('ensureAiSeoTables')) {
    ensureAiSeoTables($conn);
}

foreach (['ai_seo_requests', 'ai_seo_credits', 'ai_seo_scores', 'ai_seo_cache', 'ai_seo_history', 'ai_seo_error_logs'] as $table) {
    $exists = false;
    try {
        $check = $conn->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $check->execute([$table]);
        $exists = (int) $check->fetchColumn() > 0;
    } catch (PDOException $e) {
        $results[] = ['status' => 'error', 'table' => $table, 'msg' => $e->getMessage()];
        continue;
    }

    $results[] = [
        'status' => $exists ? 'ok' : 'error',
        'table' => $table,
        'msg' => $exists ? 'Created / already exists' : 'Table missing after migration',
    ];
}

if (PHP_SAPI === 'cli') {
    foreach ($results as $r) {
        echo "[{$r['status']}] {$r['table']}: {$r['msg']}\n";
    }
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI SEO DB Setup</title>
<style>
  body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f8fafc; }
  h1 { color: #111; }
  .row { display: flex; gap: 12px; align-items: center; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; }
  .ok   { background: #dcfce7; }
  .error{ background: #fee2e2; }
  .warn { background: #fef9c3; }
  .badge { font-weight: 700; font-size: .8rem; padding: 2px 8px; border-radius: 999px; }
  .ok   .badge { background: #16a34a; color: #fff; }
  .error .badge { background: #ef4444; color: #fff; }
  .warn  .badge { background: #eab308; color: #fff; }
  .table { font-weight: 700; flex: 0 0 180px; }
  .msg { color: #555; font-size: .88rem; flex: 1; }
  .back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #111; color: #facc15; border-radius: 8px; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>
<h1>🤖 AI SEO Database Setup</h1>
<?php foreach ($results as $r): ?>
<div class="row <?= $r['status'] ?>">
  <span class="badge"><?= strtoupper($r['status']) ?></span>
  <span class="table"><?= htmlspecialchars($r['table']) ?></span>
  <span class="msg"><?= htmlspecialchars($r['msg']) ?></span>
</div>
<?php endforeach; ?>
<a href="add-product.php" class="back">← Back to Add Product</a>
</body>
</html>
