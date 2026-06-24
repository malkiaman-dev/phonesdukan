var withBase = window.pdWithBase || function (path) {
    var basePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
    if (!path) return basePath + '/';
    if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
    if (path.startsWith(basePath + '/')) return path;
    if (path.startsWith('/')) return basePath + path;
    return basePath + '/' + path;
};

function showCartToast(msg, type) {
    type = type || 'success';
    var prev = document.getElementById('cart-toast');
    if (prev) prev.remove();

    var toast = document.createElement('div');
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

function updateCartBadge(cartItems) {
    if (!Array.isArray(cartItems)) return;
    var total = cartItems.reduce(function (sum, item) {
        return sum + (parseInt(item.total_quantity, 10) || 0);
    }, 0);
    document.querySelectorAll('.cart-count').forEach(function (el) {
        el.textContent = total;
    });
}

function updateSummaryCard(summary) {
    if (!summary) return;

    var totalEl = document.getElementById('total-price');
    if (totalEl) totalEl.textContent = 'PKR ' + summary.total;

    var subtotalEl = document.getElementById('summary-subtotal-val');
    if (subtotalEl) subtotalEl.textContent = 'PKR ' + summary.subtotal;

    var discountLabel = document.getElementById('summary-discount-label');
    var discountVal = document.getElementById('summary-discount-val');

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

    var discountText = summary.discount_rate === 7 ? '7% OFF'
        : summary.discount_rate === 5 ? '5% OFF' : '0% OFF';
    document.querySelectorAll('.discount-cell').forEach(function (cell) {
        var p = cell.querySelector('p');
        if (!p) {
            p = document.createElement('p');
            cell.innerHTML = '';
            cell.appendChild(p);
        }
        p.textContent = discountText;
    });
}

function updateCartQuantity(productId, newQuantity, clickedButton) {
    if (clickedButton) clickedButton.disabled = true;

    var unitPriceEl = document.querySelector('#unit-price_' + productId);
    if (!unitPriceEl) {
        if (clickedButton) clickedButton.disabled = false;
        return;
    }
    var unitPrice = parseFloat(unitPriceEl.textContent);

    var attrEl = document.querySelector('#attribute_' + productId);
    var attributeValue = (attrEl && attrEl.textContent.trim() !== "No attribute available")
        ? attrEl.textContent.trim()
        : null;

    if (isNaN(unitPrice) || unitPrice <= 0) {
        showCartToast("Invalid unit price. Please refresh the page.", 'error');
        if (clickedButton) clickedButton.disabled = false;
        return;
    }

    fetch(withBase("/app/Controllers/CartController.php?action=updateCartQuantity"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            product_id: productId,
            quantity: newQuantity,
            unit_price: unitPrice,
            attribute_value: attributeValue
        })
    })
    .then(function (response) {
        if (!response.ok) throw new Error("HTTP error! Status: " + response.status);
        return response.json();
    })
    .then(function (data) {
        if (data.status === "success") {
            var updated = data.updated_item;
            var qInput = document.querySelector('#quantity_' + productId);
            var row = qInput ? qInput.closest('tr') : null;

            if (qInput) qInput.value = newQuantity;

            if (row) {
                var subtotalTd = row.querySelector('td:nth-child(4)');
                if (subtotalTd) subtotalTd.textContent = 'PKR ' + Number(updated.subtotal).toFixed(2);

                var attrP = row.querySelector('.attribute');
                if (attrP && updated.attribute_value) attrP.textContent = updated.attribute_value;
            }

            updateSummaryCard(data.cart_summary);
            updateCartBadge(data.cart_items);
        } else {
            showCartToast(data.message || "Failed to update cart.", 'error');
        }
    })
    .catch(function (error) {
        console.error("UpdateCart AJAX Error:", error.message);
        showCartToast("Something went wrong. Please try again.", 'error');
    })
    .finally(function () {
        if (clickedButton) clickedButton.disabled = false;
    });
}

function removeCartItem(productId, removeButton) {
    var row = removeButton.closest('tr');
    var cartContainer = document.querySelector('.cart-container');

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
            if (row) row.remove();

            if (document.querySelectorAll('.cart-container tbody tr').length === 0) {
                var table = document.querySelector('.cart-container table');
                var cartTotal = document.querySelector('.cart-total');
                var checkoutBtn = document.querySelector('.checkout-btn');
                var cartLayout = document.querySelector('.cart-layout');

                if (table) table.style.display = 'none';
                if (cartTotal) cartTotal.style.display = 'none';
                if (checkoutBtn) checkoutBtn.style.display = 'none';
                if (cartLayout) cartLayout.style.display = 'none';

                var emptyMsg = document.querySelector('.empty-cart-message');
                if (!emptyMsg && cartContainer) {
                    emptyMsg = document.createElement('p');
                    emptyMsg.className = 'empty-cart-message';
                    cartContainer.appendChild(emptyMsg);
                }
                if (emptyMsg) {
                    emptyMsg.textContent = 'Your cart is empty.';
                    emptyMsg.style.display = 'block';
                }

                updateCartBadge(data.cart_items);
                if (window.PDAppNav && typeof window.PDAppNav.invalidate === "function") {
                    window.PDAppNav.invalidate("/cart");
                }
            } else {
                updateSummaryCard(data.cart_summary);
                updateCartBadge(data.cart_items);
            }

            showCartToast("Item removed from cart.");
        } else {
            if (row) row.style.opacity = '1';
            removeButton.disabled = false;
            showCartToast(data.message || "Failed to remove item.", 'error');
        }
    })
    .catch(function (error) {
        console.error("RemoveCartItem AJAX Error:", error.message);
        if (row) row.style.opacity = '1';
        removeButton.disabled = false;
        showCartToast("Something went wrong. Please try again.", 'error');
    });
}

function handleCartClick(event) {
    var cartContainer = document.querySelector('.cart-container');
    if (!cartContainer || !cartContainer.contains(event.target)) {
        return;
    }

    var qtyButton = event.target.closest('.plus, .minus');
    if (qtyButton) {
        event.preventDefault();

        var isPlus = qtyButton.classList.contains('plus');
        var productId = qtyButton.getAttribute("data-id");
        var quantityInput = document.querySelector('#quantity_' + productId);

        if (!quantityInput) {
            return;
        }

        var currentQty = parseInt(quantityInput.value, 10) || 1;
        if (!isPlus && currentQty <= 1) {
            showCartToast("Quantity can't be less than 1.", 'warning');
            return;
        }

        updateCartQuantity(productId, isPlus ? currentQty + 1 : currentQty - 1, qtyButton);
        return;
    }

    var removeButton = event.target.closest('.remove-item');
    if (removeButton) {
        event.preventDefault();
        var removeId = removeButton.getAttribute("data-id");
        if (removeId) {
            removeCartItem(removeId, removeButton);
        }
    }
}

if (!window.__pdCartDelegated) {
    window.__pdCartDelegated = true;
    document.addEventListener("click", handleCartClick, true);
}
