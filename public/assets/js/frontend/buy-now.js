/**
 * buy-now.js — handles .buy-button clicks across all homepage carousels.
 *
 * Key design choices:
 *  - Pure fetch/vanilla JS (no jQuery required)
 *  - Event delegation on document so carousel-cloned cards work automatically
 *  - Reads data-unit-price so CartController receives a valid price
 *  - Redirects to /checkout on success
 */
(function () {
    'use strict';

    const withBase = window.pdWithBase || function (path) {
        var basePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    function normalizeBestMobilesLinks() {
        if (window.location.pathname.indexOf('/mobiles-price-list') === -1) return;

        document.querySelectorAll('a[href]').forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href) return;

            var lowerHref = href.toLowerCase();
            if (
                href.startsWith('#') ||
                lowerHref.startsWith('javascript:') ||
                lowerHref.startsWith('mailto:') ||
                lowerHref.startsWith('tel:')
            ) {
                return;
            }

            if (/^https?:\/\//i.test(href) || href.startsWith('//')) return;

            if (href.startsWith('/mobiles-price-list/') || href.startsWith('/mobiles/')) {
                link.setAttribute('href', withBase(href));
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeBestMobilesLinks);
    } else {
        normalizeBestMobilesLinks();
    }

    /* Single delegated listener — catches clicks on original AND cloned cards */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.buy-button');
        if (!btn) return;

        /* Ignore if already processing */
        if (btn.disabled) return;

        var productId = btn.dataset.productId;
        var unitPrice = parseFloat(btn.dataset.unitPrice) || 0;

        if (!productId) return;

        /* Visual feedback */
        btn.disabled = true;
        var origText = btn.textContent.trim();
        btn.textContent = 'Processing…';

        fetch(withBase('/app/Controllers/CartController.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id:     parseInt(productId, 10),
                quantity:       1,
                unit_price:     unitPrice,
                payment_method: 'cod'
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.status === 'success') {
                /* Sync cart badge before leaving the page */
                var total = (data.cart_summary && data.cart_summary.total_quantity)
                    ? data.cart_summary.total_quantity : 0;
                if (total > 0) {
                    document.querySelectorAll('.cart-count').forEach(function (el) {
                        el.textContent = total;
                    });
                }
                window.location.href = withBase('/checkout');
            } else {
                btn.disabled = false;
                btn.textContent = origText;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Oops!',
                        text: data.message || 'Could not add to cart.',
                        icon: 'error',
                        confirmButtonText: 'Try Again'
                    });
                } else {
                    alert(data.message || 'Could not add to cart.');
                }
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = origText;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Something went wrong. Please try again.');
            }
        });
    });
})();
