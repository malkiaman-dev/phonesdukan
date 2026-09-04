<?php
/**
 * One-time helper: cache remote video thumbnails locally.
 * Run once after deploying the TikTok thumbnail fix.
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../app/Helpers/ProductMediaHelper.php';

$db = (new Database())->getConnection();
$rows = $db->query(
    "SELECT video_id, video_source, thumbnail_url, custom_thumbnail_url
     FROM product_videos
     WHERE thumbnail_url LIKE 'http%'
     ORDER BY video_id"
)->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
foreach ($rows as $row) {
    if (!empty($row['custom_thumbnail_url'])) {
        continue;
    }
    $cached = ProductMediaHelper::cacheRemoteThumbnail(
        (string) $row['thumbnail_url'],
        ($row['video_source'] ?? 'remote') . '_thumb'
    );
    if ($cached === null) {
        echo "Skip video #{$row['video_id']}: could not download thumbnail\n";
        continue;
    }
    $stmt = $db->prepare('UPDATE product_videos SET thumbnail_url = ? WHERE video_id = ?');
    $stmt->execute([$cached, $row['video_id']]);
    echo "Fixed video #{$row['video_id']}: {$cached}\n";
    $fixed++;
}

echo "\nDone. Updated {$fixed} video(s).\n";
