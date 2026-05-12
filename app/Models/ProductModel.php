<?php
require_once __DIR__ . '/../../database/db.php';

class ProductModel
{
    private PDO $db;

    /**
     * @throws RuntimeException If database connection fails
     */
    public function __construct()
    {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to connect to database');
        }
        error_log("Using ProductModel version 2025-05-19-3");
    }

    /**
     * @param string $category_slug
     * @param string $brand_slug
     * @param string $product_slug
     * @return array<string,mixed>|null
     */
    private static function normalizeSlug(string $slug): string
    {
        // Replace spaces and multiple hyphens with a single hyphen, trim edges
        return trim(preg_replace('/-+/', '-', str_replace(' ', '-', $slug)), '-');
    }

    public function getProductBySlug(string $category_slug, string $brand_slug, string $product_slug): ?array
    {
        // Normalize: spaces → hyphens so URL and DB slugs always match
        $product_slug  = self::normalizeSlug($product_slug);
        $category_slug = self::normalizeSlug($category_slug);
        $brand_slug    = self::normalizeSlug($brand_slug);

        $query = 'SELECT p.*, c.slug AS category_slug, b.slug AS brand_slug, p.regular_price, p.sale_price
                  FROM products p
                  INNER JOIN categories c ON p.category_id = c.category_id
                  INNER JOIN brands b ON p.brand_id = b.brand_id
                  WHERE REPLACE(c.slug, " ", "-") = :category_slug
                  AND REPLACE(b.slug, " ", "-") = :brand_slug
                  AND REPLACE(p.product_slug, " ", "-") = :product_slug
                  LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_slug', $category_slug, PDO::PARAM_STR);
        $stmt->bindParam(':brand_slug', $brand_slug, PDO::PARAM_STR);
        $stmt->bindParam(':product_slug', $product_slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Case-insensitive fallback: useful when URL uses wrong casing.
     */
    public function getProductBySlugCaseInsensitive(string $category_slug, string $brand_slug, string $product_slug): ?array
    {
        $query = 'SELECT p.*, c.slug AS category_slug, b.slug AS brand_slug, p.regular_price, p.sale_price
                  FROM products p
                  INNER JOIN categories c ON p.category_id = c.category_id
                  INNER JOIN brands b ON p.brand_id = b.brand_id
                  WHERE LOWER(c.slug) = LOWER(:category_slug)
                  AND LOWER(b.slug) = LOWER(:brand_slug)
                  AND LOWER(p.product_slug) = LOWER(:product_slug)
                  LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_slug', $category_slug, PDO::PARAM_STR);
        $stmt->bindParam(':brand_slug', $brand_slug, PDO::PARAM_STR);
        $stmt->bindParam(':product_slug', $product_slug, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Last-resort fallback: find a product by product_slug alone.
     * Used to detect URL/slug mismatches and issue a 301 redirect.
     */
    public function getProductByProductSlugOnly(string $product_slug): ?array
    {
        $query = 'SELECT p.*, c.slug AS category_slug, b.slug AS brand_slug, p.regular_price, p.sale_price
                  FROM products p
                  INNER JOIN categories c ON p.category_id = c.category_id
                  INNER JOIN brands b ON p.brand_id = b.brand_id
                  WHERE p.product_slug = :product_slug
                  LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_slug', $product_slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param int $product_id
     * @return array<int,array<string,mixed>>
     */
    public function getProductImages(int $product_id): array
    {
        $sql = '
            SELECT 
                pi.image_url, 
                pi.is_primary, 
                im.alt_text, 
                im.title, 
                im.description, 
                im.caption
            FROM product_images pi
            LEFT JOIN image_metadata im ON pi.image_id = im.image_id
            WHERE pi.product_id = :product_id
            ORDER BY pi.is_primary DESC, pi.image_id ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $query
     * @param int $limit
     * @return array<int,array<string,mixed>>
     */
    public function searchProducts(string $query, int $limit = 10): array
    {
        $query = trim($query);
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $limit = max(1, $limit);

        $stmt = $this->db->prepare('
            SELECT 
                p.product_name, 
                p.product_slug, 
                c.slug AS category_slug, 
                b.slug AS brand_slug, 
                pi.image_url 
            FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            WHERE p.product_status = 1
            AND p.product_name LIKE :query
            AND pi.is_primary = 1
            LIMIT :limit
        ');

        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $product_id
     * @return array<int,array<string,mixed>>
     */
    public function getProductReviews(int $product_id): array
    {
        $query = "SELECT author, rating, content, created_at 
                  FROM reviews 
                  WHERE product_id = :product_id AND status = 'approved'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $product_id
     * @return array<string,mixed>|null
     */
    public function getProductDetails(int $product_id): ?array
    {
        $query = 'SELECT p.*, 
                         b.brand_name, 
                         c.category_name, 
                         c.slug AS category_slug, 
                         b.slug AS brand_slug
                  FROM products p
                  LEFT JOIN brands b ON p.brand_id = b.brand_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.product_id = :product_id';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param int $product_id
     * @return array<int,array<string,mixed>>
     */
    public function getProductAttributes(int $product_id): array
    {
        $query = "
            SELECT 
                pa.attribute_id,
                pa.attribute_name, 
                pav.value_id,
                pav.value AS attribute_value, 
                pap.regular_price, 
                pap.sale_price
            FROM product_attributes pa
            JOIN product_attribute_prices pap ON pa.attribute_id = pap.attribute_id
            JOIN product_attribute_values pav ON pap.value_id = pav.value_id
            WHERE pap.product_id = :product_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generates JSON-LD schema for a product.
     * @param int $product_id
     * @return string
     */
    public function generateProductSchema(int $product_id): string
    {
        error_log("START: Generating schema for product ID {$product_id} at " . date('Y-m-d H:i:s'));
    
        $product = $this->getProductDetails($product_id);
        if (!$product) {
            error_log("ERROR: No product found for ID {$product_id}");
            return json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        error_log("Product data retrieved for ID {$product_id}: " . json_encode($product, JSON_UNESCAPED_UNICODE));
    
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
    
        // Get product images
        $product_images = $this->getProductImages($product_id);
        $image_urls = array_filter(array_map(function ($img) use ($baseUrl): ?string {
            return !empty($img['image_url']) ? $baseUrl . $img['image_url'] : null;
        }, $product_images));
        if (empty($image_urls)) {
            $image_urls = [$baseUrl . '/default-image.jpg'];
        }
    
        $sku = $product['product_sku'] ?? 'product-' . $product['product_id'];
    
        $description = $product['short_description'] ?? $product['product_description'] ?? 'No description available.';
        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);
        if ($description === 'No description available.') {
            $description = $product['product_name'] . ' is a high-quality mobile phone with advanced features.';
        }
    
        $category_slug = $product['category_slug'] ?? 'mobiles';
        $brand_slug = $product['brand_slug'] ?? 'brand';
        $product_slug = $product['product_slug'] ?? $this->slugify($product['product_name']);
        $product_url = "{$baseUrl}/{$category_slug}/{$brand_slug}/{$product_slug}";
    
        // Get reviews
        $reviews = $this->getProductReviews($product_id);
        $reviews_schema = [];
        $total_rating = 0;
        $review_count = 0;
        foreach ($reviews as $review) {
            $reviewBody = $this->decodeToPlainText($review['content']);
            $reviews_schema[] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $review['author']
                ],
                'datePublished' => $review['created_at'],
                'reviewBody' => $reviewBody,
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (int)$review['rating'],
                    'bestRating' => 5,
                    'worstRating' => 1
                ]
            ];
            $total_rating += (int)$review['rating'];
            $review_count++;
        }
        $aggregate_rating = $review_count > 0 ? round($total_rating / $review_count, 2) : 0;
    
        // Initialize main schema
        $main_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['product_name'],
            'productID' => (string)$product['product_id'],
            'url' => $product_url,
            'image' => $image_urls,
            'category' => $product['category_name'] ?? 'Mobiles',
            'brand' => [
                '@type' => 'Brand',
                'name' => trim($product['brand_name'] ?? 'Unknown', '.')
            ],
            'description' => $description,
            'sku' => $sku
        ];
    
        $availability = ($product['product_status'] == 1 && ($product['stock_quantity'] ?? 0) > 0)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
    
        $attributes = $this->getProductAttributes($product_id);
        error_log("Attributes for product ID {$product_id}: " . (empty($attributes) ? 'None' : json_encode($attributes, JSON_UNESCAPED_UNICODE)));
    
        // Add additionalProperty for attributes (e.g., Storage, Color)
        if (!empty($attributes)) {
            // Group attributes by name
            $groupedProperties = [];
            foreach ($attributes as $attribute) {
                $name = $attribute['attribute_name'] ?? 'Storage';
                $value = $attribute['attribute_value'] ?? ($attribute['storage'] ?? 'Unknown');
                if (!isset($groupedProperties[$name])) {
                    $groupedProperties[$name] = [];
                }
                if ($value !== 'Unknown') {
                    $groupedProperties[$name][] = $value;
                }
            }
            
            // Build additionalProperty
            $main_schema['additionalProperty'] = [];
            foreach ($groupedProperties as $name => $values) {
                if (!empty($values)) {
                    $main_schema['additionalProperty'][] = [
                        '@type' => 'PropertyValue',
                        'name' => $name,
                        'value' => implode(', ', array_unique($values))
                    ];
                }
            }
            error_log("Added additionalProperty for product ID {$product_id}: " . json_encode($main_schema['additionalProperty'], JSON_UNESCAPED_UNICODE));
        }
    
        // Handle product offers
        if (!empty($attributes)) {
            error_log("Processing attributed product ID {$product_id}");
            $offers = [];
    
            foreach ($attributes as $index => $attribute) {
                $regular_price = (isset($attribute['regular_price']) && is_numeric($attribute['regular_price'])) ? (float)$attribute['regular_price'] : 0;
                $sale_price = (isset($attribute['sale_price']) && is_numeric($attribute['sale_price'])) ? (float)$attribute['sale_price'] : 0;
                $variantPrice = ($sale_price > 0) ? $sale_price : $regular_price;
                $variantPrice = is_numeric($variantPrice) ? number_format((float)$variantPrice, 2, '.', '') : '0.00';
    
                $variantSku = $sku . '-' . ($attribute['value_id'] ?? ($index + 1));
                if ($variantSku === '-') {
                    $variantSku = $sku . '-' . ($index + 1);
                }
    
                $offer = [
                    '@type' => 'Offer',
                    'priceCurrency' => 'PKR',
                    'price' => $variantPrice,
                    'availability' => $availability,
                    'url' => $product_url . '?variant=' . ($attribute['value_id'] ?? ($index + 1)),
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'priceValidUntil' => date('Y-m-d', strtotime('+5 years')),
                    'shippingDetails' => [
                        '@type' => 'OfferShippingDetails',
                        'shippingRate' => [
                            '@type' => 'MonetaryAmount',
                            'currency' => 'PKR',
                            'value' => '0'
                        ],
                        'shippingDestination' => [
                            '@type' => 'DefinedRegion',
                            'addressCountry' => 'PK'
                        ]
                    ],
                    'hasMerchantReturnPolicy' => [
                        '@type' => 'MerchantReturnPolicy',
                        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                        'returnWindow' => 'P14D'
                    ]
                ];
    
                $price_specifications = [
                    [
                        '@type' => 'UnitPriceSpecification',
                        'priceCurrency' => 'PKR',
                        'price' => $variantPrice,
                        'valueAddedTaxIncluded' => true,
                        'eligibleQuantity' => [
                            '@type' => 'QuantitativeValue',
                            'value' => 1
                        ]
                    ]
                ];
    
                if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
                    $price_specifications[] = [
                        '@type' => 'UnitPriceSpecification',
                        'priceType' => 'https://schema.org/ListPrice',
                        'priceCurrency' => 'PKR',
                        'price' => number_format($regular_price, 2, '.', ''),
                        'valueAddedTaxIncluded' => true,
                        'eligibleQuantity' => [
                            '@type' => 'QuantitativeValue',
                            'value' => 1
                        ]
                    ];
                }
    
                $offer['priceSpecification'] = count($price_specifications) > 1 ? $price_specifications : $price_specifications[0];
                $offers[] = $offer;
            }
    
            $prices = array_map(function ($attr): float|int {
                return (isset($attr['sale_price']) && $attr['sale_price'] > 0)
                    ? $attr['sale_price']
                    : (isset($attr['regular_price']) && $attr['regular_price'] > 0 ? $attr['regular_price'] : 0);
            }, $attributes);
            $minPrice = !empty($prices) ? min(array_filter($prices, 'is_numeric')) : 0;
            $maxPrice = !empty($prices) ? max(array_filter($prices, 'is_numeric')) : 0;
            $minPrice = is_numeric($minPrice) ? number_format((float)$minPrice, 2, '.', '') : '0.00';
            $maxPrice = is_numeric($maxPrice) ? number_format((float)$maxPrice, 2, '.', '') : '0.00';
    
            $main_schema['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'PKR',
                'lowPrice' => $minPrice,
                'highPrice' => $maxPrice,
                'offerCount' => count($attributes),
                'availability' => $availability,
                'url' => $product_url,
                'itemCondition' => 'https://schema.org/NewCondition',
                'priceValidUntil' => date('Y-m-d', strtotime('+5 years')),
                'offers' => $offers,
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'currency' => 'PKR',
                        'value' => '0'
                    ],
                    'shippingDestination' => [
                        '@type' => 'DefinedRegion',
                        'addressCountry' => 'PK'
                    ]
                ],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'returnWindow' => 'P14D'
                ]
            ];
        } else {
            error_log("Processing non-attributed product ID {$product_id}");
            $regular_price = isset($product['regular_price']) && is_numeric($product['regular_price']) ? (float)$product['regular_price'] : 0;
            $sale_price = isset($product['sale_price']) && is_numeric($product['sale_price']) ? (float)$product['sale_price'] : 0;
            $final_price = ($sale_price > 0) ? $sale_price : $regular_price;
    
            if ($final_price <= 0) {
                if (preg_match('/Price in Pakistan:[\s]*(\d+(?:\.\d{1,2})?)(?:\s*PKR)?/i', $description, $matches)) {
                    $final_price = (float)$matches[1];
                    error_log("Price extracted from description for product ID {$product_id}: {$final_price}");
                } else {
                    error_log("Price extraction failed for product ID {$product_id}. Description: {$description}");
                    $final_price = 0;
                }
            }
            $final_price = number_format($final_price, 2, '.', '');
    
            error_log("Non-attribute product ID {$product_id} - sale_price: {$sale_price}, regular_price: {$regular_price}, final_price: {$final_price}");
    
            $main_schema['offers'] = [
                '@type' => 'Offer',
                'url' => $product_url,
                'priceCurrency' => 'PKR',
                'price' => $final_price,
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $availability,
                'priceValidUntil' => date('Y-m-d', strtotime('+5 years')),
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'currency' => 'PKR',
                        'value' => '0'
                    ],
                    'shippingDestination' => [
                        '@type' => 'DefinedRegion',
                        'addressCountry' => 'PK'
                    ]
                ],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'returnWindow' => 'P14D'
                ]
            ];
    
            $price_specifications = [
                [
                    '@type' => 'UnitPriceSpecification',
                    'priceCurrency' => 'PKR',
                    'price' => $final_price,
                    'valueAddedTaxIncluded' => true,
                    'eligibleQuantity' => [
                        '@type' => 'QuantitativeValue',
                        'value' => 1
                    ]
                ]
            ];
    
            if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
                $price_specifications[] = [
                    '@type' => 'UnitPriceSpecification',
                    'priceType' => 'https://schema.org/ListPrice',
                    'priceCurrency' => 'PKR',
                    'price' => number_format($regular_price, 2, '.', ''),
                    'valueAddedTaxIncluded' => true,
                    'eligibleQuantity' => [
                        '@type' => 'QuantitativeValue',
                        'value' => 1
                    ]
                ];
            }
    
            $main_schema['offers']['priceSpecification'] = count($price_specifications) > 1 ? $price_specifications : $price_specifications[0];
        }
    
        if (!empty($reviews_schema)) {
            $main_schema['review'] = $reviews_schema;
            $main_schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $aggregate_rating,
                'reviewCount' => $review_count
            ];
        }
    
        // Add BreadcrumbList schema
        $breadcrumb_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $baseUrl
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $product['category_name'] ?? ucwords(str_replace('-', ' ', $category_slug)),
                    'item' => $baseUrl . '/' . $category_slug
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $product['brand_name'] ?? ucwords(str_replace('-', ' ', $brand_slug)),
                    'item' => $baseUrl . '/' . $category_slug . '/' . $brand_slug
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $product['product_name'],
                    'item' => $product_url
                ]
            ]
        ];
    
        // Output schema
        $schema_output = [$main_schema, $breadcrumb_schema];
    
        error_log("Final schema for product ID {$product_id}: " . json_encode($schema_output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    
        return json_encode($schema_output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * @param string $content
     * @return string
     */
    private function decodeToPlainText(string $content): string
    {
        $content = preg_replace_callback('/\\\\u([0-9A-Fa-f]{4})\\\\u([0-9A-Fa-f]{4})/', function ($matches): string {
            return mb_convert_encoding(pack('H*', $matches[1] . $matches[2]), 'UTF-8', 'UTF-16');
        }, $content);

        $content = preg_replace_callback('/\\\\u([0-9A-Fa-f]{4})/', function ($matches): string {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16');
        }, $content);

        $content = preg_replace('/[\x{1F600}-\x{1F64F}'
            . '\x{1F300}-\x{1F5FF}'
            . '\x{1F680}-\x{1F6FF}'
            . '\x{2600}-\x{26FF}'
            . '\x{2700}-\x{27BF}]/u', '', $content);

        return trim(preg_replace('/\s+/', ' ', $content), '"');
    }

    /**
     * @param string $string
     * @return string
     */
    private function slugify(string $string): string
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]+/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return rtrim($string, '-');
    }

    /**
     * @param int $product_id
     * @return string|null
     */
    public function getProductImageUrl(int $product_id): ?string
    {
        $query = 'SELECT image_url FROM product_images WHERE product_id = :product_id ORDER BY image_id ASC LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['image_url'] ?? null;
    }

    /**
     * @param int $categoryId
     * @param int $brandId
     * @param int $excludeProductId
     * @param int $limit
     * @return array<int,array<string,mixed>>
     */
    public function getRelatedProducts(int $categoryId, int $brandId, int $excludeProductId, int $limit = 4): array
    {
        $sql = 'SELECT 
                    p.product_id,
                    p.product_name,
                    p.product_slug,
                    p.category_id,
                    p.brand_id,
                    p.sale_price,
                    p.regular_price,
                    c.slug AS category_slug,
                    b.slug AS brand_slug,
                    (
                        SELECT pi.image_url 
                        FROM product_images pi 
                        WHERE pi.product_id = p.product_id 
                        ORDER BY pi.is_primary DESC, pi.image_id ASC 
                        LIMIT 1
                    ) AS image_url
                FROM products p
                INNER JOIN categories c ON c.category_id = p.category_id AND c.status = 1
                INNER JOIN brands b ON b.brand_id = p.brand_id
                WHERE p.product_status = 1
                  AND p.category_id = :category_id
                  AND p.brand_id = :brand_id
                  AND p.product_id != :exclude_id
                ORDER BY RAND()
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':brand_id', $brandId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_id', $excludeProductId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * For product listing cards: if the product is variable, return the lowest
     * active variation price with a "from_variation" flag so the template can
     * render "From Rs. X". Falls back to the regular product price otherwise.
     */
    public function getDisplayPrice(int $product_id, string $product_type, float $regular_price, ?float $sale_price): array
    {
        if ($product_type !== 'variable') {
            return [
                'from_variation' => false,
                'regular_price'  => $regular_price,
                'sale_price'     => $sale_price,
            ];
        }

        $stmt = $this->db->prepare(
            "SELECT MIN(IFNULL(sale_price, regular_price)) AS lowest
             FROM product_variations
             WHERE product_id = ? AND status = 1 AND stock_quantity > 0"
        );
        $stmt->execute([$product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $lowest = $row['lowest'] !== null ? (float)$row['lowest'] : null;
        if ($lowest !== null) {
            return [
                'from_variation' => true,
                'regular_price'  => $lowest,
                'sale_price'     => null,
            ];
        }

        return [
            'from_variation' => false,
            'regular_price'  => $regular_price,
            'sale_price'     => $sale_price,
        ];
    }

}
?>