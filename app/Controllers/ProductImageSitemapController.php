<?php
// ProductImageSitemapController.php
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../Models/ProductImageSitemapModel.php'; // Include the model

class ProductImageSitemapController {
    private $db;

    // Constructor to initialize DB connection
    public function __construct() {
        $database = new Database(); // Instantiate the Database class
        $this->db = $database->getConnection(); // Get the database connection
    }

    public function generateSitemap() {
        // Ensure no output before starting the XML
        ob_clean(); // Clears the output buffer (if any)

        // Set the header to tell the browser it's XML content
        header("Content-Type: application/xml; charset=UTF-8");

        // Start the XML document (this must be the first thing printed)
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Initialize the ProductImageSitemapModel with the DB connection
        try {
            $productImageSitemapModel = new ProductImageSitemapModel($this->db);
            $images = $productImageSitemapModel->getProductImages();

            // Check if any images were found
            if (empty($images)) {
                echo '<error>No images found</error>';
                return;  // Exit if no images are found
            }

            // Loop through the images and generate the XML
            foreach ($images as $image) {
                // Construct the image URL in the desired format
                $image_url = 'https://www.phonesdukan.com' . $image['image_url'];

                echo '  <url>' . PHP_EOL;
                echo '    <loc>' . htmlspecialchars($image_url) . '</loc>' . PHP_EOL;
                echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
                echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
                echo '    <priority>0.5</priority>' . PHP_EOL;
                echo '  </url>' . PHP_EOL;
            }

        } catch (Exception $e) {
            // Catch errors, for example, database connection failure
            echo "<error>" . $e->getMessage() . "</error>";
        }

        echo '</urlset>' . PHP_EOL;
    }
}

// Instantiate the ProductImageSitemapController and pass the DB connection
$productImageSitemapController = new ProductImageSitemapController();
$productImageSitemapController->generateSitemap();
?>
