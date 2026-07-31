<?php
require_once dirname(__DIR__) . '/includes/database_migrations.php';
require_once dirname(__DIR__) . '/database/db.php';

$checks = [
    ['products', 'expected_coming_date'],
    ['products', 'prepaid_discount_amount'],
    ['products', 'product_type'],
    ['product_seo', 'canonical_url'],
    ['product_seo', 'secondary_keywords'],
    ['users', 'profile_photo'],
    ['product_images', 'sort_order'],
    ['cart', 'variation_id'],
    ['order_items', 'variation_id'],
    ['product_variations', 'prepaid_discount_amount'],
];

$tables = [
    'variation_types',
    'variation_values',
    'product_variations',
    'product_variation_values',
    'product_videos',
    'ai_seo_requests',
    'ai_seo_credits',
    'ai_seo_scores',
    'ai_seo_cache',
    'ai_seo_history',
    'ai_seo_error_logs',
    'product_group_items',
];

$ok = true;

foreach ($checks as [$table, $column]) {
    $exists = dbTableHasColumn($conn, $table, $column);
    echo ($exists ? 'OK' : 'MISSING') . " {$table}.{$column}\n";
    $ok = $ok && $exists;
}

foreach ($tables as $table) {
    $exists = dbTableExists($conn, $table);
    echo ($exists ? 'OK' : 'MISSING') . " table {$table}\n";
    $ok = $ok && $exists;
}

echo $ok ? "ALL CHECKS PASSED\n" : "SOME CHECKS FAILED\n";
exit($ok ? 0 : 1);
