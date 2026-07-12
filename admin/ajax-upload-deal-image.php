<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (empty($_FILES['deal_image']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$file = $_FILES['deal_image'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Use JPG, PNG, WEBP, or GIF.']);
    exit();
}

if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5 MB.']);
    exit();
}

$uploadDir = __DIR__ . '/../public/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
    $ext = 'webp';
}

$filename = 'deal_' . uniqid('', true) . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    exit();
}

$path = 'public/uploads/' . $filename;
echo json_encode([
    'success' => true,
    'path' => $path,
    'url' => '/' . $path,
]);
