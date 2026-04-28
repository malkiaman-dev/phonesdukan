<?php
require_once __DIR__ . '/../../database/db.php';

class CategoriesSitemapModel {
    private $db;

    public function __construct($db) {
        if ($db === null || !$db instanceof PDO) {
            throw new Exception('Database connection is not provided or invalid.');
        }
        $this->db = $db;
    }

    public function getAllPublishedCategories() {
        $allCategories = [];
        try {
            $query = "
                SELECT
                    id,
                    slug AS category_slug,
                    updated_at
                FROM post_categories
                WHERE status = 1
                AND slug != 'news'
                ORDER BY updated_at DESC
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $baseUrl = 'https://www.phonesdukan.com/blog';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $allCategories[] = [
                    'url' => rtrim($baseUrl, '/') . '/' . trim($row['category_slug'], '/') . '/',
                    'lastmod' => date('c', strtotime($row['updated_at'] ?? 'now'))
                ];
            }
        } catch (Exception $e) {
            error_log("CategoriesSitemapModel Error: " . $e->getMessage() . " in " . __FILE__ . " at line " . __LINE__);
            throw $e;
        }
        return $allCategories;
    }
}
?>