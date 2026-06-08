<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once dirname(__DIR__) . '/app/Helpers/ProductMediaHelper.php';

$videoDir = dirname(__DIR__) . '/public/uploads/products/videos/';
$thumbDir = dirname(__DIR__) . '/public/uploads/products/videos/thumbnails/';

if (!is_dir($videoDir)) {
    mkdir($videoDir, 0755, true);
}
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

if (empty($_FILES['product_video']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'No video file uploaded.']);
    exit();
}

$file = $_FILES['product_video'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed. Error code: ' . $file['error']]);
    exit();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ProductMediaHelper::ALLOWED_VIDEO_MIMES, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: MP4, WebM, MOV.']);
    exit();
}

if ($file['size'] > ProductMediaHelper::VIDEO_MAX_BYTES) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 100 MB.']);
    exit();
}

$extMap = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
$ext = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp4');
$filename = 'vid_' . uniqid() . '.' . $ext;
$dest = ProductMediaHelper::uniquePath($videoDir, $filename);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded video.']);
    exit();
}

$videoUrl = '/public/uploads/products/videos/' . basename($dest);
$thumbnailUrl = null;

$thumbPath = ProductMediaHelper::generateVideoThumbnail($dest, $thumbDir);
if ($thumbPath) {
    $thumbnailUrl = '/public/uploads/products/videos/thumbnails/' . basename($thumbPath);
}

// Optional client-generated thumbnail blob
if (!empty($_FILES['video_thumbnail']['tmp_name']) && $_FILES['video_thumbnail']['error'] === UPLOAD_ERR_OK) {
    $thumbMime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['video_thumbnail']['tmp_name']);
    if (in_array($thumbMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $thumbName = 'thumb_' . uniqid() . '.jpg';
        $thumbDest = ProductMediaHelper::uniquePath($thumbDir, $thumbName);
        if (move_uploaded_file($_FILES['video_thumbnail']['tmp_name'], $thumbDest)) {
            $thumbnailUrl = '/public/uploads/products/videos/thumbnails/' . basename($thumbDest);
        }
    }
}

echo json_encode([
    'success' => true,
    'video_url' => $videoUrl,
    'thumbnail_url' => $thumbnailUrl,
    'message' => 'Video uploaded successfully.',
]);
