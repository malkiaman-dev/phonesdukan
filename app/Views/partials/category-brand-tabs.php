<?php
/**
 * Horizontal brand filter tabs for category / brand listing pages.
 *
 * @var string $categorySlug
 * @var array<int, array<string, mixed>> $categoryBrands
 * @var string|null $activeBrandSlug  null = "All" tab active
 * @var string $allLabel
 */
$categorySlug = (string) ($categorySlug ?? '');
$categoryBrands = is_array($categoryBrands ?? null) ? $categoryBrands : [];
$activeBrandSlug = isset($activeBrandSlug) ? (string) $activeBrandSlug : null;
$allLabel = (string) ($allLabel ?? 'All');

if ($categorySlug === '' || $categoryBrands === []) {
    return;
}
?>
<div class="mob-brand-bar" id="mobBrandBar">
    <div class="mob-brand-bar-inner">
        <a href="<?= htmlspecialchars(url($categorySlug), ENT_QUOTES, 'UTF-8') ?>"
           class="mob-brand-tab<?= $activeBrandSlug === null || $activeBrandSlug === '' ? ' is-active' : '' ?>">
            <?= htmlspecialchars($allLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php foreach ($categoryBrands as $categoryBrand): ?>
            <?php
            $tabSlug = (string) ($categoryBrand['slug'] ?? '');
            $tabName = (string) ($categoryBrand['brand_name'] ?? $tabSlug);
            if ($tabSlug === '') {
                continue;
            }
            $tabHref = url($categorySlug . '/' . rawurlencode($tabSlug));
            $isActive = $activeBrandSlug !== null && $activeBrandSlug !== '' && $tabSlug === $activeBrandSlug;
            ?>
            <a href="<?= htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8') ?>"
               class="mob-brand-tab<?= $isActive ? ' is-active' : '' ?>">
                <?= htmlspecialchars($tabName, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
