<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/database/db.php';

class ProductModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getProductById($id)
    {
        $stmt = $this->db->prepare('SELECT p.*, c.slug AS category_slug, b.slug AS brand_slug FROM products p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllCategories()
    {
        $stmt = $this->db->prepare('SELECT * FROM categories');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllBrands()
    {
        $stmt = $this->db->prepare('SELECT * FROM brands');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProduct($id, $data)
    {
        $salePrice = empty($data['sale_price']) ? null : $data['sale_price'];
        $weightKg = empty($data['weight_kg']) ? null : $data['weight_kg'];
        $lengthCm = empty($data['length_cm']) ? null : $data['length_cm'];
        $widthCm = empty($data['width_cm']) ? null : $data['width_cm'];
        $heightCm = empty($data['height_cm']) ? null : $data['height_cm'];
        $taxClass = empty($data['tax_class']) ? null : $data['tax_class'];
        $b2bRegularPrice = empty($data['b2b_regular_price']) ? null : $data['b2b_regular_price'];
    
        $prepaidDiscountAmount = isset($data['prepaid_discount_amount']) && is_numeric($data['prepaid_discount_amount']) ? max(0, (float)$data['prepaid_discount_amount']) : 0;

        $stmt = $this->db->prepare('UPDATE products SET
            product_name = ?,
            product_slug = ?,
            product_description = ?,
            short_description = ?,
            product_status = ?,
            stock_quantity = ?,
            regular_price = ?,
            sale_price = ?,
            product_sku = ?,
            weight_kg = ?,
            length_cm = ?,
            width_cm = ?,
            height_cm = ?,
            tax_class = ?,
            product_tag = ?,
            category_id = ?,
            brand_id = ?,
            is_b2b_available = ?,
            b2b_regular_price = ?,
            prepaid_discount_amount = ?
            WHERE product_id = ?');

        return $stmt->execute([
            $data['product_name'],
            $data['product_slug'],
            $data['product_description'],
            $data['short_description'],
            $data['product_status'],
            $data['stock_quantity'],
            $data['regular_price'],
            $salePrice,
            $data['product_sku'],
            $weightKg,
            $lengthCm,
            $widthCm,
            $heightCm,
            $taxClass,
            $data['product_tag'],
            $data['category_id'],
            $data['brand_id'],
            $data['is_b2b_available'],
            $b2bRegularPrice,
            $prepaidDiscountAmount,
            $id
        ]);
    }

    public function getSeoDataByProductId($productId)
    {
        $stmt = $this->db->prepare('SELECT * FROM product_seo WHERE product_id = ? LIMIT 1');
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSeoData($productId, $data)
    {
        $stmt = $this->db->prepare('UPDATE product_seo SET
            focus_keyword      = ?,
            seo_title          = ?,
            seo_description    = ?,
            canonical_url      = ?,
            secondary_keywords = ?
            WHERE product_id = ?');
        return $stmt->execute([
            $data['focus_keyword'],
            $data['seo_title'],
            $data['seo_description'],
            $data['canonical_url'] ?? null,
            $data['secondary_keywords'] ?? null,
            $productId,
        ]);
    }

    public function insertSeoData($productId, $data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_seo (product_id, focus_keyword, seo_title, seo_description, canonical_url, secondary_keywords)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $productId,
            $data['focus_keyword'],
            $data['seo_title'],
            $data['seo_description'],
            $data['canonical_url'] ?? null,
            $data['secondary_keywords'] ?? null,
        ]);
    }

    public function getProductImages($productId)
    {
        $stmt = $this->db->prepare('
            SELECT pi.image_id, pi.image_url, pi.is_primary, pi.sort_order,
                   im.alt_text, im.title, im.description, im.caption
            FROM product_images pi
            LEFT JOIN image_metadata im ON pi.image_id = im.image_id
            WHERE pi.product_id = ?
            ORDER BY pi.sort_order ASC, pi.is_primary DESC, pi.image_id ASC
        ');
        $stmt->execute([$productId]);
        $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log('Fetched product images: ' . print_r($productImages, true));
        return $productImages;
    }

    public function insertProductImage($productId, $imageUrl, $isPrimary)
    {
        $stmt = $this->db->prepare('INSERT INTO product_images (product_id, image_url, is_primary) 
                                VALUES (?, ?, ?)');
        $stmt->execute([$productId, $imageUrl, $isPrimary]);
        return $this->db->lastInsertId();
    }

    public function insertImageMetadata($imageId, $metadata)
    {
        error_log('Inserting metadata for image_id: ' . $imageId);
        error_log('Metadata to insert: ' . print_r($metadata, true));
        $stmt = $this->db->prepare('INSERT INTO image_metadata (image_id, alt_text, title, description, caption) 
                                    VALUES (?, ?, ?, ?, ?)');
        $success = $stmt->execute([
            $imageId,
            $metadata['alt_text'], 
            $metadata['title'], 
            $metadata['description'], 
            $metadata['caption']
        ]);
        if ($success) {
            $lastInsertId = $this->db->lastInsertId();
            error_log('Successfully inserted metadata for image_id: ' . $imageId . ' with meta_id: ' . $lastInsertId);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Failed to insert metadata for image_id: ' . $imageId . ' Error: ' . implode(' | ', $errorInfo));
        }
        return $success;
    }

    public function updateImageMetadata($imageId, $metadata)
    {
        $stmt = $this->db->prepare('UPDATE image_metadata SET 
            alt_text = ?, 
            title = ?, 
            description = ?, 
            caption = ? 
            WHERE image_id = ?');
        return $stmt->execute([
            $metadata['alt_text'],
            $metadata['title'],
            $metadata['description'],
            $metadata['caption'],
            $imageId
        ]);
    }

    public function checkIfMetadataExists($imageId)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM image_metadata WHERE image_id = ?');
        $stmt->execute([$imageId]);
        return $stmt->fetchColumn() > 0;
    }

    public function setPrimaryImage($productId, $imageId)
    {
        $stmt = $this->db->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?');
        $stmt->execute([$productId]);
        $stmt = $this->db->prepare('UPDATE product_images SET is_primary = 1 WHERE image_id = ?');
        $stmt->execute([$imageId]);
    }

    public function removeImage($imageId)
    {
        $stmt = $this->db->prepare('DELETE FROM image_metadata WHERE image_id = ?');
        $stmt->execute([$imageId]);
        $stmt = $this->db->prepare('DELETE FROM product_images WHERE image_id = ?');
        return $stmt->execute([$imageId]);
    }

    public function getAttributeValues($attributeId)
    {
        $stmt = $this->db->prepare('SELECT * FROM product_attribute_values WHERE attribute_id = ?');
        $stmt->execute([$attributeId]);
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Fetched values for attribute_id $attributeId: " . print_r($values, true));
        return $values;
    }

    public function getAllAttributes()
    {
        $stmt = $this->db->prepare('SELECT * FROM product_attributes');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductAttributesByProductId($productId)
    {
        $stmt = $this->db->prepare('
            SELECT pa.attribute_id, pa.attribute_name, pav.value_id, pav.value, pap.regular_price, pap.sale_price
            FROM product_attribute_prices pap
            JOIN product_attributes pa ON pa.attribute_id = pap.attribute_id
            JOIN product_attribute_values pav ON pav.value_id = pap.value_id
            WHERE pap.product_id = ?
        ');
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearProductAttributes($productId, $attributeId = null, $valueId = null)
    {
        if ($attributeId && $valueId) {
            error_log("Attempting to clear specific attribute value: product_id=$productId, attribute_id=$attributeId, value_id=$valueId");
            $stmt = $this->db->prepare('DELETE FROM product_attribute_prices WHERE product_id = ? AND attribute_id = ? AND value_id = ?');
            $success = $stmt->execute([$productId, $attributeId, $valueId]);
            error_log("Cleared attribute value: product_id=$productId, attribute_id=$attributeId, value_id=$valueId, success=" . ($success ? 'true' : 'false'));
            if (!$success) {
                error_log("Error clearing attribute value: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        } elseif ($attributeId) {
            error_log("Attempting to clear all values for attribute_id: $attributeId for product_id: $productId");
            $stmt = $this->db->prepare('DELETE FROM product_attribute_prices WHERE product_id = ? AND attribute_id = ?');
            $success = $stmt->execute([$productId, $attributeId]);
            error_log("Cleared attribute_id: $attributeId for product_id: $productId, success=" . ($success ? 'true' : 'false'));
            if (!$success) {
                error_log("Error clearing attribute: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        } else {
            error_log("Attempting to clear all attributes for product_id: $productId");
            $stmt = $this->db->prepare('DELETE FROM product_attribute_prices WHERE product_id = ?');
            $success = $stmt->execute([$productId]);
            error_log("Cleared all attributes for product_id: $productId, success=" . ($success ? 'true' : 'false'));
            if (!$success) {
                error_log("Error clearing all attributes: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        }
    }

    public function assignProductAttribute($productId, $attributeId, $valueId, $regularPrice, $salePrice)
    {
        $stmt = $this->db->prepare('
            INSERT INTO product_attribute_prices (product_id, attribute_id, value_id, regular_price, sale_price)
            VALUES (?, ?, ?, ?, ?)
        ');
        $success = $stmt->execute([$productId, $attributeId, $valueId, $regularPrice, $salePrice]);
        error_log("Assigned attribute_id: $attributeId, value_id: $valueId for product_id: $productId");
        return $success;
    }

    public function updateProductAttribute($productId, $attributeId, $valueId, $regularPrice, $salePrice)
    {
        $stmt = $this->db->prepare('
            UPDATE product_attribute_prices 
            SET regular_price = ?, sale_price = ?
            WHERE product_id = ? AND attribute_id = ? AND value_id = ?
        ');
        $success = $stmt->execute([
            $regularPrice ?? null,
            $salePrice ?? null,
            $productId,
            $attributeId,
            $valueId
        ]);
        error_log("Updated attribute_id: $attributeId, value_id: $valueId for product_id: $productId");
        return $success;
    }
}