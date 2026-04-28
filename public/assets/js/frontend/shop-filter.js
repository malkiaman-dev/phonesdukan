document.addEventListener("DOMContentLoaded", function () {
    const checkboxes = document.querySelectorAll(".price-option input[type='checkbox']");
    const categoryCheckboxes = document.querySelectorAll(".category-filter");
    const sortRadios = document.querySelectorAll("input[name='sort_by']");

    function updateFilters(param, selectedValues) {
        const urlParams = new URLSearchParams(window.location.search);

        if (selectedValues.length > 0) {
            urlParams.set(param, selectedValues.join(","));
        } else {
            urlParams.delete(param);
        }

        // ✅ Store selected filters temporarily to fix mobile issue
        sessionStorage.setItem(param, JSON.stringify(selectedValues));

        // ✅ Update URL without refreshing yet
        window.history.replaceState(null, "", "?" + urlParams.toString());

        // ✅ Delay refresh slightly to ensure UI updates before reload
        requestAnimationFrame(() => {
            setTimeout(() => {
                window.location.reload();
            }, 200);
        });
    }

    function handleCheckboxChange(checkboxGroup, paramName) {
        let selectedValues = [];
        checkboxGroup.forEach(cb => {
            if (cb.checked) {
                selectedValues.push(cb.value);
            }
        });

        updateFilters(paramName, selectedValues);
    }

    // ✅ Apply event listeners to price range checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            handleCheckboxChange(checkboxes, "price_range");
        });
    });

    // ✅ Apply event listeners to category checkboxes
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            handleCheckboxChange(categoryCheckboxes, "category");
        });
    });

    // ✅ Handle sorting options
    sortRadios.forEach(radio => {
        radio.addEventListener("change", function () {
            updateFilters("sort_by", [this.value]);
        });
    });

    // ✅ Restore checkbox state on page load
    function restoreCheckboxState(param, checkboxes) {
        let selectedValues = [];

        if (sessionStorage.getItem(param)) {
            selectedValues = JSON.parse(sessionStorage.getItem(param));
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has(param)) {
                selectedValues = urlParams.get(param).split(",");
            }
        }

        checkboxes.forEach(cb => {
            cb.checked = selectedValues.includes(cb.value);
        });

        // ✅ Remove temporary storage after applying to avoid conflicts
        sessionStorage.removeItem(param);
    }

    restoreCheckboxState("price_range", checkboxes);
    restoreCheckboxState("category", categoryCheckboxes);

    // ✅ Restore sorting state
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("sort_by")) {
        const selectedSort = urlParams.get("sort_by");
        sortRadios.forEach(radio => {
            radio.checked = radio.value === selectedSort;
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const mobileCheckboxes = document.querySelectorAll(".mobile-price-option input[type='checkbox']");
    const mobileCategoryCheckboxes = document.querySelectorAll(".mobile-category-filter");
    const mobileSortRadios = document.querySelectorAll(".mobile-sort-options input[name='sort_by']");

    function updateMobileFilters(param, selectedValues) {
        const urlParams = new URLSearchParams(window.location.search);

        if (selectedValues.length > 0) {
            urlParams.set(param, selectedValues.join(","));
        } else {
            urlParams.delete(param);
        }

        // ✅ Store selected filters temporarily for mobile
        sessionStorage.setItem(param, JSON.stringify(selectedValues));

        // ✅ Update URL without refreshing yet
        window.history.replaceState(null, "", "?" + urlParams.toString());

        // ✅ Delay refresh slightly to ensure UI updates before reload
        requestAnimationFrame(() => {
            setTimeout(() => {
                window.location.reload();
            }, 200);
        });
    }

    function handleMobileCheckboxChange(checkboxGroup, paramName) {
        let selectedValues = [];
        checkboxGroup.forEach(cb => {
            if (cb.checked) {
                selectedValues.push(cb.value);
            }
        });

        updateMobileFilters(paramName, selectedValues);
    }

    // ✅ Apply event listeners to mobile price range checkboxes
    mobileCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            handleMobileCheckboxChange(mobileCheckboxes, "price_range");
        });
    });

    // ✅ Apply event listeners to mobile category checkboxes
    mobileCategoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            handleMobileCheckboxChange(mobileCategoryCheckboxes, "category");
        });
    });

    // ✅ Handle mobile sorting options
    mobileSortRadios.forEach(radio => {
        radio.addEventListener("change", function () {
            updateMobileFilters("sort_by", [this.value]);
        });
    });

    // ✅ Restore mobile checkbox state on page load
    function restoreMobileCheckboxState(param, checkboxes) {
        let selectedValues = [];

        if (sessionStorage.getItem(param)) {
            selectedValues = JSON.parse(sessionStorage.getItem(param));
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has(param)) {
                selectedValues = urlParams.get(param).split(",");
            }
        }

        checkboxes.forEach(cb => {
            cb.checked = selectedValues.includes(cb.value);
        });

        // ✅ Remove temporary storage after applying to avoid conflicts
        sessionStorage.removeItem(param);
    }

    restoreMobileCheckboxState("price_range", mobileCheckboxes);
    restoreMobileCheckboxState("category", mobileCategoryCheckboxes);

    // ✅ Restore mobile sorting state
    const mobileUrlParams = new URLSearchParams(window.location.search);
    if (mobileUrlParams.has("sort_by")) {
        const selectedSort = mobileUrlParams.get("sort_by");
        mobileSortRadios.forEach(radio => {
            radio.checked = radio.value === selectedSort;
        });
    }
});


document.addEventListener("DOMContentLoaded", function () {
    const filterBtn = document.getElementById("unique-mobile-filter-btn");
    const filterSidebar = document.getElementById("unique-mobile-filter-sidebar");
    const closeBtn = document.getElementById("unique-mobile-filter-close");

    if (filterBtn && filterSidebar && closeBtn) {
        filterBtn.addEventListener("click", function () {
            filterSidebar.classList.add("active");
        });

        closeBtn.addEventListener("click", function () {
            filterSidebar.classList.remove("active");
        });

        // Close sidebar if clicked outside
        document.addEventListener("click", function (event) {
            if (!filterSidebar.contains(event.target) && !filterBtn.contains(event.target)) {
                filterSidebar.classList.remove("active");
            }
        });
    }
});
