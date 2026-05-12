<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../public/uploads/variations/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_FILES['variation_image']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$file = $_FILES['variation_image'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Use JPG, PNG, WEBP, or GIF.']);
    exit();
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5 MB.']);
    exit();
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'var_' . uniqid() . '.' . strtolower($ext);
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    exit();
}

// Store path relative to site root (no base path prefix).
// bootstrap.php rewrites /public/... → /phonesdukan/public/... in HTML.
// For JS usage, the frontend uses withBase() helper.
$url = '/public/uploads/variations/' . $filename;
echo json_encode(['success' => true, 'url' => $url]);
