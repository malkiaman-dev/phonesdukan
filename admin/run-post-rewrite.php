<?php
/**
 * Browser tool: rewrite posts to commercial tone + product CTAs (no SSH).
 * Delete this file after use.
 */
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/post_commercial_rewrite.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = null;
$mode = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $expected = (string) ($_SESSION['post_rewrite_csrf'] ?? '');
    if ($token === '' || !hash_equals($expected, $token)) {
        $error = 'Invalid security token. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'preview');
        $apply = ($action === 'apply');
        $mode = $apply ? 'apply' : 'preview';
        try {
            $result = runCommercialPostRewrite($db, $apply);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$_SESSION['post_rewrite_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['post_rewrite_csrf'];

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
<style>
.prw-wrap{max-width:980px;margin:24px auto;padding:0 16px 40px}
.prw-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:22px}
.prw-card h1{margin:0 0 8px;font-size:1.35rem;font-weight:600}
.prw-card p{color:#4b5563;line-height:1.5}
.prw-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
.prw-btn{border:0;border-radius:8px;padding:10px 16px;font-weight:600;cursor:pointer}
.prw-preview{background:#111;color:#fff}
.prw-apply{background:#f7cf04;color:#111}
.prw-note{margin-top:14px;padding:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px}
.prw-error{margin-top:14px;padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#991b1b}
.prw-table{width:100%;border-collapse:collapse;margin-top:16px;font-size:.9rem}
.prw-table th,.prw-table td{border:1px solid #e5e7eb;padding:8px 10px;text-align:left;vertical-align:top}
.prw-table th{background:#f8fafc}
</style>
<div class="content">
  <div class="prw-wrap">
    <div class="prw-card">
      <h1>Update Blog Posts (Commercial SEO)</h1>
      <p>
        Removes casual/passionate writing, refreshes titles &amp; meta for search,
        and adds product shopping blocks so posts send buyers to your catalog.
      </p>
      <div class="prw-note">
        <strong>Steps:</strong> Preview → Apply → hard-refresh blog pages → delete
        <code>admin/run-post-rewrite.php</code>.
      </div>
      <?php if ($error !== ''): ?><div class="prw-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" class="prw-actions">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <button class="prw-btn prw-preview" type="submit" name="action" value="preview">Preview</button>
        <button class="prw-btn prw-apply" type="submit" name="action" value="apply"
          onclick="return confirm('Update ALL posts to commercial style now?');">Apply to Live Database</button>
      </form>
      <?php if (is_array($result)): ?>
        <p style="margin-top:16px;"><strong><?= (int)$result['changed'] ?></strong> of <?= (int)$result['total'] ?> posts <?= $mode === 'apply' ? 'updated' : 'ready' ?>.</p>
        <?php if ($mode === 'apply'): ?>
          <div class="prw-note" style="background:#ecfdf5;border-color:#a7f3d0;">Posts updated. Re-submit sitemaps in Google Search Console if needed.</div>
        <?php endif; ?>
        <?php if (!empty($result['samples'])): ?>
          <table class="prw-table">
            <thead><tr><th>ID</th><th>Old title</th><th>New title</th></tr></thead>
            <tbody>
            <?php foreach ($result['samples'] as $s): ?>
              <tr>
                <td>#<?= (int)$s['id'] ?></td>
                <td><?= htmlspecialchars($s['old']) ?></td>
                <td><?= htmlspecialchars($s['new']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
