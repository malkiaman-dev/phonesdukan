<?php
session_start();
require_once dirname(__DIR__, 1) . '/database/db.php';
require_once dirname(__DIR__, 1) . '/app/Models/AdminPostModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$upload_dir = dirname(__DIR__, 1) . '/public/uploads/posts/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

if ($_FILES['file']['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'error' => 'File size too large']);
    exit;
}

$original_name = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
$sanitized_name = preg_replace('/[^a-zA-Z0-9-_]/', '', str_replace(' ', '_', $original_name));
$filename = $sanitized_name . '.' . $ext;
$destination = $upload_dir . $filename;

if (file_exists($destination)) {
    $filename = $sanitized_name . '_' . time() . '.' . $ext;
    $destination = $upload_dir . $filename;
}

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    exit;
}

// Insert into post_images (without post_id, as it may be used in multiple posts)
$db = new Database();
$conn = $db->getConnection();
$query = "
    INSERT INTO post_images (image_url, image_name, created_at)
    VALUES (:image_url, :image_name, NOW())
";
$stmt = $conn->prepare($query);
$stmt->execute([
    ':image_url' => '/public/uploads/posts/' . $filename,
    ':image_name' => $_FILES['file']['name']
]);

echo json_encode(['success' => true, 'url' => '/public/uploads/posts/' . $filename]);