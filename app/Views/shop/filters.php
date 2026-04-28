<form id="product-filter" method="GET" action="/shop">
    <!-- Sorting options -->
    <div id="sort-options">
    <h4>Sort By</h4>
    <label>
        <input type="radio" name="sort_by" value="price_asc" <?= ($_GET['sort_by'] ?? '') === 'price_asc' ? 'checked' : '' ?>>
        Price (Low to High)
    </label><br>
    <label>
        <input type="radio" name="sort_by" value="price_desc" <?= ($_GET['sort_by'] ?? '') === 'price_desc' ? 'checked' : '' ?>>
        Price (High to Low)
    </label>
</div>


    <!-- Price Range Filter -->
    <div class="price-range">
        <h4>Set Your Price Range:</h4>

        <?php
        $selectedRanges = $_GET['price_range'] ?? [];
        $priceOptions = [
            '0-15000' => 'Below Rs. 15,000',
            '15000-25000' => 'Rs. 15,000 - Rs. 25,000',
            '25000-40000' => 'Rs. 25,000 - Rs. 40,000',
            '40000-60000' => 'Rs. 40,000 - Rs. 60,000',
            '60000-80000' => 'Rs. 60,000 - Rs. 80,000',
            '80000-100000' => 'Rs. 80,000 - Rs. 100,000',
            '150000-above' => 'Above Rs. 150,000',
        ];

        foreach ($priceOptions as $value => $label):
            $checked = in_array($value, $selectedRanges) ? 'checked' : '';
            $id = 'price-' . str_replace(['-', '_'], '', $value);
        ?>
            <div class="price-option">
                <input type="checkbox" id="<?= $id ?>" name="price_range[]" value="<?= $value ?>" <?= $checked ?>>
                <label for="<?= $id ?>"><?= $label ?></label>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Category Filter -->
    <div id="category-filter">
        <h4>Categories</h4>
        <?php foreach ($categories as $category): ?>
            <label>
                <input type="checkbox" name="category[]" value="<?= $category['category_id'] ?>" 
                       <?= in_array($category['category_id'], $_GET['category'] ?? []) ? 'checked' : '' ?>>
                <?= htmlspecialchars($category['category_name']) ?>
            </label><br>
        <?php endforeach; ?>
    </div>
        <!-- Brand Filter -->
        <div id="brand-filter">
        <h4>Brands</h4>
        <?php foreach ($brands as $brand): ?>
            <label>
                <input type="checkbox" name="brand[]" value="<?= $brand['brand_id'] ?>" 
                       <?= in_array($brand['brand_id'], $_GET['brand'] ?? []) ? 'checked' : '' ?>>
                <?= htmlspecialchars($brand['brand_name']) ?>
            </label><br>
        <?php endforeach; ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('product-filter');
    const inputs = form.querySelectorAll('input[type="radio"], input[type="checkbox"]');

    inputs.forEach(input => {
        input.addEventListener('change', () => {
            form.submit();
        });
    });
});
</script>
