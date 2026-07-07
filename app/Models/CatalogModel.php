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
                'show_in_sidebar' => 'TINYINT(1) NOT NULL DEFAULT 0',
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

        self::seedDefaultSidebarCategories($db);
        self::seedDefaultHomepageBrands($db);
    }

    private static function seedDefaultHomepageBrands(PDO $db): void
    {
        static $seedChecked = false;
        if ($seedChecked) {
            return;
        }
        $seedChecked = true;

        try {
            $staticSlugs = array_values(array_filter(array_map(
                static fn(array $brand): string => (string) ($brand['slug'] ?? ''),
                self::staticHomepageBrands()
            )));
            if ($staticSlugs === []) {
                return;
            }

            $quotedSlugs = implode(', ', array_map(
                static fn(string $slug): string => $db->quote($slug),
                $staticSlugs
            ));

            $enabledStatic = (int) $db
                ->query('SELECT COUNT(*) FROM brands WHERE slug IN (' . $quotedSlugs . ') AND show_on_homepage = 1')
                ->fetchColumn();
            $existingStatic = (int) $db
                ->query('SELECT COUNT(*) FROM brands WHERE slug IN (' . $quotedSlugs . ')')
                ->fetchColumn();

            if ($existingStatic === 0 || $enabledStatic > 0) {
                return;
            }

            foreach (self::staticHomepageBrands() as $staticBrand) {
                $slug = (string) ($staticBrand['slug'] ?? '');
                $logo = (string) ($staticBrand['homepage_logo'] ?? '');
                if ($slug === '') {
                    continue;
                }

                $update = $db->prepare(
                    'UPDATE brands
                     SET show_on_homepage = 1,
                         homepage_logo = CASE
                             WHEN homepage_logo IS NULL OR homepage_logo = \'\' THEN ?
                             ELSE homepage_logo
                         END
                     WHERE slug = ?'
                );
                $update->execute([$logo !== '' ? $logo : null, $slug]);
            }
        } catch (Throwable $e) {
            error_log('CatalogModel homepage brand seed: ' . $e->getMessage());
        }
    }

    private static function seedDefaultSidebarCategories(PDO $db): void
    {
        static $seedChecked = false;
        if ($seedChecked) {
            return;
        }
        $seedChecked = true;

        try {
            $stmt = $db->query('SELECT COUNT(*) FROM categories WHERE show_in_sidebar = 1');
            if ((int) $stmt->fetchColumn() > 0) {
                return;
            }

            $quotedSlugs = implode(', ', array_map(
                static fn(string $slug): string => $db->quote($slug),
                self::defaultSidebarCategorySlugs()
            ));
            $db->exec(
                'UPDATE categories SET show_in_sidebar = 1
                 WHERE parent_id IS NULL AND slug IN (' . $quotedSlugs . ')'
            );
        } catch (Throwable $e) {
            error_log('CatalogModel sidebar seed: ' . $e->getMessage());
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
        $sql = 'SELECT category_id, category_name, slug, status, sort_order, show_on_homepage, show_in_sidebar, homepage_image
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
            'SELECT category_id, category_name, slug, status, sort_order, parent_id, show_on_homepage, show_in_sidebar, homepage_image
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
        ?string $homepageImage = null,
        bool $showInSidebar = false
    ): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO categories (category_name, slug, parent_id, sort_order, status, show_on_homepage, show_in_sidebar, homepage_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $name,
                $slug,
                $parentId ?: null,
                $sortOrder,
                $status,
                $showOnHomepage ? 1 : 0,
                $showInSidebar ? 1 : 0,
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
        bool $updateHomepageImage = false,
        bool $showInSidebar = false
    ): bool {
        try {
            if ($updateHomepageImage) {
                $stmt = $this->db->prepare(
                    'UPDATE categories
                     SET category_name = ?, slug = ?, parent_id = ?, sort_order = ?, status = ?,
                         show_on_homepage = ?, show_in_sidebar = ?, homepage_image = ?
                     WHERE category_id = ?'
                );
                return $stmt->execute([
                    $name,
                    $slug,
                    $parentId ?: null,
                    $sortOrder,
                    $status,
                    $showOnHomepage ? 1 : 0,
                    $showInSidebar ? 1 : 0,
                    $homepageImage,
                    $id,
                ]);
            }

            $stmt = $this->db->prepare(
                'UPDATE categories
                 SET category_name = ?, slug = ?, parent_id = ?, sort_order = ?, status = ?,
                     show_on_homepage = ?, show_in_sidebar = ?
                 WHERE category_id = ?'
            );
            return $stmt->execute([
                $name,
                $slug,
                $parentId ?: null,
                $sortOrder,
                $status,
                $showOnHomepage ? 1 : 0,
                $showInSidebar ? 1 : 0,
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
        $sql = 'SELECT category_id, category_name, slug, status, sort_order, show_on_homepage, show_in_sidebar, homepage_image
                FROM categories
                WHERE parent_id IS NULL
                ORDER BY category_name ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, string> */
    public static function defaultSidebarCategorySlugs(): array
    {
        return [
            'mobiles',
            'smart-watches',
            'power-banks',
            'bluetooth-speakers',
            'wireless-earbuds',
            'mobile-accessories',
        ];
    }

    public static function sidebarCategoryIcon(string $slug): string
    {
        $icons = [
            'mobiles' => 'public/assets/images/mobiles_icon.svg',
            'smart-watches' => 'public/assets/images/smartwatches_icon.svg',
            'power-banks' => 'public/assets/images/power_banks_icon.svg',
            'bluetooth-speakers' => 'public/assets/images/speakers_icon.svg',
            'wireless-earbuds' => 'public/assets/images/wireless-earbuds.svg',
            'mobile-accessories' => 'public/assets/images/accessories_icon.svg',
            'tablets' => 'public/assets/images/tablets_icon.svg',
        ];

        return $icons[self::makeSlug($slug)] ?? 'public/assets/images/accessories_icon.svg';
    }

    /** @return array<int, array<string, mixed>> */
    public function getSidebarCategories(): array
    {
        $sql = 'SELECT category_id, category_name, slug
                FROM categories
                WHERE parent_id IS NULL
                  AND status = 1
                  AND show_in_sidebar = 1
                ORDER BY category_name ASC';

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, array{name: string, href: string}> */
    public function getSidebarCategoryChildren(int $categoryId, string $categorySlug): array
    {
        $categorySlug = self::makeSlug($categorySlug);
        $children = [];

        foreach ($this->getBrandsWithProductsInCategory($categoryId) as $brand) {
            $brandSlug = (string) ($brand['slug'] ?? '');
            if ($brandSlug === '') {
                continue;
            }
            $children[] = [
                'name' => (string) ($brand['brand_name'] ?? $brandSlug),
                'href' => $categorySlug . '/' . $brandSlug,
            ];
        }

        if ($children !== []) {
            return $children;
        }

        foreach ($this->getSubcategories($categoryId, true) as $sub) {
            $subSlug = (string) ($sub['slug'] ?? '');
            if ($subSlug === '') {
                continue;
            }
            $children[] = [
                'name' => (string) ($sub['category_name'] ?? $subSlug),
                'href' => $categorySlug . '/' . $subSlug,
            ];
        }

        return $children;
    }

    public function categoryShowsInSidebar(array $category): bool
    {
        return (int) ($category['show_in_sidebar'] ?? 0) === 1;
    }

    /** @return array{ok: bool, message: string} */
    public function setCategoryShowInSidebar(int $categoryId, bool $enabled): array
    {
        $category = $this->getCategoryById($categoryId);
        if (!$category || !empty($category['parent_id'])) {
            return ['ok' => false, 'message' => 'Invalid category.'];
        }

        $stmt = $this->db->prepare(
            'UPDATE categories SET show_in_sidebar = ? WHERE category_id = ? AND parent_id IS NULL'
        );
        $ok = $stmt->execute([$enabled ? 1 : 0, $categoryId]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? ($enabled ? 'Sidebar display enabled.' : 'Sidebar display disabled.')
                : 'Could not update sidebar setting.',
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function setCategoryShowOnHomepage(int $categoryId, bool $enabled): array
    {
        $category = $this->getCategoryById($categoryId);
        if (!$category || !empty($category['parent_id'])) {
            return ['ok' => false, 'message' => 'Invalid category.'];
        }

        if ($enabled && empty($category['homepage_image'])) {
            return [
                'ok' => false,
                'message' => 'Upload a homepage image to show this category on the home page.',
                'redirect' => 'manage-catalog.php?edit_category=' . $categoryId . '&enable_home=1',
            ];
        }

        $stmt = $this->db->prepare(
            'UPDATE categories SET show_on_homepage = ? WHERE category_id = ? AND parent_id IS NULL'
        );
        $ok = $stmt->execute([$enabled ? 1 : 0, $categoryId]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? ($enabled ? 'Home page display enabled.' : 'Home page display disabled.')
                : 'Could not update home page setting.',
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function setBrandShowOnHomepage(int $brandId, bool $enabled): array
    {
        $brand = $this->getBrandById($brandId);
        if (!$brand) {
            return ['ok' => false, 'message' => 'Invalid brand.'];
        }

        if ($enabled && empty($brand['homepage_logo'])) {
            return [
                'ok' => false,
                'message' => 'Upload a brand logo to show this brand on the home page.',
                'redirect' => 'manage-catalog.php?edit_brand=' . $brandId . '&enable_home=1',
            ];
        }

        $stmt = $this->db->prepare('UPDATE brands SET show_on_homepage = ? WHERE brand_id = ?');
        $ok = $stmt->execute([$enabled ? 1 : 0, $brandId]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? ($enabled ? 'Home page display enabled.' : 'Home page display disabled.')
                : 'Could not update home page setting.',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function staticHomepageCategories(): array
    {
        return [
            ['slug' => 'mobiles', 'category_name' => 'Mobiles', 'homepage_image' => 'public/assets/images/mobile_category.png', 'bg' => '#dbeafe'],
            ['slug' => 'smart-watches', 'category_name' => 'Smart Watches', 'homepage_image' => 'public/assets/images/smartwatches_category.webp', 'bg' => '#d1fae5'],
            ['slug' => 'wireless-earbuds', 'category_name' => 'Wireless Earbuds', 'homepage_image' => 'public/assets/images/wireless_earbuds.webp', 'bg' => '#ede9fe'],
            ['slug' => 'mobile-accessories', 'category_name' => 'Mobile Accessories', 'homepage_image' => 'public/assets/images/mobile_accessories.webp', 'bg' => '#fde8d8'],
            ['slug' => 'power-banks', 'category_name' => 'Fast Charging Power Banks', 'homepage_image' => 'public/assets/images/power-banks.webp', 'bg' => '#fef9c3'],
            ['slug' => 'bluetooth-speakers', 'category_name' => 'Portable Bluetooth Speakers', 'homepage_image' => 'public/assets/images/bluetooth-speakers.webp', 'bg' => '#fce7f3'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function staticHomepageBrands(): array
    {
        return [
            ['slug' => 'apple', 'brand_name' => 'Apple', 'homepage_logo' => 'public/assets/images/apple_logo.webp'],
            ['slug' => 'infinix', 'brand_name' => 'Infinix', 'homepage_logo' => 'public/assets/images/infinix_logo.webp'],
            ['slug' => 'oppo', 'brand_name' => 'Oppo', 'homepage_logo' => 'public/assets/images/oppo_logo.webp'],
            ['slug' => 'realme', 'brand_name' => 'Realme', 'homepage_logo' => 'public/assets/images/realme_logo.webp'],
            ['slug' => 'samsung', 'brand_name' => 'Samsung', 'homepage_logo' => 'public/assets/images/samsung_logo.webp'],
            ['slug' => 'tecno', 'brand_name' => 'Tecno', 'homepage_logo' => 'public/assets/images/tecno_logo.webp'],
            ['slug' => 'vivo', 'brand_name' => 'Vivo', 'homepage_logo' => 'public/assets/images/vivo_logo.webp'],
            ['slug' => 'xiaomi', 'brand_name' => 'Xiaomi', 'homepage_logo' => 'public/assets/images/xiaomi_logo.webp'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getHomepageCarouselCategories(): array
    {
        $dynamic = $this->getHomepageCategories();
        $dynamicSlugs = array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), $dynamic);

        $carousel = $dynamic;
        foreach (self::staticHomepageCategories() as $staticCategory) {
            $staticSlug = (string) ($staticCategory['slug'] ?? '');
            if ($staticSlug !== '' && !in_array($staticSlug, $dynamicSlugs, true)) {
                $carousel[] = $staticCategory;
            }
        }

        if ($dynamic === []) {
            $carousel = self::staticHomepageCategories();
        }

        usort($carousel, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['category_name'] ?? ''), (string) ($b['category_name'] ?? ''));
        });

        return $carousel;
    }

    /** @return array<int, string> */
    public function getHomepageCarouselSlugs(): array
    {
        $slugs = [];
        foreach ($this->getHomepageCarouselCategories() as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public function categoryShowsOnHomepage(array $category): bool
    {
        if ((int) ($category['show_on_homepage'] ?? 0) === 1) {
            return true;
        }

        $slug = (string) ($category['slug'] ?? '');
        return $slug !== '' && in_array($slug, $this->getHomepageCarouselSlugs(), true);
    }

    /** @return array<int, array<string, mixed>> */
    public function getHomepageBrandCarousel(): array
    {
        $dynamic = $this->getHomepageBrands();
        $dynamicSlugs = array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), $dynamic);

        $carousel = $dynamic;
        foreach (self::staticHomepageBrands() as $staticBrand) {
            $staticSlug = (string) ($staticBrand['slug'] ?? '');
            if ($staticSlug !== '' && !in_array($staticSlug, $dynamicSlugs, true)) {
                $carousel[] = $staticBrand;
            }
        }

        if ($dynamic === []) {
            $carousel = self::staticHomepageBrands();
        }

        usort($carousel, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['brand_name'] ?? ''), (string) ($b['brand_name'] ?? ''));
        });

        return $carousel;
    }

    /** @return array<int, string> */
    public function getHomepageBrandCarouselSlugs(): array
    {
        $slugs = [];
        foreach ($this->getHomepageBrandCarousel() as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public function brandShowsOnHomepage(array $brand): bool
    {
        if ((int) ($brand['show_on_homepage'] ?? 0) === 1) {
            return true;
        }

        $slug = (string) ($brand['slug'] ?? '');
        return $slug !== '' && in_array($slug, $this->getHomepageBrandCarouselSlugs(), true);
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
