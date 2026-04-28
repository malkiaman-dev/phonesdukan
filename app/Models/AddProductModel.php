<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

class AddProductModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addProduct($productData, $seoData, $imageData)
    {
        // Insert product details into products table
        $query = 'INSERT INTO products (product_name, product_slug, category_id, brand_id, product_description, 
        product_status, stock_quantity, regular_price, sale_price, product_sku, weight_kg, length_cm, width_cm, height_cm, tax_class, created_at, updated_at, product_tag)
        VALUES (:product_name, :product_slug, :category_id, :brand_id, :product_description, :product_status, 
        :stock_quantity, :regular_price, :sale_price, :product_sku, :weight_kg, :length_cm, :width_cm, :height_cm, :tax_class, NOW(), NOW(), :product_tag)';
        $stmt = $this->db->prepare($query);
        $stmt->execute($productData);

        $productId = $this->db->lastInsertId();  // Get the last inserted product ID

        // Insert SEO data (SEO code remains the same)
        $seoData = [
            'product_id' => $productId,  // Correctly use the last inserted product ID
            'seo_title' => $_POST['seo_title'],
            'seo_description' => $_POST['seo_description'],
            'focus_keyword' => $_POST['focus_keyword'],
        ];

        $seoQuery = 'INSERT INTO product_seo (product_id, seo_title, seo_description, focus_keyword)
                     VALUES (:product_id, :seo_title, :seo_description, :focus_keyword)';
        $seoStmt = $this->db->prepare($seoQuery);
        $seoStmt->execute($seoData);

        // Handle the image upload and insertion
        foreach ($_FILES['images']['name'] as $index => $name) {
            $targetDir = dirname(__DIR__, 2) . '/public/uploads/';
            $fileExtension = pathinfo($name, PATHINFO_EXTENSION);
            $newFileName = uniqid('product_', true) . '.' . $fileExtension;
            $targetFile = $targetDir . $newFileName;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$index], $targetFile)) {
                // Insert image into product_images table
                $imageData = [
                    'image_url' => '/public/uploads/' . $newFileName,
                    'is_primary' => $index == 0 ? 1 : 0,
                    'status' => ($_POST['product_status'] == 'active') ? 1 : 0,
                    'product_id' => $productId  // Correctly use the last inserted product ID
                ];

                $imageQuery = 'INSERT INTO product_images (image_url, is_primary, status, product_id) 
                               VALUES (:image_url, :is_primary, :status, :product_id)';
                $imageStmt = $this->db->prepare($imageQuery);
                $imageStmt->execute($imageData);

                $imageId = $this->db->lastInsertId();  // Correctly retrieve the last inserted image_id

                // Insert image metadata using the correct image_id from product_images
                $metaQuery = 'INSERT INTO image_metadata (image_id, alt_text, meta_id, title, caption, description) 
                              VALUES (:image_id, :alt_text, :meta_id, :title, :caption, :description)';
                $metaQuery = 'INSERT INTO image_metadata (image_id, alt_text, meta_id, title, caption, description) 
VALUES (:image_id, :alt_text, :meta_id, :title, :caption, :description)';
                $imageMetaData = [
                    'image_id' => $imageId,  // Use the correct image_id from the last inserted product_images
                    'alt_text' => $_POST['alt_text'][$index],
                    'meta_id' => 1,  // Default meta_id
                    'title' => $_POST['image_title'][$index],
                    'caption' => $_POST['image_caption'][$index],
                    'description' => $_POST['image_description'][$index]
                ];
                $metaStmt = $this->db->prepare($metaQuery);
                $metaStmt->execute($imageMetaData);
            } else {
                $_SESSION['message'] = 'There was an error uploading the image.';
                $_SESSION['message_type'] = 'error';
                return false;
            }
        }

        return true;
    }
}
