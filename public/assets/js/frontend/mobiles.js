(function () {
    'use strict';

    var withBase = window.pdWithBase || function (path) {
        var base = (window.location.pathname.split('/').filter(Boolean)[0] === 'phonesdukan') ? '/phonesdukan' : '';
        if (!path) return base + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        return base + (path.startsWith('/') ? path : '/' + path);
    };

    /* ── Brand filter tabs ─────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var tabs       = document.querySelectorAll('.mob-brand-tab');
        var cards      = document.querySelectorAll('.mob-na-card');
        var brandLinks = document.querySelectorAll('[data-brand-link]');
        var viewAllEl  = document.getElementById('mobViewAllLink');

        if (!tabs.length) return;

        function filterByBrand(brand) {
            /* Update tab states */
            tabs.forEach(function (t) {
                var active = t.getAttribute('data-brand') === brand;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            /* Show / hide product cards */
            cards.forEach(function (card) {
                var show = brand === 'all' || card.getAttribute('data-brand') === brand;
                card.hidden = !show;
                card.style.display = show ? '' : 'none';
            });

            /* Update view-all link */
            brandLinks.forEach(function (link) {
                link.hidden = link.getAttribute('data-brand-link') !== brand;
            });
            if (viewAllEl) viewAllEl.hidden = brand !== 'all';
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                filterByBrand(tab.getAttribute('data-brand') || 'all');
            });
        });

        /* Initialise: show "All" */
        filterByBrand('all');
    });

    /* ── Add to Cart ───────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        var cartBtn = e.target.closest('.na-btn--cart');
        if (!cartBtn || cartBtn.disabled) return;

        var productId = cartBtn.dataset.productId;
        var unitPrice = parseFloat(cartBtn.dataset.unitPrice) || 0;
        if (!productId) return;

        var origText = cartBtn.textContent.trim();
        cartBtn.disabled = true;
        cartBtn.textContent = 'Adding…';

        fetch(withBase('/app/Controllers/CartController.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: parseInt(productId, 10),
                quantity:   1,
                unit_price: unitPrice,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    cartBtn.textContent = 'Added ✓';
                    cartBtn.classList.add('na-btn--added');

                    var qty = 0;
                    if (data.cart_summary && typeof data.cart_summary.total_quantity !== 'undefined') {
                        qty = parseInt(data.cart_summary.total_quantity, 10) || 0;
                    } else if (Array.isArray(data.cart_items)) {
                        qty = data.cart_items.reduce(function (s, i) {
                            return s + (parseInt(i.total_quantity, 10) || 0);
                        }, 0);
                    }
                    document.querySelectorAll('.cart-count').forEach(function (el) {
                        el.textContent = qty;
                    });
                    return;
                }
                cartBtn.disabled = false;
                cartBtn.textContent = origText;
            })
            .catch(function () {
                cartBtn.disabled = false;
                cartBtn.textContent = origText;
            });
    });

    /* ── Buy Now ───────────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.buy-button');
        if (!btn || btn.disabled) return;

        var productId = btn.dataset.productId;
        var unitPrice = parseFloat(btn.dataset.unitPrice) || 0;
        if (!productId) return;

        btn.disabled = true;
        var origText = btn.textContent.trim();
        btn.textContent = 'Processing…';

        fetch(withBase('/app/Controllers/CartController.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: parseInt(productId, 10),
                quantity:   1,
                unit_price: unitPrice,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    window.location.href = withBase('/checkout');
                } else {
                    btn.disabled = false;
                    btn.textContent = origText;
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = origText;
            });
    });

}());
