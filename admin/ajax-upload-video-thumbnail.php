<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once dirname(__DIR__) . '/app/Helpers/ProductMediaHelper.php';

$thumbDir = dirname(__DIR__) . '/public/uploads/products/videos/thumbnails/';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

if (empty($_FILES['video_thumbnail']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'No thumbnail uploaded.']);
    exit();
}

$file = $_FILES['video_thumbnail'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Thumbnail upload failed.']);
    exit();
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ProductMediaHelper::ALLOWED_THUMB_EXTENSIONS, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid thumbnail format. Use JPG, PNG, or WEBP.']);
    exit();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMimes, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid thumbnail file type.']);
    exit();
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Thumbnail too large. Max 5 MB.']);
    exit();
}

$filename = 'poster_' . uniqid() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
$dest = ProductMediaHelper::uniquePath($thumbDir, $filename);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save thumbnail.']);
    exit();
}

echo json_encode([
    'success' => true,
    'thumbnail_url' => '/public/uploads/products/videos/thumbnails/' . basename($dest),
]);
