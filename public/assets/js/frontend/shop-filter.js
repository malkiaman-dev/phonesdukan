document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("shopFilterForm");
    if (!form) return;

    var sidebar = document.getElementById("shopSidebar");
    var backdrop = document.getElementById("shopDrawerBackdrop");
    var filterToggleBtn = document.getElementById("shopFilterToggle");
    var filterCloseBtn = document.getElementById("shopFilterClose");

    var sidebarSort = document.getElementById("shopSidebarSort");
    var topSort = document.getElementById("shopTopSort");
    var mobileSort = document.getElementById("shopMobileSort");

    var brandSearchInput = document.getElementById("shopBrandSearch");
    var brandItems = document.querySelectorAll("[data-brand-item]");
    var brandEmptyState = document.getElementById("shopBrandEmptyState");

    var priceSlider = form.querySelector("[data-price-slider]");
    var minInput = form.querySelector("[data-range-min]");
    var maxInput = form.querySelector("[data-range-max]");
    var selectedPriceLabel = form.querySelector("[data-selected-price-range]");
    var rangeProgress = form.querySelector("[data-range-progress]");
    var hiddenMinInput = form.querySelector("[data-hidden-min-price]");
    var hiddenMaxInput = form.querySelector("[data-hidden-max-price]");

    var desktopBreakpoint = window.matchMedia("(min-width: 992px)");

    function showNotice(message) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Oops!",
                text: message,
                icon: "error",
                confirmButtonText: "OK",
            });
        } else {
            alert(message);
        }
    }

    function openDrawer() {
        if (!sidebar) return;
        sidebar.classList.add("is-open");
        if (backdrop) backdrop.classList.add("is-visible");
        document.body.classList.add("shop-drawer-open");
    }

    function closeDrawer() {
        if (!sidebar) return;
        sidebar.classList.remove("is-open");
        if (backdrop) backdrop.classList.remove("is-visible");
        document.body.classList.remove("shop-drawer-open");
    }

    if (filterToggleBtn) {
        filterToggleBtn.addEventListener("click", openDrawer);
    }

    if (filterCloseBtn) {
        filterCloseBtn.addEventListener("click", closeDrawer);
    }

    if (backdrop) {
        backdrop.addEventListener("click", closeDrawer);
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeDrawer();
    });

    function syncSortControls(value, source) {
        if (sidebarSort && source !== "sidebar") sidebarSort.value = value;
        if (topSort && source !== "top") topSort.value = value;
        if (mobileSort && source !== "mobile") mobileSort.value = value;
    }

    function submitWithSort(value, source) {
        syncSortControls(value, source);
        form.submit();
    }

    if (sidebarSort) {
        sidebarSort.addEventListener("change", function () {
            submitWithSort(sidebarSort.value, "sidebar");
        });
    }

    if (topSort) {
        topSort.addEventListener("change", function () {
            submitWithSort(topSort.value, "top");
        });
    }

    if (mobileSort) {
        mobileSort.addEventListener("change", function () {
            submitWithSort(mobileSort.value, "mobile");
        });
    }

    if (brandSearchInput && brandItems.length) {
        brandSearchInput.addEventListener("input", function () {
            var query = brandSearchInput.value.trim().toLowerCase();
            var visibleCount = 0;

            Array.prototype.forEach.call(brandItems, function (item) {
                var text = item.textContent ? item.textContent.trim().toLowerCase() : "";
                var visible = query === "" || text.indexOf(query) !== -1;
                item.style.display = visible ? "flex" : "none";
                if (visible) visibleCount++;
            });

            if (brandEmptyState) {
                brandEmptyState.hidden = visibleCount !== 0;
            }
        });
    }

    if (priceSlider && minInput && maxInput) {
        var ABS_MIN = parseInt(priceSlider.getAttribute("data-min"), 10) || 0;
        var ABS_MAX = parseInt(priceSlider.getAttribute("data-max"), 10) || 200000;
        var STEP_GAP = 500;

        function formatPrice(value) {
            return "Rs. " + Number(value).toLocaleString("en-PK");
        }

        function syncHiddenInputs(minVal, maxVal) {
            if (hiddenMinInput) hiddenMinInput.value = String(minVal);
            if (hiddenMaxInput) hiddenMaxInput.value = String(maxVal);
        }

        function clampRangeInputs(changedInput) {
            var minVal = parseInt(minInput.value, 10);
            var maxVal = parseInt(maxInput.value, 10);

            if (isNaN(minVal)) minVal = ABS_MIN;
            if (isNaN(maxVal)) maxVal = ABS_MAX;

            if (maxVal - minVal < STEP_GAP) {
                if (changedInput === "min") {
                    minVal = maxVal - STEP_GAP;
                } else {
                    maxVal = minVal + STEP_GAP;
                }
            }

            minVal = Math.max(ABS_MIN, Math.min(minVal, ABS_MAX - STEP_GAP));
            maxVal = Math.min(ABS_MAX, Math.max(maxVal, ABS_MIN + STEP_GAP));

            minInput.value = String(minVal);
            maxInput.value = String(maxVal);

            return { min: minVal, max: maxVal };
        }

        function updateSliderUI(minVal, maxVal) {
            var left = ((minVal - ABS_MIN) / (ABS_MAX - ABS_MIN)) * 100;
            var right = ((ABS_MAX - maxVal) / (ABS_MAX - ABS_MIN)) * 100;

            if (rangeProgress) {
                rangeProgress.style.left = left + "%";
                rangeProgress.style.right = right + "%";
            }

            if (selectedPriceLabel) {
                selectedPriceLabel.textContent = formatPrice(minVal) + " — " + formatPrice(maxVal);
            }

            syncHiddenInputs(minVal, maxVal);
        }

        function onSliderInput(changedInput) {
            var clamped = clampRangeInputs(changedInput);
            updateSliderUI(clamped.min, clamped.max);
        }

        minInput.addEventListener("input", function () {
            onSliderInput("min");
        });

        maxInput.addEventListener("input", function () {
            onSliderInput("max");
        });

        var initialClamped = clampRangeInputs("max");
        updateSliderUI(initialClamped.min, initialClamped.max);
    }

    var quickDesktopInputs = form.querySelectorAll('input[name="category[]"], input[name="brand[]"]');
    Array.prototype.forEach.call(quickDesktopInputs, function (input) {
        input.addEventListener("change", function () {
            if (desktopBreakpoint.matches) {
                form.submit();
            }
        });
    });

    var resetLinks = document.querySelectorAll(".shop-btn-reset");
    Array.prototype.forEach.call(resetLinks, function (link) {
        link.addEventListener("click", closeDrawer);
    });

    form.addEventListener("submit", function () {
        if (priceSlider && minInput && maxInput) {
            var minVal = parseInt(minInput.value, 10);
            var maxVal = parseInt(maxInput.value, 10);
            if (!isNaN(minVal) && !isNaN(maxVal)) {
                if (hiddenMinInput) hiddenMinInput.value = String(minVal);
                if (hiddenMaxInput) hiddenMaxInput.value = String(maxVal);
            }
        }

        closeDrawer();
    });

    document.addEventListener("click", function (e) {
        var btn = e.target.closest(".na-btn--cart");
        if (!btn || btn.disabled) return;

        var productId = btn.getAttribute("data-product-id");
        var unitPrice = parseFloat(btn.getAttribute("data-unit-price") || 0);
        if (!productId) return;

        var originalText = btn.textContent.trim();
        btn.disabled = true;
        btn.textContent = "Adding...";

        fetch(window.pdWithBase("/app/Controllers/CartController.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                product_id: parseInt(productId, 10),
                quantity: 1,
                unit_price: unitPrice,
            }),
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.status === "success") {
                    btn.textContent = "Added ✓";
                    btn.classList.add("na-btn--added");

                    var totalQty = 0;
                    if (data.cart_summary && typeof data.cart_summary.total_quantity !== "undefined") {
                        totalQty = parseInt(data.cart_summary.total_quantity, 10) || 0;
                    } else if (Array.isArray(data.cart_items)) {
                        totalQty = data.cart_items.reduce(function (sum, item) {
                            return sum + (parseInt(item.total_quantity, 10) || 0);
                        }, 0);
                    }

                    document.querySelectorAll(".cart-count").forEach(function (el) {
                        el.textContent = totalQty;
                    });
                    return;
                }

                btn.disabled = false;
                btn.textContent = originalText;
                showNotice(data.message || "Could not add to cart.");
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = originalText;
                showNotice("Something went wrong. Please try again.");
            });
    });

    var lazyImages = document.querySelectorAll(".na-img-box img, .product-img img");
    Array.prototype.forEach.call(lazyImages, function (img) {
        var wrapper = img.closest(".product-img-wrapper");
        if (!wrapper) return;

        if (!img.complete) {
            wrapper.classList.add("is-loading");
            img.addEventListener(
                "load",
                function () {
                    wrapper.classList.remove("is-loading");
                },
                { once: true }
            );
            img.addEventListener(
                "error",
                function () {
                    wrapper.classList.remove("is-loading");
                },
                { once: true }
            );
        }
    });
});
