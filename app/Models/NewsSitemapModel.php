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

    public function getRecentNewsArticles(): array
    {
        $newsArticles = [];
        try {
            // posts.category_id is a direct FK to post_categories.id — no junction table.
            // Google News requires articles within 2 days, but we use 14 days so the
            // sitemap is never empty on slow publishing weeks, avoiding the "Missing url
            // tag" GSC error. Google still processes the extra articles as regular news.
            $query = "
                SELECT p.slug AS post_slug, p.title, p.published_at
                FROM posts p
                INNER JOIN post_categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                  AND c.slug = 'news'
                  AND p.published_at >= :cutoff
                ORDER BY p.published_at DESC
                LIMIT 50
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':cutoff', date('Y-m-d H:i:s', strtotime('-14 days')), PDO::PARAM_STR);
            $stmt->execute();

            // News posts are routed at /news/{slug} (see routes.php)
            $baseUrl = 'https://www.phonesdukan.com';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $newsArticles[] = [
                    'url'              => $baseUrl . '/news/' . trim($row['post_slug'], '/') . '/',
                    'title'            => htmlspecialchars($row['title'], ENT_XML1, 'UTF-8'),
                    'publication_date' => date('c', strtotime($row['published_at'] ?? 'now')),
                ];
            }
        } catch (Exception $e) {
            error_log('NewsSitemapModel Error: ' . $e->getMessage());
            throw $e;
        }
        return $newsArticles;
    }
}
?>