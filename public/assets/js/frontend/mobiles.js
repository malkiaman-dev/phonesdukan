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

        /* ── Brand carousel (transform, auto-scroll, dots) ─── */
        initBrandsCarousel();
    });

    function initBrandsCarousel() {
        var wrap   = document.getElementById('mobBrandsCarousel');
        var track  = document.getElementById('mobBrandsTrack');
        var dotsEl = document.getElementById('mobBrandsDots');
        if (!wrap || !track) return;

        var origCards = Array.from(track.children);
        var total     = origCards.length; // 7 brands

        /* Build infinite clone set: [end-clones][originals][start-clones] */
        origCards.forEach(function (c) { track.appendChild(c.cloneNode(true)); });
        origCards.slice().reverse().forEach(function (c) {
            track.insertBefore(c.cloneNode(true), track.firstChild);
        });
        var allCards = Array.from(track.children); // 21 total

        var gap      = 16;
        var cardW    = 0;
        var curIdx   = total; // points to first original card
        var autoTimer  = null;
        var pauseTimer = null;
        var isDragging = false;
        var moved      = false;
        var dragStartX = 0;
        var dragOff    = 0;
        var touchStartX = 0;
        var touchOff    = 0;

        function setCardWidths() {
            /* Cards have fixed CSS width; read it from the first rendered card */
            var first = allCards[0];
            cardW = first ? first.offsetWidth : 150;
        }

        function offsetOf(idx) { return idx * (cardW + gap); }

        function setTransform(offset, animated) {
            track.classList.toggle('no-transition', !animated);
            track.style.transform = 'translateX(-' + offset + 'px)';
        }

        function goTo(idx, animated) {
            curIdx = idx;
            setTransform(offsetOf(curIdx), animated !== false);
        }

        /* Seamless loop: after animation settles, silently jump within clone set */
        track.addEventListener('transitionend', function () {
            if (curIdx >= total * 2) { goTo(curIdx - total, false); }
            else if (curIdx < total)  { goTo(curIdx + total, false); }
            updateDots();
        });

        /* ── Dots ──────────────────────────────────────────── */
        function dotIndex() { return ((curIdx - total) % total + total) % total; }

        function buildDots() {
            if (!dotsEl) return;
            dotsEl.innerHTML = '';
            for (var i = 0; i < total; i++) {
                (function (i) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'mob-brands-dot';
                    btn.setAttribute('aria-label', 'Show brand ' + (i + 1));
                    btn.setAttribute('role', 'tab');
                    btn.addEventListener('click', function () {
                        pauseAuto();
                        goTo(total + i, true);
                        updateDots(i);
                    });
                    dotsEl.appendChild(btn);
                }(i));
            }
        }

        function updateDots(forced) {
            if (!dotsEl) return;
            var active = (typeof forced !== 'undefined') ? forced : dotIndex();
            dotsEl.querySelectorAll('.mob-brands-dot').forEach(function (d, i) {
                d.classList.toggle('is-active', i === active);
                d.setAttribute('aria-selected', i === active ? 'true' : 'false');
            });
        }

        /* ── Auto-scroll ───────────────────────────────────── */
        function startAuto() {
            if (autoTimer) return;
            autoTimer = setInterval(function () {
                goTo(curIdx + 1, true);
            }, 3500);
        }

        function stopAuto() {
            clearInterval(autoTimer);
            autoTimer = null;
        }

        function pauseAuto() {
            stopAuto();
            clearTimeout(pauseTimer);
            pauseTimer = setTimeout(startAuto, 4000);
        }

        /* ── Mouse drag ────────────────────────────────────── */
        wrap.addEventListener('mousedown', function (e) {
            isDragging = true;
            moved      = false;
            dragStartX = e.clientX;
            dragOff    = offsetOf(curIdx);
            track.classList.add('no-transition');
            wrap.classList.add('is-dragging');
            pauseAuto();
            e.preventDefault();
        });

        document.addEventListener('mouseup', function (e) {
            if (!isDragging) return;
            isDragging = false;
            wrap.classList.remove('is-dragging');
            if (moved) {
                var diff = dragStartX - e.clientX;
                if (Math.abs(diff) > 50) {
                    goTo(curIdx + (diff > 0 ? 1 : -1), true);
                } else {
                    goTo(curIdx, true);
                }
                updateDots();
            }
            moved = false;
        });

        document.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            moved = true;
            setTransform(dragOff + (dragStartX - e.clientX), false);
        });

        wrap.addEventListener('click', function (e) {
            if (moved) { e.preventDefault(); e.stopPropagation(); }
        }, true);

        /* ── Touch drag ────────────────────────────────────── */
        wrap.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
            touchOff    = offsetOf(curIdx);
            track.classList.add('no-transition');
            pauseAuto();
        }, { passive: true });

        wrap.addEventListener('touchmove', function (e) {
            setTransform(touchOff + (touchStartX - e.touches[0].clientX), false);
        }, { passive: true });

        wrap.addEventListener('touchend', function (e) {
            var diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) {
                goTo(curIdx + (diff > 0 ? 1 : -1), true);
            } else {
                goTo(curIdx, true);
            }
            updateDots();
        }, { passive: true });

        /* ── Init ──────────────────────────────────────────── */
        setCardWidths();
        goTo(total, false);  // land on first original, no animation
        buildDots();
        updateDots(0);
        startAuto();

        window.addEventListener('resize', function () {
            setCardWidths();
            goTo(curIdx, false);
        }, { passive: true });
    }

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

    /* ── Collapse unfilled AdSense containers ──────────────── */
    (function () {
        function collapseContainer(ins) {
            var c = ins.closest ? ins.closest('.ad-container') : null;
            if (!c) return;
            c.style.setProperty('display',  'none',    'important');
            c.style.setProperty('height',   '0',       'important');
            c.style.setProperty('margin',   '0',       'important');
            c.style.setProperty('padding',  '0',       'important');
            c.style.setProperty('overflow', 'hidden',  'important');
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
                /* MutationObserver fires the instant AdSense sets data-ad-status */
                new MutationObserver(function (mutations) {
                    mutations.forEach(function (m) {
                        if (m.attributeName === 'data-ad-status' &&
                            ins.getAttribute('data-ad-status') === 'unfilled') {
                            collapseContainer(ins);
                        }
                    });
                }).observe(ins, { attributes: true, attributeFilter: ['data-ad-status'] });
            });

            /* Fallback polls in case the mutation fires before observer is attached */
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
