<?php
// Ensure output buffering is started
ob_start();

// Define SEO meta tags
$pageTitle = "Wholesale Mobile Accessories in Pakistan - Best Prices"; // 61 chars
$metaDescription = "Shop top-quality wholesale mobile accessories in Pakistan. Get bulk deals on chargers, cases, earphones & more at unbeatable prices. Fast delivery!"; // 118 chars
$metaKeywords = "wholesale mobile accessories, mobile accessories Pakistan, bulk mobile accessories, cheap phone accessories, Pakistan wholesale, mobile chargers wholesale, phone cases bulk, earphones wholesale Pakistan, mobile accessories supplier, Pakistan mobile accessories"; // 6 keywords
$metaRobots = "index, follow";

// Debug: Log variables to ensure they are set
error_log("wholesale.php: pageTitle=$pageTitle");
error_log("wholesale.php: metaDescription=$metaDescription");
error_log("wholesale.php: metaKeywords=$metaKeywords");
error_log("wholesale.php: metaRobots=$metaRobots");

// Include header
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>
    <div class="hero-content">
    <h1>Wholesale Mobile Accessories in Pakistan - Bulk Deals & Quality</h1>
    <p>Get top-notch phone chargers, cases, and earphones at cheap bulk prices for your business in Pakistan.</p>
    </div>
<div class="wholesale-container">
    <!-- B2B Product Search -->
    <div class="b2b-search-container">
        <input type="text" id="b2b-search-input" placeholder="Search B2B products..." autocomplete="off">
        <button id="b2b-clear-search" style="display: none;">✖</button>
    </div>
    <div class="product-wrapper">

    <div class="wholesale-products-grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-item" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>" 
                     data-price="<?php echo htmlspecialchars($product['b2b_regular_price'] ?? 0); ?>" 
                     data-name="<?php echo htmlspecialchars(strtolower($product['product_name'])); ?>">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? '/public/assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p>Price: Rs. <?php echo number_format($product['b2b_regular_price'] ?? 0, 2); ?></p>
                    <label class="custom-checkbox">
    <input type="checkbox" class="product-select" value="<?php echo htmlspecialchars($product['product_id']); ?>">
    <span class="checkmark"></span>
    <span class="select-text">Select</span>
</label>
                    <div class="quantity-container">
                        <button type="button" class="quantity-btn minus">−</button>
                        <input type="number" class="product-quantity" name="quantity" value="5" min="5" max="<?php echo htmlspecialchars($product['stock_quantity'] ?? 999); ?>" readonly>
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No B2B products available at the moment.</p>
        <?php endif; ?>
    </div>
    </div>
    <form id="bulk-inquiry-form" class="inquiry-form">
        <h3>Submit Your Bulk Inquiry</h3>
        <div class="form-group flex-group">
            <div>
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Type Here" required>
            </div>
            <div>
                <label>Email *</label>
                <input type="email" name="email" placeholder="Type Here" required>
            </div>
        </div>

        <div class="form-group flex-group">
            <div>
                <label>Phone * (11 digits only)</label>
                <input type="tel" name="phone" placeholder="Type Here" pattern="[0-9]{11}" maxlength="11" required title="Phone must be exactly 11 digits.">
            </div>
            <div>
                <label>Tel No (Optional)</label>
                <input type="tel" name="tel_no" placeholder="Type Here" pattern="[0-9]{0,12}" maxlength="12" title="Tel No must contain only digits (up to 12).">
            </div>
        </div>

        <div class="form-group flex-group">
            <div>
                <label>Business Name *</label>
                <input type="text" name="business_name" placeholder="Type Here" required>
            </div>
            <div>
                <label>Address *</label>
                <input type="text" name="address" placeholder="Type Here" required>
            </div>
        </div>

        <div class="form-group">
            <div>
                <label>Your Message (Optional)</label>
                <textarea name="message" placeholder="Type Here"></textarea>
            </div>
        </div>

        <input type="hidden" name="token" value="<?php echo bin2hex(random_bytes(16)); ?>">

        <div id="total-price">Total Price: Rs. 0.00</div>

        <button type="submit">Submit Order</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const withBase = window.pdWithBase || function (path) {
        const basePath = (window.location.pathname.split('/').filter(Boolean)[0] === 'phonesdukan') ? '/phonesdukan' : '';
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    const selects = document.querySelectorAll('.product-select');
    const form = document.getElementById('bulk-inquiry-form');
    const totalPriceEl = document.getElementById('total-price');
    const searchInput = document.getElementById('b2b-search-input');
    const clearSearchBtn = document.getElementById('b2b-clear-search');
    const productItems = document.querySelectorAll('.product-item');

    // Product search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        clearSearchBtn.style.display = searchTerm ? 'inline-block' : 'none';

        productItems.forEach(item => {
            const productName = item.dataset.name;
            if (searchTerm === '' || productName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        updateTotal(); // Recalculate total in case selected items are hidden
    });

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        productItems.forEach(item => {
            item.style.display = 'block';
        });
        updateTotal();
    });

    selects.forEach(select => {
        select.addEventListener('change', function() {
            const item = this.closest('.product-item');
            const quantityContainer = item.querySelector('.quantity-container');
            const quantityInput = quantityContainer.querySelector('.product-quantity');
            const minusBtn = quantityContainer.querySelector('.quantity-btn.minus');
            const plusBtn = quantityContainer.querySelector('.quantity-btn.plus');

            if (this.checked) {
                quantityInput.disabled = false;
                quantityInput.value = 5; // Default to 5
                updateTotal();
            } else {
                quantityInput.disabled = true;
                quantityInput.value = 5;
                updateTotal();
            }

            // Setup quantity buttons
            setupQuantityButtons(quantityContainer, quantityInput, minusBtn, plusBtn);
        });
    });

    function setupQuantityButtons(container, input, minusBtn, plusBtn) {
        // Remove existing listeners to avoid duplicates
        const maxQuantity = parseInt(input.getAttribute('max')) || 999;
        minusBtn.removeEventListener('click', minusBtn._handler);
        plusBtn.removeEventListener('click', plusBtn._handler);

        function minusHandler(e) {
            e.preventDefault();
            let quantity = parseInt(input.value) || 5;
            if (quantity > 5) {
                input.value = quantity - 1;
                updateTotal();
            } else {
                Swal.fire({
                    title: "Minimum Quantity!",
                    text: "You cannot select less than 5 items for bulk orders.",
                    icon: "warning",
                    confirmButtonText: "OK"
                });
            }
        }

        function plusHandler(e) {
            e.preventDefault();
            let quantity = parseInt(input.value) || 5;
            if (quantity < maxQuantity) {
                input.value = quantity + 1;
                updateTotal();
            } else {
                Swal.fire({
                    title: "Maximum Quantity!",
                    text: `You cannot select more than ${maxQuantity} items due to stock limits.`,
                    icon: "warning",
                    confirmButtonText: "OK"
                });
            }
        }

        minusBtn._handler = minusHandler;
        plusBtn._handler = plusHandler;
        minusBtn.addEventListener('click', minusHandler);
        plusBtn.addEventListener('click', plusHandler);
    }

    // Initial setup for all quantity containers
    document.querySelectorAll('.quantity-container').forEach(container => {
        const input = container.querySelector('.product-quantity');
        const minusBtn = container.querySelector('.quantity-btn.minus');
        const plusBtn = container.querySelector('.quantity-btn.plus');
        if (minusBtn && plusBtn && input) {
            setupQuantityButtons(container, input, minusBtn, plusBtn);
        }
    });

    function updateTotal() {
        let total = 0;
        selects.forEach(select => {
            if (select.checked) {
                const item = select.closest('.product-item');
                // Only include visible and selected items
                if (item.style.display !== 'none') {
                    const price = parseFloat(item.dataset.price) || 0;
                    const quantity = parseInt(item.querySelector('.product-quantity').value) || 5;
                    total += price * quantity;
                } else {
                    // Deselect hidden items to avoid incorrect totals
                    select.checked = false;
                    const quantityInput = item.querySelector('.product-quantity');
                    quantityInput.disabled = true;
                    quantityInput.value = 5;
                }
            }
        });
        totalPriceEl.textContent = `Total Price: Rs. ${total.toFixed(2)}`;
        form.dataset.totalPrice = total.toFixed(2);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.items = [];
        data.total_price = parseFloat(form.dataset.totalPrice) || 0;

        selects.forEach(select => {
            if (select.checked) {
                const item = select.closest('.product-item');
                const productId = parseInt(item.dataset.productId);
                const quantity = parseInt(item.querySelector('.product-quantity').value) || 5;
                data.items.push({ product_id: productId, quantity: quantity });
            }
        });

        if (data.items.length === 0) {
            console.log('No products selected');
            Swal.fire({
                title: "Error!",
                text: "Please select at least one product.",
                icon: "error",
                confirmButtonText: "OK"
            });
            return;
        }

        // Client-side validation for phone (11 digits)
        if (!/^\d{11}$/.test(data.phone)) {
            Swal.fire({
                title: "Invalid Phone Number!",
                text: "Phone number must be exactly 11 digits.",
                icon: "error",
                confirmButtonText: "OK"
            });
            return;
        }

        console.log('Submitting bulk inquiry:', JSON.stringify(data));

        $.ajax({
            url: withBase("/app/Controllers/BulkInquiryController.php"),
            type: "POST",
            data: JSON.stringify(data),
            contentType: "application/json",
            dataType: "json",
            beforeSend: function(xhr) {
                console.log('Sending AJAX POST to', withBase('/app/Controllers/BulkInquiryController.php'));
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                console.log("Bulk Inquiry Response:", response);
                if (response.status === "success") {
                    Swal.fire({
                        title: "Success!",
                        text: response.message || "Your inquiry has been submitted successfully.",
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then(() => {
                        // Reset form and UI
                        form.reset();
                        searchInput.value = '';
                        clearSearchBtn.style.display = 'none';
                        productItems.forEach(item => {
                            item.style.display = 'block';
                        });
                        selects.forEach(select => {
                            select.checked = false;
                            const item = select.closest('.product-item');
                            const quantityContainer = item.querySelector('.quantity-container');
                            const quantityInput = quantityContainer.querySelector('.product-quantity');
                            const minusBtn = quantityContainer.querySelector('.quantity-btn.minus');
                            const plusBtn = quantityContainer.querySelector('.quantity-btn.plus');
                            quantityInput.disabled = true;
                            quantityInput.value = 5;
                            setupQuantityButtons(quantityContainer, quantityInput, minusBtn, plusBtn);
                        });
                        totalPriceEl.textContent = `Total Price: Rs. 0.00`;
                        form.dataset.totalPrice = '0.00';
                    });
                } else {
                    console.log('Error response:', response);
                    Swal.fire({
                        title: "Error!",
                        text: response.message || "Failed to submit inquiry.",
                        icon: "error",
                        confirmButtonText: "Try Again"
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("Bulk Inquiry AJAX Error:", status, error, xhr.responseText);
                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong, please try again. Error: " + (xhr.responseJSON?.message || error),
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        });
    });
});
</script>

<?php
// Flush output buffer
ob_end_flush();
require_once dirname(__DIR__, 3) . '/includes/footer.php';
?>