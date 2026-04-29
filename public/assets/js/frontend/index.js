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
});

