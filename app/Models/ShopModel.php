<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct database connection

class ProductModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    private function addActiveProductCondition(&$whereClauses) {
        $whereClauses[] = "p.product_status != '0' AND LOWER(p.product_status) != 'out of stock'";
    }
    
    public function getAllCategories() {
        $query = "SELECT c.category_id, c.category_name, c.slug
                  FROM categories c
                  WHERE c.status = 1
                    AND c.parent_id IS NULL
                    AND EXISTS (
                        SELECT 1
                        FROM products p
                        WHERE p.product_status != '0'
                          AND LOWER(p.product_status) != 'out of stock'
                          AND (
                              p.category_id = c.category_id
                              OR p.subcategory_id IN (
                                  SELECT sc.category_id
                                  FROM categories sc
                                  WHERE sc.parent_id = c.category_id
                              )
                          )
                    )
                  ORDER BY c.category_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Map selected category IDs to parent IDs (legacy subcategory filter URLs).
     *
     * @param array<int|string> $categoryIds
     * @return int[]
     */
    public function normalizeCategoryFilterIds(array $categoryIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $key = ':cat_' . $index;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $query = 'SELECT category_id, parent_id
                  FROM categories
                  WHERE category_id IN (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $parentIds = [];
        foreach ($rows as $row) {
            $parentIds[] = !empty($row['parent_id'])
                ? (int) $row['parent_id']
                : (int) $row['category_id'];
        }

        return array_values(array_unique($parentIds));
    }
    
    public function getAllBrands() {
        $query = "SELECT brand_id, brand_name, slug FROM brands";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Returns all active brands
    }
    
    public function applyPriceRangeFilter($filters, &$whereClauses, &$params) {
        $priceExpr = "(CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price ELSE p.regular_price END)";

        $hasMin = isset($filters['min_price']) && $filters['min_price'] !== '';
        $hasMax = isset($filters['max_price']) && $filters['max_price'] !== '';

        if ($hasMin || $hasMax) {
            $minPrice = $hasMin ? max(0, (int) $filters['min_price']) : null;
            $maxPrice = $hasMax ? max(0, (int) $filters['max_price']) : null;

            if ($minPrice !== null && $maxPrice !== null && $maxPrice < $minPrice) {
                $temp = $minPrice;
                $minPrice = $maxPrice;
                $maxPrice = $temp;
            }

            if ($minPrice !== null) {
                $whereClauses[] = "$priceExpr >= :min_price";
                $params[':min_price'] = $minPrice;
            }

            if ($maxPrice !== null) {
                $whereClauses[] = "$priceExpr <= :max_price";
                $params[':max_price'] = $maxPrice;
            }

            return;
        }

        if (!empty($filters['price_range'])) {
            $priceConditions = [];
            foreach ($filters['price_range'] as $index => $range) {
                if ($range === '150000-above') {
                    $priceConditions[] = "$priceExpr >= :price_above_$index";
                    $params[":price_above_$index"] = 150000;
                } elseif (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
                    $priceConditions[] = "($priceExpr BETWEEN :min_$index AND :max_$index)";
                    $params[":min_$index"] = (int)$matches[1];
                    $params[":max_$index"] = (int)$matches[2];
                }
            }
            if (!empty($priceConditions)) {
                $whereClauses[] = '(' . implode(' OR ', $priceConditions) . ')';
            }
        }
    }

    public function applyBrandFilter($filters, &$whereClauses, &$params) {
        if (!empty($filters['brand'])) {
            $brandConditions = [];
            $index = 0;
    
            // Loop through each selected brand and bind a parameter for each
            foreach ($filters['brand'] as $brandId) {
                // Dynamically create placeholders for each selected brand
                $brandConditions[] = "p.brand_id = :brand_id_$index";
                $params[":brand_id_$index"] = $brandId; // Bind the brand_id
    
                $index++;
            }
    
            // If we have brand conditions, add them to the WHERE clause
            if (!empty($brandConditions)) {
                $whereClauses[] = '(' . implode(' OR ', $brandConditions) . ')';
            }
        }
    }
    
    public function applyCategoryFilter($filters, &$whereClauses, &$params) {
        if (empty($filters['category'])) {
            return;
        }

        $parentCategoryIds = $this->normalizeCategoryFilterIds((array) $filters['category']);
        if ($parentCategoryIds === []) {
            return;
        }

        $categoryConditions = [];
        foreach ($parentCategoryIds as $index => $parentCategoryId) {
            $categoryConditions[] = "(
                p.category_id = :category_id_$index
                OR p.subcategory_id IN (
                    SELECT category_id
                    FROM categories
                    WHERE parent_id = :parent_category_id_$index AND status = 1
                )
            )";
            $params[":category_id_$index"] = $parentCategoryId;
            $params[":parent_category_id_$index"] = $parentCategoryId;
        }

        $whereClauses[] = '(' . implode(' OR ', $categoryConditions) . ')';
    }
    
    public function getPaginatedProducts($limit, $offset, $filters = []) {
        $whereClauses = [];
        $params = [];
    
        $this->addActiveProductCondition($whereClauses);

        // Apply price range filter
        $this->applyPriceRangeFilter($filters, $whereClauses, $params);
    
        // Apply category filter
        $this->applyCategoryFilter($filters, $whereClauses, $params);
    
        // Apply brand filter
        $this->applyBrandFilter($filters, $whereClauses, $params);
    
        // Base query
        $query = "
            SELECT p.product_id, p.product_name, p.product_slug, 
                   p.regular_price, p.sale_price, p.stock_quantity, 
                   p.short_description, p.product_tag, p.product_status,
                   pi.image_url, c.slug AS category_slug, b.slug AS brand_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 
        ";
    
        // Add WHERE clause if filters are applied
        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }
    
        // Sorting by price
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'price_asc':
                    $query .= ' ORDER BY 
                                CASE 
                                    WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price
                                    ELSE p.regular_price 
                                END ASC'; // Low to High
                    break;
                case 'price_desc':
                    $query .= ' ORDER BY 
                                CASE 
                                    WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price
                                    ELSE p.regular_price 
                                END DESC'; // High to Low
                    break;
                default:
                    $query .= ' ORDER BY p.product_id DESC'; // Default sorting by product ID
            }
        } else {
            $query .= ' ORDER BY p.product_id DESC'; // Default sorting by product ID
        }
    
        // Limit & offset for pagination
        $query .= ' LIMIT :limit OFFSET :offset';
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
    
        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total product count (with filters applied)
    public function getTotalFilteredProductCount($filters = []) {
        $whereClauses = [];
        $params = [];

        $this->addActiveProductCondition($whereClauses);

        // Keep total count filtering exactly aligned with paginated query filters
        $this->applyPriceRangeFilter($filters, $whereClauses, $params);
        $this->applyCategoryFilter($filters, $whereClauses, $params);
        $this->applyBrandFilter($filters, $whereClauses, $params);

        // Base query for counting products
        $query = "
            SELECT COUNT(DISTINCT p.product_id)
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        ";

        // Add WHERE if needed
        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchColumn(); // Returns the total count of filtered products
    }
    
}
