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

    /* ── Collapse targeted empty AdSense containers ─────── */
    (function () {
        var TARGET_SLOTS = ['8736670293', '4555776348'];

        function isTargetSlot(ins) {
            if (!ins) return false;
            return TARGET_SLOTS.indexOf(String(ins.getAttribute('data-ad-slot') || '')) !== -1;
        }

        function collapseContainer(ins) {
            var container = ins && ins.closest ? ins.closest('.ad-container') : null;
            if (!container) return;

            container.classList.add('is-collapsed');
            container.style.setProperty('display', 'none', 'important');
            container.style.setProperty('height', '0', 'important');
            container.style.setProperty('margin', '0', 'important');
            container.style.setProperty('padding', '0', 'important');
            container.style.setProperty('overflow', 'hidden', 'important');
        }

        function hasOnePixelIframe(ins) {
            var iframe = ins.querySelector('iframe');
            if (!iframe) return false;

            var rect = iframe.getBoundingClientRect ? iframe.getBoundingClientRect() : null;
            var h = rect ? rect.height : (iframe.offsetHeight || iframe.clientHeight || parseInt(iframe.getAttribute('height') || '0', 10));
            var w = rect ? rect.width : (iframe.offsetWidth || iframe.clientWidth || parseInt(iframe.getAttribute('width') || '0', 10));

            return (h > 0 && h <= 1) || (w > 0 && w <= 1);
        }

        function hasOnePixelInlineHeight(ins) {
            var styleHeight = parseFloat((ins.style.height || '').replace('px', ''));
            return !isNaN(styleHeight) && styleHeight > 0 && styleHeight <= 1;
        }

        function shouldCollapse(ins) {
            if (!isTargetSlot(ins)) return false;

            var status = String(ins.getAttribute('data-ad-status') || '').toLowerCase();
            if (status === 'unfilled') return true;

            if (hasOnePixelInlineHeight(ins)) return true;
            if (hasOnePixelIframe(ins)) return true;

            return false;
        }

        function evaluateAd(ins) {
            if (shouldCollapse(ins)) {
                collapseContainer(ins);
            }
        }

        function evaluateAllTargetAds() {
            document.querySelectorAll('ins.adsbygoogle').forEach(function (ins) {
                if (isTargetSlot(ins)) {
                    evaluateAd(ins);
                }
            });
        }

        function watchAds() {
            document.querySelectorAll('ins.adsbygoogle').forEach(function (ins) {
                if (!isTargetSlot(ins)) return;

                new MutationObserver(function () {
                    evaluateAd(ins);
                }).observe(ins, {
                    attributes: true,
                    childList: true,
                    subtree: true,
                    attributeFilter: ['data-ad-status', 'style']
                });
            });

            var tries = 0;
            var poller = setInterval(function () {
                evaluateAllTargetAds();
                tries += 1;
                if (tries >= 25) {
                    clearInterval(poller);
                }
            }, 400);

            setTimeout(evaluateAllTargetAds, 1500);
            setTimeout(evaluateAllTargetAds, 5000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', watchAds);
        } else {
            watchAds();
        }
    }());

}());