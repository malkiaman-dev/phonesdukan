document.addEventListener("DOMContentLoaded", function () {
    const withBase = window.pdWithBase || function (path) {
        const basePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    if (window.__cartInitialized) return;
    window.__cartInitialized = true;

    const cartContainer = document.querySelector('.cart-container');
    if (!cartContainer) {
        console.error("Cart container not found");
        return;
    }

    // ----------------------------------------------------------------
    // Toast — lightweight non-blocking notification
    // ----------------------------------------------------------------
    function showToast(msg, type) {
        type = type || 'success';
        const prev = document.getElementById('cart-toast');
        if (prev) prev.remove();

        const toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.className = 'cart-toast cart-toast--' + type;
        toast.textContent = msg;
        document.body.appendChild(toast);

        requestAnimationFrame(function () { toast.classList.add('cart-toast--show'); });
        setTimeout(function () {
            toast.classList.remove('cart-toast--show');
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 320);
        }, 2800);
    }

    // ----------------------------------------------------------------
    // Sync header cart-count badge from live cart_items array
    // Replaces the missing syncCartBadge() that was causing the false
    // "Something went wrong" error on every successful quantity update.
    // ----------------------------------------------------------------
    function updateCartBadge(cartItems) {
        if (!Array.isArray(cartItems)) return;
        const total = cartItems.reduce(function (sum, item) {
            return sum + (parseInt(item.total_quantity, 10) || 0);
        }, 0);
        document.querySelectorAll('.cart-count').forEach(function (el) {
            el.textContent = total;
        });
    }

    // ----------------------------------------------------------------
    // Update the right-side Order Summary card + all discount badges
    // ----------------------------------------------------------------
    function updateSummaryCard(summary) {
        if (!summary) return;

        // Grand total (already targeted by original code)
        const totalEl = document.getElementById('total-price');
        if (totalEl) totalEl.textContent = 'PKR ' + summary.total;

        // Subtotal row
        const subtotalEl = document.getElementById('summary-subtotal-val');
        if (subtotalEl) subtotalEl.textContent = 'PKR ' + summary.subtotal;

        // Discount row — label + value
        const discountLabel = document.getElementById('summary-discount-label');
        const discountVal   = document.getElementById('summary-discount-val');

        if (discountLabel) {
            discountLabel.textContent = summary.discount_rate > 0
                ? 'Discount (' + summary.discount_rate + '%)'
                : 'Discount';
        }
        if (discountVal) {
            if (summary.discount_rate > 0) {
                discountVal.textContent = '− PKR ' + summary.discount_amount;
                discountVal.classList.add('summary-val--discount');
            } else {
                discountVal.textContent = 'PKR 0.00';
                discountVal.classList.remove('summary-val--discount');
            }
        }

        // Per-row discount badges in the table
        const discountText = summary.discount_rate === 7 ? '7% OFF'
                           : summary.discount_rate === 5 ? '5% OFF' : '0% OFF';
        document.querySelectorAll('.discount-cell').forEach(function (cell) {
            let p = cell.querySelector('p');
            if (!p) {
                p = document.createElement('p');
                cell.innerHTML = '';
                cell.appendChild(p);
            }
            p.textContent = discountText;
        });
    }

    // ================================================================
    // QUANTITY  +  /  −
    // ================================================================
    cartContainer.addEventListener("click", function (e) {
        // closest() handles clicks on child SVG/text nodes inside the button
        const button = e.target.closest('.plus, .minus');
        if (!button) return;

        e.preventDefault();

        const isPlus    = button.classList.contains('plus');
        const productId = button.getAttribute("data-id");
        const quantityInput = document.querySelector('#quantity_' + productId);

        if (!quantityInput) {
            console.error("Quantity input not found for product ID:", productId);
            return;
        }

        const currentQty = parseInt(quantityInput.value, 10) || 1;

        if (!isPlus && currentQty <= 1) {
            showToast("Quantity can't be less than 1.", 'warning');
            return;
        }

        const newQty = isPlus ? currentQty + 1 : currentQty - 1;
        updateCart(productId, newQty, button);
    });

    function updateCart(productId, newQuantity, clickedButton) {
        if (clickedButton) clickedButton.disabled = true;

        const unitPriceEl = document.querySelector('#unit-price_' + productId);
        if (!unitPriceEl) {
            if (clickedButton) clickedButton.disabled = false;
            return;
        }
        const unitPrice = parseFloat(unitPriceEl.textContent);

        const attrEl = document.querySelector('#attribute_' + productId);
        const attributeValue = (attrEl && attrEl.textContent.trim() !== "No attribute available")
            ? attrEl.textContent.trim()
            : null;

        if (isNaN(unitPrice) || unitPrice <= 0) {
            showToast("Invalid unit price. Please refresh the page.", 'error');
            if (clickedButton) clickedButton.disabled = false;
            return;
        }

        fetch(withBase("/app/Controllers/CartController.php?action=updateCartQuantity"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                product_id:      productId,
                quantity:        newQuantity,
                unit_price:      unitPrice,
                attribute_value: attributeValue
            })
        })
        .then(function (response) {
            if (!response.ok) throw new Error("HTTP error! Status: " + response.status);
            return response.json();
        })
        .then(function (data) {
            if (data.status === "success") {
                const updated      = data.updated_item;
                const qInput       = document.querySelector('#quantity_' + productId);
                const tr           = qInput.closest('tr');

                // Update quantity display
                qInput.value = newQuantity;

                // Update per-row subtotal — td:nth-child(4) as expected by this handler
                const subtotalTd = tr.querySelector('td:nth-child(4)');
                if (subtotalTd) subtotalTd.textContent = 'PKR ' + Number(updated.subtotal).toFixed(2);

                // Update attribute text if returned
                const attrP = tr.querySelector('.attribute');
                if (attrP && updated.attribute_value) attrP.textContent = updated.attribute_value;

                // Silently update summary card, discount badges, header badge — no popup
                updateSummaryCard(data.cart_summary);
                updateCartBadge(data.cart_items);
            } else {
                showToast(data.message || "Failed to update cart.", 'error');
            }
        })
        .catch(function (error) {
            console.error("UpdateCart AJAX Error:", error.message);
            showToast("Something went wrong. Please try again.", 'error');
        })
        .finally(function () {
            if (clickedButton) clickedButton.disabled = false;
        });
    }

    // ================================================================
    // REMOVE ITEM
    // ================================================================
    cartContainer.addEventListener("click", function (e) {
        // closest() so clicking the SVG icon inside the button is caught
        const removeButton = e.target.closest('.remove-item');
        if (!removeButton) return;

        const productId = removeButton.getAttribute("data-id");

        // Visual feedback: fade the row while request is in-flight
        const row = removeButton.closest('tr');
        if (row) row.style.opacity = '0.4';
        removeButton.disabled = true;

        fetch(withBase("/app/Controllers/CartController.php?action=removeCartItem"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ product_id: productId })
        })
        .then(function (response) {
            if (!response.ok) throw new Error("HTTP error! Status: " + response.status);
            return response.json();
        })
        .then(function (data) {
            if (data.status === "success") {
                // Remove card row from DOM immediately
                if (row) row.remove();

                if (document.querySelectorAll('tbody tr').length === 0) {
                    // Cart is now empty — hide layout, show empty state
                    const table       = document.querySelector('.cart-container table');
                    const cartTotal   = document.querySelector('.cart-total');
                    const checkoutBtn = document.querySelector('.checkout-btn');
                    const cartLayout  = document.querySelector('.cart-layout');

                    if (table)       table.style.display       = 'none';
                    if (cartTotal)   cartTotal.style.display    = 'none';
                    if (checkoutBtn) checkoutBtn.style.display  = 'none';
                    if (cartLayout)  cartLayout.style.display   = 'none';

                    let emptyMsg = document.querySelector('.empty-cart-message');
                    if (!emptyMsg) {
                        emptyMsg = document.createElement('p');
                        emptyMsg.className = 'empty-cart-message';
                        cartContainer.appendChild(emptyMsg);
                    }
                    emptyMsg.textContent = 'Your cart is empty.';
                    emptyMsg.style.display = 'block';

                    // Badge must update to 0 even when no rows remain
                    updateCartBadge(data.cart_items);
                } else {
                    // Update totals for remaining items
                    updateSummaryCard(data.cart_summary);
                    updateCartBadge(data.cart_items);
                }

                showToast("Item removed from cart.");
            } else {
                // Restore row opacity on failure
                if (row) row.style.opacity = '1';
                removeButton.disabled = false;
                showToast(data.message || "Failed to remove item.", 'error');
            }
        })
        .catch(function (error) {
            console.error("RemoveCartItem AJAX Error:", error.message);
            if (row) row.style.opacity = '1';
            removeButton.disabled = false;
            showToast("Something went wrong. Please try again.", 'error');
        });
    });
});
