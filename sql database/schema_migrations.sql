-- Run all incremental schema updates on an existing Phones Dukan database.
-- Safe to re-run on MariaDB 10.3+ (uses IF NOT EXISTS).

SOURCE expected_coming_date_migration.sql;
SOURCE prepaid_discount_migration.sql;
SOURCE variations_migration.sql;
SOURCE product_seo_migration.sql;
SOURCE users_profile_photo_migration.sql;
SOURCE product_groups_migration.sql;
