<?php
// CategorySitemapController.php
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../Models/ProductCatSitemapModel.php'; // Include the model

class CategorySitemapController {
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

        // Initialize the CategorySitemapModel with the DB connection
        try {
            $categorySitemapModel = new CategorySitemapModel($this->db);
            $category_slugs = $categorySitemapModel->getAllCategorySlugs();

            // Check if any category slugs were found
            if (empty($category_slugs)) {
                echo '<error>No categories found</error>';
                return;  // Exit if no categories are found
            }

            // Loop through the category slugs and generate the XML
            foreach ($category_slugs as $category) {
                // Construct the category URL in the desired format
                $category_url = 'https://www.phonesdukan.com/' . $category['category_slug'];

                echo '  <url>' . PHP_EOL;
                echo '    <loc>' . htmlspecialchars($category_url) . '</loc>' . PHP_EOL;
                echo '    <lastmod>' . date('c') . '</lastmod>' . PHP_EOL;
                echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
                echo '    <priority>0.7</priority>' . PHP_EOL;
                echo '  </url>' . PHP_EOL;
            }

        } catch (Exception $e) {
            // Catch errors, for example, database connection failure
            echo "<error>" . $e->getMessage() . "</error>";
        }

        echo '</urlset>' . PHP_EOL;
    }
}

// Instantiate the CategorySitemapController and pass the DB connection
$categorySitemapController = new CategorySitemapController();
$categorySitemapController->generateSitemap();
?>
