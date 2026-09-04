-- Group / accessory products linked to a parent product (e.g. phone → cables, adapters).
-- Safe to re-run on MariaDB 10.3+.

CREATE TABLE IF NOT EXISTS product_group_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE product_group_items
    ADD COLUMN IF NOT EXISTS group_price DECIMAL(12,2) NULL DEFAULT NULL AFTER child_product_id;
