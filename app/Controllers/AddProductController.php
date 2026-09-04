<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/product_title_rewrite.php';
require_once dirname(__DIR__, 2) . '/app/Models/AddProductModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/CatalogModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/VariationModel.php';
require_once dirname(__DIR__, 2) . '/app/Services/ProductMediaService.php';

class ProductController
{
    private $db;
    private $productModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        if (!$this->db) {
            die('Database connection failed. Please check your database credentials.');
        }
        $this->productModel = new AddProductModel($this->db);  // Instantiate the model
    }

    public function addProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Schema DDL must run before beginTransaction(); MySQL implicitly commits active transactions for DDL.
            $mediaService = new ProductMediaService($this->db);

            // Start a database transaction to ensure data integrity
            $this->db->beginTransaction();

            try {
                // Collect POST data (product data)
                $product_type = in_array($_POST['product_type'] ?? 'simple', ['simple','variable']) ? ($_POST['product_type'] ?? 'simple') : 'simple';
                $subcategoryId = !empty($_POST['subcategory_id']) ? (int) $_POST['subcategory_id'] : null;
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $catalogModel = new CatalogModel();
                if ($subcategoryId && !$catalogModel->validateSubcategoryForParent($subcategoryId, $categoryId)) {
                    $this->rollbackIfActive();
                    $_SESSION['message'] = 'Error: Selected subcategory does not belong to the chosen category.';
                    $_SESSION['message_type'] = 'error';
                    header('Location: ' . url('admin/add-product.php'));
                    exit();
                }

                $productData = [
                    'product_name' => $_POST['product_name'],
                    'product_slug' => trim(preg_replace('/-+/', '-', str_replace(' ', '-', trim($_POST['product_slug']))), '-'),
                    'category_id' => $categoryId,
                    'subcategory_id' => $subcategoryId,
                    'brand_id' => $_POST['brand_id'],
                    'product_description' => $_POST['product_description'],
                    'short_description' => $_POST['short_description'],
                    'product_status' => mapProductStatusFromForm($_POST['product_status'] ?? 'inactive'),
                    'expected_coming_date' => resolveExpectedComingDateFromPost($_POST['product_status'] ?? 'inactive', $_POST['expected_coming_date'] ?? null),
                    'stock_quantity' => $_POST['stock_quantity'],
                    'regular_price' => $_POST['regular_price'],
                    'sale_price' => $_POST['sale_price'],
                    'product_sku' => !empty($_POST['product_sku']) ? $_POST['product_sku'] : NULL,
                    'weight_kg' => !empty($_POST['weight_kg']) ? $_POST['weight_kg'] : NULL,
                    'length_cm' => !empty($_POST['length_cm']) ? $_POST['length_cm'] : NULL,
                    'width_cm' => !empty($_POST['width_cm']) ? $_POST['width_cm'] : NULL,
                    'height_cm' => !empty($_POST['height_cm']) ? $_POST['height_cm'] : NULL,
                    'tax_class' => isset($_POST['tax_class']) && is_numeric($_POST['tax_class']) ? $_POST['tax_class'] : NULL,
                    'product_tag' => $_POST['product_tag'],
                    'prepaid_discount_amount' => isset($_POST['prepaid_discount_amount']) && is_numeric($_POST['prepaid_discount_amount']) ? max(0, (float)$_POST['prepaid_discount_amount']) : 0,
                    'is_b2b_available' => isset($_POST['is_b2b_available']) ? 1 : 0,
                    'b2b_regular_price' => (!empty($_POST['b2b_regular_price']) && is_numeric($_POST['b2b_regular_price']))
                        ? (float) $_POST['b2b_regular_price']
                        : null,
                ];

                // Ensure slug is unique — auto-append numeric suffix if a duplicate exists
                $slugCheck = $this->db->prepare('SELECT product_id FROM products WHERE product_slug = ?');
                $slugCheck->execute([$productData['product_slug']]);
                if ($slugCheck->fetch()) {
                    $originalSlug = $productData['product_slug'];
                    $counter = 2;
                    do {
                        $productData['product_slug'] = $originalSlug . '-' . $counter++;
                        $slugCheck->execute([$productData['product_slug']]);
                    } while ($slugCheck->fetch());
                }

                // Validate SKU uniqueness if a SKU was provided
                if (!empty($productData['product_sku'])) {
                    $skuCheck = $this->db->prepare('SELECT product_id FROM products WHERE product_sku = ?');
                    $skuCheck->execute([$productData['product_sku']]);
                    if ($skuCheck->fetch()) {
                        $this->rollbackIfActive();
                        $_SESSION['message'] = 'Error: SKU "' . htmlspecialchars($productData['product_sku']) . '" is already used by another product.';
                        $_SESSION['message_type'] = 'error';
                        header('Location: ' . url('admin/add-product.php'));
                        exit();
                    }
                }

                // Fetch category and brand info needed for SEO auto-fill and canonical URL
                $catStmt = $this->db->prepare('SELECT category_name, slug FROM categories WHERE category_id = ?');
                $catStmt->execute([$productData['category_id']]);
                $catRow = $catStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $brandStmt = $this->db->prepare('SELECT brand_name, slug FROM brands WHERE brand_id = ?');
                $brandStmt->execute([$productData['brand_id']]);
                $brandRow = $brandStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                // Auto-expand short name into Amazon/Daraz-style title (slug stays as entered).
                enrichProductTitleFromContext(
                    $productData,
                    (string) ($brandRow['brand_name'] ?? ''),
                    (string) ($catRow['category_name'] ?? '')
                );

                $subSlug = null;
                if ($subcategoryId) {
                    $subStmt = $this->db->prepare('SELECT slug FROM categories WHERE category_id = ? AND parent_id IS NOT NULL LIMIT 1');
                    $subStmt->execute([$subcategoryId]);
                    $subRow = $subStmt->fetch(PDO::FETCH_ASSOC);
                    $subSlug = $subRow['slug'] ?? null;
                }

                // Insert the product into the database
                $query = 'INSERT INTO products (product_name, product_slug, category_id, subcategory_id, brand_id, product_description,
                                                short_description, product_status, expected_coming_date, stock_quantity, regular_price, sale_price,
                                                product_sku, weight_kg, length_cm, width_cm, height_cm, tax_class, created_at, updated_at, product_tag, product_type, prepaid_discount_amount, is_b2b_available, b2b_regular_price)
                          VALUES (:product_name, :product_slug, :category_id, :subcategory_id, :brand_id, :product_description,
                                  :short_description, :product_status, :expected_coming_date, :stock_quantity, :regular_price, :sale_price,
                                  :product_sku, :weight_kg, :length_cm, :width_cm, :height_cm, :tax_class, NOW(), NOW(), :product_tag, :product_type, :prepaid_discount_amount, :is_b2b_available, :b2b_regular_price)';
                $productData['product_type'] = $product_type;
                $stmt = $this->db->prepare($query);
                $stmt->execute($productData);
                $productId = $this->db->lastInsertId();  // Get the last inserted product ID

                // Auto-fill SEO fields if the user left them empty
                $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                $monthYear = $months[(int)date('n') - 1] . ' ' . date('Y');

                $seoTitle = !empty(trim($_POST['seo_title'] ?? ''))
                    ? trim($_POST['seo_title'])
                    : ($productData['product_name'] . ' Price in Pakistan ' . $monthYear . ' | Phones Dukan');

                $seoDesc = trim($_POST['seo_description'] ?? '');
                if (empty($seoDesc)) {
                    $brandName = $brandRow['brand_name'] ?? '';
                    $priceStr  = !empty($productData['regular_price'])
                        ? 'Starting from Rs. ' . number_format((float)$productData['regular_price'], 0) . '. '
                        : '';
                    $seoDesc = 'Buy ' . $productData['product_name'] . ' in Pakistan at the best price. ' . $priceStr
                        . ($brandName ? 'PTA-approved ' . $brandName . ' with official warranty. ' : '')
                        . 'Fast delivery across Pakistan. Shop now at Phones Dukan.';
                    if (strlen($seoDesc) > 160) {
                        $seoDesc = substr($seoDesc, 0, 157) . '...';
                    }
                }

                $focusKeyword = !empty(trim($_POST['focus_keyword'] ?? ''))
                    ? trim($_POST['focus_keyword'])
                    : (strtolower($productData['product_name']) . ' price in pakistan');

                $canonicalUrl = !empty(trim($_POST['canonical_url'] ?? ''))
                    ? trim($_POST['canonical_url'])
                    : buildProductCanonicalUrl(
                        (string) ($brandRow['slug'] ?? ''),
                        (string) ($catRow['slug'] ?? ''),
                        (string) $productData['product_slug'],
                        $subSlug
                    );

                $secondaryKeywords = trim($_POST['secondary_keywords'] ?? '');

                // Insert SEO data
                $seoData = [
                    'product_id'         => $productId,
                    'seo_title'          => $seoTitle,
                    'seo_description'    => $seoDesc,
                    'focus_keyword'      => $focusKeyword,
                    'canonical_url'      => $canonicalUrl,
                    'secondary_keywords' => $secondaryKeywords,
                ];

                $seoQuery = 'INSERT INTO product_seo
                                (product_id, seo_title, seo_description, focus_keyword, canonical_url, secondary_keywords)
                             VALUES
                                (:product_id, :seo_title, :seo_description, :focus_keyword, :canonical_url, :secondary_keywords)';
                $seoStmt = $this->db->prepare($seoQuery);
                $seoStmt->execute($seoData);

                $productStatusInt = (int) $productData['product_status'];
                $imageStatus = $productStatusInt === 1 ? 1 : 0;
                $keyToImageId = [];

                // Handle Primary Image Upload
                if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] == 0) {
                    $primaryImageName = $_FILES['primary_image']['name'];
                    $primaryImageTmpName = $_FILES['primary_image']['tmp_name'];
                    $targetDir = dirname(__DIR__, 2) . '/public/uploads/';

                    // Get the original file name and extension
                    $fileExtension = pathinfo($primaryImageName, PATHINFO_EXTENSION);
                    $fileNameWithoutExtension = pathinfo($primaryImageName, PATHINFO_FILENAME);

                    // Sanitize the original file name (remove special characters, replace spaces with dashes)
                    $sanitizedFileName = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($fileNameWithoutExtension)));

                    // Check if the file already exists in the target directory
                    $newFileName = $sanitizedFileName . '.' . $fileExtension;
                    $targetFile = $targetDir . $newFileName;

                    // If file already exists, append a unique suffix
                    $counter = 1;
                    while (file_exists($targetFile)) {
                        $newFileName = $sanitizedFileName . '-' . $counter . '.' . $fileExtension;
                        $targetFile = $targetDir . $newFileName;
                        $counter++;
                    }

                    // Move the uploaded file to the target directory
                    if (move_uploaded_file($primaryImageTmpName, $targetFile)) {
                        // Insert primary image into product_images table
                        $imageData = [
                            'image_url' => normalizeStoredUploadPath('/public/uploads/' . $newFileName),
                            'is_primary' => 1,  // Set primary flag to 1 for primary image
                            'status' => $imageStatus,
                            'product_id' => $productId
                        ];

                        $imageQuery = 'INSERT INTO product_images (image_url, is_primary, status, product_id) 
                       VALUES (:image_url, :is_primary, :status, :product_id)';
                        $imageStmt = $this->db->prepare($imageQuery);
                        $imageStmt->execute($imageData);

                        // Get the image ID for the primary image
                        $primaryImageId = $this->db->lastInsertId();
                        $keyToImageId['primary'] = (int) $primaryImageId;

                        // Insert metadata for the primary image
                        $metaQuery = 'INSERT INTO image_metadata (image_id, alt_text, meta_id, title, caption, description) 
                      VALUES (:image_id, :alt_text, :meta_id, :title, :caption, :description)';
                        $primaryImageMetaData = [
                            'image_id' => $primaryImageId,
                            'alt_text' => $_POST['primary_alt_text'],  // Alt text for primary image
                            'meta_id' => null,  // Auto-incremented by MySQL
                            'title' => $_POST['primary_image_title'],
                            'caption' => $_POST['primary_image_caption'],
                            'description' => $_POST['primary_image_description']
                        ];
                        $metaStmt = $this->db->prepare($metaQuery);
                        $metaStmt->execute($primaryImageMetaData);
                    } else {
                        $this->rollbackIfActive();
                        $_SESSION['message'] = 'There was an error uploading the primary image.';
                        $_SESSION['message_type'] = 'error';
                        header('Location: ' . url('admin/add-product.php'));
                        exit();
                    }
                }

                // Handle Gallery Images Upload
                if (isset($_FILES['gallery_images']) && !empty(array_filter($_FILES['gallery_images']['name']))) {
                    foreach ($_FILES['gallery_images']['name'] as $index => $name) {
                        if (empty($name)) {
                            continue; // Skip if no file selected
                        }
                        $galleryImageTmpName = $_FILES['gallery_images']['tmp_name'][$index];
                        $targetDir = dirname(__DIR__, 2) . '/public/uploads/';

                        // Get the original file name and extension
                        $fileExtension = pathinfo($name, PATHINFO_EXTENSION);
                        $fileNameWithoutExtension = pathinfo($name, PATHINFO_FILENAME);

                        // Sanitize the original file name (remove special characters, replace spaces with dashes)
                        $sanitizedFileName = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($fileNameWithoutExtension)));

                        // Check if the file already exists in the target directory
                        $newFileName = $sanitizedFileName . '.' . $fileExtension;
                        $targetFile = $targetDir . $newFileName;

                        // If file already exists, append a unique suffix
                        $counter = 1;
                        while (file_exists($targetFile)) {
                            $newFileName = $sanitizedFileName . '-' . $counter . '.' . $fileExtension;
                            $targetFile = $targetDir . $newFileName;
                            $counter++;
                        }

                        if (move_uploaded_file($galleryImageTmpName, $targetFile)) {
                            // Insert gallery image into product_images table
                            $imageData = [
                                'image_url' => normalizeStoredUploadPath('/public/uploads/' . $newFileName),
                                'is_primary' => 0,  // Set primary flag to 0 for gallery images
                                'status' => $imageStatus,
                                'product_id' => $productId
                            ];

                            $imageQuery = 'INSERT INTO product_images (image_url, is_primary, status, product_id) 
                           VALUES (:image_url, :is_primary, :status, :product_id)';
                            $imageStmt = $this->db->prepare($imageQuery);
                            $imageStmt->execute($imageData);

                            $galleryImageId = $this->db->lastInsertId();  // Get the last inserted gallery image ID
                            $keyToImageId['gallery-' . $index] = (int) $galleryImageId;

                            // Insert metadata for the gallery image
                            $metaQuery = 'INSERT INTO image_metadata (image_id, alt_text, meta_id, title, caption, description) 
                          VALUES (:image_id, :alt_text, :meta_id, :title, :caption, :description)';
                            $galleryImageMetaData = [
                                'image_id' => $galleryImageId,
                                'alt_text' => $_POST['alt_text_gallery'][$index],  // Alt text from form input
                                'meta_id' => null,  // Auto-incremented by MySQL
                                'title' => $_POST['image_title_gallery'][$index],
                                'caption' => $_POST['image_caption_gallery'][$index],
                                'description' => $_POST['image_description_gallery'][$index]
                            ];
                            $metaStmt = $this->db->prepare($metaQuery);
                            $metaStmt->execute($galleryImageMetaData);
                        } else {
                            $this->rollbackIfActive();
                            $_SESSION['message'] = 'There was an error uploading a gallery image.';
                            $_SESSION['message_type'] = 'error';
                            header('Location: ' . url('admin/add-product.php'));
                            exit();
                        }
                    }
                }

                // Keep image visibility aligned with product status and guarantee a primary thumbnail.
                $this->productModel->syncImageStatusForProduct((int) $productId, $imageStatus);
                $this->productModel->ensurePrimaryImageExists((int) $productId);

                // Save product video and gallery order
                $mediaService->saveFromRequest($productId, $_POST, $_FILES);
                $mediaService->applyGalleryOrder(
                    $productId,
                    $_POST['gallery_order_json'] ?? '[]',
                    $keyToImageId
                );

                // Save product variations if variable
                if ($product_type === 'variable' && !empty($_POST['variations_json'])) {
                    $variationsJson = $_POST['variations_json'];
                    $variations = json_decode($variationsJson, true);
                    if (is_array($variations) && !empty($variations)) {
                        $varModel = new VariationModel();
                        $varModel->saveProductVariations($productId, $variations);
                    }
                }

                require_once __DIR__ . '/../Models/ProductGroupModel.php';
                $groupIds = isset($_POST['group_product_ids']) && is_array($_POST['group_product_ids'])
                    ? $_POST['group_product_ids']
                    : [];
                $groupPrices = isset($_POST['group_product_prices']) && is_array($_POST['group_product_prices'])
                    ? $_POST['group_product_prices']
                    : [];
                (new ProductGroupModel($this->db))->saveGroupProducts((int) $productId, $groupIds, $groupPrices);

                // Commit the transaction
                $this->commitIfActive();

                // Redirect after successful addition
                $_SESSION['message'] = 'Product added successfully!';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . url('admin/manage-products.php'));
                exit();
            } catch (Exception $e) {
                // Rollback only when the transaction is still open
                $this->rollbackIfActive();
                $_SESSION['message'] = 'There was an error: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header('Location: ' . url('admin/add-product.php'));
                exit();
            }
        }

        // Load the view for adding a product
        require_once 'add-product.php';
    }

    private function commitIfActive(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    private function rollbackIfActive(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
