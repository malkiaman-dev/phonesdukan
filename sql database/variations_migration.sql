-- ============================================================
-- PhonesDukan: Product Variations & Swatches System Migration
-- Run this once. All statements are safe (IF NOT EXISTS / IF EXISTS guards).
-- ============================================================

-- ------------------------------------------------------------
-- 1. Global variation types  (Color, RAM, Storage, Size …)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `variation_types` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)    NOT NULL,
  `slug`         VARCHAR(120)    NOT NULL UNIQUE,
  `display_type` ENUM('button','color','image') NOT NULL DEFAULT 'button',
  `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Values under each variation type  (Black, 8GB, 256GB …)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `variation_values` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `variation_type_id` INT UNSIGNED    NOT NULL,
  `value`             VARCHAR(150)    NOT NULL,
  `slug`              VARCHAR(170)    NOT NULL,
  `color_code`        VARCHAR(20)     NULL DEFAULT NULL,
  `image`             VARCHAR(500)    NULL DEFAULT NULL,
  `sort_order`        SMALLINT        NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_type_slug` (`variation_type_id`, `slug`),
  CONSTRAINT `fk_vv_type` FOREIGN KEY (`variation_type_id`)
      REFERENCES `variation_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. One row per variation combination for a product
--    (e.g. iPhone / Black / 8GB / 256GB)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_variations` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `product_id`     INT             NOT NULL,
  `sku`            VARCHAR(200)    NULL DEFAULT NULL,
  `regular_price`  DECIMAL(12,2)   NULL DEFAULT NULL,
  `sale_price`     DECIMAL(12,2)   NULL DEFAULT NULL,
  `stock_quantity` INT             NOT NULL DEFAULT 0,
  `image`          VARCHAR(500)    NULL DEFAULT NULL,
  `status`         TINYINT(1)      NOT NULL DEFAULT 1,
  `is_default`     TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pv_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Which values belong to which variation row
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_variation_values` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_variation_id` INT UNSIGNED NOT NULL,
  `variation_type_id`    INT UNSIGNED NOT NULL,
  `variation_value_id`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pvv` (`product_variation_id`, `variation_type_id`),
  CONSTRAINT `fk_pvv_variation` FOREIGN KEY (`product_variation_id`)
      REFERENCES `product_variations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pvv_type`  FOREIGN KEY (`variation_type_id`)
      REFERENCES `variation_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pvv_value` FOREIGN KEY (`variation_value_id`)
      REFERENCES `variation_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Add product_type column to products table (safe)
-- ------------------------------------------------------------
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `product_type` ENUM('simple','variable') NOT NULL DEFAULT 'simple';

-- ------------------------------------------------------------
-- 6. Add variation columns to cart table (safe)
-- ------------------------------------------------------------
ALTER TABLE `cart`
  ADD COLUMN IF NOT EXISTS `variation_id`         INT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `variation_attributes` TEXT         NULL DEFAULT NULL;

-- ------------------------------------------------------------
-- 7. Add variation columns to order_items table (safe)
-- ------------------------------------------------------------
ALTER TABLE `order_items`
  ADD COLUMN IF NOT EXISTS `variation_id`         INT UNSIGNED  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `variation_sku`        VARCHAR(200)  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `variation_attributes` TEXT          NULL DEFAULT NULL;
