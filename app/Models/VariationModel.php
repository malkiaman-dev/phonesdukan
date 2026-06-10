<?php
require_once __DIR__ . '/../../database/db.php';

class VariationModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        self::ensureSchema($this->db);
    }

    public static function ensureSchema(PDO $db): void
    {
        self::runMigrationOnce($db);
    }

    // Run DDL exactly once per PHP process (request). Never throws.
    private static bool $migrationDone = false;
    private const MIGRATION_SESSION_KEY = 'var_migration_v3';

    private static function tablesAreReady(\PDO $db): bool {
        try {
            $tblCheck = $db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME IN ('variation_types','variation_values','product_variations','product_variation_values')"
            );
            if ((int) $tblCheck->fetchColumn() !== 4) {
                return false;
            }

            $colCheck = $db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'product_variations'
                 AND COLUMN_NAME IN ('id','is_default','sku','stock_quantity')"
            );

            return (int) $colCheck->fetchColumn() === 4;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function migrateLegacyProductVariations(\PDO $db): void {
        try {
            $legacyCol = $db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'product_variations'
                 AND COLUMN_NAME = 'variation_id'"
            );
            if ((int) $legacyCol->fetchColumn() === 0) {
                return;
            }

            $rowCount = (int) $db->query('SELECT COUNT(*) FROM product_variations')->fetchColumn();
            if ($rowCount > 0) {
                $db->exec('RENAME TABLE product_variations TO product_variations_legacy');
                return;
            }

            $db->exec('DROP TABLE product_variations');
        } catch (\Throwable $e) {
            error_log('VariationModel legacy migration warning: ' . $e->getMessage());
        }
    }

    private static function runMigrationOnce(\PDO $db): void {
        if (self::$migrationDone) return;
        self::$migrationDone = true;

        if (self::tablesAreReady($db)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION[self::MIGRATION_SESSION_KEY] = true;
            }
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::MIGRATION_SESSION_KEY]);
        }

        try {
            $db->exec("SET FOREIGN_KEY_CHECKS=0");

            self::migrateLegacyProductVariations($db);

            $db->exec("CREATE TABLE IF NOT EXISTS `variation_types` (
              `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name`         VARCHAR(100) NOT NULL,
              `slug`         VARCHAR(120) NOT NULL,
              `display_type` ENUM('button','color','image') NOT NULL DEFAULT 'button',
              `sort_order`   SMALLINT     NOT NULL DEFAULT 0,
              `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_vt_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS `variation_values` (
              `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `variation_type_id` INT UNSIGNED NOT NULL,
              `value`             VARCHAR(150) NOT NULL,
              `slug`              VARCHAR(170) NOT NULL,
              `color_code`        VARCHAR(20)  NULL DEFAULT NULL,
              `image`             VARCHAR(500) NULL DEFAULT NULL,
              `sort_order`        SMALLINT     NOT NULL DEFAULT 0,
              `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_vv_type` (`variation_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS `product_variations` (
              `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
              `product_id`     INT           NOT NULL,
              `sku`            VARCHAR(200)  NULL DEFAULT NULL,
              `regular_price`  DECIMAL(12,2) NULL DEFAULT NULL,
              `sale_price`     DECIMAL(12,2) NULL DEFAULT NULL,
              `stock_quantity` INT           NOT NULL DEFAULT 0,
              `image`          VARCHAR(500)  NULL DEFAULT NULL,
              `status`         TINYINT(1)    NOT NULL DEFAULT 1,
              `is_default`     TINYINT(1)    NOT NULL DEFAULT 0,
              `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_pv_product` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS `product_variation_values` (
              `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_variation_id` INT UNSIGNED NOT NULL,
              `variation_type_id`    INT UNSIGNED NOT NULL,
              `variation_value_id`   INT UNSIGNED NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_pvv` (`product_variation_id`,`variation_type_id`),
              KEY `idx_pvv_pv` (`product_variation_id`),
              KEY `idx_pvv_vt` (`variation_type_id`),
              KEY `idx_pvv_vv` (`variation_value_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            if (!self::tablesAreReady($db)) {
                throw new \RuntimeException('Variation tables migration incomplete');
            }

            $db->exec("SET FOREIGN_KEY_CHECKS=1");

            // Add missing columns — each wrapped individually so one failure doesn't stop the rest
            $alterCols = [
                'product_variations' => [
                    'sku            VARCHAR(200)  NULL DEFAULT NULL',
                    'regular_price  DECIMAL(12,2) NULL DEFAULT NULL',
                    'sale_price     DECIMAL(12,2) NULL DEFAULT NULL',
                    'stock_quantity INT           NOT NULL DEFAULT 0',
                    'image          VARCHAR(500)  NULL DEFAULT NULL',
                    'status         TINYINT(1)    NOT NULL DEFAULT 1',
                    'is_default     TINYINT(1)    NOT NULL DEFAULT 0',
                    'prepaid_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0',
                ],
                'product_variation_values' => [
                    'variation_type_id  INT UNSIGNED NOT NULL DEFAULT 0',
                    'variation_value_id INT UNSIGNED NOT NULL DEFAULT 0',
                ],
                'products' => [
                    "product_type ENUM('simple','variable') NOT NULL DEFAULT 'simple'",
                ],
                'cart' => [
                    'variation_id         INT UNSIGNED NULL DEFAULT NULL',
                    'variation_attributes TEXT         NULL DEFAULT NULL',
                ],
                'order_items' => [
                    'variation_id         INT UNSIGNED NULL DEFAULT NULL',
                    'variation_sku        VARCHAR(200) NULL DEFAULT NULL',
                    'variation_attributes TEXT         NULL DEFAULT NULL',
                ],
            ];

            foreach ($alterCols as $table => $cols) {
                foreach ($cols as $col) {
                    try {
                        $db->exec("ALTER TABLE `$table` ADD COLUMN $col");
                    } catch (\PDOException $e) {
                        // Column already exists — silently continue
                    }
                }
            }

            // Migration complete — cache in session so next request skips DDL entirely
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION[self::MIGRATION_SESSION_KEY] = true;
            }

        } catch (\Throwable $e) {
            // Never crash the page over a migration error — log and continue
            error_log('VariationModel migration error: ' . $e->getMessage());
            try { $db->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (\Throwable $ignored) {}
        }
    }

    // ----------------------------------------------------------------
    // VARIATION TYPES
    // ----------------------------------------------------------------

    public function getAllVariationTypes() {
        if (!self::tablesAreReady($this->db)) {
            self::$migrationDone = false;
            self::runMigrationOnce($this->db);
        }

        if (!self::tablesAreReady($this->db)) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT * FROM variation_types ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVariationTypeById($id) {
        $stmt = $this->db->prepare("SELECT * FROM variation_types WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addVariationType($name, $slug, $display_type, $sort_order = 0) {
        $stmt = $this->db->prepare(
            "INSERT INTO variation_types (name, slug, display_type, sort_order) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$name, $slug, $display_type, (int)$sort_order]);
    }

    public function updateVariationType($id, $name, $slug, $display_type, $sort_order = 0) {
        $stmt = $this->db->prepare(
            "UPDATE variation_types SET name=?, slug=?, display_type=?, sort_order=? WHERE id=?"
        );
        return $stmt->execute([$name, $slug, $display_type, (int)$sort_order, (int)$id]);
    }

    public function deleteVariationType($id) {
        $stmt = $this->db->prepare("DELETE FROM variation_types WHERE id=?");
        return $stmt->execute([(int)$id]);
    }

    public function slugExistsForType($slug, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM variation_types WHERE slug=? AND id!=?");
            $stmt->execute([$slug, (int)$excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM variation_types WHERE slug=?");
            $stmt->execute([$slug]);
        }
        return (bool)$stmt->fetch();
    }

    // ----------------------------------------------------------------
    // VARIATION VALUES
    // ----------------------------------------------------------------

    public function getValuesByTypeId($type_id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM variation_values WHERE variation_type_id=? ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([(int)$type_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllValuesGrouped() {
        $stmt = $this->db->query(
            "SELECT vv.*, vt.name AS type_name, vt.display_type
             FROM variation_values vv
             JOIN variation_types vt ON vt.id = vv.variation_type_id
             ORDER BY vt.sort_order ASC, vt.id ASC, vv.sort_order ASC, vv.id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getValueById($id) {
        $stmt = $this->db->prepare("SELECT * FROM variation_values WHERE id=?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addVariationValue($type_id, $value, $slug, $color_code = null, $image = null, $sort_order = 0) {
        $stmt = $this->db->prepare(
            "INSERT INTO variation_values (variation_type_id, value, slug, color_code, image, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([(int)$type_id, $value, $slug, $color_code, $image, (int)$sort_order]);
    }

    public function updateVariationValue($id, $value, $slug, $color_code = null, $image = null, $sort_order = 0) {
        $stmt = $this->db->prepare(
            "UPDATE variation_values SET value=?, slug=?, color_code=?, image=?, sort_order=? WHERE id=?"
        );
        return $stmt->execute([$value, $slug, $color_code, $image, (int)$sort_order, (int)$id]);
    }

    public function deleteVariationValue($id) {
        $stmt = $this->db->prepare("DELETE FROM variation_values WHERE id=?");
        return $stmt->execute([(int)$id]);
    }

    public function isValueUsedInProducts($value_id) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM product_variation_values WHERE variation_value_id=?"
        );
        $stmt->execute([(int)$value_id]);
        return $stmt->fetchColumn() > 0;
    }

    // ----------------------------------------------------------------
    // PRODUCT VARIATIONS
    // ----------------------------------------------------------------

    public function getProductVariations($product_id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT pv.*,
                        GROUP_CONCAT(
                            CONCAT(pvv.variation_type_id, ':', pvv.variation_value_id)
                            ORDER BY pvv.variation_type_id ASC
                            SEPARATOR '|'
                        ) AS value_map
                 FROM product_variations pv
                 LEFT JOIN product_variation_values pvv ON pvv.product_variation_id = pv.id
                 WHERE pv.product_id = ?
                 GROUP BY pv.id
                 ORDER BY pv.is_default DESC, pv.id ASC"
            );
            $stmt->execute([(int)$product_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('VariationModel::getProductVariations error: ' . $e->getMessage());
            return [];
        }
    }

    public function getProductVariationsForFrontend($product_id) {
        $variations = $this->getProductVariations($product_id);
        if (empty($variations)) return [];

        $result = [];
        foreach ($variations as $v) {
            $entry = [
                'id'                      => (int)$v['id'],
                'sku'                     => $v['sku'],
                'regular_price'           => (float)$v['regular_price'],
                'sale_price'              => $v['sale_price'] !== null ? (float)$v['sale_price'] : null,
                'stock_quantity'          => (int)$v['stock_quantity'],
                'image'                   => $v['image'],
                'status'                  => (int)$v['status'],
                'is_default'              => (int)$v['is_default'],
                'prepaid_discount_amount' => isset($v['prepaid_discount_amount']) ? (float)$v['prepaid_discount_amount'] : 0,
                'attributes'              => [],
            ];
            if (!empty($v['value_map'])) {
                foreach (explode('|', $v['value_map']) as $pair) {
                    [$type_id, $value_id] = explode(':', $pair);
                    $entry['attributes'][(int)$type_id] = (int)$value_id;
                }
            }
            $result[] = $entry;
        }
        return $result;
    }

    public function getVariationById($variation_id) {
        $stmt = $this->db->prepare(
            "SELECT pv.*,
                    GROUP_CONCAT(
                        CONCAT(pvv.variation_type_id, ':', pvv.variation_value_id)
                        ORDER BY pvv.variation_type_id ASC
                        SEPARATOR '|'
                    ) AS value_map
             FROM product_variations pv
             LEFT JOIN product_variation_values pvv ON pvv.product_variation_id = pv.id
             WHERE pv.id = ?
             GROUP BY pv.id"
        );
        $stmt->execute([(int)$variation_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $attrs = [];
        if (!empty($row['value_map'])) {
            foreach (explode('|', $row['value_map']) as $pair) {
                [$tid, $vid] = explode(':', $pair);
                $attrs[(int)$tid] = (int)$vid;
            }
        }
        $row['attributes'] = $attrs;
        return $row;
    }

    public function saveProductVariations($product_id, array $variations) {
        $this->db->beginTransaction();
        try {
            // Remove old variations for this product
            $del = $this->db->prepare(
                "DELETE pv, pvv FROM product_variations pv
                 LEFT JOIN product_variation_values pvv ON pvv.product_variation_id = pv.id
                 WHERE pv.product_id = ?"
            );
            $del->execute([(int)$product_id]);

            foreach ($variations as $v) {
                $sku                    = $v['sku'] ?? null;
                $regular_price          = isset($v['regular_price']) && $v['regular_price'] !== '' ? (float)$v['regular_price'] : null;
                $sale_price             = isset($v['sale_price']) && $v['sale_price'] !== '' ? (float)$v['sale_price'] : null;
                $stock_quantity         = isset($v['stock_quantity']) ? (int)$v['stock_quantity'] : 0;
                $image                  = $v['image'] ?? null;
                $status                 = isset($v['status']) ? (int)$v['status'] : 1;
                $is_default             = isset($v['is_default']) ? (int)$v['is_default'] : 0;
                $prepaid_discount_amount = isset($v['prepaid_discount_amount']) && is_numeric($v['prepaid_discount_amount']) ? max(0, (float)$v['prepaid_discount_amount']) : 0;

                $ins = $this->db->prepare(
                    "INSERT INTO product_variations
                     (product_id, sku, regular_price, sale_price, stock_quantity, image, status, is_default, prepaid_discount_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->execute([(int)$product_id, $sku, $regular_price, $sale_price, $stock_quantity, $image, $status, $is_default, $prepaid_discount_amount]);
                $pv_id = $this->db->lastInsertId();

                // Insert value mapping
                if (!empty($v['attributes']) && is_array($v['attributes'])) {
                    $insVal = $this->db->prepare(
                        "INSERT INTO product_variation_values
                         (product_variation_id, variation_type_id, variation_value_id)
                         VALUES (?, ?, ?)"
                    );
                    foreach ($v['attributes'] as $type_id => $value_id) {
                        $insVal->execute([(int)$pv_id, (int)$type_id, (int)$value_id]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("VariationModel::saveProductVariations error: " . $e->getMessage());
            return false;
        }
    }

    public function getLowestVariationPrice($product_id) {
        $stmt = $this->db->prepare(
            "SELECT
                MIN(IFNULL(sale_price, regular_price)) AS lowest_price,
                MIN(regular_price) AS lowest_regular
             FROM product_variations
             WHERE product_id = ? AND status = 1 AND stock_quantity > 0"
        );
        $stmt->execute([(int)$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function productHasActiveVariations($product_id) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM product_variations WHERE product_id=? AND status=1"
        );
        $stmt->execute([(int)$product_id]);
        return $stmt->fetchColumn() > 0;
    }

    // ----------------------------------------------------------------
    // VARIATION ATTRIBUTES LABEL BUILDER (for cart/order display)
    // ----------------------------------------------------------------

    public function buildAttributesLabel($variation_id) {
        $stmt = $this->db->prepare(
            "SELECT vt.name AS type_name, vv.value AS value_name
             FROM product_variation_values pvv
             JOIN variation_types vt  ON vt.id  = pvv.variation_type_id
             JOIN variation_values vv ON vv.id   = pvv.variation_value_id
             WHERE pvv.product_variation_id = ?
             ORDER BY vt.sort_order ASC, vt.id ASC"
        );
        $stmt->execute([(int)$variation_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $parts = [];
        foreach ($rows as $r) {
            $parts[] = $r['type_name'] . ': ' . $r['value_name'];
        }
        return implode(', ', $parts);
    }

    public function getVariationTypesWithValues() {
        $types = $this->getAllVariationTypes();
        if (empty($types) || !self::tablesAreReady($this->db)) {
            return [];
        }

        $allVal = $this->getAllValuesGrouped();

        $byType = [];
        foreach ($allVal as $v) {
            $byType[$v['variation_type_id']][] = $v;
        }
        foreach ($types as &$t) {
            $t['values'] = $byType[$t['id']] ?? [];
        }
        return $types;
    }
}
