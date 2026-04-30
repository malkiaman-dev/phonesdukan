<?php
$selectedSort = $_GET['sort_by'] ?? 'latest';
$selectedSort = $selectedSort === '' ? 'latest' : $selectedSort;

$selectedPriceRanges = $_GET['price_range'] ?? [];
$selectedCategories = $_GET['category'] ?? [];
$selectedBrands = $_GET['brand'] ?? [];

if (!is_array($selectedPriceRanges)) {
    $selectedPriceRanges = array_filter(explode(',', (string) $selectedPriceRanges));
}
if (!is_array($selectedCategories)) {
    $selectedCategories = array_filter(explode(',', (string) $selectedCategories));
}
if (!is_array($selectedBrands)) {
    $selectedBrands = array_filter(explode(',', (string) $selectedBrands));
}

$selectedPriceRanges = array_map('strval', $selectedPriceRanges);
$selectedCategories = array_map('strval', $selectedCategories);
$selectedBrands = array_map('strval', $selectedBrands);

$sortOptions = [
    'latest'     => 'Latest',
    'price_asc'  => 'Price Low to High',
    'price_desc' => 'Price High to Low',
];

$priceOptions = [
    '0-15000' => 'Below Rs. 15,000',
    '15000-25000' => 'Rs. 15,000 - Rs. 25,000',
    '25000-40000' => 'Rs. 25,000 - Rs. 40,000',
    '40000-60000' => 'Rs. 40,000 - Rs. 60,000',
    '60000-80000' => 'Rs. 60,000 - Rs. 80,000',
    '80000-100000' => 'Rs. 80,000 - Rs. 100,000',
    '100000-150000' => 'Rs. 100,000 - Rs. 150,000',
    '150000-above' => 'Above Rs. 150,000',
];

$absoluteMin = 0;
$absoluteMax = 200000;

$requestedMin = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? (int) $_GET['min_price'] : null;
$requestedMax = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? (int) $_GET['max_price'] : null;

if (($requestedMin === null || $requestedMax === null) && !empty($selectedPriceRanges)) {
    $derivedMin = null;
    $derivedMax = null;

    foreach ($selectedPriceRanges as $range) {
        $rangeMin = null;
        $rangeMax = null;

        if ($range === '150000-above') {
            $rangeMin = 150000;
            $rangeMax = $absoluteMax;
        } elseif (preg_match('/^(\d+)-(\d+)$/', (string) $range, $matches)) {
            $rangeMin = (int) $matches[1];
            $rangeMax = (int) $matches[2];
        }

        if ($rangeMin === null || $rangeMax === null) {
            continue;
        }

        if ($derivedMin === null || $rangeMin < $derivedMin) {
            $derivedMin = $rangeMin;
        }
        if ($derivedMax === null || $rangeMax > $derivedMax) {
            $derivedMax = $rangeMax;
        }
    }

    if ($requestedMin === null && $derivedMin !== null) {
        $requestedMin = $derivedMin;
    }
    if ($requestedMax === null && $derivedMax !== null) {
        $requestedMax = $derivedMax;
    }
}

if ($requestedMin === null) {
    $requestedMin = $absoluteMin;
}
if ($requestedMax === null) {
    $requestedMax = $absoluteMax;
}

$requestedMin = max($absoluteMin, min($requestedMin, $absoluteMax));
$requestedMax = max($absoluteMin, min($requestedMax, $absoluteMax));
if ($requestedMax < $requestedMin) {
    $swap = $requestedMin;
    $requestedMin = $requestedMax;
    $requestedMax = $swap;
}
?>

<form id="shopFilterForm" class="shop-filter-form" method="GET" action="<?= htmlspecialchars(url('shop'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="filter-group" id="shopSidebarSortGroup">
        <label class="filter-label">Sort By</label>
        <div class="shop-sidebar-custom-sort">
            <button type="button" class="shop-sidebar-sort-btn" id="shopSidebarSortBtn" aria-haspopup="listbox" aria-expanded="false">
                <span class="shop-sidebar-sort-current" id="shopSidebarSortCurrent"><?= htmlspecialchars($sortOptions[$selectedSort] ?? 'Latest', ENT_QUOTES, 'UTF-8') ?></span>
                <svg class="shop-sidebar-sort-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <ul class="shop-sidebar-sort-menu" id="shopSidebarSortMenu" role="listbox" aria-label="Sort options">
                <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                    <li class="shop-sidebar-sort-option<?= $selectedSort === $sortValue ? ' is-active' : '' ?>"
                        data-value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>"
                        role="option"
                        aria-selected="<?= $selectedSort === $sortValue ? 'true' : 'false' ?>">
                        <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <select name="sort_by" id="shopSidebarSort" class="shop-sidebar-sort-hidden" aria-hidden="true" tabindex="-1">
                <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                    <option value="<?= htmlspecialchars($sortValue, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSort === $sortValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="filter-group">
        <div class="filter-group-title-row">
            <h4>Price Range</h4>
            <span class="shop-selected-price" data-selected-price-range>
                Rs. <?= number_format((int) $requestedMin) ?> — Rs. <?= number_format((int) $requestedMax) ?>
            </span>
        </div>

        <div class="shop-price-slider" data-price-slider data-min="<?= (int) $absoluteMin ?>" data-max="<?= (int) $absoluteMax ?>">
            <div class="shop-range-track">
                <span class="shop-range-progress" data-range-progress></span>
            </div>
            <input type="range" min="<?= (int) $absoluteMin ?>" max="<?= (int) $absoluteMax ?>" step="500" value="<?= (int) $requestedMin ?>" class="shop-range-input shop-range-min" data-range-min aria-label="Minimum price">
            <input type="range" min="<?= (int) $absoluteMin ?>" max="<?= (int) $absoluteMax ?>" step="500" value="<?= (int) $requestedMax ?>" class="shop-range-input shop-range-max" data-range-max aria-label="Maximum price">
        </div>

        <input type="hidden" name="min_price" value="<?= (int) $requestedMin ?>" data-hidden-min-price>
        <input type="hidden" name="max_price" value="<?= (int) $requestedMax ?>" data-hidden-max-price>
    </div>

    <details class="filter-group filter-collapsible" open>
        <summary>Categories</summary>
        <div class="filter-checklist">
            <?php foreach ($categories as $category): ?>
                <?php $catId = (string) $category['category_id']; ?>
                <label class="filter-option">
                    <input type="checkbox" name="category[]" value="<?= htmlspecialchars($catId, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($catId, $selectedCategories, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="filter-group filter-collapsible" open>
        <summary>Brands</summary>
        <div class="filter-brand-search-wrap">
            <input type="search" id="shopBrandSearch" class="shop-brand-search" placeholder="Search brand..." autocomplete="off">
        </div>
        <div class="filter-checklist brand-list" id="shopBrandList">
            <?php foreach ($brands as $brand): ?>
                <?php $brandId = (string) $brand['brand_id']; ?>
                <label class="filter-option brand-option" data-brand-item>
                    <input type="checkbox" name="brand[]" value="<?= htmlspecialchars($brandId, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($brandId, $selectedBrands, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
            <p class="brand-empty-state" id="shopBrandEmptyState" hidden>No brand found.</p>
        </div>
    </details>

    <div class="shop-filter-actions">
        <button type="submit" class="shop-btn shop-btn-apply">Apply Filters</button>
        <a href="<?= htmlspecialchars(url('shop'), ENT_QUOTES, 'UTF-8') ?>" class="shop-btn shop-btn-reset">Reset</a>
    </div>
</form>
