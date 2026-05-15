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
        const autoplayDelay = 3000;
        let touchStartX = 0;
        let touchEndX = 0;

        const setActiveSlide = (index, direction) => {
            if (!slides.length) return;
            if (direction === undefined) direction = 1;

            const safeIndex = (index + slides.length) % slides.length;
            if (safeIndex === currentIndex) return;

            const outgoing = slides[currentIndex];
            const incoming = slides[safeIndex];

            if (direction >= 0) {
                // Forward: outgoing exits left, incoming enters from right
                outgoing.classList.remove("is-active");
                outgoing.classList.add("is-exiting");
                outgoing.setAttribute("aria-hidden", "true");

                incoming.classList.remove("is-exiting");
                incoming.classList.add("is-active");
                incoming.setAttribute("aria-hidden", "false");

                const done = outgoing;
                setTimeout(() => {
                    done.style.transition = "none";
                    done.classList.remove("is-exiting");
                    void done.offsetWidth;
                    done.style.transition = "";
                }, 700);
            } else {
                // Backward: outgoing exits right, incoming enters from left
                incoming.classList.add("is-entering-left");
                void incoming.offsetWidth; // commit start position before animating

                outgoing.classList.remove("is-active");
                outgoing.classList.add("is-exiting-right");
                outgoing.setAttribute("aria-hidden", "true");

                incoming.classList.remove("is-entering-left");
                incoming.classList.add("is-active");
                incoming.setAttribute("aria-hidden", "false");

                const doneOut = outgoing;
                setTimeout(() => {
                    doneOut.style.transition = "none";
                    doneOut.classList.remove("is-exiting-right");
                    void doneOut.offsetWidth;
                    doneOut.style.transition = "";
                }, 700);
            }

            currentIndex = safeIndex;

            dots.forEach((dot, i) => {
                const isActive = i === safeIndex;
                dot.classList.toggle("is-active", isActive);
                dot.setAttribute("aria-selected", isActive ? "true" : "false");
            });
        };

        const goNext = () => setActiveSlide(currentIndex + 1, 1);
        const goPrev = () => setActiveSlide(currentIndex - 1, -1);

        const stopAutoplay = () => {
            if (autoplayTimer) {
                clearTimeout(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const queueNextAutoplay = () => {
            stopAutoplay();
            autoplayTimer = setTimeout(() => {
                goNext();
                queueNextAutoplay();
            }, autoplayDelay);
        };

        const startAutoplay = () => {
            if (slides.length < 2) return;
            queueNextAutoplay();
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
                    const dir = index >= currentIndex ? 1 : -1;
                    setActiveSlide(index, dir);
                    startAutoplay();
                }
            });
        });

        heroSlider.addEventListener("mouseenter", stopAutoplay);
        heroSlider.addEventListener("mouseleave", startAutoplay);
        heroSlider.addEventListener("focusin", stopAutoplay);
        heroSlider.addEventListener("focusout", startAutoplay);

        const onVisibilityChange = () => {
            if (document.hidden) { stopAutoplay(); } else { startAutoplay(); }
        };
        const onPageShow = () => startAutoplay();

        document.addEventListener("visibilitychange", onVisibilityChange);
        window.addEventListener("pageshow", onPageShow);

        heroSlider.addEventListener("remove", () => {
            document.removeEventListener("visibilitychange", onVisibilityChange);
            window.removeEventListener("pageshow", onPageShow);
        });

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
            stopAutoplay();
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
            }
            startAutoplay();
        }, { passive: true });

        startAutoplay();
    }

    document.querySelectorAll(".product-grid-wrapper").forEach((wrapper) => {
        const container = wrapper.querySelector(".product-grid-container");
        const grid = wrapper.querySelector(".product-grid");
        const prevBtn = wrapper.querySelector(".prev-btn");
        const nextBtn = wrapper.querySelector(".next-btn");

        if (!container || !grid || !prevBtn || !nextBtn) {
            return;
        }

        const getScrollAmount = () => container.clientWidth * 0.8;

        nextBtn.addEventListener("click", () => {
            container.scrollBy({ left: getScrollAmount(), behavior: "smooth" });
            setTimeout(() => updateButtonState(container, prevBtn, nextBtn, grid), 500);
        });

        prevBtn.addEventListener("click", () => {
            container.scrollBy({ left: -getScrollAmount(), behavior: "smooth" });
            setTimeout(() => updateButtonState(container, prevBtn, nextBtn, grid), 500);
        });

        function updateButtonState(container, prevBtn, nextBtn, grid) {
            prevBtn.disabled = container.scrollLeft <= 0;
            nextBtn.disabled = container.scrollLeft + container.clientWidth >= grid.scrollWidth;
        }

        updateButtonState(container, prevBtn, nextBtn, grid); // Initial check
    });

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
                // Permanently mark as added — no reset
                btn.textContent = "Added ✓";
                btn.disabled = true;
                btn.classList.add("na-btn--added");

                // Sync header badge from server total
                const items = Array.isArray(data.cart_items) ? data.cart_items : [];
                const realTotal = items.reduce(
                    (sum, item) => sum + (parseInt(item.total_quantity, 10) || 0), 0
                );
                if (realTotal > 0) {
                    document.querySelectorAll(".cart-count").forEach(el => {
                        el.textContent = realTotal;
                    });
                }
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

