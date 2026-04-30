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
        var tabs  = document.querySelectorAll('.mob-brand-tab');
        var cards = document.querySelectorAll('.mob-na-card');

        if (!tabs.length) return;

        function filterByBrand(brand) {
            tabs.forEach(function (t) {
                var active = t.getAttribute('data-brand') === brand;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            cards.forEach(function (card) {
                var show = brand === 'all' || card.getAttribute('data-brand') === brand;
                card.style.display = show ? '' : 'none';
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                filterByBrand(tab.getAttribute('data-brand') || 'all');
            });
        });

        filterByBrand('all');

        /* ── Brand carousel drag-scroll ────────────────────── */
        var carousel = document.getElementById('mobBrandsCarousel');
        if (!carousel) return;

        var isDragging  = false;
        var startX      = 0;
        var scrollStart = 0;
        var moved       = false;

        carousel.addEventListener('mousedown', function (e) {
            isDragging  = true;
            moved       = false;
            startX      = e.pageX - carousel.offsetLeft;
            scrollStart = carousel.scrollLeft;
            carousel.classList.add('is-dragging');
        });

        document.addEventListener('mouseup', function () {
            if (!isDragging) return;
            isDragging = false;
            carousel.classList.remove('is-dragging');
        });

        carousel.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            e.preventDefault();
            moved = true;
            var x    = e.pageX - carousel.offsetLeft;
            var walk = (x - startX) * 1.4;
            carousel.scrollLeft = scrollStart - walk;
        });

        /* Prevent click-through on drag */
        carousel.addEventListener('click', function (e) {
            if (moved) e.preventDefault();
        }, true);

        /* Touch */
        var touchStartX   = 0;
        var touchScrollLeft = 0;

        carousel.addEventListener('touchstart', function (e) {
            touchStartX    = e.touches[0].pageX;
            touchScrollLeft = carousel.scrollLeft;
        }, { passive: true });

        carousel.addEventListener('touchmove', function (e) {
            var walk = touchStartX - e.touches[0].pageX;
            carousel.scrollLeft = touchScrollLeft + walk;
        }, { passive: true });
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
