<?php

require_once __DIR__ . '/../../database/db.php';

class CatalogModel
{
    private PDO $db;
    private static bool $schemaDone = false;

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
        } else {
            $this->db = (new Database())->getConnection();
        }
        self::ensureSchema($this->db);
    }

    public static function ensureSchema(PDO $db): void
    {
        if (self::$schemaDone) {
            return;
        }
        self::$schemaDone = true;

        $columns = [
            'categories' => [
                'parent_id' => 'INT NULL DEFAULT NULL',
                'sort_order' => 'INT NOT NULL DEFAULT 0',
                'show_on_homepage' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'homepage_image' => 'VARCHAR(512) NULL DEFAULT NULL',
            ],
            'products' => [
                'subcategory_id' => 'INT NULL DEFAULT NULL',
            ],
            'brands' => [
                'show_on_homepage' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'homepage_logo' => 'VARCHAR(512) NULL DEFAULT NULL',
            ],
        ];

        foreach ($columns as $table => $defs) {
            foreach ($defs as $column => $definition) {
                try {
                    $stmt = $db->prepare(
                        'SELECT COUNT(*) FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
                    );
                    $stmt->execute([$table, $column]);
                    if ((int) $stmt->fetchColumn() === 0) {
                        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                    }
                } catch (Throwable $e) {
                    error_log("CatalogModel schema ($table.$column): " . $e->getMessage());
                }
            }
        }

        try {
            $db->exec('ALTER TABLE categories ADD INDEX idx_categories_parent (parent_id)');
        } catch (Throwable $e) {
            // index may already exist
        }

        try {
            $db->exec('ALTER TABLE products ADD INDEX idx_products_subcategory (subcategory_id)');
        } catch (Throwable $e) {
            // index may already exist
        }
    }

    public static function makeSlug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        return $slug !== '' ? $slug : 'item';
    }

    // ── Brands ─────────────────────────────────────────────────────────────

    public function getAllBrands(): array
    {
        $stmt = $this->db->query(
            'SELECT brand_id, brand_name, slug, show_on_homepage, homepage_logo
             FROM brands
             ORDER BY brand_name ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getBrandById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT brand_id, brand_name, slug, show_on_homepage, homepage_logo
             FROM brands WHERE brand_id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getBrandBySlug(string $slug): ?array
    {
        $slug = self::makeSlug($slug);
        if ($slug === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT brand_id, brand_name, slug, show_on_homepage, homepage_logo
             FROM brands WHERE slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getBrandsWithProductsInCategory(int $categoryId): array
    {
        $sql = 'SELECT DISTINCT b.brand_id, b.brand_name, b.slug, b.homepage_logo, b.show_on_homepage
                FROM brands b
                INNER JOIN products p ON p.brand_id = b.brand_id
                WHERE p.product_status != 0
                  AND LOWER(p.product_status) != \'out of stock\'
                  AND (
                      p.category_id = :category_id
                      OR p.subcategory_id IN (
                          SELECT sc.category_id
                          FROM categories sc
                          WHERE sc.parent_id = :parent_id
                      )
                  )
                ORDER BY b.brand_name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':parent_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function brandSlugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM brands WHERE slug = ?';
        $params = [$slug];
        if ($excludeId) {
            $sql .= ' AND brand_id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function addBrand(string $name, string $slug, bool $showOnHomepage = false, ?string $homepageLogo = null): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO brands (brand_name, slug, show_on_homepage, homepage_logo) VALUES (?, ?, ?, ?)'
        );
        return $stmt->execute([$name, $slug, $showOnHomepage ? 1 : 0, $homepageLogo]);
    }

    public function updateBrand(
        int $id,
        string $name,
        string $slug,
        bool $showOnHomepage = false,
        ?string $homepageLogo = null,
        bool $updateLogo = false
    ): bool {
        if ($updateLogo) {
            $stmt = $this->db->prepare(
                'UPDATE brands SET brand_name = ?, slug = ?, show_on_homepage = ?, homepage_logo = ? WHERE brand_id = ?'
            );
            return $stmt->execute([$name, $slug, $showOnHomepage ? 1 : 0, $homepageLogo, $id]);
        }

        $stmt = $this->db->prepare(
            'UPDATE brands SET brand_name = ?, slug = ?, show_on_homepage = ? WHERE brand_id = ?'
        );
        return $stmt->execute([$name, $slug, $showOnHomepage ? 1 : 0, $id]);
    }

    public function countProductsUsingBrand(int $brandId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE brand_id = ?');
        $stmt->execute([$brandId]);
        return (int) $stmt->fetchColumn();
    }

    public function deleteBrand(int $id): bool
    {
        if ($this->countProductsUsingBrand($id) > 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM brands WHERE brand_id = ?');
        return $stmt->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function getHomepageBrands(): array
    {
        $sql = 'SELECT b.brand_id, b.brand_name, b.slug, b.homepage_logo
                FROM brands b
                WHERE b.show_on_homepage = 1
                  AND b.homepage_logo IS NOT NULL
                  AND b.homepage_logo != \'\'
                  AND EXISTS (
                      SELECT 1
                      FROM products p
                      WHERE p.brand_id = b.brand_id
                        AND p.product_status != 0
                        AND LOWER(p.product_status) != \'out of stock\'
                  )
                ORDER BY b.brand_name ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ── Categories (parent + sub) ──────────────────────────────────────────

    public function getParentCategories(bool $activeOnly = false): array
    {
        $sql = 'SELECT category_id, category_name, slug, status, sort_order, show_on_homepage, homepage_image
                FROM categories
                WHERE parent_id IS NULL';
        if ($activeOnly) {
            $sql .= ' AND status = 1';
        }
        $sql .= ' ORDER BY category_name ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSubcategories(int $parentId, bool $activeOnly = false): array
    {
        $sql = 'SELECT category_id, category_name, slug, status, sort_order, parent_id
                FROM categories
                WHERE parent_id = ?';
        $params = [$parentId];
        if ($activeOnly) {
            $sql .= ' AND status = 1';
        }
        $sql .= ' ORDER BY category_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getCategoryById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT category_id, category_name, slug, status, sort_order, parent_id, show_on_homepage, homepage_image
             FROM categories WHERE category_id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function categorySlugExists(string $slug, ?int $parentId, ?int $excludeId = null): bool
    {
        if ($parentId === null || $parentId === 0) {
            $sql = 'SELECT COUNT(*) FROM categories WHERE slug = ? AND parent_id IS NULL';
            $params = [$slug];
        } else {
            $sql = 'SELECT COUNT(*) FROM categories WHERE slug = ? AND parent_id = ?';
            $params = [$slug, $parentId];
        }
        if ($excludeId) {
            $sql .= ' AND category_id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function categoryNameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE category_name = ?';
        $params = [trim($name)];
        if ($excludeId) {
            $sql .= ' AND category_id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function addCategory(
        string $name,
        string $slug,
        ?int $parentId = null,
        int $sortOrder = 0,
        int $status = 1,
        bool $showOnHomepage = false,
        ?string $homepageImage = null
    ): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO categories (category_name, slug, parent_id, sort_order, status, show_on_homepage, homepage_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $name,
                $slug,
                $parentId ?: null,
                $sortOrder,
                $status,
                $showOnHomepage ? 1 : 0,
                $homepageImage,
            ]);
        } catch (PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                return false;
            }
            throw $e;
        }
    }

    public function updateCategory(
        int $id,
        string $name,
        string $slug,
        ?int $parentId,
        int $sortOrder,
        int $status,
        bool $showOnHomepage = false,
        ?string $homepageImage = null,
        bool $updateHomepageImage = false
    ): bool {
        try {
            if ($updateHomepageImage) {
                $stmt = $this->db->prepare(
                    'UPDATE categories
                     SET category_name = ?, slug = ?, parent_id = ?, sort_order = ?, status = ?,
                         show_on_homepage = ?, homepage_image = ?
                     WHERE category_id = ?'
                );
                return $stmt->execute([
                    $name,
                    $slug,
                    $parentId ?: null,
                    $sortOrder,
                    $status,
                    $showOnHomepage ? 1 : 0,
                    $homepageImage,
                    $id,
                ]);
            }

            $stmt = $this->db->prepare(
                'UPDATE categories
                 SET category_name = ?, slug = ?, parent_id = ?, sort_order = ?, status = ?,
                     show_on_homepage = ?
                 WHERE category_id = ?'
            );
            return $stmt->execute([
                $name,
                $slug,
                $parentId ?: null,
                $sortOrder,
                $status,
                $showOnHomepage ? 1 : 0,
                $id,
            ]);
        } catch (PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                return false;
            }
            throw $e;
        }
    }

    private function isDuplicateKeyError(PDOException $e): bool
    {
        $info = $e->errorInfo ?? [];
        return ($info[0] ?? '') === '23000' || (int) ($info[1] ?? 0) === 1062;
    }

    public function countChildCategories(int $parentId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = ?');
        $stmt->execute([$parentId]);
        return (int) $stmt->fetchColumn();
    }

    public function countProductsUsingCategory(int $categoryId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products WHERE category_id = ? OR subcategory_id = ?'
        );
        $stmt->execute([$categoryId, $categoryId]);
        return (int) $stmt->fetchColumn();
    }

    public function deleteCategory(int $id): bool
    {
        if ($this->countChildCategories($id) > 0 || $this->countProductsUsingCategory($id) > 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM categories WHERE category_id = ?');
        return $stmt->execute([$id]);
    }

    public function getParentCategoriesForAdmin(): array
    {
        $sql = 'SELECT category_id, category_name, slug, status, sort_order, show_on_homepage, homepage_image
                FROM categories
                WHERE parent_id IS NULL
                ORDER BY category_name ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getHomepageCategories(): array
    {
        $sql = 'SELECT c.category_id, c.category_name, c.slug, c.sort_order, c.homepage_image
                FROM categories c
                WHERE c.parent_id IS NULL
                  AND c.status = 1
                  AND c.show_on_homepage = 1
                  AND EXISTS (
                      SELECT 1
                      FROM products p
                      WHERE p.product_status != 0
                        AND (
                            p.category_id = c.category_id
                            OR p.subcategory_id IN (
                                SELECT sc.category_id
                                FROM categories sc
                                WHERE sc.parent_id = c.category_id
                            )
                        )
                  )
                ORDER BY c.category_name ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getActiveParentCategoryBySlug(string $slug): ?array
    {
        $slug = self::makeSlug($slug);
        if ($slug === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT category_id, category_name, slug, status, sort_order, show_on_homepage, homepage_image
             FROM categories
             WHERE parent_id IS NULL AND status = 1 AND slug = ?
             LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function homepageCardColors(): array
    {
        return ['#dbeafe', '#d1fae5', '#ede9fe', '#fde8d8', '#fef9c3', '#fce7f3'];
    }

    public function getCategoriesWithSubcounts(): array
    {
        $parents = $this->getParentCategoriesForAdmin();
        foreach ($parents as &$parent) {
            $parent['subcategories'] = $this->getSubcategories((int) $parent['category_id']);
            $parent['subcategory_count'] = count($parent['subcategories']);
        }
        unset($parent);
        return $parents;
    }

    public function validateSubcategoryForParent(int $subcategoryId, int $parentId): bool
    {
        if ($subcategoryId <= 0) {
            return true;
        }
        $sub = $this->getCategoryById($subcategoryId);
        if (!$sub) {
            return false;
        }
        return (int) ($sub['parent_id'] ?? 0) === $parentId;
    }
}
