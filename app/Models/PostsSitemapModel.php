<?php
require_once __DIR__ . '/../../database/db.php';

class PostsSitemapModel {
    private $db;

    public function __construct($db) {
        if ($db === null || !$db instanceof PDO) {
            throw new Exception('Database connection is not provided or invalid.');
        }
        $this->db = $db;
    }

    public function getAllPublishedPosts() {
        $allPosts = [];

        try {
            $query = "
                SELECT 
                    p.id,
                    p.slug AS post_slug, 
                    p.updated_at, 
                    COALESCE(c.slug, 'uncategorized') AS category_slug
                FROM posts p
                LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
                LEFT JOIN post_categories c ON pcm.category_id = c.id
                WHERE p.status = 'published'
                GROUP BY p.id, p.slug, p.updated_at, c.slug
                ORDER BY pcm.created_at ASC
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $baseUrl = 'https://www.phonesdukan.com/blog';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $allPosts[] = [
                    'url' => rtrim($baseUrl, '/') . '/' . trim($row['category_slug'], '/') . '/' . trim($row['post_slug'], '/') . '/',
                    'lastmod' => date('c', strtotime($row['updated_at'] ?? 'now'))
                ];
            }
        } catch (Exception $e) {
            error_log("PostsSitemapModel Error: " . $e->getMessage());
            throw $e;
        }

        return $allPosts;
    }
}
?>