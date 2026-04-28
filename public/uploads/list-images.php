<?php
// Set the upload directory
$uploadDir = dirname(__DIR__, 2) . '/public/uploads/';

// Ensure the uploads directory exists
if (!is_dir($uploadDir)) {
    die("Directory does not exist or is not readable: " . $uploadDir); // Output error if directory is wrong
}

// Get all files in the uploads directory
$files = array_diff(scandir($uploadDir), array('..', '.'));  // Ignore . and ..

// Filter image files by extensions (jpg, jpeg, png, gif, webp)
$imageFiles = array_filter($files, function($file) use ($uploadDir) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
});

// Generate the fully qualified URLs for the images and return them as a numeric array
$imageUrls = array_map(function($file) {
    return 'http://custom.local/public/uploads/' . $file; // Return full URL to the image
}, $imageFiles);

// Return the list of image URLs as a JSON array (not an object)
echo json_encode(array_values($imageUrls));  // Ensure the response is an array
?>
