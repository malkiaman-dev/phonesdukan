document.addEventListener("DOMContentLoaded", function () {
    const heroSlider = document.querySelector("[data-pd-hero-slider]");

    if (heroSlider) {
        const slides = Array.from(heroSlider.querySelectorAll("[data-pd-slide]"));
        const dots = Array.from(heroSlider.querySelectorAll("[data-pd-hero-dot]"));
        const prevBtn = heroSlider.querySelector("[data-pd-hero-prev]");
        const nextBtn = heroSlider.querySelector("[data-pd-hero-next]");

        let currentIndex = slides.findIndex((slide) => slide.classList.contains("is-active"));
        if (currentIndex < 0) currentIndex = 0;

        let autoplayTimer = null;
        const autoplayDelay = 5000;
        let touchStartX = 0;
        let touchEndX = 0;

        const setActiveSlide = (index) => {
            if (!slides.length) return;

            const safeIndex = (index + slides.length) % slides.length;
            if (safeIndex === currentIndex) return;

            const outgoing = slides[currentIndex];
            const incoming = slides[safeIndex];

            // Exit current slide to the left
            outgoing.classList.remove("is-active");
            outgoing.classList.add("is-exiting");
            outgoing.setAttribute("aria-hidden", "true");

            // Bring new slide in from the right
            incoming.classList.remove("is-exiting");
            incoming.classList.add("is-active");
            incoming.setAttribute("aria-hidden", "false");

            currentIndex = safeIndex;

            dots.forEach((dot, i) => {
                const isActive = i === safeIndex;
                dot.classList.toggle("is-active", isActive);
                dot.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            // After transition, snap outgoing back off-screen (no animation)
            const done = outgoing;
            setTimeout(() => {
                done.style.transition = "none";
                done.classList.remove("is-exiting");
                void done.offsetWidth;
                done.style.transition = "";
            }, 700);
        };

        const goNext = () => setActiveSlide(currentIndex + 1);
        const goPrev = () => setActiveSlide(currentIndex - 1);

        const stopAutoplay = () => {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const startAutoplay = () => {
            if (slides.length < 2) return;
            stopAutoplay();
            autoplayTimer = setInterval(goNext, autoplayDelay);
        };

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                goPrev();
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                goNext();
                startAutoplay();
            });
        }

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const index = Number(dot.getAttribute("data-pd-hero-dot"));
                if (!Number.isNaN(index)) {
                    setActiveSlide(index);
                    startAutoplay();
                }
            });
        });

        heroSlider.addEventListener("mouseenter", stopAutoplay);
        heroSlider.addEventListener("mouseleave", startAutoplay);
        heroSlider.addEventListener("focusin", stopAutoplay);
        heroSlider.addEventListener("focusout", startAutoplay);

        heroSlider.addEventListener("keydown", (event) => {
            if (event.key === "ArrowRight") {
                goNext();
                startAutoplay();
            } else if (event.key === "ArrowLeft") {
                goPrev();
                startAutoplay();
            }
        });

        heroSlider.addEventListener("touchstart", (event) => {
            touchStartX = event.changedTouches[0].screenX;
        }, { passive: true });

        heroSlider.addEventListener("touchend", (event) => {
            touchEndX = event.changedTouches[0].screenX;
            const swipeDistance = touchEndX - touchStartX;

            if (Math.abs(swipeDistance) > 45) {
                if (swipeDistance < 0) {
                    goNext();
                } else {
                    goPrev();
                }
                startAutoplay();
            }
        }, { passive: true });

        setActiveSlide(currentIndex);
        startAutoplay();
    }

    document.querySelectorAll(".product-grid-wrapper").forEach((wrapper) => {
        const container = wrapper.querySelector(".product-grid-container");
        const grid = wrapper.querySelector(".product-grid");
        const prevBtn = wrapper.querySelector(".prev-btn");
        const nextBtn = wrapper.querySelector(".next-btn");

        if (!container || !grid || !prevBtn || !nextBtn) {
            console.error("One or more elements are missing in a section!", { container, grid, prevBtn, nextBtn });
            return;
        }

        let scrollAmount = container.clientWidth * 0.8; // Scroll by 80% of container width

        nextBtn.addEventListener("click", () => {
            container.scrollBy({ left: scrollAmount, behavior: "smooth" });
            setTimeout(() => updateButtonState(container, prevBtn, nextBtn, grid), 500);
        });

        prevBtn.addEventListener("click", () => {
            container.scrollBy({ left: -scrollAmount, behavior: "smooth" });
            setTimeout(() => updateButtonState(container, prevBtn, nextBtn, grid), 500);
        });

        function updateButtonState(container, prevBtn, nextBtn, grid) {
            prevBtn.disabled = container.scrollLeft <= 0;
            nextBtn.disabled = container.scrollLeft + container.clientWidth >= grid.scrollWidth;
        }

        updateButtonState(container, prevBtn, nextBtn, grid); // Initial check
    });

    // ── Category infinite carousel ──────────────────
    const catViewport = document.querySelector(".cat-viewport");
    const catTrack    = document.getElementById("cat-track");
    const catPrevBtn  = document.querySelector(".cat-prev");
    const catNextBtn  = document.querySelector(".cat-next");

    if (catViewport && catTrack && catPrevBtn && catNextBtn) {
        const GAP = 18;
        const origCards = Array.from(catTrack.children);
        const total = origCards.length;
        let currentIdx = total; // start at first original card (after prepended clones)
        let isAnimating = false;

        // Build [clones-of-all][originals][clones-of-all] for seamless loop
        origCards.forEach(card => catTrack.appendChild(card.cloneNode(true)));
        [...origCards].reverse().forEach(card =>
            catTrack.insertBefore(card.cloneNode(true), catTrack.firstChild)
        );

        // How many cards are visible based on viewport width
        const getVisibleCount = () => {
            const w = window.innerWidth;
            if (w >= 1200) return 5;
            if (w >= 992)  return 4;
            if (w >= 576)  return 3;
            return 2;
        };

        // Set card width via CSS variable so CSS uses it
        const setCardWidths = () => {
            const vw = catViewport.offsetWidth;
            const n  = getVisibleCount();
            const w  = Math.floor((vw - (n - 1) * GAP) / n);
            catViewport.style.setProperty("--cat-card-w", w + "px");
        };

        const getCardStep = () => {
            const card = catTrack.children[0];
            return card ? card.offsetWidth + GAP : 160 + GAP;
        };

        const moveTo = (idx, animate) => {
            catTrack.style.transition = animate
                ? "transform 0.42s cubic-bezier(0.25, 0.46, 0.45, 0.94)"
                : "none";
            catTrack.style.transform = `translateX(${-(idx * getCardStep())}px)`;
            currentIdx = idx;
        };

        // Init
        setCardWidths();
        // Let CSS variable apply before measuring
        requestAnimationFrame(() => {
            requestAnimationFrame(() => moveTo(total, false));
        });

        catTrack.addEventListener("transitionend", (e) => {
            if (e.target !== catTrack) return;
            if (currentIdx >= total * 2) {
                moveTo(currentIdx - total, false);
            } else if (currentIdx < total) {
                moveTo(currentIdx + total, false);
            }
            isAnimating = false;
        });

        catNextBtn.addEventListener("click", () => {
            if (isAnimating) return;
            isAnimating = true;
            moveTo(currentIdx + 1, true);
        });

        catPrevBtn.addEventListener("click", () => {
            if (isAnimating) return;
            isAnimating = true;
            moveTo(currentIdx - 1, true);
        });

        window.addEventListener("resize", () => {
            setCardWidths();
            requestAnimationFrame(() => moveTo(currentIdx, false));
        }, { passive: true });
    }

    // ── New Arrivals: Add to Cart ────────────────
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".na-btn--cart");
        if (!btn) return;

        const productId = btn.getAttribute("data-product-id");
        const unitPrice = parseFloat(btn.getAttribute("data-unit-price") || 0);
        if (!productId) return;

        const originalText = btn.textContent.trim();
        btn.disabled = true;

        fetch(window.pdWithBase("/app/Controllers/CartController.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ product_id: productId, quantity: 1, unit_price: unitPrice })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                btn.textContent = "Added ✓";

                // Use the actual server total — never guess with +1
                const items = Array.isArray(data.cart_items) ? data.cart_items : [];
                const realTotal = items.reduce(
                    (sum, item) => sum + (parseInt(item.total_quantity, 10) || 0), 0
                );
                if (realTotal > 0) {
                    document.querySelectorAll(".cart-count").forEach(el => {
                        el.textContent = realTotal;
                    });
                }

                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }, 2000);
            } else {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    });
});

