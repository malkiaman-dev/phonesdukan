<?php
require_once __DIR__ . '/../../database/db.php';

class NewsSitemapModel {
    private $db;

    public function __construct($db) {
        if ($db === null || !$db instanceof PDO) {
            throw new Exception('Database connection is not provided or invalid.');
        }
        $this->db = $db;
    }

    public function getRecentNewsArticles() {
        $newsArticles = [];
        try {
            // Assuming a junction table `post_category_mappings` exists
            $query = "
                SELECT
                    p.slug AS post_slug,
                    p.title,
                    p.published_at,
                    c.slug AS category_slug
                FROM posts p
                INNER JOIN post_category_mappings pcm ON p.id = pcm.post_id
                INNER JOIN post_categories c ON pcm.category_id = c.id
                WHERE p.status = 'published'
                AND c.slug = 'news'
                AND p.published_at >= :two_days_ago
                ORDER BY p.published_at DESC
            ";
            $stmt = $this->db->prepare($query);
            $two_days_ago = date('Y-m-d H:i:s', strtotime('-48 hours'));
            $stmt->bindParam(':two_days_ago', $two_days_ago, PDO::PARAM_STR);
            $stmt->execute();

            $baseUrl = 'https://www.phonesdukan.com';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $newsArticles[] = [
                    'url' => rtrim($baseUrl, '/') . '/' . trim($row['category_slug'], '/') . '/' . trim($row['post_slug'], '/') . '/',
                    'title' => htmlspecialchars($row['title']),
                    'publication_date' => date('c', strtotime($row['published_at'] ?? 'now'))
                ];
            }
        } catch (Exception $e) {
            error_log("NewsSitemapModel Error: " . $e->getMessage() . " in " . __FILE__ . " at line " . __LINE__);
            throw $e;
        }
        return $newsArticles;
    }
}
?>