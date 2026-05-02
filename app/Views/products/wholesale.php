<?php
ob_start();

$pageTitle       = "Wholesale Mobile Accessories in Pakistan - Best Prices";
$metaDescription = "Shop top-quality wholesale mobile accessories in Pakistan. Get bulk deals on chargers, cases, earphones & more at unbeatable prices. Fast delivery!";
$metaKeywords    = "wholesale mobile accessories, mobile accessories Pakistan, bulk mobile accessories, cheap phone accessories, Pakistan wholesale, mobile chargers wholesale, phone cases bulk, earphones wholesale Pakistan, mobile accessories supplier, Pakistan mobile accessories";
$metaRobots      = "index, follow";

error_log("wholesale.php: pageTitle=$pageTitle");
error_log("wholesale.php: metaDescription=$metaDescription");
error_log("wholesale.php: metaKeywords=$metaKeywords");
error_log("wholesale.php: metaRobots=$metaRobots");

require_once dirname(__DIR__, 3) . '/includes/header.php';
?>

<!-- ═══════════════════════════════════════
     SECTION 1 · HERO
     ═══════════════════════════════════════ -->
<section class="ws-hero">
    <div class="ws-hero-inner">
        <p class="ws-hero-eyebrow">B2B Wholesale Portal</p>
        <h1 class="ws-hero-title"><span>Wholesale Mobile Accessories</span> in Pakistan</h1>
        <p class="ws-hero-sub">Get top-notch phone chargers, cases, and earphones at unbeatable bulk prices for your business across Pakistan.</p>
    </div>
</section>

<!-- ═══════════════════════════════════════
     MAIN CONTENT WRAPPER
     ═══════════════════════════════════════ -->
<div class="ws-container">

    <!-- SECTION 2 · SEARCH BAR -->
    <div class="ws-search-wrapper">
        <div class="ws-search-bar">
            <svg class="ws-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="b2b-search-input" placeholder="Search B2B products..." autocomplete="off">
            <button id="b2b-clear-search" class="ws-search-clear" style="display:none;" aria-label="Clear search">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- SECTION 3 · PRODUCT GRID -->
    <div class="ws-product-wrap">
        <div class="wholesale-products-grid ws-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-item ws-card"
                         data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"
                         data-price="<?php echo htmlspecialchars($product['b2b_regular_price'] ?? 0); ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($product['product_name'])); ?>">

                        <div class="ws-card__img-wrap">
                            <img src="<?php echo url(ltrim($product['image_url'] ?? 'public/assets/images/Phones_dukan_favicon.png', '/')); ?>"
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='<?= url('public/assets/images/Phones_dukan_favicon.png') ?>';">
                        </div>

                        <div class="ws-card__body">
                            <h3 class="ws-card__name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="ws-card__price">Rs. <?php echo number_format($product['b2b_regular_price'] ?? 0, 2); ?></p>
                        </div>

                        <div class="ws-card__actions">
                            <label class="custom-checkbox">
                                <input type="checkbox" class="product-select" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                <span class="checkmark"></span>
                                <span class="select-text">Select Item</span>
                            </label>
                            <div class="quantity-container">
                                <button type="button" class="quantity-btn minus" aria-label="Decrease quantity">−</button>
                                <input type="number" class="product-quantity" name="quantity" value="5" min="5"
                                       max="<?php echo htmlspecialchars($product['stock_quantity'] ?? 999); ?>" readonly>
                                <button type="button" class="quantity-btn plus" aria-label="Increase quantity">+</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="ws-empty">
                    <p>No B2B products available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 5 · BULK INQUIRY FORM -->
    <div class="ws-form-section">

        <!-- Order Summary — shown dynamically when items are selected -->
        <div class="ws-order-summary" id="ws-order-summary" style="display:none;">
            <div class="ws-order-summary__left">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <span class="ws-order-summary__title">Order Summary</span>
                <span class="ws-order-summary__count"><span id="ws-summary-count">0</span> items selected</span>
            </div>
            <div class="ws-order-summary__total">
                Total: <strong id="ws-summary-total-inline">Rs. 0.00</strong>
            </div>
        </div>

        <form id="bulk-inquiry-form" class="ws-form">
            <div class="ws-form__header">
                <h2>Submit Your Bulk Inquiry</h2>
                <p>Fill in your details and our team will contact you with pricing and availability.</p>
            </div>

            <div class="ws-form__grid">
                <div class="ws-form__field">
                    <label>Full Name <span class="ws-required">*</span></label>
                    <input type="text" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="ws-form__field">
                    <label>Email Address <span class="ws-required">*</span></label>
                    <input type="email" name="email" placeholder="Enter your email address" required>
                </div>
                <div class="ws-form__field">
                    <label>Phone <span class="ws-required">*</span> <small>(11 digits only)</small></label>
                    <input type="tel" name="phone" placeholder="03XXXXXXXXX" pattern="[0-9]{11}" maxlength="11" required title="Phone must be exactly 11 digits.">
                </div>
                <div class="ws-form__field">
                    <label>Tel No <small>(Optional)</small></label>
                    <input type="tel" name="tel_no" placeholder="Landline number" pattern="[0-9]{0,12}" maxlength="12" title="Tel No must contain only digits (up to 12).">
                </div>
                <div class="ws-form__field">
                    <label>Business Name <span class="ws-required">*</span></label>
                    <input type="text" name="business_name" placeholder="Your company or shop name" required>
                </div>
                <div class="ws-form__field">
                    <label>Address <span class="ws-required">*</span></label>
                    <input type="text" name="address" placeholder="Full delivery address" required>
                </div>
                <div class="ws-form__field ws-form__field--full">
                    <label>Message <small>(Optional)</small></label>
                    <textarea name="message" placeholder="Any specific requirements, quantities, or questions..."></textarea>
                </div>
            </div>

            <input type="hidden" name="token" value="<?php echo bin2hex(random_bytes(16)); ?>">

            <div class="ws-form__total-row">
                <div id="total-price" class="ws-total-display">Total Price: Rs. 0.00</div>
            </div>

            <div class="ws-form__submit">
                <button type="submit" class="ws-submit-btn">
                    Submit Bulk Inquiry
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

</div><!-- /.ws-container -->

<!-- SECTION 4 · FLOATING SUMMARY PANEL (shown when ≥1 product selected) -->
<div class="ws-float-panel" id="ws-float-panel" role="status" aria-live="polite">
    <div class="ws-float-panel__info">
        <div class="ws-float-panel__count">
            <span id="ws-float-count">0</span> item(s) selected
        </div>
        <div class="ws-float-panel__total" id="ws-float-total">Rs. 0.00</div>
    </div>
    <button class="ws-float-panel__cta" id="ws-float-cta" type="button">
        Proceed to Inquiry →
    </button>
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

    const selects      = document.querySelectorAll('.product-select');
    const form         = document.getElementById('bulk-inquiry-form');
    const totalPriceEl = document.getElementById('total-price');
    const searchInput  = document.getElementById('b2b-search-input');
    const clearBtn     = document.getElementById('b2b-clear-search');
    const productItems = document.querySelectorAll('.product-item');

    // Floating panel & order summary refs
    const floatPanel   = document.getElementById('ws-float-panel');
    const floatCount   = document.getElementById('ws-float-count');
    const floatTotal   = document.getElementById('ws-float-total');
    const floatCta     = document.getElementById('ws-float-cta');
    const orderSummary = document.getElementById('ws-order-summary');
    const summaryCount = document.getElementById('ws-summary-count');
    const summaryTotal = document.getElementById('ws-summary-total-inline');

    // Scroll to form when floating CTA is clicked
    if (floatCta) {
        floatCta.addEventListener('click', function () {
            const formSection = document.querySelector('.ws-form-section');
            if (formSection) formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ── Search ────────────────────────────────
    searchInput.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        clearBtn.style.display = term ? 'flex' : 'none';
        productItems.forEach(item => {
            item.style.display = (!term || item.dataset.name.includes(term)) ? '' : 'none';
        });
        updateTotal();
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        productItems.forEach(item => { item.style.display = ''; });
        updateTotal();
    });

    // ── Checkbox selection ────────────────────
    selects.forEach(select => {
        select.addEventListener('change', function () {
            const item             = this.closest('.product-item');
            const qtyContainer     = item.querySelector('.quantity-container');
            const qtyInput         = qtyContainer.querySelector('.product-quantity');
            const minusBtn         = qtyContainer.querySelector('.quantity-btn.minus');
            const plusBtn          = qtyContainer.querySelector('.quantity-btn.plus');

            item.classList.toggle('is-selected', this.checked);

            if (this.checked) {
                qtyInput.disabled = false;
                qtyInput.value    = 5;
            } else {
                qtyInput.disabled = true;
                qtyInput.value    = 5;
            }

            updateTotal();
            setupQuantityButtons(qtyContainer, qtyInput, minusBtn, plusBtn);
        });
    });

    // ── Quantity buttons ──────────────────────
    function setupQuantityButtons(container, input, minusBtn, plusBtn) {
        const maxQty = parseInt(input.getAttribute('max')) || 999;
        minusBtn.removeEventListener('click', minusBtn._handler);
        plusBtn.removeEventListener('click', plusBtn._handler);

        function minusHandler(e) {
            e.preventDefault();
            const qty = parseInt(input.value) || 5;
            if (qty > 5) {
                input.value = qty - 1;
                updateTotal();
            } else {
                Swal.fire({ title: "Minimum Quantity!", text: "You cannot select less than 5 items for bulk orders.", icon: "warning", confirmButtonText: "OK" });
            }
        }

        function plusHandler(e) {
            e.preventDefault();
            const qty = parseInt(input.value) || 5;
            if (qty < maxQty) {
                input.value = qty + 1;
                updateTotal();
            } else {
                Swal.fire({ title: "Maximum Quantity!", text: `You cannot select more than ${maxQty} items due to stock limits.`, icon: "warning", confirmButtonText: "OK" });
            }
        }

        minusBtn._handler = minusHandler;
        plusBtn._handler  = plusHandler;
        minusBtn.addEventListener('click', minusHandler);
        plusBtn.addEventListener('click', plusHandler);
    }

    document.querySelectorAll('.quantity-container').forEach(container => {
        const input    = container.querySelector('.product-quantity');
        const minusBtn = container.querySelector('.quantity-btn.minus');
        const plusBtn  = container.querySelector('.quantity-btn.plus');
        if (minusBtn && plusBtn && input) setupQuantityButtons(container, input, minusBtn, plusBtn);
    });

    // ── Total calculation + UI sync ───────────
    function updateTotal() {
        let total = 0, selected = 0;

        selects.forEach(select => {
            if (select.checked) {
                const item = select.closest('.product-item');
                if (item.style.display !== 'none') {
                    total    += (parseFloat(item.dataset.price) || 0) * (parseInt(item.querySelector('.product-quantity').value) || 5);
                    selected++;
                } else {
                    // Deselect items hidden by search
                    select.checked = false;
                    item.classList.remove('is-selected');
                    const qi = item.querySelector('.product-quantity');
                    qi.disabled = true;
                    qi.value    = 5;
                }
            }
        });

        totalPriceEl.textContent = `Total Price: Rs. ${total.toFixed(2)}`;
        form.dataset.totalPrice  = total.toFixed(2);

        // Floating panel
        if (floatPanel) {
            floatPanel.classList.toggle('is-visible', selected > 0);
            floatCount.textContent = selected;
            floatTotal.textContent = `Rs. ${total.toFixed(2)}`;
        }

        // Order summary above form
        if (orderSummary) {
            orderSummary.style.display = selected > 0 ? 'flex' : 'none';
            summaryCount.textContent   = selected;
            summaryTotal.textContent   = `Rs. ${total.toFixed(2)}`;
        }
    }

    // ── Form submission ───────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const data         = Object.fromEntries(new FormData(this));
        data.items         = [];
        data.total_price   = parseFloat(form.dataset.totalPrice) || 0;

        selects.forEach(select => {
            if (select.checked) {
                const item = select.closest('.product-item');
                data.items.push({
                    product_id: parseInt(item.dataset.productId),
                    quantity:   parseInt(item.querySelector('.product-quantity').value) || 5
                });
            }
        });

        if (data.items.length === 0) {
            Swal.fire({ title: "Error!", text: "Please select at least one product.", icon: "error", confirmButtonText: "OK" });
            return;
        }

        if (!/^\d{11}$/.test(data.phone)) {
            Swal.fire({ title: "Invalid Phone Number!", text: "Phone number must be exactly 11 digits.", icon: "error", confirmButtonText: "OK" });
            return;
        }

        $.ajax({
            url: withBase("/app/Controllers/BulkInquiryController.php"),
            type: "POST",
            data: JSON.stringify(data),
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function (response) {
                if (response.status === "success") {
                    Swal.fire({ title: "Success!", text: response.message || "Your inquiry has been submitted successfully.", icon: "success", confirmButtonText: "OK" }).then(() => {
                        form.reset();
                        searchInput.value      = '';
                        clearBtn.style.display = 'none';
                        productItems.forEach(item => { item.style.display = ''; });
                        selects.forEach(select => {
                            select.checked = false;
                            const item     = select.closest('.product-item');
                            item.classList.remove('is-selected');
                            const qc  = item.querySelector('.quantity-container');
                            const qi  = qc.querySelector('.product-quantity');
                            const mb  = qc.querySelector('.quantity-btn.minus');
                            const pb  = qc.querySelector('.quantity-btn.plus');
                            qi.disabled = true;
                            qi.value    = 5;
                            setupQuantityButtons(qc, qi, mb, pb);
                        });
                        totalPriceEl.textContent = 'Total Price: Rs. 0.00';
                        form.dataset.totalPrice  = '0.00';
                        if (floatPanel)    floatPanel.classList.remove('is-visible');
                        if (orderSummary)  orderSummary.style.display = 'none';
                    });
                } else {
                    Swal.fire({ title: "Error!", text: response.message || "Failed to submit inquiry.", icon: "error", confirmButtonText: "Try Again" });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({ title: "Error!", text: "Something went wrong, please try again. Error: " + (xhr.responseJSON?.message || error), icon: "error", confirmButtonText: "OK" });
            }
        });
    });
});
</script>

<?php
ob_end_flush();
require_once dirname(__DIR__, 3) . '/includes/footer.php';
?>
