<?php
require_once __DIR__ . '/../../database/db.php';

class PostModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    private function addActivePostCondition(&$whereClauses) {
        $whereClauses[] = "p.status = 'published' AND p.published_at <= NOW()";
    }

    public function getAllCategories() {
        $query = "SELECT id, category_name, slug FROM post_categories WHERE status = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginatedPosts($limit, $offset, $category_slug = null) {
        $whereClauses = [];
        $params = [];

        $this->addActivePostCondition($whereClauses);

        $query = "
            SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at,
                   pi.image_url, pi.alt_text, pi.caption,
                   ps.meta_title, ps.meta_description,
                   pc.category_name, pc.slug AS category_slug
            FROM posts p
            LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.is_main = 1
            LEFT JOIN post_seo ps ON p.id = ps.post_id
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
        ";

        if ($category_slug) {
            $whereClauses[] = "pc.slug = :category_slug";
            $params[':category_slug'] = $category_slug;
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $query .= ' ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalPostCount($category_slug = null) {
        $whereClauses = [];
        $params = [];

        $this->addActivePostCondition($whereClauses);

        $query = "
            SELECT COUNT(DISTINCT p.id)
            FROM posts p
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
        ";

        if ($category_slug) {
            $whereClauses[] = "pc.slug = :category_slug";
            $params[':category_slug'] = $category_slug;
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getPostBySlug($slug, $category_slug = null) {
        $whereClauses = [];
        $params = [];
        
        $this->addActivePostCondition($whereClauses);
        $whereClauses[] = "p.slug = :slug";
        $params[':slug'] = $slug;
        
        if ($category_slug) {
            $whereClauses[] = "pc.slug = :category_slug";
            $params[':category_slug'] = $category_slug;
        }
        
        $query = "
            SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at, p.updated_at,
                   pi.image_url, pi.alt_text, pi.caption,
                   ps.meta_title, ps.meta_description,
                   pc.category_name, pc.slug AS category_slug
            FROM posts p
            LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.is_main = 1
            LEFT JOIN post_seo ps ON p.id = ps.post_id
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            error_log("getPostBySlug: slug=$slug, category_slug=" . ($category_slug ?: 'none') . 
                     ", found=yes, published_at={$post['published_at']}, updated_at={$post['updated_at']}");
        } else {
            error_log("getPostBySlug: slug=$slug, category_slug=" . ($category_slug ?: 'none') . ", found=no");
        }
        return $post;
    }

    public function getDefaultCategory() {
        $query = "SELECT id, category_name, slug FROM post_categories WHERE status = 1 ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getNonPrimaryImages($post_id) {
        $query = "
            SELECT id, image_url, alt_text, caption
            FROM post_images
            WHERE post_id = :post_id AND is_main = 0
            ORDER BY created_at ASC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchPosts($search_term, $limit, $offset) {
        $whereClauses = [];
        $params = [];
    
        $this->addActivePostCondition($whereClauses);
        $whereClauses[] = "(p.title LIKE :search_term OR p.excerpt LIKE :search_term OR p.content LIKE :search_term)";
        $params[':search_term'] = '%' . $search_term . '%';
    
        $query = "
            SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at,
                   pi.image_url, pi.alt_text, pi.caption,
                   ps.meta_title, ps.meta_description,
                   pc.category_name, pc.slug AS category_slug
            FROM posts p
            LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.is_main = 1
            LEFT JOIN post_seo ps ON p.id = ps.post_id
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY p.published_at DESC
            LIMIT :limit OFFSET :offset
        ";
    
        $stmt = $this->db->prepare($query);
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSearchPostCount($search_term) {
        $whereClauses = [];
        $params = [];
    
        $this->addActivePostCondition($whereClauses);
        $whereClauses[] = "(p.title LIKE :search_term OR p.excerpt LIKE :search_term OR p.content LIKE :search_term)";
        $params[':search_term'] = '%' . $search_term . '%';
    
        $query = "
            SELECT COUNT(DISTINCT p.id)
            FROM posts p
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE " . implode(' AND ', $whereClauses);
    
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getPostSuggestions($search_term, $limit = 5) {
        $whereClauses = [];
        $params = [];
    
        $this->addActivePostCondition($whereClauses);
        $whereClauses[] = "(p.title LIKE :search_term)";
        $params[':search_term'] = '%' . $search_term . '%';
    
        $query = "
            SELECT p.title, p.slug, pc.slug AS category_slug
            FROM posts p
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY p.published_at DESC
            LIMIT :limit
        ";
    
        $stmt = $this->db->prepare($query);
        $params[':limit'] = (int)$limit;
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryBySlug($category_slug) {
        $query = "SELECT id, category_name, slug FROM post_categories WHERE slug = :category_slug AND status = 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':category_slug', $category_slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Updated method to fetch phone models grouped by brand
    public function getPhoneModels() {
        $query = "
            SELECT brand, model_name, price_pkr, pta_tax_passport, pta_tax_cnic
            FROM phone_models
            ORDER BY brand ASC, updated_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by brand
        $grouped_models = [];
        foreach ($models as $model) {
            $brand = trim(ucfirst(strtolower($model['brand']))); // Normalize brand name
            if (!isset($grouped_models[$brand])) {
                $grouped_models[$brand] = [];
            }
            $grouped_models[$brand][] = $model;
        }
        return $grouped_models;
    }
}
?>