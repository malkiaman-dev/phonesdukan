<?php
// ProductSitemapController.php
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../Models/ProductSitemapModel.php'; // Include the model

class ProductSitemapController {
    private $db;

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
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;
    
        // Always use the canonical www domain to prevent duplicate-content issues
        $domain = 'https://www.phonesdukan.com';
    
        // Initialize the ProductSitemapModel with the DB connection
        try {
            $productSitemapModel = new ProductSitemapModel($this->db);
            $product_slugs = $productSitemapModel->getAllProductSlugs();
    
            // Check if any product slugs were found
            if (empty($product_slugs)) {
                echo '<error>No products found</error>';
                return;  // Exit if no products are found
            }
    
            // Loop through the product slugs and generate the XML
            foreach ($product_slugs as $product) {
                // Construct the product URL in the desired format
                require_once dirname(__DIR__, 2) . '/includes/functions.php';
                $product_url = rtrim($domain, '/') . buildProductPathFromRow($product) . '/';
    
                // Fetch the product images
                $images = $productSitemapModel->getProductImages($product['product_id']);
                $lastmod = !empty($product['updated_at'])
                    ? date('c', strtotime((string) $product['updated_at']))
                    : date('c');
                
                echo '  <url>' . PHP_EOL;
                echo '    <loc>' . htmlspecialchars($product_url) . '</loc>' . PHP_EOL;
                echo '    <lastmod>' . htmlspecialchars($lastmod) . '</lastmod>' . PHP_EOL;
                echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
                echo '    <priority>0.9</priority>' . PHP_EOL;
    
                // Loop through images and add image tags
                foreach ($images as $image) {
                    // Dynamically prepend the domain to the image URL
                    $image_url = $domain . htmlspecialchars($image['image_url']);
                    $product_name = htmlspecialchars($product['product_name']);
                    $image_caption = $product_name;
                    $image_title = $product_name . ' Image';
    
                    echo '    <image:image>' . PHP_EOL;
                    echo '      <image:loc>' . $image_url . '</image:loc>' . PHP_EOL;
                    echo '      <image:caption>' . $image_caption . '</image:caption>' . PHP_EOL;
                    echo '      <image:title>' . $image_title . '</image:title>' . PHP_EOL;
                    echo '    </image:image>' . PHP_EOL;
                }
    
                echo '  </url>' . PHP_EOL;
            }
    
        } catch (Exception $e) {
            // Catch errors, for example, database connection failure
            echo "<error>" . $e->getMessage() . "</error>";
        }
    
        echo '</urlset>' . PHP_EOL;
    }
}
// Instantiate the ProductSitemapController and pass the DB connection
$productSitemapController = new ProductSitemapController();
$productSitemapController->generateSitemap();

?>