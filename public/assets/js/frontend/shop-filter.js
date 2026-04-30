document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("shopFilterForm");
    if (!form) return;

    var sidebar = document.getElementById("shopSidebar");
    var backdrop = document.getElementById("shopDrawerBackdrop");
    var filterToggleBtn = document.getElementById("shopFilterToggle");
    var filterCloseBtn = document.getElementById("shopFilterClose");

    var brandSearchInput = document.getElementById("shopBrandSearch");
    var brandEmptyState = document.getElementById("shopBrandEmptyState");

    var priceSlider = form.querySelector("[data-price-slider]");
    var minInput = form.querySelector("[data-range-min]");
    var maxInput = form.querySelector("[data-range-max]");
    var selectedPriceLabel = form.querySelector("[data-selected-price-range]");
    var rangeProgress = form.querySelector("[data-range-progress]");
    var hiddenMinInput = form.querySelector("[data-hidden-min-price]");
    var hiddenMaxInput = form.querySelector("[data-hidden-max-price]");

    var ABS_MIN = priceSlider ? (parseInt(priceSlider.getAttribute("data-min"), 10) || 0) : 0;
    var ABS_MAX = priceSlider ? (parseInt(priceSlider.getAttribute("data-max"), 10) || 200000) : 200000;
    var STEP_GAP = 500;

    var debounceTimer = null;
    var requestController = null;

    function showNotice(message) {
        if (typeof Swal !== "undefined") {
            Swal.fire({ title: "Oops!", text: message, icon: "error", confirmButtonText: "OK" });
        } else {
            alert(message);
        }
    }

    function getSortControls() {
        return {
            sidebar: document.getElementById("shopSidebarSort"),
            top: document.getElementById("shopTopSort"),
            mobile: document.getElementById("shopMobileSort"),
        };
    }

    function syncSortControls(value) {
        var controls = getSortControls();
        [controls.sidebar, controls.top, controls.mobile].forEach(function (el) {
            if (el && el.value !== value) {
                el.value = value;
            }
        });
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

    function formatPrice(value) {
        return "Rs. " + Number(value).toLocaleString("en-PK");
    }

    function syncHiddenPriceInputs(minVal, maxVal) {
        if (hiddenMinInput) hiddenMinInput.value = String(minVal);
        if (hiddenMaxInput) hiddenMaxInput.value = String(maxVal);
    }

    function clampSliderValues(changedInput) {
        if (!minInput || !maxInput) return { min: ABS_MIN, max: ABS_MAX };

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
        if (rangeProgress) {
            var left = ((minVal - ABS_MIN) / (ABS_MAX - ABS_MIN)) * 100;
            var right = ((ABS_MAX - maxVal) / (ABS_MAX - ABS_MIN)) * 100;
            rangeProgress.style.left = left + "%";
            rangeProgress.style.right = right + "%";
        }

        if (selectedPriceLabel) {
            selectedPriceLabel.textContent = formatPrice(minVal) + " — " + formatPrice(maxVal);
        }

        syncHiddenPriceInputs(minVal, maxVal);
    }

    function hydrateLazyImages() {
        var lazyImages = document.querySelectorAll(".na-img-box img, .product-img img");
        Array.prototype.forEach.call(lazyImages, function (img) {
            var wrapper = img.closest(".product-img-wrapper");
            if (!wrapper) return;

            if (!img.complete) {
                wrapper.classList.add("is-loading");
                img.addEventListener("load", function () {
                    wrapper.classList.remove("is-loading");
                }, { once: true });
                img.addEventListener("error", function () {
                    wrapper.classList.remove("is-loading");
                }, { once: true });
            }
        });
    }

    function serializeFilters(resetPaged) {
        var formData = new FormData(form);
        var params = new URLSearchParams(formData);

        if (resetPaged) params.delete("paged");
        if (!params.get("sort_by")) params.set("sort_by", "latest");

        return params;
    }

    function renderFromHtml(html, requestUrl) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, "text/html");
        var incomingSection = doc.querySelector(".product-section");
        var currentSection = document.querySelector(".product-section");

        if (!incomingSection || !currentSection) {
            throw new Error("Could not parse updated product section.");
        }

        currentSection.innerHTML = incomingSection.innerHTML;

        var urlObj = new URL(requestUrl, window.location.origin);
        var sortValue = urlObj.searchParams.get("sort_by") || "latest";
        syncSortControls(sortValue);

        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, "", urlObj.pathname + urlObj.search);
        }

        hydrateLazyImages();
    }

    function fetchAndRender(params, options) {
        var opts = options || {};
        var url = form.action + (params.toString() ? "?" + params.toString() : "");

        if (requestController) {
            requestController.abort();
        }

        requestController = new AbortController();

        if (opts.closeDrawer) {
            closeDrawer();
        }

        return fetch(url, {
            method: "GET",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: requestController.signal,
        })
            .then(function (response) {
                if (!response.ok) throw new Error("Failed to fetch products.");
                return response.text();
            })
            .then(function (html) {
                renderFromHtml(html, url);
            })
            .catch(function (error) {
                if (error && error.name === "AbortError") return;
                showNotice("Could not refresh products. Please try again.");
            })
            .finally(function () {
                requestController = null;
            });
    }

    function runRealtimeUpdate(options) {
        var params = serializeFilters(true);
        fetchAndRender(params, options || {});
    }

    function debouncedRealtimeUpdate() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(function () {
            runRealtimeUpdate({ closeDrawer: false });
        }, 140);
    }

    if (filterToggleBtn) filterToggleBtn.addEventListener("click", openDrawer);
    if (filterCloseBtn) filterCloseBtn.addEventListener("click", closeDrawer);
    if (backdrop) backdrop.addEventListener("click", closeDrawer);

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeDrawer();
    });

    if (brandSearchInput) {
        brandSearchInput.addEventListener("input", function () {
            var query = brandSearchInput.value.trim().toLowerCase();
            var brandItems = document.querySelectorAll("[data-brand-item]");
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
        var initialClamped = clampSliderValues("max");
        updateSliderUI(initialClamped.min, initialClamped.max);

        minInput.addEventListener("input", function () {
            var clamped = clampSliderValues("min");
            updateSliderUI(clamped.min, clamped.max);
            debouncedRealtimeUpdate();
        });

        maxInput.addEventListener("input", function () {
            var clamped = clampSliderValues("max");
            updateSliderUI(clamped.min, clamped.max);
            debouncedRealtimeUpdate();
        });

        minInput.addEventListener("change", function () {
            runRealtimeUpdate({ closeDrawer: false });
        });
        maxInput.addEventListener("change", function () {
            runRealtimeUpdate({ closeDrawer: false });
        });
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (priceSlider && minInput && maxInput) {
            var clamped = clampSliderValues("max");
            updateSliderUI(clamped.min, clamped.max);
        }
        runRealtimeUpdate({ closeDrawer: true });
    });

    document.addEventListener("change", function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) return;

        if (target.id === "shopSidebarSort" || target.id === "shopTopSort" || target.id === "shopMobileSort") {
            syncSortControls(target.value || "latest");
            runRealtimeUpdate({ closeDrawer: false });
            return;
        }

        if (target.matches('input[name="category[]"], input[name="brand[]"]')) {
            runRealtimeUpdate({ closeDrawer: false });
        }
    });

    document.addEventListener("click", function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) return;

        var paginationLink = target.closest(".pagination a");
        if (paginationLink) {
            event.preventDefault();
            var href = paginationLink.getAttribute("href");
            if (!href) return;
            var urlObj = new URL(href, window.location.origin);
            fetchAndRender(urlObj.searchParams, { closeDrawer: false });
            return;
        }

        var resetLink = target.closest(".shop-btn-reset");
        if (resetLink) {
            event.preventDefault();

            form.reset();
            syncSortControls("latest");

            if (priceSlider && minInput && maxInput) {
                minInput.value = String(ABS_MIN);
                maxInput.value = String(ABS_MAX);
                updateSliderUI(ABS_MIN, ABS_MAX);
            }

            if (brandSearchInput) {
                brandSearchInput.value = "";
                brandSearchInput.dispatchEvent(new Event("input"));
            }

            fetchAndRender(new URLSearchParams(), { closeDrawer: true });
            return;
        }

        var cartBtn = target.closest(".na-btn--cart");
        if (!cartBtn || cartBtn.disabled) return;

        var productId = cartBtn.getAttribute("data-product-id");
        var unitPrice = parseFloat(cartBtn.getAttribute("data-unit-price") || 0);
        if (!productId) return;

        var originalText = cartBtn.textContent.trim();
        cartBtn.disabled = true;
        cartBtn.textContent = "Adding...";

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
                    cartBtn.textContent = "Added ✓";
                    cartBtn.classList.add("na-btn--added");

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

                cartBtn.disabled = false;
                cartBtn.textContent = originalText;
                showNotice(data.message || "Could not add to cart.");
            })
            .catch(function () {
                cartBtn.disabled = false;
                cartBtn.textContent = originalText;
                showNotice("Something went wrong. Please try again.");
            });
    });

    hydrateLazyImages();
});
