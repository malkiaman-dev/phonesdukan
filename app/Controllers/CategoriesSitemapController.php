<?php
require_once __DIR__ . '/../Models/CategoriesSitemapModel.php';
require_once __DIR__ . '/../../database/db.php';

class CategoriesSitemapController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function generateSitemap() {
        ob_clean();
        header("Content-Type: application/xml; charset=UTF-8");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        try {
            $model = new CategoriesSitemapModel($this->db);
            $categories = $model->getAllPublishedCategories();

            if (empty($categories)) {
                echo '<error>No published categories found</error>' . PHP_EOL;
            } else {
                foreach ($categories as $category) {
                    echo '  <url>' . PHP_EOL;
                    echo '    <loc>' . htmlspecialchars($category['url']) . '</loc>' . PHP_EOL;
                    echo '    <lastmod>' . $category['lastmod'] . '</lastmod>' . PHP_EOL;
                    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
                    echo '    <priority>0.9</priority>' . PHP_EOL;
                    echo '  </url>' . PHP_EOL;
                }
            }
        } catch (Exception $e) {
            error_log("Categories Sitemap Error: " . $e->getMessage());
            echo '<error>Failed to generate sitemap: ' . htmlspecialchars($e->getMessage()) . '</error>' . PHP_EOL;
        }

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new CategoriesSitemapController();
$controller->generateSitemap();
?>