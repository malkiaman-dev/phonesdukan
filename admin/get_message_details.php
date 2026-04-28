<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure UTF-8 encoding in PHP
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once __DIR__ . '/../database/db.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Get message ID from the AJAX request
if (!isset($_GET['message_id'])) {
    echo "Invalid request!";
    exit;
}

$message_id = (int)$_GET['message_id']; // Ensure message_id is an integer

// Fetch message details
$query = "SELECT id, name, email, subject, message, created_at 
          FROM contact_messages 
          WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$message_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    echo "Message not found!";
    exit;
}

// Decode HTML entities and ensure proper emoji handling
$decoded_message = html_entity_decode($message['message'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
$formatted_message = str_replace(['<br />', '<br>'], "\n", $decoded_message);

// Sanitize the message to prevent XSS but preserve emojis
$formatted_message = strip_tags($formatted_message, ''); // Remove all HTML tags
$formatted_message = preg_replace('/[\x00-\x1F\x7F]/u', '', $formatted_message); // Remove control characters
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Message</title>
</head>
<body>
<div class="modal-content">
    <span class="close">×</span>
    <h2>Contact Message #<?= htmlspecialchars($message['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
    
    <h3>Details</h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($message['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($message['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Subject:</strong> <?= htmlspecialchars($message['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Message:</strong></p>
    <pre style="white-space: pre-wrap; font-family: inherit;"><?= $formatted_message ?></pre>
    <p><strong>Received At:</strong> <?= date('d-M-Y H:i', strtotime($message['created_at'] ?? 'now')) ?></p>
</div>
</body>
</html>