<?php

class CategoryController {
    // Handles category pages (e.g., /mobiles, /smartwatches)
    public function showCategory($category_slug) {
        $view_path = __DIR__ . "/../Views/{$category_slug}.php"; // Dynamically set the path

        if (file_exists($view_path)) {
            include $view_path;
        } else {
            include __DIR__ . '/../Views/404.php'; // Show 404 if category page is missing
        }
    } 
}

?>
