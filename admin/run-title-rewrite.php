<?php
/**
 * Browser-based product title rewriter (no SSH needed).
 * Login required. Delete this file after you finish.
 */
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/product_title_rewrite.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = null;
$mode = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $expected = (string) ($_SESSION['title_rewrite_csrf'] ?? '');
    if ($token === '' || !hash_equals($expected, $token)) {
        $error = 'Invalid security token. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'preview');
        $apply = ($action === 'apply');
        $mode = $apply ? 'apply' : 'preview';
        try {
            $result = runProductTitleRewrite($conn, $apply, null);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$_SESSION['title_rewrite_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['title_rewrite_csrf'];

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
<style>
.trw-wrap { max-width: 980px; margin: 24px auto; padding: 0 16px 40px; }
.trw-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:22px; }
.trw-card h1 { margin:0 0 8px; font-size:1.4rem; font-weight:600; }
.trw-card p { color:#4b5563; line-height:1.5; }
.trw-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
.trw-btn { border:0; border-radius:8px; padding:10px 16px; font-weight:600; cursor:pointer; }
.trw-btn-preview { background:#111; color:#fff; }
.trw-btn-apply { background:#f7cf04; color:#111; }
.trw-note { margin-top:14px; padding:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:.92rem; }
.trw-error { margin-top:14px; padding:12px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#991b1b; }
.trw-stats { margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; }
.trw-stat { background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; min-width:120px; }
.trw-stat strong { display:block; font-size:1.2rem; }
.trw-table { width:100%; border-collapse:collapse; margin-top:16px; font-size:.9rem; }
.trw-table th, .trw-table td { border:1px solid #e5e7eb; padding:8px 10px; vertical-align:top; text-align:left; }
.trw-table th { background:#f8fafc; }
.trw-old { color:#6b7280; }
.trw-new { color:#111; }
</style>

<div class="content">
  <div class="trw-wrap">
    <div class="trw-card">
      <h1>Professional Product Titles</h1>
      <p>
        This updates all product titles to Amazon/Daraz style using brand + description features.
        Product URLs (slugs) stay the same. No SSH needed.
      </p>

      <div class="trw-note">
        <strong>Steps:</strong> 1) Click <em>Preview</em> first.
        2) If samples look good, click <em>Apply to Live Database</em>.
        3) After success, delete this file: <code>admin/run-title-rewrite.php</code>
      </div>

      <?php if ($error !== ''): ?>
        <div class="trw-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" class="trw-actions">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <button class="trw-btn trw-btn-preview" type="submit" name="action" value="preview">Preview changes</button>
        <button class="trw-btn trw-btn-apply" type="submit" name="action" value="apply"
                onclick="return confirm('Apply professional titles to ALL products on this live database?');">
          Apply to Live Database
        </button>
      </form>

      <?php if (is_array($result)): ?>
        <div class="trw-stats">
          <div class="trw-stat"><strong><?= (int) $result['total'] ?></strong> scanned</div>
          <div class="trw-stat"><strong><?= (int) $result['changed'] ?></strong> <?= $mode === 'apply' ? 'updated' : 'will change' ?></div>
          <div class="trw-stat"><strong><?= (int) $result['skipped'] ?></strong> unchanged</div>
        </div>

        <?php if ($mode === 'apply'): ?>
          <div class="trw-note" style="background:#ecfdf5;border-color:#a7f3d0;">
            Done. Titles are updated on this database. Hard-refresh your storefront, then delete this admin page file.
          </div>
        <?php endif; ?>

        <?php if (!empty($result['samples'])): ?>
          <table class="trw-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Old title</th>
                <th>New title</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($result['samples'] as $sample): ?>
                <tr>
                  <td>#<?= (int) $sample['id'] ?></td>
                  <td class="trw-old"><?= htmlspecialchars($sample['old']) ?></td>
                  <td class="trw-new"><?= htmlspecialchars($sample['new']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
