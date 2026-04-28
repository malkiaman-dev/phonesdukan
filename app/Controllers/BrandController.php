<?php
class BrandController {
    public function showBrand($category, $brand) {
        // Load the brand page dynamically based on category and brand
        $view_file = __DIR__ . "/../Views/brands/{$category}_{$brand}.php";
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            include __DIR__ . '/../Views/404.php'; // Show 404 if the brand page doesn't exist
        }
    }
}
?>
