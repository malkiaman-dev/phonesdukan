<?php
/**
 * Frequently bought together / group accessories on the product page.
 * Expects $groupProducts from ProductController.
 */
if (empty($groupProducts) || !is_array($groupProducts)) {
    return;
}
?>
<div class="pd-group-products" id="pd-group-products">
    <div class="pd-group-products__header">
        <h3 class="pd-group-products__title">Frequently bought together</h3>
        <p class="pd-group-products__sub">Select accessories to add with this product</p>
    </div>
    <div class="pd-group-products__list">
        <?php foreach ($groupProducts as $gp):
            $gpId = (int) ($gp['product_id'] ?? 0);
            $gpPrice = (float) ($gp['unit_price'] ?? 0);
            if ($gpId <= 0 || $gpPrice <= 0) {
                continue;
            }
            $gpName = (string) ($gp['product_name'] ?? '');
            $gpImg = (string) ($gp['image_url'] ?? '');
            $gpUrl = function_exists('buildProductPathFromRow')
                ? rtrim(getBaseURL(), '/') . buildProductPathFromRow($gp)
                : '#';
            $variationId = !empty($gp['default_variation_id']) ? (int) $gp['default_variation_id'] : 0;
            $regular = isset($gp['regular_price']) && is_numeric($gp['regular_price']) ? (float) $gp['regular_price'] : 0;
            $sale = isset($gp['sale_price']) && is_numeric($gp['sale_price']) ? (float) $gp['sale_price'] : 0;
            if (($gp['product_type'] ?? '') === 'variable') {
                $sale = 0;
                $regular = $gpPrice;
            }
        ?>
            <label class="pd-group-item">
                <input
                    type="checkbox"
                    class="pd-group-item__check"
                    data-product-id="<?= $gpId ?>"
                    data-unit-price="<?= htmlspecialchars((string) $gpPrice, ENT_QUOTES, 'UTF-8') ?>"
                    data-variation-id="<?= $variationId > 0 ? $variationId : '' ?>"
                >
                <span class="pd-group-item__media">
                    <?php if ($gpImg !== ''): ?>
                        <img src="<?= htmlspecialchars($gpImg, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($gpName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    <?php endif; ?>
                </span>
                <span class="pd-group-item__body">
                    <a class="pd-group-item__name" href="<?= htmlspecialchars($gpUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                        <?= htmlspecialchars($gpName, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <span class="pd-group-item__price">
                        <?php if ($sale > 0 && $regular > $sale): ?>
                            <span class="pd-group-item__old">Rs. <?= number_format($regular) ?></span>
                            <span class="pd-group-item__new">Rs. <?= number_format($sale) ?></span>
                        <?php else: ?>
                            <span class="pd-group-item__new">Rs. <?= number_format($gpPrice) ?></span>
                        <?php endif; ?>
                    </span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
