<?php
session_start();
require_once __DIR__ . '/../database/db.php'; // For potential DB checks if needed

// Admin auth check
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Invalid file');
}

// Sanitize: basename only (prevents path traversal)
$safeFileName = basename($_GET['file']);

// Absolute path using DOCUMENT_ROOT—reliable across setups
$filePath = dirname(__DIR__, 1) . '/private/screenshots/' . $safeFileName;

error_log('🔍 Screenshot Serve Debug: File param: ' . $_GET['file'] . ' | Safe name: ' . $safeFileName . ' | Full path: ' . $filePath . ' | Exists: ' . (file_exists($filePath) ? 'YES' : 'NO'));

if (!file_exists($filePath) || !is_file($filePath)) {
    error_log('❌ Screenshot Not Found: ' . $filePath);
    http_response_code(404);
    die('File not found');
}

// Security: Ensure it's an image
$imageInfo = getimagesize($filePath);
if ($imageInfo === false) {
    error_log('❌ Invalid Image: ' . $filePath);
    http_response_code(400);
    die('Invalid image');
}

error_log('✅ Serving Image: ' . $filePath . ' | Type: ' . $imageInfo['mime'] . ' | Size: ' . filesize($filePath));

header('Content-Type: ' . $imageInfo['mime']);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600'); // Optional: Cache for 1hr
readfile($filePath);
exit();
?>