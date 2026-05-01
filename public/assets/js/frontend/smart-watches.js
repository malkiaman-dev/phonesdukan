(function () {
    'use strict';

    var withBase = window.pdWithBase || function (path) {
        var base = (window.location.pathname.split('/').filter(Boolean)[0] === 'phonesdukan') ? '/phonesdukan' : '';
        if (!path) return base + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        return base + (path.startsWith('/') ? path : '/' + path);
    };

    /* ── FAQ Accordion ───────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var faqItems = Array.from(document.querySelectorAll('.sw-faq-item'));
        if (!faqItems.length) return;

        function closeItem(item) {
            var btn = item.querySelector('.sw-faq-question');
            var answer = item.querySelector('.sw-faq-answer');
            if (!btn || !answer) return;

            item.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            answer.style.maxHeight = '0px';
        }

        function openItem(item) {
            var btn = item.querySelector('.sw-faq-question');
            var answer = item.querySelector('.sw-faq-answer');
            if (!btn || !answer) return;

            item.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            answer.style.maxHeight = answer.scrollHeight + 'px';
        }

        faqItems.forEach(function (item) {
            var btn = item.querySelector('.sw-faq-question');
            if (!btn) return;

            btn.addEventListener('click', function () {
                var isOpen = item.classList.contains('is-open');

                faqItems.forEach(closeItem);
                if (!isOpen) {
                    openItem(item);
                }
            });
        });

        window.addEventListener('resize', function () {
            faqItems.forEach(function (item) {
                if (item.classList.contains('is-open')) {
                    var answer = item.querySelector('.sw-faq-answer');
                    if (answer) answer.style.maxHeight = answer.scrollHeight + 'px';
                }
            });
        }, { passive: true });
    });

    /* ── Add to Cart ─────────────────────────────────────── */
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
                quantity: 1,
                unit_price: unitPrice
            })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    cartBtn.textContent = 'Added ✓';
                    cartBtn.classList.add('na-btn--added');

                    var qty = 0;
                    if (data.cart_summary && typeof data.cart_summary.total_quantity !== 'undefined') {
                        qty = parseInt(data.cart_summary.total_quantity, 10) || 0;
                    } else if (Array.isArray(data.cart_items)) {
                        qty = data.cart_items.reduce(function (sum, item) {
                            return sum + (parseInt(item.total_quantity, 10) || 0);
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

    /* ── Buy Now ─────────────────────────────────────────── */
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
                quantity: 1,
                unit_price: unitPrice
            })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    window.location.href = withBase('/checkout');
                    return;
                }

                btn.disabled = false;
                btn.textContent = origText;
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = origText;
            });
    });

    /* ── Collapse unfilled AdSense containers ───────────── */
    (function () {
        function collapseContainer(ins) {
            var container = ins.closest ? ins.closest('.ad-container') : null;
            if (!container) return;

            container.style.setProperty('display', 'none', 'important');
            container.style.setProperty('height', '0', 'important');
            container.style.setProperty('margin', '0', 'important');
            container.style.setProperty('padding', '0', 'important');
            container.style.setProperty('overflow', 'hidden', 'important');
        }

        function checkUnfilled() {
            document.querySelectorAll('ins.adsbygoogle').forEach(function (ins) {
                if (ins.getAttribute('data-ad-status') === 'unfilled') {
                    collapseContainer(ins);
                }
            });
        }

        function watchAds() {
            document.querySelectorAll('ins.adsbygoogle').forEach(function (ins) {
                new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.attributeName === 'data-ad-status' &&
                            ins.getAttribute('data-ad-status') === 'unfilled') {
                            collapseContainer(ins);
                        }
                    });
                }).observe(ins, { attributes: true, attributeFilter: ['data-ad-status'] });
            });

            setTimeout(checkUnfilled, 1500);
            setTimeout(checkUnfilled, 5000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', watchAds);
        } else {
            watchAds();
        }
    }());

}());