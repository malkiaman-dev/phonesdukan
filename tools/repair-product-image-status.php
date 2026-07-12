<?php
/**
 * One-time repair: sync product_images.status with products.product_status
 * and restore missing primary images.
 *
 * Run once on each environment after deploying the edit-product image fix:
 *   php tools/repair-product-image-status.php
 */
require dirname(__DIR__) . '/database/db.php';

$db = (new Database())->getConnection();

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
echo "Done.\n";
