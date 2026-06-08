<?php
require_once __DIR__ . '/../../database/db.php';  // Ensure correct database connection

class ProductModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }


    public function getProductsByIds($product_ids)
    {
        if (empty($product_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

        $query = "SELECT p.product_id, p.product_name, p.product_slug, 
        COALESCE(p.regular_price, 0) AS regular_price, 
        NULLIF(p.sale_price, '') AS sale_price,  
        COALESCE(p.stock_quantity, 0) AS stock_quantity,
        p.product_status,
        p.product_tag,
        pi.image_url,
        c.slug AS category_slug,
        b.slug AS brand_slug
 FROM products p
 LEFT JOIN product_images pi ON p.product_id = pi.product_id
 LEFT JOIN categories c ON p.category_id = c.category_id 
 LEFT JOIN brands b ON p.brand_id = b.brand_id
 WHERE p.product_id IN ($placeholders)
   AND p.product_status != '0'
   AND LOWER(p.product_status) != 'out of stock'
 ORDER BY FIELD(p.product_id, " . implode(',', $product_ids) . ")";


        $stmt = $this->db->prepare($query);
        foreach ($product_ids as $index => $id) {
            $stmt->bindValue(($index + 1), $id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cards = [];
        foreach ($products as $product) {
            $cards[] = prepareProductCardFromRow($product);
        }

        return $cards;
    }

    // For coming soon
    public function getComingSoonProducts($limit = 12)
    {
        $stmt = $this->db->prepare("
            SELECT p.product_id, 
                   p.product_name, 
                   p.product_slug, 
                   p.regular_price, 
                   p.sale_price,
                   p.expected_coming_date,
                   pi.image_url,
                   c.slug AS category_slug, 
                   b.slug AS brand_slug
            FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            WHERE (p.product_status = 2 OR p.product_tag LIKE '%coming_soon%')
            AND p.product_status != '0'
            AND LOWER(p.product_status) != 'out of stock'
            GROUP BY p.product_id
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getProductsByCategory($category_id, $limit = 12)
    {
        $query = "SELECT p.product_id, p.product_name, p.product_slug, 
                         COALESCE(p.regular_price, 0) AS regular_price, 
                         NULLIF(p.sale_price, '') AS sale_price,  
                         COALESCE(p.stock_quantity, 0) AS stock_quantity,
                         p.product_status,
                         p.product_tag,
                         pi.image_url,
                         c.slug AS category_slug,
                         b.slug AS brand_slug
                  FROM products p
                  LEFT JOIN product_images pi ON p.product_id = pi.product_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  LEFT JOIN brands b ON p.brand_id = b.brand_id
                  WHERE p.category_id = ? 
                  ORDER BY p.created_at DESC
                  LIMIT ?";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // for smartwatch
    public function getSmartWatches($limit = 12)
    {
        $stmt = $this->db->prepare('
        SELECT p.product_id, p.product_name, p.product_slug, 
               p.regular_price, p.sale_price, p.stock_quantity,
               p.product_status, p.product_tag,
               pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.category_id = 11 AND p.product_status != 0
        ORDER BY p.product_id DESC
        LIMIT :limit
    ');
    
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory13Products($limit = 12)
    {
        $stmt = $this->db->prepare('
            SELECT 
                p.product_id, 
                p.product_name, 
                p.product_slug, 
                p.regular_price, 
                p.sale_price, 
                p.stock_quantity,
                p.product_status,
                p.product_tag,
                pi.image_url, 
                c.slug AS category_slug, 
                b.slug AS brand_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE 
                p.category_id = 13
                AND p.product_status != "0"
                AND LOWER(p.product_status) != "out of stock"
            ORDER BY p.product_id DESC
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getCategory2Products($limit = 12)
    {
        $stmt = $this->db->prepare('
        SELECT p.product_id, p.product_name, p.product_slug, 
               p.regular_price, p.sale_price, p.stock_quantity,
               p.product_status, p.product_tag,
               pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.category_id = 2
          AND p.product_status != "0"
          AND LOWER(p.product_status) != "out of stock"
        ORDER BY p.product_id DESC
        LIMIT :limit
    ');
    
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory9Products($limit = 12)
    {
        $stmt = $this->db->prepare('
            SELECT p.product_id, p.product_name, p.product_slug, 
                   p.regular_price, p.sale_price, p.stock_quantity,
                   p.product_status, p.product_tag,
                   pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE p.category_id = 9 AND p.product_status != "0"
            ORDER BY p.product_id DESC
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory10Products($limit = 12)
    {
        $stmt = $this->db->prepare('
            SELECT p.product_id, p.product_name, p.product_slug, 
                   p.regular_price, p.sale_price, p.stock_quantity,
                   p.product_status, p.product_tag,
                   pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE p.category_id = 10 AND p.product_status != "0"
            ORDER BY p.product_id DESC
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory4Products($limit = 12)
    {
        $stmt = $this->db->prepare('
            SELECT p.product_id, p.product_name, p.product_slug, 
                   p.regular_price, p.sale_price, p.stock_quantity,
                   p.product_status, p.product_tag,
                   pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE p.category_id = 4 AND p.product_status != "0"
            ORDER BY p.product_id DESC
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
