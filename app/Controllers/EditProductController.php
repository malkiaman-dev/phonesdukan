<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/app/Models/EditProductModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/VariationModel.php';
require_once dirname(__DIR__, 2) . '/app/Services/ProductMediaService.php';

class ProductController
{
    private $model;

    private function redirectToCurrentEditPage($id)
    {
        $requestPath = $_SERVER['REQUEST_URI'] ?? '';
        $basePath = strtok($requestPath, '?');
        if (!is_string($basePath) || trim($basePath) === '') {
            $basePath = 'edit-product.php';
        }

        header('Location: ' . $basePath . '?id=' . urlencode((string) $id));
        exit();
    }

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        ensureProductExpectedComingDateColumn($db);
        $this->model = new ProductModel($db);
    }

    public function getProductDetails($id)
    {
        if (empty($id)) {
            die('No product ID provided.');
        }
    
        $product = $this->model->getProductById($id);
        $categories = $this->model->getAllCategories();
        $brands = $this->model->getAllBrands();
        $productAttributes = $this->model->getAllAttributes();
        $attributeValues = [];
        foreach ($productAttributes as $attribute) {
            $values = $this->model->getAttributeValues($attribute['attribute_id']);
            $attributeValues[$attribute['attribute_id']] = $values;
        }
        return [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'productAttributes' => $productAttributes,
            'attributeValues' => $attributeValues,
        ];
    }
    
    public function getProductImages($productId)
    {
        return $this->model->getProductImages($productId);
    }

    public function getSeoData($id)
    {
        if (empty($id)) {
            die('No product ID provided.');
        }
        return $this->model->getSeoDataByProductId($id);
    }

    public function updateProduct($id, $data, $seoData, $primaryImage, $galleryImages, $imageMetadata, $primaryImageId = null)
    {
        
        if (empty($id) || empty($data)) {
            session_start();
            $_SESSION['message'] = 'Error: Invalid product ID or data.';
            $_SESSION['message_type'] = 'error';
            $this->redirectToCurrentEditPage($id);
        }
    
        try {
            if (empty($data['product_sku'])) {
                $data['product_sku'] = null;
            }
        
            $this->model->updateProduct($id, $data);

            // Auto-regenerate canonical URL from the current category/brand/product slugs
            try {
                $dbSlug = (new Database())->getConnection();
                $catS = $dbSlug->prepare('SELECT slug FROM categories WHERE category_id = ?');
                $catS->execute([$data['category_id']]);
                $catSlugRow = $catS->fetch(PDO::FETCH_ASSOC);

                $brandS = $dbSlug->prepare('SELECT slug FROM brands WHERE brand_id = ?');
                $brandS->execute([$data['brand_id']]);
                $brandSlugRow = $brandS->fetch(PDO::FETCH_ASSOC);

                $seoData['canonical_url'] = 'https://www.phonesdukan.com/'
                    . ($catSlugRow['slug'] ?? '') . '/'
                    . ($brandSlugRow['slug'] ?? '') . '/'
                    . $data['product_slug'] . '/';
            } catch (Exception $eSlug) {
                // Fall back to client-provided value
            }

            $existingSeo = $this->model->getSeoDataByProductId($id);
            if ($existingSeo) {
                $this->model->updateSeoData($id, $seoData);
            } else {
                $this->model->insertSeoData($id, $seoData);
            }
        
            $this->updateProductImages($id, $primaryImage, $imageMetadata, $primaryImageId);

            $mediaService = new ProductMediaService((new Database())->getConnection());
            $mediaService->saveFromRequest((int) $id, $_POST, $_FILES);
            $mediaService->applyGalleryOrder((int) $id, $_POST['gallery_order_json'] ?? '[]', []);
        
            $attributes = $_POST['attributes'] ?? [];
            $removeAttributes = $_POST['remove_attributes'] ?? [];
        
            $this->updateAssignedProductAttributes($id, $attributes, $removeAttributes);

            // Save product_type and variations
            $product_type = in_array($_POST['product_type'] ?? 'simple', ['simple','variable'])
                ? ($_POST['product_type'] ?? 'simple') : 'simple';
            $db = (new Database())->getConnection();
            $db->prepare("UPDATE products SET product_type=? WHERE product_id=?")->execute([$product_type, $id]);

            if ($product_type === 'variable' && isset($_POST['variations_json'])) {
                $variations = json_decode($_POST['variations_json'], true);
                if (is_array($variations)) {
                    $varModel = new VariationModel();
                    $varModel->saveProductVariations($id, $variations);
                }
            } elseif ($product_type === 'simple') {
                // Clear existing variations when switching back to simple
                $db->prepare(
                    "DELETE pv FROM product_variations pv WHERE pv.product_id = ?"
                )->execute([$id]);
            }

            session_start();
            $_SESSION['message'] = 'Product updated successfully!';
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            session_start();
            $_SESSION['message'] = 'Error updating product: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            error_log('Update product error: ' . $e->getMessage());
        }
        
        $this->redirectToCurrentEditPage($id);
    }
    
    public function updateProductImages($productId, $primaryImage, $imageMetadata, $primaryImageId = null)
    {
        $isPrimarySet = false;
        if ($primaryImage && !empty($primaryImage['name'])) {
            foreach ($primaryImage['tmp_name'] as $key => $tmpName) {
                if ($primaryImage['error'][$key] === UPLOAD_ERR_OK) {
                    $imagePath = $this->uploadImage($primaryImage, $key);
                    $isPrimary = ($primaryImageId != null && $primaryImageId == $key) ? 1 : 0;
                    if (!$isPrimary && !$isPrimarySet) {
                        $isPrimary = 1;
                        $isPrimarySet = true;
                    }
                    $imageId = $this->model->insertProductImage($productId, $imagePath, $isPrimary);
                    $metadataToInsert = [
                        'alt_text' => null,
                        'title' => null,
                        'description' => null,
                        'caption' => null
                    ];
                    $this->model->insertImageMetadata($imageId, $metadataToInsert);
                }
            }
        }
    
        if (!empty($imageMetadata)) {
            foreach ($imageMetadata['alt_text'] as $imageId => $metadata) {
                $metadataExists = $this->model->checkIfMetadataExists($imageId);
                if ($metadataExists) {
                    $metadataToUpdate = [
                        'alt_text' => $metadata,
                        'title' => isset($imageMetadata['title'][$imageId]) ? $imageMetadata['title'][$imageId] : null,
                        'description' => isset($imageMetadata['description'][$imageId]) ? $imageMetadata['description'][$imageId] : null,
                        'caption' => isset($imageMetadata['caption'][$imageId]) ? $imageMetadata['caption'][$imageId] : null
                    ];
                    $this->model->updateImageMetadata($imageId, $metadataToUpdate);
                } else {
                    $metadataToInsert = [
                        'alt_text' => null,
                        'title' => null,
                        'description' => null,
                        'caption' => null
                    ];
                    $this->model->insertImageMetadata($imageId, $metadataToInsert);
                }
            }
        }
    
        if ($primaryImageId) {
            $this->model->setPrimaryImage($productId, $primaryImageId);
        }
    
        $removeImages = isset($_POST['remove_image']) ? $_POST['remove_image'] : [];
        if (!empty($removeImages)) {
            foreach ($removeImages as $imageId => $value) {
                $this->model->removeImage($imageId);
            }
        }
    }
    
    public function uploadImage($file, $key = null)
    {
        if (isset($_SERVER['IS_TEST_ENV']) && $_SERVER['IS_TEST_ENV'] === true) {
            return '/public/uploads/mock_image.jpg';
        }
        if ($key !== null) {
            $filePath = $file['tmp_name'][$key];
            $fileName = $file['name'][$key];
        } else {
            $filePath = $file['tmp_name'];
            $fileName = $file['name'];
        }
        $sanitizedFilename = strtolower(str_replace(' ', '-', basename($fileName)));
        $targetDir = dirname(__DIR__, 2) . '/public/uploads/';
        $targetFile = $targetDir . $sanitizedFilename;
        if (!is_uploaded_file($filePath)) {
            throw new Exception("File is not uploaded correctly: " . $filePath);
        }
        if (!is_writable($targetDir)) {
            throw new Exception("Target directory is not writable: $targetDir");
        }
        if (move_uploaded_file($filePath, $targetFile)) {
            return '/public/uploads/' . $sanitizedFilename;
        } else {
            throw new Exception("Sorry, there was an error uploading your file.");
        }
    }

    public function getAllAttributes()
    {
        return $this->model->getAllAttributes();
    }

    public function getAttributeValuesForProduct($productId)
    {
        $productAttributes = $this->getAssignedProductAttributes($productId);
        $attributeValues = [];
        foreach ($productAttributes as $attribute) {
            $values = $this->model->getAttributeValues($attribute['attribute_id']);
            $attributeValues[$attribute['attribute_id']] = $values;
        }
        return $attributeValues;
    }

    public function getAssignedProductAttributes($productId)
    {
        return $this->model->getProductAttributesByProductId($productId);
    }

    public function updateAssignedProductAttributes($productId, $attributes, $removeAttributes = [])
    {

        // Remove specified attributes
        if (!empty($removeAttributes)) {
            foreach ($removeAttributes as $index => $attributeId) {
                if (!empty($attributeId) && is_numeric($attributeId)) {
                    $this->model->clearProductAttributes($productId, $attributeId);
                } else {
                }
            }
        }

        // Process attributes to keep or update
        foreach ($attributes as $index => $attribute) {
            if (!empty($attribute['attribute_id']) && !empty($attribute['value_id']) && !isset($removeAttributes[$index])) {
                $existingAttributes = $this->model->getProductAttributesByProductId($productId);
                $attributeExists = false;
                foreach ($existingAttributes as $existing) {
                    if ($existing['attribute_id'] == $attribute['attribute_id'] && $existing['value_id'] == $attribute['value_id']) {
                        $attributeExists = true;
                        break;
                    }
                }

                if ($attributeExists) {
                    $this->model->updateProductAttribute(
                        $productId,
                        $attribute['attribute_id'],
                        $attribute['value_id'],
                        $attribute['regular_price'] ?? null,
                        $attribute['sale_price'] ?? null
                    );
                } else {
                    $this->model->assignProductAttribute(
                        $productId,
                        $attribute['attribute_id'],
                        $attribute['value_id'],
                        $attribute['regular_price'] ?? null,
                        $attribute['sale_price'] ?? null
                    );
                }
            } else {
            }
        }
    }
}