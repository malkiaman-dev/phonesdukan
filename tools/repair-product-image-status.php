<?php
/**
 * Repair product image rows:
 * - sync product_images.status with products.product_status
 * - restore missing primary images
 * - fix upload URLs to the folder where files actually exist
 *   (/wp-content/uploads for legacy WP files, /public/uploads for new files)
 *
 * Does NOT delete any image files.
 *
 * Run on each environment after deploying image fixes:
 *   php tools/repair-product-image-status.php
 */
require dirname(__DIR__) . '/database/db.php';
require dirname(__DIR__) . '/includes/functions.php';

$db = (new Database())->getConnection();
$root = dirname(__DIR__);

$fixStatus = $db->exec("
    UPDATE product_images pi
    INNER JOIN products p ON p.product_id = pi.product_id
    SET pi.status = 1
    WHERE p.product_status = 1 AND (pi.status IS NULL OR pi.status = 0)
");
echo "Synced active image status rows: " . (int) $fixStatus . PHP_EOL;

$fixInactive = $db->exec("
    UPDATE product_images pi
    INNER JOIN products p ON p.product_id = pi.product_id
    SET pi.status = 0
    WHERE p.product_status != 1 AND pi.status = 1
");
echo "Synced inactive/coming-soon image status rows: " . (int) $fixInactive . PHP_EOL;

$products = $db->query("
    SELECT p.product_id
    FROM products p
    INNER JOIN product_images pi ON pi.product_id = p.product_id
    GROUP BY p.product_id
    HAVING SUM(CASE WHEN pi.is_primary = 1 THEN 1 ELSE 0 END) = 0
")->fetchAll(PDO::FETCH_COLUMN);

$fixedPrimary = 0;
$fixPrimary = $db->prepare('UPDATE product_images SET is_primary = 1 WHERE image_id = ?');
$firstImage = $db->prepare("
    SELECT image_id FROM product_images
    WHERE product_id = ?
    ORDER BY sort_order ASC, image_id ASC
    LIMIT 1
");
foreach ($products as $productId) {
    $firstImage->execute([(int) $productId]);
    $imageId = $firstImage->fetchColumn();
    if ($imageId) {
        $fixPrimary->execute([(int) $imageId]);
        $fixedPrimary++;
    }
}
echo "Restored missing primary images: " . $fixedPrimary . PHP_EOL;

$urlRows = $db->query('SELECT image_id, image_url FROM product_images')->fetchAll(PDO::FETCH_ASSOC);
$updateUrl = $db->prepare('UPDATE product_images SET image_url = ? WHERE image_id = ?');
$fixedUrls = 0;
$restoredWp = 0;
$keptPublic = 0;
$missing = 0;

foreach ($urlRows as $row) {
    $current = (string) ($row['image_url'] ?? '');
    if ($current === '') {
        continue;
    }

    // Resolve to the web path where the file actually exists.
    $resolved = resolveExistingUploadWebPath($current);
    if ($resolved === '' || preg_match('#^https?://#i', $resolved)) {
        continue;
    }

    // Prefer storing a clean site-relative path (no /phonesdukan prefix).
    $resolved = normalizeStoredUploadPath($resolved);

    $rel = ltrim($resolved, '/');
    $publicFs = $root . '/public/' . (stripos($rel, 'public/') === 0 ? substr($rel, 7) : (preg_match('#^uploads/#i', $rel) ? $rel : ''));
    $wpFs = null;
    if (preg_match('#^(?:public/)?uploads/(.+)$#i', $rel, $m) || preg_match('#^wp-content/uploads/(.+)$#i', $rel, $m)) {
        $suffix = $m[1];
        $publicFs = $root . '/public/uploads/' . $suffix;
        $wpFs = $root . '/wp-content/uploads/' . $suffix;
    }

    $publicExists = is_string($publicFs) && $publicFs !== '' && is_file($publicFs);
    $wpExists = is_string($wpFs) && $wpFs !== '' && is_file($wpFs);

    $target = $resolved;
    if ($wpExists && !$publicExists) {
        $target = '/wp-content/uploads/' . $suffix;
        if ($target !== $current) {
            $restoredWp++;
        }
    } elseif ($publicExists) {
        $target = '/public/uploads/' . $suffix;
        if ($target !== $current) {
            $keptPublic++;
        }
    } elseif (!$wpExists && !$publicExists) {
        $missing++;
        // Keep current (or normalized) path — do not invent a location.
        $target = $resolved !== '' ? $resolved : $current;
    }

    if ($target !== '' && $target !== $current) {
        $updateUrl->execute([$target, (int) $row['image_id']]);
        $fixedUrls++;
    }
}

echo "Normalized/restored image URLs: " . $fixedUrls . PHP_EOL;
echo "  restored to wp-content/uploads: " . $restoredWp . PHP_EOL;
echo "  pointed at public/uploads: " . $keptPublic . PHP_EOL;
echo "  files missing in both folders: " . $missing . PHP_EOL;
echo "Done. No image files were deleted.\n";
