<?php
/**
 * CLI wrapper — prefers shared include. No SSH required on Hostinger;
 * use admin/run-title-rewrite.php in the browser instead.
 *
 *   php tools/rewrite-product-titles.php
 *   php tools/rewrite-product-titles.php --apply
 */

require dirname(__DIR__) . '/database/db.php';
require dirname(__DIR__) . '/includes/product_title_rewrite.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = null;
foreach (($argv ?? []) as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    }
}

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = runProductTitleRewrite($db, $apply, $limit);

foreach ($result['samples'] as $sample) {
    echo sprintf(
        "[%s] #%d\n  OLD: %s\n  NEW: %s\n\n",
        $apply ? 'APPLY' : 'DRY',
        $sample['id'],
        $sample['old'],
        $sample['new']
    );
}

echo "----\n";
echo $apply ? "APPLIED to database.\n" : "DRY-RUN only. Re-run with --apply to save.\n";
echo "Scanned: {$result['changed']} changed, {$result['skipped']} unchanged, total {$result['total']}\n";
