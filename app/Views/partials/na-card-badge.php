<?php if (!empty($product['is_coming_soon'])): ?>
    <span class="na-badge na-badge--soon">Coming Soon</span>
<?php elseif (!empty($product['is_sold_out'])): ?>
    <span class="na-badge na-badge--sold">Sold Out</span>
<?php elseif (!empty($product['discount_pct']) && (int) $product['discount_pct'] > 0): ?>
    <span class="na-badge"><?= (int) $product['discount_pct'] ?>% OFF</span>
<?php endif; ?>
