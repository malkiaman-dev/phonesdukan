var withBase = window.pdWithBase || function (path) {
    var localBasePath = String(window.__PD_BASE_PATH__ || "").replace(/\/+$/, "");
    if (!path) return localBasePath + "/";
    if (/^https?:\/\//i.test(path) || path.startsWith("//")) return path;
    if (path.startsWith(localBasePath + "/")) return path;
    if (path.startsWith("/")) return localBasePath + path;
    return localBasePath + "/" + path;
};

function resolveGalleryMediaUrl(path) {
    if (!path) return "";
    if (/^https?:\/\//i.test(path) || path.startsWith("//")) return path;
    return withBase(path);
}

function playGalleryVideo(videoEl) {
    if (!videoEl) return;
    const playPromise = videoEl.play();
    if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(function () {
            // Browser may block autoplay until user interacts; controls remain available.
        });
    }
}

function updateMainMedia(thumb) {
    const mainImage = document.getElementById("mainImage");
    const videoContainer = document.getElementById("mainVideoContainer");
    if (!mainImage || !videoContainer) return;

    const mediaType = thumb.getAttribute("data-media-type") || "image";

    document.querySelectorAll(".gallery-thumb").forEach(function (el) {
        el.classList.remove("active");
    });
    thumb.classList.add("active");

    if (mediaType === "video") {
        const source = thumb.getAttribute("data-video-source") || "upload";
        const embedUrl = thumb.getAttribute("data-embed-url") || "";
        const videoUrl = resolveGalleryMediaUrl(thumb.getAttribute("data-video-url") || "");
        const posterUrl = resolveGalleryMediaUrl(thumb.getAttribute("data-thumb-src") || "");
        let html = "";

        if (source === "youtube" || source === "tiktok" || source === "facebook") {
            let iframeUrl = embedUrl;
            if (source === "youtube") {
                iframeUrl = embedUrl + (embedUrl.indexOf("?") >= 0 ? "&" : "?") + "autoplay=1";
            }
            html = '<iframe src="' + iframeUrl + '" title="Product video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
        } else {
            html = '<video controls playsinline preload="auto"'
                + (posterUrl ? ' poster="' + posterUrl + '"' : "")
                + ' src="' + videoUrl + '"></video>';
        }

        videoContainer.innerHTML = html;
        videoContainer.style.display = "flex";
        videoContainer.classList.add("is-active");
        mainImage.style.display = "none";
        mainImage.classList.add("is-hidden");

        if (source !== "youtube" && source !== "tiktok" && source !== "facebook") {
            playGalleryVideo(videoContainer.querySelector("video"));
        }
        return;
    }

    mainImage.src = thumb.src;
    mainImage.alt = thumb.alt || "";
    mainImage.style.display = "";
    mainImage.classList.remove("is-hidden");
    videoContainer.innerHTML = "";
    videoContainer.style.display = "none";
    videoContainer.classList.remove("is-active");
}

function updateMainImage(thumbnail) {
    updateMainMedia(thumbnail);
}

function scrollThumbnails(direction) {
    let container = document.querySelector(".thumbnail-gallery");
    let scrollAmount = 100;
    if (direction === "left") {
        container.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    } else {
        container.scrollBy({ left: scrollAmount, behavior: "smooth" });
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const wrapper = document.querySelector(".image-wrapper");
    const image = document.getElementById("mainImage");
    let startX = 0;
    let isSwiping = false;
    let isZooming = false;

    wrapper.addEventListener("touchstart", function (e) {
        if (e.touches.length === 1 && !isZooming) {
            startX = e.touches[0].clientX;
            isSwiping = true;
        }
    });

    wrapper.addEventListener("touchmove", function (e) {
        if (isSwiping) {
            e.preventDefault();
        }
    }, { passive: false });

    wrapper.addEventListener("touchend", function (e) {
        if (!isSwiping) return;
        isSwiping = false;
        const endX = e.changedTouches[0].clientX;
        const diffX = endX - startX;
        if (Math.abs(diffX) > 50) {
            const thumbnails = Array.from(document.querySelectorAll(".gallery-thumb"));
            const activeIndex = thumbnails.findIndex(function (el) { return el.classList.contains("active"); });
            if (diffX < 0 && activeIndex < thumbnails.length - 1) {
                updateMainMedia(thumbnails[activeIndex + 1]);
            } else if (diffX > 0 && activeIndex > 0) {
                updateMainMedia(thumbnails[activeIndex - 1]);
            }
        }
    });

    let targetX = 50;
    let targetY = 50;
    let currentX = 50;
    let currentY = 50;
    let animationFrame;

    function isDesktop() {
        return window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    }

    if (isDesktop()) {
        wrapper.addEventListener("mouseenter", function () {
            if (!isSwiping) {
                image.classList.add("zoomed");
                animate();
            }
        });

        wrapper.addEventListener("mouseleave", function () {
            if (!isSwiping) {
                image.classList.remove("zoomed");
                cancelAnimationFrame(animationFrame);
                image.style.transformOrigin = "50% 50%";
            }
        });

        wrapper.addEventListener("mousemove", function (e) {
            if (isSwiping) return;
            const rect = wrapper.getBoundingClientRect();
            targetX = ((e.clientX - rect.left) / rect.width) * 100;
            targetY = ((e.clientY - rect.top) / rect.height) * 100;
        });

        function animate() {
            currentX += (targetX - currentX) * 0.1;
            currentY += (targetY - currentY) * 0.1;
            image.style.transformOrigin = `${currentX}% ${currentY}%`;
            animationFrame = requestAnimationFrame(animate);
        }
    }

    document.querySelectorAll('.bulk-cta-item.bulk-quantity').forEach(item => {
        item.addEventListener('click', function() {
            const quantity = parseInt(this.getAttribute('data-quantity'));
            if (quantity) {
                const desktopInput = document.getElementById('quantity-desktop');
                const mobileInput = document.getElementById('quantity-mobile');
                if (desktopInput) desktopInput.value = quantity;
                if (mobileInput) mobileInput.value = quantity;
                const addToCartButton = document.querySelector('.add-to-cart');
                if (addToCartButton) {
                    addToCartButton.click();  // This will now respect payment method via handleCartAction
                }
            }
        });
    });

    document.querySelectorAll('.bulk-cta-item.bulk-inquiry, .bulk-inquiry-trigger').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.pdOpenWholesaleAccess === 'function') {
                window.pdOpenWholesaleAccess();
            } else {
                window.location.href = withBase('/wholesale');
            }
        });
    });

    // WhatsApp Chat Button Handler
    document.querySelectorAll('.chat-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productName = this.getAttribute('data-product-name') || 'this product';
            const message = encodeURIComponent(`I want to know the retailer price per product of ${productName}.`);
            const whatsappUrl = `https://wa.me/+923116600031?text=${message}`;
            window.open(whatsappUrl, '_blank');
        });
    });

    window.scrollToReviews = function scrollToReviews() {
        const reviewTabTitle = document.querySelector('.custom-tab-title[data-tab="tab-reviews"]');
        const reviewTab = document.getElementById('tab-reviews');
        if (reviewTabTitle && reviewTab) {
            document.querySelectorAll('.custom-tab-title').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.custom-tab').forEach(c => { c.classList.remove('active'); c.style.display = 'none'; });
            reviewTabTitle.classList.add('active');
            reviewTab.classList.add('active');
            reviewTab.style.display = 'block';
        }
        const el = document.querySelector('.reviews-section-wrapper') || document.querySelector('.custom-tabs');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    };

    const addToCartButtons = {
        desktop: document.getElementById('add-to-cart-btn-desktop'),
        mobile: document.getElementById('add-to-cart-btn-mobile')
    };
    const buyNowButtons = {
        desktop: document.getElementById('buy-now-desktop'),
        mobile: document.getElementById('buy-now-mobile')
    };
    const quantityInputs = {
        desktop: document.getElementById('quantity-desktop'),
        mobile: document.getElementById('quantity-mobile')
    };
    const priceContent = document.querySelector('.single-product-price .price-content');

    // Payment method handling
    let currentPaymentMethod = 'cod';  // Default
    let originalRegularPrice = 0;  // Store original regular price
    let originalSalePrice = 0;  // Store original sale price (if any)
    let baseUnitPrice = 0;  // Track base price (before payment discount)

    // Helper: Format price with commas (PKR style, always 2 decimals)
    function formatPrice(price) {
        return price.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Helper: Parse price text from DOM (handle Rs., commas)
    function parsePriceText(selector) {
        const text = document.querySelector(selector)?.textContent || '';
        return parseFloat(text.replace(/Rs\.\s*/i, '').replace(/,/g, '')) || 0;
    }

    // Sync payment selection across desktop/mobile
    function updatePaymentSelection(method) {
        currentPaymentMethod = method;
        document.querySelectorAll('.payment-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll(`.payment-btn[data-method="${method}"]`).forEach(btn => {
            btn.classList.add('active');
        });
        // Update data-payment-method on buttons
        for (let key in addToCartButtons) {
            if (addToCartButtons[key]) {
                addToCartButtons[key].setAttribute('data-payment-method', method);
            }
        }
        applyPaymentDiscount();  // Re-apply discount on current base price
    }

    // Expose applyPaymentDiscount globally so the inline variation script can call it
    window.pdApplyPaymentDiscount = function() { applyPaymentDiscount(); };

    // Allow variation switcher (inline script) to keep base prices in sync
    window.pdSyncVariationPrice = function(reg, sale) {
        originalRegularPrice = Math.max(0, parseFloat(reg) || 0);
        originalSalePrice    = (sale > 0 && sale < originalRegularPrice) ? Math.max(0, parseFloat(sale)) : 0;
        baseUnitPrice        = originalSalePrice > 0 ? originalSalePrice : originalRegularPrice;
    };

    // Apply payment discount and update display/buttons
    function applyPaymentDiscount() {
        const prepaidDiscountPKR = parseFloat(document.querySelector('.payment-btn.prepaid')?.getAttribute('data-prepaid-discount') || 0);

        // Prefer variation-level prices set by pdMatch(); fall back to closure vars for simple products
        const activeRegular = (window.pdCurrentRegularPrice > 0) ? window.pdCurrentRegularPrice : originalRegularPrice;
        const activeSale    = (window.pdCurrentBasePrice > 0 && window.pdCurrentBasePrice < activeRegular)
            ? window.pdCurrentBasePrice
            : (originalSalePrice > 0 && originalSalePrice < activeRegular ? originalSalePrice : 0);
        const basePrice     = activeSale > 0 ? activeSale : activeRegular;

        const prepaidDeduction = (currentPaymentMethod === 'prepaid' && prepaidDiscountPKR > 0) ? prepaidDiscountPKR : 0;
        const displayPrice = Math.max(0, basePrice - prepaidDeduction);
        const formattedDisplay = formatPrice(displayPrice);

        // Update price display
        let priceHTML = `
            <div class="product-price">
                <span class="price-label">Phones Dukan Price</span>
                <span class="price-amount new-price">Rs. ${formattedDisplay}</span>
            </div>
        `;

        // Show discount block when a sale price exists OR when prepaid deduction applies
        let showDiscount = false;
        let oldPrice = 0;
        let totalDiscount = 0;
        if (activeRegular > basePrice) {
            // Existing sale discount — combine with any prepaid deduction
            showDiscount = true;
            oldPrice = activeRegular;
            totalDiscount = activeRegular > 0
                ? Math.min(100, Math.round((activeRegular - displayPrice) / activeRegular * 100))
                : 0;
        } else if (prepaidDeduction > 0 && basePrice > 0) {
            // Prepaid-only deduction (no existing sale)
            showDiscount = true;
            oldPrice = basePrice;
            totalDiscount = Math.min(100, Math.round((prepaidDeduction / basePrice) * 100));
        }

        if (showDiscount) {
            const formattedOld = formatPrice(oldPrice);
            priceHTML += `
                <div class="price-discount">
                    <span class="price-amount old-price line-through">Rs. ${formattedOld}</span>
                    <div class="discount-section">
                        <span class="discount-percentage">${totalDiscount}% OFF</span>
                    </div>
                </div>
            `;
        }

        priceContent.innerHTML = priceHTML;

        // NEW: Update bulk CTA prices based on current displayPrice
        const bulkItems = document.querySelectorAll('.bulk-cta-item.bulk-quantity');
        bulkItems.forEach(item => {
            const quantity = parseInt(item.getAttribute('data-quantity'));
            let bulkDiscountFactor = 1.0;
            if (quantity === 3) {
                bulkDiscountFactor = 0.95;  // 5% off
            } else if (quantity === 10) {
                bulkDiscountFactor = 0.93;  // 7% off
            }
            const bulkPrice = displayPrice * bulkDiscountFactor;
            const formattedBulk = formatPrice(bulkPrice);
            const priceElement = item.querySelector('.bulk-cta-price strong');
            if (priceElement) {
                priceElement.textContent = `Rs. ${formattedBulk}/Item`;
            }
        });

        // Update unit price on buttons (discounted)
        for (let key in addToCartButtons) {
            if (addToCartButtons[key]) {
                addToCartButtons[key].setAttribute('data-unit-price', displayPrice);
            }
        }

        disableButtons(displayPrice < 0);
    }

    // Event listeners for payment buttons (desktop and mobile)
    document.querySelectorAll('.payment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const method = this.getAttribute('data-method');
            updatePaymentSelection(method);
        });
    });

    const firstAttributeButton = document.querySelector('.attribute-button');
    if (firstAttributeButton) {
        firstAttributeButton.classList.add('active');
        originalRegularPrice = parseFloat(firstAttributeButton.getAttribute('data-regular-price')) || 0;
        originalSalePrice = parseFloat(firstAttributeButton.getAttribute('data-sale-price')) || 0;
        baseUnitPrice = originalSalePrice > 0 ? originalSalePrice : originalRegularPrice;
        updatePriceAndButton(firstAttributeButton);  // Existing attribute logic
    } else {
        // Default prices from PHP/DOM (non-attribute)
        const hasOriginalSale = !!document.querySelector('.old-price');
        if (hasOriginalSale) {
            originalRegularPrice = parsePriceText('.old-price');
            originalSalePrice = parsePriceText('.new-price');
        } else {
            originalRegularPrice = parsePriceText('.new-price');
            originalSalePrice = 0;
        }
        baseUnitPrice = originalSalePrice > 0 ? originalSalePrice : originalRegularPrice;
        if (baseUnitPrice <= 0) {
            disableButtons(true);
            priceContent.innerHTML = `<div class="product-price"><span class="price-label">Phones Dukan Price</span><span class="error-price">Price not available</span></div>`;
        } else {
            applyPaymentDiscount();  // Apply default COD (no discount)
        }
    }

    function disableButtons(disable) {
        for (let key in addToCartButtons) {
            if (addToCartButtons[key]) addToCartButtons[key].disabled = disable;
        }
        for (let key in buyNowButtons) {
            if (buyNowButtons[key]) buyNowButtons[key].disabled = disable;
        }
    }

    function updatePriceAndButton(button) {
        originalRegularPrice = parseFloat(button.getAttribute('data-regular-price')) || 0;
        originalSalePrice = parseFloat(button.getAttribute('data-sale-price')) || 0;
        baseUnitPrice = originalSalePrice > 0 ? originalSalePrice : originalRegularPrice;
        const attributeValue = button.getAttribute('data-attribute-value');
        applyPaymentDiscount();  // Now apply payment on top
        for (let key in addToCartButtons) {
            if (addToCartButtons[key]) {
                addToCartButtons[key].setAttribute('data-attribute-value', attributeValue);
            }
        }
    }

    document.querySelectorAll('.attribute-button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.attribute-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            updatePriceAndButton(this);
        });
    });

    function setupQuantityButtons(formType) {
        const quantityInput = quantityInputs[formType];
        if (!quantityInput) return;
        document.querySelectorAll(`.${formType}-cart-form .quantity-btn`).forEach(button => {
            button.addEventListener('click', function() {
                let quantity = parseInt(quantityInput.value) || 1;
                if (this.classList.contains('plus') && quantity < quantityInput.max) {
                    quantityInput.value = quantity + 1;
                    if (quantityInputs[formType === 'desktop' ? 'mobile' : 'desktop']) {
                        quantityInputs[formType === 'desktop' ? 'mobile' : 'desktop'].value = quantityInput.value;
                    }
                } else if (this.classList.contains('minus') && quantity > 1) {
                    quantityInput.value = quantity - 1;
                    if (quantityInputs[formType === 'desktop' ? 'mobile' : 'desktop']) {
                        quantityInputs[formType === 'desktop' ? 'mobile' : 'desktop'].value = quantityInput.value;
                    }
                }
            });
        });
    }

    setupQuantityButtons('desktop');
    setupQuantityButtons('mobile');

    function handleCartAction(redirectToCheckout = false, buttonType = 'desktop') {
        const addToCartButton = addToCartButtons[buttonType];
        const quantityInput = quantityInputs[buttonType];
        if (!addToCartButton || !quantityInput) return;
        const productId = addToCartButton.getAttribute('data-product-id');
        const unitPrice = parseFloat(addToCartButton.getAttribute('data-unit-price') || 0);
        const attributeValue = addToCartButton.getAttribute('data-attribute-value') || null;
        const paymentMethod = addToCartButton.getAttribute('data-payment-method') || 'cod';
        const variationId = addToCartButton.getAttribute('data-variation-id') || null;
        const variationIdHidden = document.getElementById('selectedVariationId');
        const resolvedVariationId = variationId || (variationIdHidden ? variationIdHidden.value : null) || null;
        const quantity = parseInt(quantityInput.value) || 1;
        if (!productId || quantity < 1) {
            Swal.fire({
                title: "Invalid Input!",
                text: "Please select a valid product and quantity.",
                icon: "warning",
                confirmButtonText: "OK"
            });
            return;
        }
        if (unitPrice <= 0) {
            Swal.fire({
                title: "Error!",
                text: "Invalid price. Please select a valid product attribute.",
                icon: "error",
                confirmButtonText: "OK"
            });
            return;
        }

        const groupExtras = Array.from(document.querySelectorAll('.pd-group-item__check:checked')).map(function (el) {
            return {
                product_id: parseInt(el.getAttribute('data-product-id'), 10),
                unit_price: parseFloat(el.getAttribute('data-unit-price') || 0),
                variation_id: el.getAttribute('data-variation-id') || null,
                quantity: 1,
                payment_method: paymentMethod
            };
        }).filter(function (item) {
            return item.product_id > 0 && item.unit_price > 0;
        });

        function postCartItem(payload) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: withBase("/app/Controllers/CartController.php"),
                    type: "POST",
                    data: JSON.stringify(payload),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (response) {
                        if (response && response.status === "success") {
                            resolve(response);
                        } else {
                            reject(new Error((response && response.message) || "Failed to add item"));
                        }
                    },
                    error: function () {
                        reject(new Error("Something went wrong, please try again."));
                    }
                });
            });
        }

        const mainPayload = {
            product_id: productId,
            quantity: quantity,
            attribute_value: attributeValue,
            unit_price: unitPrice,
            payment_method: paymentMethod,
            variation_id: resolvedVariationId ? parseInt(resolvedVariationId) : null,
        };

        postCartItem(mainPayload)
            .then(function (mainResponse) {
                let chain = Promise.resolve(mainResponse);
                groupExtras.forEach(function (extra) {
                    chain = chain.then(function (last) {
                        return postCartItem({
                            product_id: extra.product_id,
                            quantity: extra.quantity,
                            unit_price: extra.unit_price,
                            payment_method: extra.payment_method,
                            variation_id: extra.variation_id ? parseInt(extra.variation_id, 10) : null,
                        }).catch(function () {
                            // Keep main product in cart even if an accessory fails
                            return last;
                        });
                    });
                });
                return chain;
            })
            .then(function (response) {
                const cartCountElements = document.querySelectorAll('.cart-count');
                const totalQuantity = (response.cart_summary && response.cart_summary.total_quantity) || 0;
                cartCountElements.forEach(function (element) {
                    element.textContent = totalQuantity;
                });
                if (redirectToCheckout) {
                    window.location.href = withBase("/checkout");
                } else {
                    const extrasCount = groupExtras.length;
                    Swal.fire({
                        title: "Added to Cart!",
                        text: extrasCount > 0
                            ? ("Product and " + extrasCount + " accessory item(s) added. What would you like to do next?")
                            : "Your product has been added. What would you like to do next?",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: "View Cart",
                        cancelButtonText: "Continue Shopping",
                        reverseButtons: true,
                        customClass: {
                            popup: "pd-cart-popup",
                            icon: "pd-cart-popup__icon",
                            title: "pd-cart-popup__title",
                            htmlContainer: "pd-cart-popup__text",
                            confirmButton: "pd-cart-popup__btn pd-cart-popup__btn--primary",
                            cancelButton: "pd-cart-popup__btn pd-cart-popup__btn--secondary",
                            actions: "pd-cart-popup__actions"
                        },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = withBase("/cart");
                        }
                    });
                }
            })
            .catch(function (err) {
                Swal.fire({
                    title: "Oops!",
                    text: err && err.message ? err.message : "Something went wrong, please try again.",
                    icon: "error",
                    confirmButtonText: "Try Again"
                });
            });
    }

    for (let key in addToCartButtons) {
        if (addToCartButtons[key]) {
            addToCartButtons[key].addEventListener('click', () => handleCartAction(false, key));
        }
    }
    for (let key in buyNowButtons) {
        if (buyNowButtons[key]) {
            buyNowButtons[key].addEventListener('click', () => handleCartAction(true, key));
        }
    }

    const tabs = document.querySelectorAll('.custom-tab-title');
    const contents = document.querySelectorAll('.custom-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            contents.forEach(c => c.style.display = 'none');
            tab.classList.add('active');
            const contentId = tab.getAttribute('data-tab');
            const content = document.getElementById(contentId);
            content.classList.add('active');
            content.style.display = 'block';
        });
    });

    const stars = document.querySelectorAll(".star-rating input[type='radio']");
    stars.forEach(star => {
        star.addEventListener("change", function () {
            // Radios alone are enough; keep for compatibility if hidden exists.
            const hiddenRating = document.getElementById("hidden-rating");
            if (hiddenRating) hiddenRating.value = this.value;
        });
    });

    const viewersCount = document.querySelector('.viewers-count');
    if (viewersCount) {
        setInterval(() => {
            const newCount = Math.floor(Math.random() * (100 - 10 + 1)) + 10;
            viewersCount.textContent = newCount;
        }, 2000);
    }

    // ---- Related Products: Add to Cart ----
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.rp-cart-btn');
        if (!btn || btn.disabled) return;

        const productId = btn.getAttribute('data-product-id');
        const unitPrice = parseFloat(btn.getAttribute('data-unit-price')) || 0;
        if (!productId || unitPrice <= 0) return;

        btn.disabled = true;
        const origText = btn.textContent.trim();
        btn.textContent = '…';

        $.ajax({
            url: withBase('/app/Controllers/CartController.php'),
            type: 'POST',
            data: JSON.stringify({ product_id: parseInt(productId), quantity: 1, unit_price: unitPrice, payment_method: 'cod' }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const total = response.cart_summary ? (response.cart_summary.total_quantity || 0) : 0;
                    document.querySelectorAll('.cart-count').forEach(el => el.textContent = total);
                    btn.textContent = '✓ Added';
                    btn.classList.add('na-btn--added');
                } else {
                    btn.disabled = false;
                    btn.textContent = origText;
                    Swal.fire({ title: 'Oops!', text: response.message, icon: 'error', confirmButtonText: 'Try Again' });
                }
            },
            error: function() {
                btn.disabled = false;
                btn.textContent = origText;
                Swal.fire({ title: 'Error!', text: 'Something went wrong.', icon: 'error', confirmButtonText: 'OK' });
            }
        });
    });

    // ---- Related Products: Buy Now ----
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.rp-buy-btn');
        if (!btn || btn.disabled) return;

        const productId = btn.getAttribute('data-product-id');
        const unitPrice = parseFloat(btn.getAttribute('data-unit-price')) || 0;
        if (!productId || unitPrice <= 0) return;

        btn.disabled = true;
        btn.textContent = '…';

        $.ajax({
            url: withBase('/app/Controllers/CartController.php'),
            type: 'POST',
            data: JSON.stringify({ product_id: parseInt(productId), quantity: 1, unit_price: unitPrice, payment_method: 'cod' }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = withBase('/checkout');
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Buy Now';
                    Swal.fire({ title: 'Oops!', text: response.message, icon: 'error', confirmButtonText: 'Try Again' });
                }
            },
            error: function() {
                btn.disabled = false;
                btn.textContent = 'Buy Now';
                Swal.fire({ title: 'Error!', text: 'Something went wrong.', icon: 'error', confirmButtonText: 'OK' });
            }
        });
    });
});