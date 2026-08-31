<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/product_title_rewrite.php';
require_once dirname(__DIR__, 2) . '/app/Models/EditProductModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/VariationModel.php';
require_once dirname(__DIR__, 2) . '/app/Services/ProductMediaService.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/ProductMediaHelper.php';

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
        // One query for all attribute values instead of N+1 round-trips.
        $attributeValues = $this->model->getAllAttributeValuesGrouped();
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

            // Auto-expand title from brand + short description (keeps slug unchanged).
            try {
                $brandName = '';
                $categoryName = '';
                $dbConn = (new Database())->getConnection();
                if (!empty($data['brand_id'])) {
                    $bs = $dbConn->prepare('SELECT brand_name FROM brands WHERE brand_id = ?');
                    $bs->execute([(int) $data['brand_id']]);
                    $brandName = (string) ($bs->fetchColumn() ?: '');
                }
                if (!empty($data['category_id'])) {
                    $cs = $dbConn->prepare('SELECT category_name FROM categories WHERE category_id = ?');
                    $cs->execute([(int) $data['category_id']]);
                    $categoryName = (string) ($cs->fetchColumn() ?: '');
                }
                enrichProductTitleFromContext($data, $brandName, $categoryName);
                if (empty($seoData['seo_title'])) {
                    $seoData['seo_title'] = $data['product_name'];
                }
            } catch (Throwable $eTitle) {
                // Keep posted title if enrichment fails
            }
        
            $this->model->updateProduct($id, $data);

            // Keep gallery image visibility in sync with product status.
            // Coming Soon / Inactive products store images with status=0;
            // activating the product must promote those images too.
            $imageStatus = ((int) ($data['product_status'] ?? 0) === 1) ? 1 : 0;
            $this->model->syncImageStatusForProduct((int) $id, $imageStatus);

            // Auto-regenerate canonical URL from the current category/brand/product slugs
            try {
                $dbSlug = (new Database())->getConnection();
                $catS = $dbSlug->prepare('SELECT slug FROM categories WHERE category_id = ?');
                $catS->execute([$data['category_id']]);
                $catSlugRow = $catS->fetch(PDO::FETCH_ASSOC);

                $brandS = $dbSlug->prepare('SELECT slug FROM brands WHERE brand_id = ?');
                $brandS->execute([$data['brand_id']]);
                $brandSlugRow = $brandS->fetch(PDO::FETCH_ASSOC);

                $subSlug = null;
                if (!empty($data['subcategory_id'])) {
                    $subS = $dbSlug->prepare('SELECT slug FROM categories WHERE category_id = ? AND parent_id IS NOT NULL LIMIT 1');
                    $subS->execute([(int) $data['subcategory_id']]);
                    $subSlugRow = $subS->fetch(PDO::FETCH_ASSOC);
                    $subSlug = $subSlugRow['slug'] ?? null;
                }

                $seoData['canonical_url'] = buildProductCanonicalUrl(
                    (string) ($brandSlugRow['slug'] ?? ''),
                    (string) ($catSlugRow['slug'] ?? ''),
                    (string) ($data['product_slug'] ?? ''),
                    $subSlug
                );
            } catch (Exception $eSlug) {
                // Fall back to client-provided value
            }

            $existingSeo = $this->model->getSeoDataByProductId($id);
            if ($existingSeo) {
                $this->model->updateSeoData($id, $seoData);
            } else {
                $this->model->insertSeoData($id, $seoData);
            }
        
            $imageUpdate = $this->updateProductImages($id, $primaryImage, $imageMetadata, $primaryImageId, $imageStatus);
            $this->model->normalizeProductImageUrls((int) $id);
            $this->model->ensurePrimaryImageExists((int) $id);

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

            require_once __DIR__ . '/../Models/ProductGroupModel.php';
            $groupIds = isset($_POST['group_product_ids']) && is_array($_POST['group_product_ids'])
                ? $_POST['group_product_ids']
                : [];
            $groupPrices = isset($_POST['group_product_prices']) && is_array($_POST['group_product_prices'])
                ? $_POST['group_product_prices']
                : [];
            (new ProductGroupModel($db))->saveGroupProducts((int) $id, $groupIds, $groupPrices);

            session_start();
            $uploadErrors = $imageUpdate['errors'] ?? [];
            if (!empty($uploadErrors)) {
                $_SESSION['message'] = 'Product updated, but some images failed to upload: ' . implode(' ', $uploadErrors);
                $_SESSION['message_type'] = 'error';
            } else {
                $_SESSION['message'] = 'Product updated successfully!';
                $_SESSION['message_type'] = 'success';
            }
        } catch (Exception $e) {
            session_start();
            $_SESSION['message'] = 'Error updating product: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            error_log('Update product error: ' . $e->getMessage());
        }
        
        $this->redirectToCurrentEditPage($id);
    }
    
    public function updateProductImages($productId, $primaryImage, $imageMetadata, $primaryImageId = null, $imageStatus = 1)
    {
        $errors = [];
        $productId = (int) $productId;

        // Removals first so primary selection cannot target a deleted row.
        $removeImages = isset($_POST['remove_image']) && is_array($_POST['remove_image'])
            ? $_POST['remove_image']
            : [];
        if (!empty($removeImages)) {
            foreach ($removeImages as $imageId => $value) {
                if ((string) $value === '1' || (int) $value === 1) {
                    $this->model->removeImage((int) $imageId, $productId);
                }
            }
        }

        $hasExistingPrimarySelection = $primaryImageId !== null
            && $primaryImageId !== ''
            && ctype_digit((string) $primaryImageId)
            && !isset($removeImages[(int) $primaryImageId])
            && !isset($removeImages[(string) $primaryImageId]);

        $existingCount = $this->model->countProductImages($productId);
        $shouldPromoteNewUpload = !$hasExistingPrimarySelection && $existingCount === 0;
        $isPrimarySet = false;

        if ($primaryImage && isset($primaryImage['name']) && is_array($primaryImage['name'])) {
            foreach ($primaryImage['name'] as $key => $name) {
                if ($name === null || trim((string) $name) === '') {
                    continue;
                }

                $errorCode = (int) ($primaryImage['error'][$key] ?? UPLOAD_ERR_NO_FILE);
                if ($errorCode === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errors[] = $this->uploadErrorMessage((string) $name, $errorCode);
                    continue;
                }

                try {
                    $imagePath = $this->uploadImage($primaryImage, $key);
                    $isPrimary = 0;
                    if ($shouldPromoteNewUpload && !$isPrimarySet) {
                        $isPrimary = 1;
                        $isPrimarySet = true;
                    }
                    $imageId = $this->model->insertProductImage($productId, $imagePath, $isPrimary, (int) $imageStatus);
                    $metadataToInsert = [
                        'alt_text' => null,
                        'title' => null,
                        'description' => null,
                        'caption' => null
                    ];
                    $this->model->insertImageMetadata($imageId, $metadataToInsert);
                } catch (Exception $uploadEx) {
                    $errors[] = $uploadEx->getMessage();
                    error_log('Product image upload failed: ' . $uploadEx->getMessage());
                }
            }
        }
    
        if (!empty($imageMetadata) && !empty($imageMetadata['alt_text']) && is_array($imageMetadata['alt_text'])) {
            foreach ($imageMetadata['alt_text'] as $imageId => $metadata) {
                if (!$this->model->imageBelongsToProduct((int) $imageId, $productId)) {
                    continue;
                }
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
    
        if ($hasExistingPrimarySelection) {
            $this->model->setPrimaryImage($productId, (int) $primaryImageId);
        }

        return ['errors' => $errors];
    }

    private function uploadErrorMessage(string $fileName, int $errorCode): string
    {
        $label = $fileName !== '' ? $fileName : 'image';
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "{$label} exceeds the maximum upload size.";
            case UPLOAD_ERR_PARTIAL:
                return "{$label} was only partially uploaded.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder while uploading {$label}.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write {$label} to disk.";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped {$label} from uploading.";
            default:
                return "Failed to upload {$label}.";
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

        $targetDir = dirname(__DIR__, 2) . '/public/uploads/';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new Exception('Upload directory could not be created.');
        }
        if (!is_writable($targetDir)) {
            throw new Exception("Target directory is not writable: $targetDir");
        }
        if (!is_uploaded_file($filePath)) {
            throw new Exception('File is not uploaded correctly.');
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new Exception('Unsupported image type. Use JPG, PNG, WEBP, or GIF.');
        }

        $sanitizedFilename = ProductMediaHelper::sanitizeFilename((string) $fileName);
        $targetFile = ProductMediaHelper::uniquePath($targetDir, $sanitizedFilename);
        if (!move_uploaded_file($filePath, $targetFile)) {
            throw new Exception('Sorry, there was an error uploading your file.');
        }

        return normalizeStoredUploadPath('/public/uploads/' . basename($targetFile));
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