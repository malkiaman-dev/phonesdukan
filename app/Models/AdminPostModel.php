<?php
require_once __DIR__ . '/../../database/db.php';

class AdminPostModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllPosts($status_filter = 'all', $search_query = '') {
        $query = "
            SELECT 
                p.id, p.title, p.slug, p.status, p.updated_at,
                GROUP_CONCAT(COALESCE(pc.category_name, 'None') SEPARATOR ', ') AS categories,
                pc.slug AS category_slug,
                pi.image_url, pi.alt_text
            FROM posts p
            LEFT JOIN post_category_mappings pcm ON p.id = pcm.post_id
            LEFT JOIN post_categories pc ON pcm.category_id = pc.id
            LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.is_main = 1
        ";
        $whereClauses = [];
        $params = [];
        if ($status_filter === 'published') {
            $whereClauses[] = "p.status = :status";
            $params[':status'] = 'published';
        } elseif ($status_filter === 'draft') {
            $whereClauses[] = "p.status = :status";
            $params[':status'] = 'draft';
        }
        if (!empty($search_query)) {
            $whereClauses[] = "p.title LIKE :search_query";
            $params[':search_query'] = '%' . $search_query . '%';
        }
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }
        $query .= " GROUP BY p.id ORDER BY p.updated_at DESC";


        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("AdminPostModel: Failed to prepare query: " . print_r($this->db->errorInfo(), true));
            return [];
        }
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $posts ?: [];
        } catch (PDOException $e) {
            error_log("AdminPostModel: getAllPosts Error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllCategories() {
        $query = "SELECT id, category_name, slug, status FROM post_categories ORDER BY category_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostById($id) {
        $query = "
            SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.status, p.updated_at, p.published_at,
                   ps.meta_title, ps.meta_description, ps.meta_keywords
            FROM posts p
            LEFT JOIN post_seo ps ON p.id = ps.post_id
            WHERE p.id = :id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getPostById Error: " . $e->getMessage());
            throw new Exception("Failed to fetch post: " . $e->getMessage());
        }
    }

    public function getPostImages($post_id) {
        $query = "
            SELECT id, image_url, is_main, alt_text, caption, image_name
            FROM post_images
            WHERE post_id = :post_id
            ORDER BY is_main DESC, created_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostCategories($post_id) {
        $query = "
            SELECT pc.id, pc.category_name, pc.slug
            FROM post_category_mappings pcm
            JOIN post_categories pc ON pcm.category_id = pc.id
            WHERE pcm.post_id = :post_id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isSlugUnique($slug, $exclude_post_id = null) {
        $query = "SELECT COUNT(*) FROM posts WHERE slug = :slug";
        if ($exclude_post_id) {
            $query .= " AND id != :exclude_post_id";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        if ($exclude_post_id) {
            $stmt->bindValue(':exclude_post_id', $exclude_post_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count == 0;
    }

    public function getSeoRowCount($post_id) {
        $query = "SELECT COUNT(*) FROM post_seo WHERE post_id = :post_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $count = $stmt->fetchColumn();
            return $count;
        } catch (PDOException $e) {
            error_log("getSeoRowCount Error: " . $e->getMessage());
            throw new Exception("Failed to count SEO rows: " . $e->getMessage());
        }
    }

    public function createPost($data, $author_id) {
        if (!$this->isSlugUnique($data['slug'])) {
            throw new Exception("Slug is already in use.");
        }

        $this->db->beginTransaction();
        try {
            $query = "
                INSERT INTO posts (title, slug, excerpt, content, author_id, status, created_at, updated_at, published_at)
                VALUES (:title, :slug, :excerpt, :content, :author_id, :status, NOW(), NOW(), :published_at)
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':excerpt' => $data['excerpt'],
                ':content' => $data['content'],
                ':author_id' => $author_id,
                ':status' => $data['status'],
                ':published_at' => $data['published_at']
            ]);
            $post_id = $this->db->lastInsertId();

            $query = "
                INSERT INTO post_seo (post_id, meta_title, meta_description, meta_keywords, created_at, updated_at)
                VALUES (:post_id, :meta_title, :meta_description, :meta_keywords, NOW(), NOW())
            ";
            $stmt = $this->db->prepare($query);
            try {
                $stmt->execute([
                    ':post_id' => $post_id,
                    ':meta_title' => $data['meta_title'] !== '' ? $data['meta_title'] : null,
                    ':meta_description' => $data['meta_description'] !== '' ? $data['meta_description'] : null,
                    ':meta_keywords' => $data['meta_keywords'] !== '' ? $data['meta_keywords'] : null
                ]);
            } catch (PDOException $e) {
                error_log("SEO Insert Error: " . $e->getMessage());
                throw new Exception("Failed to insert SEO data: " . $e->getMessage());
            }

            if (!empty($data['categories']) && is_array($data['categories'])) {
                $query = "INSERT INTO post_category_mappings (post_id, category_id, created_at) VALUES (:post_id, :category_id, NOW())";
                $stmt = $this->db->prepare($query);
                foreach ($data['categories'] as $category_id) {
                    $stmt->execute([
                        ':post_id' => $post_id,
                        ':category_id' => (int)$category_id
                    ]);
                }
            }

            if (!empty($data['images']) && is_array($data['images'])) {
                $query = "
                    INSERT INTO post_images (post_id, image_url, image_name, is_main, alt_text, caption, created_at)
                    VALUES (:post_id, :image_url, :image_name, :is_main, :alt_text, :caption, NOW())
                ";
                $stmt = $this->db->prepare($query);
                foreach ($data['images'] as $image) {
                    $stmt->execute([
                        ':post_id' => $post_id,
                        ':image_url' => $image['url'],
                        ':image_name' => $image['name'] ?? null,
                        ':is_main' => $image['is_main'] ?? 0,
                        ':alt_text' => $image['alt_text'] ?? null,
                        ':caption' => $image['caption'] ?? null
                    ]);
                }
            }

            $this->db->commit();
            return $post_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Create Post Error: " . $e->getMessage());
            throw new Exception("Error creating post: " . $e->getMessage());
        }
    }

    public function updatePost($post_id, $data) {
        if (!$this->isSlugUnique($data['slug'], $post_id)) {
            throw new Exception("Slug is already in use by another post.");
        }
    
    
        $this->db->beginTransaction();
        try {
            // Update posts table
            $query = "
                UPDATE posts
                SET title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                    status = :status, updated_at = :updated_at, published_at = :published_at
                WHERE id = :post_id
            ";
            $stmt = $this->db->prepare($query);
            $params = [
                ':post_id' => $post_id,
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':excerpt' => $data['excerpt'],
                ':content' => $data['content'],
                ':status' => $data['status'],
                ':updated_at' => !empty($data['updated_at']) ? date('Y-m-d H:i:s', strtotime($data['updated_at'])) : date('Y-m-d H:i:s'),
                ':published_at' => !empty($data['published_at']) ? $data['published_at'] : ($data['status'] === 'published' ? date('Y-m-d H:i:s') : null)
            ];
            $stmt->execute($params);
    
            // Check if a post_seo record exists
            $seo_query = "SELECT id FROM post_seo WHERE post_id = :post_id";
            $seo_stmt = $this->db->prepare($seo_query);
            $seo_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
            $seo_stmt->execute();
            $seo_record = $seo_stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($seo_record) {
                // Update existing post_seo record
                $query = "
                    UPDATE post_seo
                    SET meta_title = :meta_title, meta_description = :meta_description,
                        meta_keywords = :meta_keywords, updated_at = NOW()
                    WHERE post_id = :post_id
                ";
                $stmt = $this->db->prepare($query);
                $params = [
                    ':post_id' => $post_id,
                    ':meta_title' => $data['meta_title'] !== '' ? $data['meta_title'] : null,
                    ':meta_description' => $data['meta_description'] !== '' ? $data['meta_description'] : null,
                    ':meta_keywords' => $data['meta_keywords'] !== '' ? $data['meta_keywords'] : null
                ];
                $stmt->execute($params);
            } else {
                // Insert new post_seo record
                $query = "
                    INSERT INTO post_seo (post_id, meta_title, meta_description, meta_keywords, created_at, updated_at)
                    VALUES (:post_id, :meta_title, :meta_description, :meta_keywords, NOW(), NOW())
                ";
                $stmt = $this->db->prepare($query);
                $params = [
                    ':post_id' => $post_id,
                    ':meta_title' => $data['meta_title'] !== '' ? $data['meta_title'] : null,
                    ':meta_description' => $data['meta_description'] !== '' ? $data['meta_description'] : null,
                    ':meta_keywords' => $data['meta_keywords'] !== '' ? $data['meta_keywords'] : null
                ];
                $stmt->execute($params);
            }
    
            // Delete existing category mappings
            $query = "DELETE FROM post_category_mappings WHERE post_id = :post_id";
            $stmt = $this->db->prepare($query);
            $params = [':post_id' => $post_id];
            $stmt->execute($params);
    
            // Insert new category mappings
            if (!empty($data['categories']) && is_array($data['categories'])) {
                $query = "INSERT INTO post_category_mappings (post_id, category_id, created_at) VALUES (:post_id, :category_id, NOW())";
                $stmt = $this->db->prepare($query);
                foreach ($data['categories'] as $category_id) {
                    $params = [
                        ':post_id' => $post_id,
                        ':category_id' => (int)$category_id
                    ];
                    $stmt->execute($params);
                }
            }
    
            // Delete specified images
            if (!empty($data['remove_image_ids']) && is_array($data['remove_image_ids'])) {
                $placeholders = implode(',', array_fill(0, count($data['remove_image_ids']), '?'));
                $query = "DELETE FROM post_images WHERE post_id = :post_id AND id IN ($placeholders)";
                $stmt = $this->db->prepare($query);
                $params = array_merge([':post_id' => $post_id], array_map('intval', $data['remove_image_ids']));
                $stmt->execute($params);
            }
    
            // Update existing images
            if (!empty($data['existing_images']) && is_array($data['existing_images'])) {
                $query = "
                    UPDATE post_images
                    SET alt_text = :alt_text, caption = :caption, is_main = :is_main
                    WHERE id = :image_id AND post_id = :post_id
                ";
                $stmt = $this->db->prepare($query);
                foreach ($data['existing_images'] as $image_id => $image_data) {
                    $is_main = isset($image_data['is_main']) && $image_data['is_main'] ? 1 : 0;
                    $params = [
                        ':image_id' => (int)$image_id,
                        ':post_id' => $post_id,
                        ':alt_text' => $image_data['alt_text'] ?: null,
                        ':caption' => $image_data['caption'] ?: null,
                        ':is_main' => $is_main
                    ];
                    $stmt->execute($params);
                }
            }
    
            // Insert new images
            if (!empty($data['images']) && is_array($data['images'])) {
                $query = "
                    INSERT INTO post_images (post_id, image_url, image_name, is_main, alt_text, caption, created_at)
                    VALUES (:post_id, :image_url, :image_name, :is_main, :alt_text, :caption, NOW())
                ";
                $stmt = $this->db->prepare($query);
                foreach ($data['images'] as $image) {
                    $is_main = isset($image['is_main']) && $image['is_main'] ? 1 : 0;
                    $params = [
                        ':post_id' => $post_id,
                        ':image_url' => $image['url'],
                        ':image_name' => $image['name'] ?: null,
                        ':is_main' => $is_main,
                        ':alt_text' => $image['alt_text'] ?: null,
                        ':caption' => $image['caption'] ?: null
                    ];
                    $stmt->execute($params);
                }
            }
    
            // Ensure only one image is main
            $query = "UPDATE post_images SET is_main = 0 WHERE post_id = :post_id AND is_main = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':post_id' => $post_id]);
    
            $main_image_id = $_POST['main_image_id'] ?? '';
            if ($main_image_id) {
                if (strpos($main_image_id, 'new_') === 0) {
                    // Find the new image by temp_id
                    foreach ($data['images'] as $image) {
                        if ($image['temp_id'] === $main_image_id) {
                            $query = "UPDATE post_images SET is_main = 1 WHERE post_id = :post_id AND image_url = :image_url";
                            $stmt = $this->db->prepare($query);
                            $stmt->execute([
                                ':post_id' => $post_id,
                                ':image_url' => $image['url']
                            ]);
                            break;
                        }
                    }
                } else {
                    $query = "UPDATE post_images SET is_main = 1 WHERE post_id = :post_id AND id = :image_id";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':post_id' => $post_id,
                        ':image_id' => (int)$main_image_id
                    ]);
                }
            }
    
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update Post Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            throw new Exception("Error updating post: " . $e->getMessage());
        }
    }

    public function deleteImage($image_id, $post_id) {
        $query = "DELETE FROM post_images WHERE id = :image_id AND post_id = :post_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':image_id' => $image_id,
            ':post_id' => $post_id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function createCategory($data) {
        if (!$this->isCategorySlugUnique($data['slug'])) {
            throw new Exception("Category slug is already in use.");
        }

        $query = "
            INSERT INTO post_categories (category_name, slug, status, created_at, updated_at)
            VALUES (:category_name, :slug, :status, NOW(), NOW())
        ";
        $stmt = $this->db->prepare($query);
        try {
            $stmt->execute([
                ':category_name' => $data['category_name'],
                ':slug' => $data['slug'],
                ':status' => $data['status']
            ]);
        } catch (PDOException $e) {
            error_log("Create Category Error: " . $e->getMessage());
            throw new Exception("Failed to create category: " . $e->getMessage());
        }
    }

    public function updateCategory($category_id, $data) {
        if (!$this->isCategorySlugUnique($data['slug'], $category_id)) {
            throw new Exception("Category slug is already in use by another category.");
        }

        $query = "
            UPDATE post_categories
            SET category_name = :category_name, slug = :slug, status = :status, updated_at = NOW()
            WHERE id = :category_id
        ";
        $stmt = $this->db->prepare($query);
        $params = [
            ':category_id' => $category_id,
            ':category_name' => $data['category_name'],
            ':slug' => $data['slug'],
            ':status' => $data['status']
        ];
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update Category Error: " . $e->getMessage());
            throw new Exception("Failed to update category: " . $e->getMessage());
        }
    }

    public function deleteCategory($category_id) {
        // Check if category is linked to any posts
        $query = "SELECT COUNT(*) FROM post_category_mappings WHERE category_id = :category_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Cannot delete category because it is linked to $count post(s).");
        }

        $query = "DELETE FROM post_categories WHERE id = :category_id";
        $stmt = $this->db->prepare($query);
        try {
            $stmt->execute([':category_id' => $category_id]);
        } catch (PDOException $e) {
            error_log("Delete Category Error: " . $e->getMessage());
            throw new Exception("Failed to delete category: " . $e->getMessage());
        }
    }

    public function isCategorySlugUnique($slug, $exclude_category_id = null) {
        $query = "SELECT COUNT(*) FROM post_categories WHERE slug = :slug";
        if ($exclude_category_id) {
            $query .= " AND id != :exclude_category_id";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        if ($exclude_category_id) {
            $stmt->bindValue(':exclude_category_id', $exclude_category_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count == 0;
    }
}
?>