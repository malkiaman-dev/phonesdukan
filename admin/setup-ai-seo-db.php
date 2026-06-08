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
$database = new Database();
$conn = $database->getConnection();

$results = [];

$queries = [
    // ── AI Request Logs ────────────────────────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_requests` (
        `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `admin_id`     INT DEFAULT NULL,
        `action`       VARCHAR(60) NOT NULL COMMENT 'refine|generate|suggest|score',
        `field_name`   VARCHAR(100) DEFAULT NULL,
        `input_text`   TEXT,
        `output_text`  MEDIUMTEXT,
        `model`        VARCHAR(100) DEFAULT 'compound-beta',
        `tokens_used`  INT DEFAULT 0,
        `latency_ms`   INT DEFAULT 0,
        `status`       ENUM('success','error','timeout') DEFAULT 'success',
        `error_msg`    TEXT DEFAULT NULL,
        `product_id`   INT DEFAULT NULL,
        `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_action`     (`action`),
        KEY `idx_created`    (`created_at`),
        KEY `idx_admin`      (`admin_id`),
        KEY `idx_product`    (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Logs every Groq AI SEO API call'",

    // ── AI Credit / Usage Tracking ────────────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_credits` (
        `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `date`             DATE NOT NULL,
        `daily_requests`   INT DEFAULT 0,
        `daily_tokens`     INT DEFAULT 0,
        `monthly_requests` INT DEFAULT 0,
        `monthly_tokens`   INT DEFAULT 0,
        `total_requests`   BIGINT DEFAULT 0,
        `daily_limit`      INT DEFAULT 100,
        `monthly_limit`    INT DEFAULT 2000,
        `updated_at`       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Daily/monthly AI usage counters'",

    // ── SEO Scores per Product ────────────────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_scores` (
        `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `product_id`          INT NOT NULL,
        `overall_score`       TINYINT UNSIGNED DEFAULT 0,
        `readability_score`   TINYINT UNSIGNED DEFAULT 0,
        `keyword_score`       TINYINT UNSIGNED DEFAULT 0,
        `content_score`       TINYINT UNSIGNED DEFAULT 0,
        `meta_score`          TINYINT UNSIGNED DEFAULT 0,
        `score_data`          JSON DEFAULT NULL COMMENT 'Full checks array',
        `computed_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_product` (`product_id`),
        KEY `idx_score`   (`overall_score`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Stores the latest SEO score for each product'",

    // ── AI-Generated Content Cache ────────────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_cache` (
        `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `cache_key`  VARCHAR(64) NOT NULL COMMENT 'SHA256 of action+input',
        `action`     VARCHAR(60) NOT NULL,
        `field_name` VARCHAR(100) DEFAULT NULL,
        `output`     MEDIUMTEXT NOT NULL,
        `hit_count`  INT DEFAULT 1,
        `expires_at` DATETIME NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_cache_key` (`cache_key`),
        KEY `idx_expires` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Caches identical Groq responses to save API calls'",

    // ── AI Generation History per Product ─────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_history` (
        `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `product_id`  INT DEFAULT NULL,
        `admin_id`    INT DEFAULT NULL,
        `field_name`  VARCHAR(100) NOT NULL,
        `old_value`   TEXT,
        `new_value`   MEDIUMTEXT,
        `action`      VARCHAR(60) NOT NULL,
        `applied`     TINYINT(1) DEFAULT 0,
        `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_product` (`product_id`),
        KEY `idx_admin`   (`admin_id`),
        KEY `idx_field`   (`field_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='History of every AI suggestion shown/applied'",

    // ── AI Error Logs ──────────────────────────────────────────────────────────
    "CREATE TABLE IF NOT EXISTS `ai_seo_error_logs` (
        `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `error_type`     VARCHAR(50) NOT NULL COMMENT 'credits|rate_limit|timeout|auth|network|server|invalid_response|unknown',
        `error_code`     INT DEFAULT NULL COMMENT 'HTTP status code',
        `error_raw`      TEXT DEFAULT NULL COMMENT 'raw API error (debug only)',
        `user_message`   VARCHAR(300) NOT NULL COMMENT 'friendly message shown to admin',
        `action`         VARCHAR(60) DEFAULT NULL COMMENT 'refine|generate|suggest',
        `field_name`     VARCHAR(100) DEFAULT NULL,
        `product_id`     INT DEFAULT NULL,
        `admin_id`       INT DEFAULT NULL,
        `prompt_preview` VARCHAR(500) DEFAULT NULL COMMENT 'first 500 chars of prompt',
        `model`          VARCHAR(100) DEFAULT NULL,
        `latency_ms`     INT DEFAULT 0,
        `retried`        TINYINT(1) DEFAULT 0,
        `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_error_type` (`error_type`),
        KEY `idx_created`    (`created_at`),
        KEY `idx_product`    (`product_id`),
        KEY `idx_admin`      (`admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Logs every AI failure for monitoring and debugging'",
];

foreach ($queries as $sql) {
    try {
        $conn->exec($sql);
        // Extract table name from SQL
        preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
        $results[] = ['status' => 'ok', 'table' => $m[1] ?? '?', 'msg' => 'Created / already exists'];
    } catch (PDOException $e) {
        preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
        $results[] = ['status' => 'error', 'table' => $m[1] ?? '?', 'msg' => $e->getMessage()];
    }
}

// Seed today's credit row if not exists
try {
    $conn->exec("INSERT IGNORE INTO `ai_seo_credits` (`date`, `daily_requests`, `monthly_requests`, `total_requests`) VALUES (CURDATE(), 0, 0, 0)");
    $results[] = ['status' => 'ok', 'table' => 'ai_seo_credits', 'msg' => 'Seed row ensured for today'];
} catch (PDOException $e) {
    $results[] = ['status' => 'warn', 'table' => 'ai_seo_credits', 'msg' => $e->getMessage()];
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
