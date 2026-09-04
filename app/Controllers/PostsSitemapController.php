<?php
require_once __DIR__ . '/../Models/PostsSitemapModel.php';
require_once __DIR__ . '/../../database/db.php';

class PostsSitemapController {
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
            $model = new PostsSitemapModel($this->db);
            $posts = $model->getAllPublishedPosts();

            if (empty($posts)) {
                echo '<error>No published posts found</error>' . PHP_EOL;
            } else {
                foreach ($posts as $post) {
                    echo '  <url>' . PHP_EOL;
                    echo '    <loc>' . htmlspecialchars($post['url']) . '</loc>' . PHP_EOL;
                    echo '    <lastmod>' . $post['lastmod'] . '</lastmod>' . PHP_EOL;
                    echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
                    echo '    <priority>0.4</priority>' . PHP_EOL;
                    echo '  </url>' . PHP_EOL;
                }
            }
        } catch (Exception $e) {
            error_log("Posts Sitemap Error: " . $e->getMessage());
            echo '<error>Failed to generate sitemap: ' . htmlspecialchars($e->getMessage()) . '</error>' . PHP_EOL;
        }

        echo '</urlset>' . PHP_EOL;
    }
}

$controller = new PostsSitemapController();
$controller->generateSitemap();
?>