let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
  let i;
  let slides = document.getElementsByClassName("mySlides");
  if (n > slides.length) { slideIndex = 1 }    
  if (n < 1) { slideIndex = slides.length }
  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";  
  }
  slides[slideIndex - 1].style.display = "block";  
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("Scroller script loaded!");

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

