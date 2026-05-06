<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/app/Models/EditProductModel.php';

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
            error_log("Fetched values for attribute_id " . $attribute['attribute_id'] . ": " . print_r($values, true));
            $attributeValues[$attribute['attribute_id']] = $values;
        }
        error_log("Final attributeValues array: " . print_r($attributeValues, true));
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
        error_log('updateProduct called with id: ' . $id);
        error_log('POST data: ' . print_r($_POST, true));
        
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
        
            $existingSeo = $this->model->getSeoDataByProductId($id);
            if ($existingSeo) {
                $this->model->updateSeoData($id, $seoData);
            } else {
                $this->model->insertSeoData($id, $seoData);
            }
        
            $this->updateProductImages($id, $primaryImage, $imageMetadata, $primaryImageId);
        
            $attributes = $_POST['attributes'] ?? [];
            $removeAttributes = $_POST['remove_attributes'] ?? [];
            error_log('Attributes sent in POST for product_id ' . $id . ': ' . print_r($attributes, true));
            error_log('Attributes to remove for product_id ' . $id . ': ' . print_r($removeAttributes, true));
        
            $this->updateAssignedProductAttributes($id, $attributes, $removeAttributes);
        
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
                    error_log("New image uploaded with image_id: $imageId");
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
                    error_log("Updating metadata for existing image with image_id: $imageId");
                    $this->model->updateImageMetadata($imageId, $metadataToUpdate);
                } else {
                    error_log("Metadata not found for image_id $imageId, inserting new metadata with null values.");
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
            error_log("Setting image with image_id $primaryImageId as primary image for product_id: $productId");
            $this->model->setPrimaryImage($productId, $primaryImageId);
        }
    
        $removeImages = isset($_POST['remove_image']) ? $_POST['remove_image'] : [];
        if (!empty($removeImages)) {
            foreach ($removeImages as $imageId => $value) {
                error_log("Removing image with image_id: $imageId for product_id: $productId");
                $this->model->removeImage($imageId);
            }
        }
    }
    
    public function uploadImage($file, $key = null)
    {
        if (isset($_SERVER['IS_TEST_ENV']) && $_SERVER['IS_TEST_ENV'] === true) {
            error_log('Mocking file upload for testing');
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
        error_log('Updating attributes for product_id: ' . $productId);
        error_log('Attributes to process: ' . print_r($attributes, true));
        error_log('Attributes to remove: ' . print_r($removeAttributes, true));

        // Remove specified attributes
        if (!empty($removeAttributes)) {
            foreach ($removeAttributes as $index => $attributeId) {
                if (!empty($attributeId) && is_numeric($attributeId)) {
                    $this->model->clearProductAttributes($productId, $attributeId);
                    error_log("Removed attribute_id: $attributeId for product_id: $productId");
                } else {
                    error_log("Skipping invalid attribute_id: " . var_export($attributeId, true) . " for index: $index");
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
                error_log("Skipping attribute at index $index due to missing attribute_id, value_id, or marked for removal");
            }
        }
    }
}