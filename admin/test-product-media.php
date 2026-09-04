<?php
/**
 * Product Media feature smoke test
 * Run: C:\xampp\php\php.exe admin/test-product-media.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
require_once $root . '/database/db.php';
require_once $root . '/app/Helpers/ProductMediaHelper.php';
require_once $root . '/app/Models/ProductMediaModel.php';
require_once $root . '/app/Services/ProductMediaService.php';

$passed = 0;
$failed = 0;
$results = [];

function test(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($ok) {
        $passed++;
        $results[] = ['PASS', $name, $detail];
    } else {
        $failed++;
        $results[] = ['FAIL', $name, $detail];
    }
}

echo "=== Product Media Feature Tests ===\n\n";

$db = (new Database())->getConnection();

// 1. Schema
$tables = $db->query("SHOW TABLES LIKE 'product_videos'")->fetchAll();
test('product_videos table exists', count($tables) === 1);

$col = $db->query("SHOW COLUMNS FROM product_images LIKE 'sort_order'")->fetch(PDO::FETCH_ASSOC);
test('product_images.sort_order column exists', !empty($col));

// 2. Upload directories
$videoDir = $root . '/public/uploads/products/videos/';
$thumbDir = $root . '/public/uploads/products/videos/thumbnails/';
if (!is_dir($videoDir)) {
    mkdir($videoDir, 0755, true);
}
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}
test('Video upload directory writable', is_dir($videoDir) && is_writable($videoDir));
test('Thumbnail directory writable', is_dir($thumbDir) && is_writable($thumbDir));

// 3. URL validation
$yt = ProductMediaHelper::validateVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube');
test('YouTube URL validation', $yt['valid'] === true, $yt['message'] ?? $yt['normalized_url'] ?? '');

$tt = ProductMediaHelper::validateVideoUrl('https://www.tiktok.com/@user/video/7123456789012345678', 'tiktok');
test('TikTok URL validation', $tt['valid'] === true, $tt['message'] ?? $tt['normalized_url'] ?? '');

$fb = ProductMediaHelper::validateVideoUrl('https://www.facebook.com/watch/?v=123456789', 'facebook');
test('Facebook URL validation', $fb['valid'] === true, $fb['message'] ?? $fb['normalized_url'] ?? '');

$mp4 = ProductMediaHelper::validateVideoUrl('https://example.com/video/sample.mp4', 'mp4');
test('Direct MP4 URL validation', $mp4['valid'] === true);

$bad = ProductMediaHelper::validateVideoUrl('https://example.com/page.html', 'youtube');
test('Invalid URL rejected', $bad['valid'] === false);

// 4. YouTube thumbnail
$thumb = ProductMediaHelper::getRemoteThumbnailUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube');
test('YouTube thumbnail URL generated', !empty($thumb) && str_contains($thumb, 'img.youtube.com'));

// 5. Model CRUD on a test product
$model = new ProductMediaModel($db);
$productId = (int) $db->query('SELECT product_id FROM products ORDER BY product_id ASC LIMIT 1')->fetchColumn();

if ($productId > 0) {
    $model->deleteProductVideo($productId);
    $model->saveProductVideo($productId, [
        'video_source' => 'youtube',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'thumbnail_url' => $thumb,
        'custom_thumbnail_url' => null,
        'sort_order' => 3,
    ]);

    $saved = $model->getProductVideo($productId);
    test('Save and load product video', !empty($saved) && $saved['video_source'] === 'youtube');

    $items = $model->getGalleryOrderItems($productId);
    $hasVideo = false;
    foreach ($items as $item) {
        if ($item['type'] === 'video') {
            $hasVideo = true;
        }
    }
    test('Gallery order includes video item', $hasVideo, 'items=' . count($items));

    $orderJson = json_encode([
        ['type' => 'image', 'id' => (int) ($items[0]['id'] ?? 0)],
        ['type' => 'video'],
        ['type' => 'image', 'id' => (int) ($items[1]['id'] ?? 0)],
    ]);
    $model->applyGalleryOrder($productId, json_decode($orderJson, true));

    $videoAfter = $model->getProductVideo($productId);
    test('Gallery order updates video sort_order', (int) ($videoAfter['sort_order'] ?? 0) === 2);

    $service = new ProductMediaService($db);
    $service->saveFromRequest($productId, [
        'has_product_video' => '1',
        'video_source' => 'mp4',
        'video_url_hidden' => 'https://cdn.example.com/demo.mp4',
        'video_thumbnail_path' => '',
        'remove_video_flag' => '0',
        'gallery_order_json' => $orderJson,
    ], []);
    $updated = $model->getProductVideo($productId);
    test('ProductMediaService updates video source', ($updated['video_source'] ?? '') === 'mp4');

    $service->saveFromRequest($productId, [
        'remove_video_flag' => '1',
        'gallery_order_json' => '[]',
    ], []);
    test('Remove video via service', $model->getProductVideo($productId) === null);
} else {
    test('Product CRUD tests', false, 'No products in database to test against');
}

// 6. Required files exist
$files = [
    'admin/includes/product-media-section.php',
    'admin/css/product-media.css',
    'admin/js/product-media.js',
    'admin/ajax-upload-product-video.php',
    'admin/ajax-fetch-video-preview.php',
];
foreach ($files as $f) {
    test("File exists: $f", is_file($root . '/' . $f));
}

// 7. Page includes check
$addPage = file_get_contents($root . '/admin/add-product.php');
test('add-product.php includes product media section', str_contains($addPage, 'product-media-section.php'));
test('add-product.php loads product-media.js', str_contains($addPage, 'product-media.js'));

$editPage = file_get_contents($root . '/admin/edit-product.php');
test('edit-product.php includes product media section', str_contains($editPage, 'product-media-section.php'));
test('edit-product.php loads ProductMedia.init', str_contains($editPage, 'ProductMedia.init'));

echo "\n--- Results ---\n";
foreach ($results as [$status, $name, $detail]) {
    $line = "[$status] $name";
    if ($detail !== '') {
        $line .= " — $detail";
    }
    echo $line . "\n";
}

echo "\nTotal: $passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
