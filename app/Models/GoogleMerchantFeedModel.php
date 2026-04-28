<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

class ProductModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $query = 'SELECT 
                    p.product_id AS id, 
                    p.product_name AS name, 
                    p.product_slug AS slug, 
                    p.product_description AS description, 
                    p.regular_price AS price, 
                    p.sale_price,
                    p.stock_quantity, 
                    p.product_status, 
                    p.product_sku AS sku,
                    i.image_url, 
                    b.brand_name,
                    b.slug AS brand_slug,
                    c.slug AS category_slug,
                    c.category_name  -- Add this line to fetch the category name
                  FROM products p
                  LEFT JOIN product_images i ON p.product_id = i.product_id
                  LEFT JOIN brands b ON p.brand_id = b.brand_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.product_status = 1';
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductBySlug($category_slug, $brand_slug, $product_slug)
    {
        $query = 'SELECT p.*, c.slug AS category_slug, b.slug AS brand_slug, p.regular_price, p.sale_price
                  FROM products p
                  INNER JOIN categories c ON p.category_id = c.category_id
                  INNER JOIN brands b ON p.brand_id = b.brand_id
                  WHERE c.slug = :category_slug
                  AND b.slug = :brand_slug
                  AND p.product_slug = :product_slug
                  LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_slug', $category_slug, PDO::PARAM_STR);
        $stmt->bindParam(':brand_slug', $brand_slug, PDO::PARAM_STR);
        $stmt->bindParam(':product_slug', $product_slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Optionally, method to get product details in XML format

    // Optionally, method to get product details in XML format
    public function getProductDetailsXML($category_slug, $brand_slug, $product_slug)
    {
        // Adjusted SQL to join categories and brands tables to get slugs
        $query = 'SELECT p.id, p.product_name AS name, p.product_slug AS slug, p.product_description AS description, 
                     p.regular_price AS price, p.product_status AS availability, p.product_sku AS sku,
                     p.stock_quantity, c.slug AS category_slug, b.slug AS brand_slug, 
                     c.category_name AS category, b.brand_name AS brand
              FROM products p
              JOIN categories c ON p.category_id = c.category_id
              JOIN brands b ON p.brand_id = b.brand_id
              WHERE c.slug = :category_slug AND b.slug = :brand_slug AND p.product_slug = :product_slug';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_slug', $category_slug);
        $stmt->bindParam(':brand_slug', $brand_slug);
        $stmt->bindParam(':product_slug', $product_slug);
        $stmt->execute();

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Generate XML based on the product data
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><product></product>');
            $xml->addChild('id', $product['id']);
            $xml->addChild('name', htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'));
            $xml->addChild('url', htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'));
            $xml->addChild('image_url', 'http://example.com/image.jpg');  // Add image URL if available
            $xml->addChild('price', $product['price']);
            $xml->addChild('availability', htmlspecialchars($product['availability'], ENT_QUOTES, 'UTF-8'));
            $xml->addChild('brand', htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8'));
            $xml->addChild('description', htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'));
            $xml->addChild('category', htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'));

            return $xml->asXML();
        }

        return null;  // Return null if product not found
    }
}
?>
