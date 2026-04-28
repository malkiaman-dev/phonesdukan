document.addEventListener("DOMContentLoaded", function () {
    const withBase = window.pdWithBase || function (path) {
        const basePath = (window.location.pathname.split('/').filter(Boolean)[0] === 'phonesdukan') ? '/phonesdukan' : '';
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    let isInitialized = false;
    if (isInitialized) return;
    isInitialized = true;

    const cartContainer = document.querySelector('.cart-container');
    if (!cartContainer) {
        console.error("Cart container not found");
        return;
    }

    cartContainer.addEventListener("click", function (e) {
        const button = e.target;
        const isPlus = button.classList.contains('plus');
        const isMinus = button.classList.contains('minus');

        if (!isPlus && !isMinus) return;

        e.preventDefault();
        const productId = button.getAttribute("data-id");
        const quantityInput = document.querySelector(`#quantity_${productId}`);
        
        if (!quantityInput) {
            console.error("Quantity input not found for product ID:", productId);
            return;
        }

        let currentQuantity = parseInt(quantityInput.value, 10) || 1;
        let newQuantity = currentQuantity;
        if (isPlus) {
            newQuantity = currentQuantity + 1;
        } else if (isMinus && currentQuantity > 1) {
            newQuantity = currentQuantity - 1;
        } else if (isMinus) {
            Swal.fire("Error!", "Quantity can't be less than 1.", "warning");
            return;
        }

        updateCart(productId, newQuantity, button);
    });

    function updateCart(productId, newQuantity, clickedButton) {
        if (clickedButton) clickedButton.disabled = true;

        let unitPriceElement = document.querySelector(`#unit-price_${productId}`);
        if (!unitPriceElement) {
            console.error(`Unit price element not found for product ID: ${productId}`);
            if (clickedButton) clickedButton.disabled = false;
            return;
        }
        let unitPrice = parseFloat(unitPriceElement.textContent);

        let attributeElement = document.querySelector(`#attribute_${productId}`);
        let attributeValue = attributeElement && attributeElement.textContent.trim() !== "No attribute available" ? attributeElement.textContent : null;

        if (isNaN(unitPrice) || unitPrice <= 0) {
            console.error(`Invalid unit price for product ID: ${productId}`);
            Swal.fire("Error!", "Invalid unit price. Please refresh the page.", "error");
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === "success") {
                let updated = data.updated_item;
                let quantityInput = document.querySelector(`#quantity_${productId}`);
                let tr = quantityInput.closest('tr');

                quantityInput.value = newQuantity;

                let subtotalTd = tr.querySelector('td:nth-child(4)');
                if (subtotalTd) subtotalTd.textContent = `PKR ${Number(updated.subtotal).toFixed(2)}`;

                let discountTd = tr.querySelector('.discount-cell');
                let attrP = tr.querySelector('.attribute');
                if (attrP) attrP.textContent = updated.attribute_value || 'No attribute available';
                if (data.cart_summary) {
                    document.querySelector('#total-price').textContent = `PKR ${data.cart_summary.total}`;
                    let discountText = data.cart_summary.discount_rate === 7 ? '7% OFF' : data.cart_summary.discount_rate === 5 ? '5% OFF' : '0% OFF';
                    
                    document.querySelectorAll('.discount-cell').forEach(cell => {
                        // Create a <p> tag to wrap the discount text
                        let pTag = document.createElement('p');
                        pTag.textContent = discountText;
                        
                        // Clear the existing content and append the new <p> tag
                        cell.innerHTML = '';
                        cell.appendChild(pTag);
                    });
                
                }

                Swal.fire({
                    title: "Updated!",
                    text: "The cart has been updated successfully.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire("Error!", data.message, "error");
            }
            if (clickedButton) clickedButton.disabled = false;
        })
        .catch(error => {
            console.error("UpdateCart AJAX Error:", error.message);
            Swal.fire("Oops!", "Something went wrong. Please try again.", "error");
            if (clickedButton) clickedButton.disabled = false;
        });
    }

    cartContainer.addEventListener("click", function (e) {
        if (!e.target.classList.contains('remove-item')) return;

        let productId = e.target.getAttribute("data-id");
        let removeButton = e.target;

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to undo this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, remove it!"
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(withBase("/app/Controllers/CartController.php?action=removeCartItem"), {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("RemoveCartItem Response:", data); // Debug log
                    if (data.status === "success") {
                        removeButton.closest('tr').remove();

                        if (data.cart_summary) {
                            let discountText = data.cart_summary.discount_rate === 7 ? '7%' : data.cart_summary.discount_rate === 5 ? '5%' : '0%';
                            document.querySelectorAll('.discount-cell').forEach(cell => {
                                cell.textContent = discountText;
                            });

                            if (document.querySelectorAll('tbody tr').length === 0) {
                                // Hide table and cart-total, show empty message
                                const table = document.querySelector('.cart-container table');
                                const cartTotal = document.querySelector('.cart-total');
                                const checkoutBtn = document.querySelector('.checkout-btn');
                                const emptyMessage = document.querySelector('.empty-cart-message') || document.createElement('p');

                                if (table) table.style.display = 'none';
                                if (cartTotal) cartTotal.style.display = 'none';
                                if (checkoutBtn) checkoutBtn.style.display = 'none';

                                emptyMessage.className = 'empty-cart-message';
                                emptyMessage.textContent = 'Your cart is empty.';
                                emptyMessage.style.display = 'block';
                                if (!document.querySelector('.empty-cart-message')) {
                                    cartContainer.appendChild(emptyMessage);
                                }
                            } else {
                                // Update total for non-empty cart
                                document.querySelector('#total-price').textContent = `PKR ${data.cart_summary.total}`;
                            }
                        }

                        Swal.fire({
                            title: "Removed!",
                            text: "The item has been removed.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire("Error!", data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("RemoveCartItem AJAX Error:", error.message);
                    Swal.fire("Oops!", "Something went wrong. Please try again.", "error");
                });
            }
        });
    });
});