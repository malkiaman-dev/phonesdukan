<?php

require_once dirname(__DIR__, 2) . '/database/db.php';

if (!class_exists('ProductGroupModel')) {
class ProductGroupModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: (new Database())->getConnection();
        // Schema is ensured once via database_migrations lock; skip per-request CREATE/ALTER.
        if (!is_file(dirname(__DIR__, 2) . '/storage/.schema_migration_v4.lock')) {
            self::ensureSchema($this->db);
        }
    }

    public static function ensureSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $db->exec(
                "CREATE TABLE IF NOT EXISTS product_group_items (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    parent_product_id INT UNSIGNED NOT NULL,
                    child_product_id INT UNSIGNED NOT NULL,
                    group_price DECIMAL(12,2) NULL DEFAULT NULL,
                    sort_order INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_parent_child (parent_product_id, child_product_id),
                    KEY idx_parent (parent_product_id),
                    KEY idx_child (child_product_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
            error_log('product_group_items schema: ' . $e->getMessage());
        }

        // Existing installs: add group_price if missing
        if (function_exists('dbAddColumnIfMissing')) {
            dbAddColumnIfMissing(
                $db,
                'product_group_items',
                'group_price',
                'DECIMAL(12,2) NULL DEFAULT NULL AFTER child_product_id'
            );
        } else {
            try {
                $stmt = $db->prepare(
                    'SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND COLUMN_NAME = ?'
                );
                $stmt->execute(['product_group_items', 'group_price']);
                if ((int) $stmt->fetchColumn() === 0) {
                    $db->exec(
                        'ALTER TABLE product_group_items
                         ADD COLUMN group_price DECIMAL(12,2) NULL DEFAULT NULL
                         AFTER child_product_id'
                    );
                }
            } catch (Throwable $e) {
                error_log('product_group_items.group_price: ' . $e->getMessage());
            }
        }
    }

    /**
     * @return list<int>
     */
    public function getChildIds(int $parentProductId): array
    {
        $stmt = $this->db->prepare(
            'SELECT child_product_id FROM product_group_items
             WHERE parent_product_id = ?
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$parentProductId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Group / accessory products for the product detail page.
     *
     * @return list<array<string, mixed>>
     */
    public function getGroupProductsForDisplay(int $parentProductId): array
    {
        $sql = "SELECT
                    p.product_id,
                    p.product_name,
                    p.product_slug,
                    p.sale_price,
                    p.regular_price,
                    p.stock_quantity,
                    p.product_status,
                    p.product_type,
                    g.group_price,
                    c.slug AS category_slug,
                    b.slug AS brand_slug,
                    sc.slug AS subcategory_slug,
                    (
                        SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY pi.is_primary DESC, pi.image_id ASC
                        LIMIT 1
                    ) AS image_url,
                    (
                        SELECT pv.id
                        FROM product_variations pv
                        WHERE pv.product_id = p.product_id
                        ORDER BY pv.is_default DESC, pv.id ASC
                        LIMIT 1
                    ) AS default_variation_id,
                    (
                        SELECT COALESCE(NULLIF(pv.sale_price, 0), pv.regular_price)
                        FROM product_variations pv
                        WHERE pv.product_id = p.product_id
                        ORDER BY pv.is_default DESC, pv.id ASC
                        LIMIT 1
                    ) AS default_variation_price
                FROM product_group_items g
                INNER JOIN products p ON p.product_id = g.child_product_id
                INNER JOIN categories c ON c.category_id = p.category_id AND c.status = 1
                INNER JOIN brands b ON b.brand_id = p.brand_id
                LEFT JOIN categories sc ON p.subcategory_id = sc.category_id
                WHERE g.parent_product_id = :parent_id
                  AND p.product_status = 1
                  AND (
                      (COALESCE(p.product_type, 'simple') <> 'variable' AND p.stock_quantity > 0)
                      OR (
                          COALESCE(p.product_type, 'simple') = 'variable'
                          AND EXISTS (
                              SELECT 1 FROM product_variations pv2
                              WHERE pv2.product_id = p.product_id
                                AND pv2.stock_quantity > 0
                                AND (pv2.status = 1 OR pv2.status IS NULL)
                          )
                      )
                  )
                ORDER BY g.sort_order ASC, g.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':parent_id', $parentProductId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $isVariable = ($row['product_type'] ?? 'simple') === 'variable';
            if ($isVariable && !empty($row['default_variation_price'])) {
                $standalone = (float) $row['default_variation_price'];
            } else {
                $sale = isset($row['sale_price']) && is_numeric($row['sale_price']) ? (float) $row['sale_price'] : 0;
                $regular = isset($row['regular_price']) && is_numeric($row['regular_price']) ? (float) $row['regular_price'] : 0;
                $standalone = $sale > 0 ? $sale : $regular;
            }

            $row['original_price'] = $standalone;
            $groupPrice = isset($row['group_price']) && is_numeric($row['group_price'])
                ? (float) $row['group_price']
                : 0;
            // Group price only applies when set and positive; does not alter catalog price
            $row['unit_price'] = $groupPrice > 0 ? $groupPrice : $standalone;
            $row['has_group_discount'] = $groupPrice > 0 && $standalone > $groupPrice;

            if (!$isVariable) {
                $row['default_variation_id'] = null;
            }
        }
        unset($row);

        return array_values(array_filter($rows, static function (array $row): bool {
            return (float) ($row['unit_price'] ?? 0) > 0;
        }));
    }

    /**
     * Admin: selected children with display info.
     *
     * @return list<array<string, mixed>>
     */
    public function getGroupProductsForAdmin(int $parentProductId): array
    {
        $sql = 'SELECT
                    p.product_id,
                    p.product_name,
                    p.product_sku,
                    p.sale_price,
                    p.regular_price,
                    p.product_status,
                    g.group_price,
                    (
                        SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY pi.is_primary DESC, pi.image_id ASC
                        LIMIT 1
                    ) AS image_url
                FROM product_group_items g
                INNER JOIN products p ON p.product_id = g.child_product_id
                WHERE g.parent_product_id = ?
                ORDER BY g.sort_order ASC, g.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentProductId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Replace all group links for a parent product.
     *
     * @param list<int|string|array{id?:int|string,product_id?:int|string,group_price?:float|string|null}> $items
     *        Accepts plain IDs or ['id' => int, 'group_price' => float|null]
     * @param array<int|string, float|string|null>|null $priceMap Optional map product_id => group_price
     */
    public function saveGroupProducts(int $parentProductId, array $items, ?array $priceMap = null): void
    {
        $clean = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $id = (int) ($item['id'] ?? $item['product_id'] ?? 0);
                $priceRaw = $item['group_price'] ?? null;
            } else {
                $id = (int) $item;
                $priceRaw = null;
            }

            if ($id <= 0 || $id === $parentProductId) {
                continue;
            }

            if ($priceMap !== null && array_key_exists($id, $priceMap)) {
                $priceRaw = $priceMap[$id];
            } elseif ($priceMap !== null && array_key_exists((string) $id, $priceMap)) {
                $priceRaw = $priceMap[(string) $id];
            }

            $groupPrice = null;
            if ($priceRaw !== null && $priceRaw !== '' && is_numeric($priceRaw)) {
                $parsed = (float) $priceRaw;
                if ($parsed > 0) {
                    $groupPrice = round($parsed, 2);
                }
            }

            $clean[$id] = [
                'id' => $id,
                'group_price' => $groupPrice,
            ];
        }
        $clean = array_values($clean);

        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $del = $this->db->prepare('DELETE FROM product_group_items WHERE parent_product_id = ?');
            $del->execute([$parentProductId]);

            if (!empty($clean)) {
                $ins = $this->db->prepare(
                    'INSERT INTO product_group_items (parent_product_id, child_product_id, group_price, sort_order)
                     VALUES (?, ?, ?, ?)'
                );
                foreach ($clean as $sort => $row) {
                    $ins->execute([
                        $parentProductId,
                        $row['id'],
                        $row['group_price'],
                        $sort,
                    ]);
                }
            }

            if ($ownTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lightweight search for admin picker.
     *
     * @return list<array<string, mixed>>
     */
    public function searchProducts(string $query, int $excludeProductId = 0, int $limit = 20): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $like = '%' . $q . '%';

        $sql = 'SELECT
                    p.product_id,
                    p.product_name,
                    p.product_sku,
                    p.sale_price,
                    p.regular_price,
                    p.product_status,
                    (
                        SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY pi.is_primary DESC, pi.image_id ASC
                        LIMIT 1
                    ) AS image_url
                FROM products p
                WHERE p.product_id != :exclude
                  AND (
                      p.product_name LIKE :q
                      OR p.product_sku LIKE :q2
                      OR CAST(p.product_id AS CHAR) = :exact_id
                  )
                ORDER BY
                    CASE WHEN p.product_status = 1 THEN 0 ELSE 1 END,
                    p.product_name ASC
                LIMIT ' . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':exclude', $excludeProductId, PDO::PARAM_INT);
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':exact_id', preg_match('/^\d+$/', $q) ? $q : '0', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
} // class_exists ProductGroupModel
