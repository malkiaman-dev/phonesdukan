<?php

if (!function_exists('dbTableExists')) {
    function dbTableExists(PDO $db, string $table): bool
    {
        try {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('dbTableHasColumn')) {
    function dbTableHasColumn(PDO $db, string $table, string $column): bool
    {
        try {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('dbAddColumnIfMissing')) {
    function dbAddColumnIfMissing(PDO $db, string $table, string $column, string $definition): void
    {
        if (dbTableHasColumn($db, $table, $column)) {
            return;
        }

        try {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        } catch (Throwable $e) {
            error_log("DB migration ($table.$column): " . $e->getMessage());
        }
    }
}

if (!function_exists('ensureProductSeoColumns')) {
    function ensureProductSeoColumns(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        dbAddColumnIfMissing($db, 'product_seo', 'canonical_url', 'VARCHAR(500) NULL DEFAULT NULL');
        dbAddColumnIfMissing($db, 'product_seo', 'secondary_keywords', 'TEXT NULL DEFAULT NULL');
    }
}

if (!function_exists('ensureUsersProfilePhotoColumn')) {
    function ensureUsersProfilePhotoColumn(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        dbAddColumnIfMissing($db, 'users', 'profile_photo', 'VARCHAR(255) NULL DEFAULT NULL');
    }
}

if (!function_exists('ensureAiSeoTables')) {
    function ensureAiSeoTables(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $queries = [
            "CREATE TABLE IF NOT EXISTS `ai_seo_requests` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_id` INT DEFAULT NULL,
                `action` VARCHAR(60) NOT NULL,
                `field_name` VARCHAR(100) DEFAULT NULL,
                `input_text` TEXT,
                `output_text` MEDIUMTEXT,
                `model` VARCHAR(100) DEFAULT 'compound-beta',
                `tokens_used` INT DEFAULT 0,
                `latency_ms` INT DEFAULT 0,
                `status` ENUM('success','error','timeout') DEFAULT 'success',
                `error_msg` TEXT DEFAULT NULL,
                `product_id` INT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_action` (`action`),
                KEY `idx_created` (`created_at`),
                KEY `idx_admin` (`admin_id`),
                KEY `idx_product` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `ai_seo_credits` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `date` DATE NOT NULL,
                `daily_requests` INT DEFAULT 0,
                `daily_tokens` INT DEFAULT 0,
                `monthly_requests` INT DEFAULT 0,
                `monthly_tokens` INT DEFAULT 0,
                `total_requests` BIGINT DEFAULT 0,
                `daily_limit` INT DEFAULT 100,
                `monthly_limit` INT DEFAULT 2000,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `ai_seo_scores` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT NOT NULL,
                `overall_score` TINYINT UNSIGNED DEFAULT 0,
                `readability_score` TINYINT UNSIGNED DEFAULT 0,
                `keyword_score` TINYINT UNSIGNED DEFAULT 0,
                `content_score` TINYINT UNSIGNED DEFAULT 0,
                `meta_score` TINYINT UNSIGNED DEFAULT 0,
                `score_data` JSON DEFAULT NULL,
                `computed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_product` (`product_id`),
                KEY `idx_score` (`overall_score`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `ai_seo_cache` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cache_key` VARCHAR(64) NOT NULL,
                `action` VARCHAR(60) NOT NULL,
                `field_name` VARCHAR(100) DEFAULT NULL,
                `output` MEDIUMTEXT NOT NULL,
                `hit_count` INT DEFAULT 1,
                `expires_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_cache_key` (`cache_key`),
                KEY `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `ai_seo_history` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT DEFAULT NULL,
                `admin_id` INT DEFAULT NULL,
                `field_name` VARCHAR(100) NOT NULL,
                `old_value` TEXT,
                `new_value` MEDIUMTEXT,
                `action` VARCHAR(60) NOT NULL,
                `applied` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_product` (`product_id`),
                KEY `idx_admin` (`admin_id`),
                KEY `idx_field` (`field_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `ai_seo_error_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `error_type` VARCHAR(50) NOT NULL,
                `error_code` INT DEFAULT NULL,
                `error_raw` TEXT DEFAULT NULL,
                `user_message` VARCHAR(300) NOT NULL,
                `action` VARCHAR(60) DEFAULT NULL,
                `field_name` VARCHAR(100) DEFAULT NULL,
                `product_id` INT DEFAULT NULL,
                `admin_id` INT DEFAULT NULL,
                `prompt_preview` VARCHAR(500) DEFAULT NULL,
                `model` VARCHAR(100) DEFAULT NULL,
                `latency_ms` INT DEFAULT 0,
                `retried` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_error_type` (`error_type`),
                KEY `idx_created` (`created_at`),
                KEY `idx_product` (`product_id`),
                KEY `idx_admin` (`admin_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($queries as $sql) {
            try {
                $db->exec($sql);
            } catch (Throwable $e) {
                error_log('AI SEO table migration: ' . $e->getMessage());
            }
        }

        try {
            $db->exec(
                "INSERT IGNORE INTO `ai_seo_credits`
                 (`date`, `daily_requests`, `monthly_requests`, `total_requests`)
                 VALUES (CURDATE(), 0, 0, 0)"
            );
        } catch (Throwable $e) {
            error_log('AI SEO credits seed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('ensureDatabaseSchema')) {
    function ensureDatabaseSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $functionsFile = dirname(__DIR__) . '/includes/functions.php';
        if (is_file($functionsFile)) {
            require_once $functionsFile;
        }

        if (function_exists('ensureProductExpectedComingDateColumn')) {
            ensureProductExpectedComingDateColumn($db);
        }
        if (function_exists('ensureProductPrepaidDiscountColumn')) {
            ensureProductPrepaidDiscountColumn($db);
        }

        ensureProductSeoColumns($db);
        ensureUsersProfilePhotoColumn($db);
        ensureAiSeoTables($db);

        $mediaModelFile = dirname(__DIR__) . '/app/Models/ProductMediaModel.php';
        if (is_file($mediaModelFile)) {
            require_once $mediaModelFile;
            (new ProductMediaModel($db))->ensureSchema();
        }

        $variationModelFile = dirname(__DIR__) . '/app/Models/VariationModel.php';
        if (is_file($variationModelFile)) {
            require_once $variationModelFile;
            if (class_exists('VariationModel') && method_exists('VariationModel', 'ensureSchema')) {
                VariationModel::ensureSchema($db);
            }
        }
    }
}
