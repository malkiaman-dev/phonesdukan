const pdBasePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
window.pdWithBase = window.pdWithBase || function (path) {
    const baseUrl = pdBasePath + '/';
    if (!path) return baseUrl;
    if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
    if (path.startsWith(pdBasePath + '/')) return path;
    if (path.startsWith('/')) return pdBasePath + path;
    return pdBasePath + '/' + path;
};

document.addEventListener("DOMContentLoaded", function () {

    // Desktop sidebar elements
    const desktopHamburgerIcon = document.getElementById("hamburger-icon"); // Desktop icon
    const closeSidebar = document.getElementById("close-sidebar");

    // Mobile sidebar elements
    const mobileMenuToggle = document.getElementById("mobile-menu-toggle"); // Mobile icon
    const mobileSidebar = document.getElementById("mobile-sidebar");
    const pageContent = document.querySelector(".page-content");
    const body = document.body;

    const sidebar = document.getElementById("sidebar");
    const sidebarContainer = document.getElementById("sidebar-container");
    const sidebarOverlay = document.getElementById("sidebar-overlay");
    const headerStack = document.querySelector(".pd-header-stack");

    if (headerStack) {
        const topBars = document.querySelector(".pd-top-bars");

        const setHeaderScrolledState = () => {
            const threshold = topBars ? topBars.offsetHeight : 74;
            headerStack.classList.toggle("is-scrolled", window.scrollY > threshold);
        };

        setHeaderScrolledState();
        window.addEventListener("scroll", setHeaderScrolledState, { passive: true });
    }

    // Open sidebar function (this works for both mobile and desktop)
    function openSidebar() {
        if (!sidebar || !sidebarContainer || !sidebarOverlay) return;
        sidebar.classList.add("open"); // Show the sidebar
        sidebarContainer.classList.add("open"); // Show the sidebar container (including overlay)
        sidebarOverlay.classList.add("open"); // Show the overlay
        if (pageContent) {
            pageContent.classList.add("dimmed"); // Dim the page content
        }
        body.classList.add("dimmed"); // Optional: Apply dimming to the body as well
    }

    // Close sidebar function
    function closeSidebarFunction() {
        if (!sidebar || !sidebarContainer || !sidebarOverlay) return;
        sidebar.classList.remove("open"); // Hide the sidebar
        sidebarContainer.classList.remove("open"); // Hide the sidebar container (including overlay)
        sidebarOverlay.classList.remove("open"); // Hide the overlay
        if (pageContent) {
            pageContent.classList.remove("dimmed"); // Remove dimming effect from the page content
        }
        body.classList.remove("dimmed"); // Optional: Remove dimming effect from body
    }

    // Mobile hamburger icon click event (for opening sidebar)
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener("click", function () {
            openSidebar(); // Open sidebar when mobile icon is clicked
        });
    }

    // Desktop hamburger icon click event (for opening sidebar)
    if (desktopHamburgerIcon) {
        desktopHamburgerIcon.addEventListener("click", function () {
            openSidebar(); // Open sidebar when desktop icon is clicked
        });
    }

    // Close sidebar when clicking on the close button (close-sidebar)
    if (closeSidebar) {
        closeSidebar.addEventListener("click", function () {
            closeSidebarFunction(); // Close sidebar when close button is clicked
        });
    }

    // Close sidebar when clicking on the overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", function () {
            closeSidebarFunction(); // Close sidebar when overlay is clicked
        });
    }
});

// dropdwon of mobile
document.addEventListener("DOMContentLoaded", function () {
    const categoryHeading = document.getElementById("mobiles-category");
    const categoryContent = document.getElementById("mobiles-content");
    const toggleIcon = document.getElementById("mobiles-toggle-icon");

    if (!categoryHeading || !categoryContent || !toggleIcon) {
        return;
    }

    // Hide the subcategory list by default
    categoryContent.style.display = "none";

    categoryHeading.addEventListener("click", function () {
        if (categoryContent.style.display === "none") {
            categoryContent.style.display = "block";
            toggleIcon.innerHTML = "&#x25B2;"; // Change to up arrow
        } else {
            categoryContent.style.display = "none";
            toggleIcon.innerHTML = "&#x25BC;"; // Change to down arrow
        }
    });
});


// Search functionality for desktop
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("search-input");
    const searchResults = document.getElementById("search-results");
    const closeButton = document.getElementById("desktop-close-results");

// Mobile Search Functionality
const mobileSearchIcon = document.getElementById("mobile-search-icon");
const mobileSearchContainer = document.querySelector(".mobile-live-search-container");
const mobileCloseButton = document.getElementById("mobile-close-search");
const mobileSearchInput = document.getElementById("mobile-search-input");
const mobileSearchResults = document.getElementById("mobile-search-results");

// Show mobile search box
if (mobileSearchIcon && mobileSearchContainer && mobileSearchInput && mobileSearchResults) {
    mobileSearchIcon.addEventListener("click", function (e) {
        e.preventDefault();
        mobileSearchContainer.style.display = "block";
        mobileSearchInput.focus();
        mobileSearchResults.innerHTML = ""; // Clear any old results
        mobileSearchResults.style.display = "none"; // Hide search results initially
    });
}

// Hide mobile search box
if (mobileCloseButton && mobileSearchContainer && mobileSearchInput && mobileSearchResults) {
    mobileCloseButton.addEventListener("click", function () {
        mobileSearchContainer.style.display = "none";
        mobileSearchInput.value = "";
        mobileSearchResults.innerHTML = ""; // Clear results when closing
        mobileSearchResults.style.display = "none"; // Ensure results container is hidden
    });
}

// Function to sanitize input by removing unwanted characters
function sanitizeInput(input) {
    return input.replace(/[^a-zA-Z0-9\s]/g, ''); // Removes anything except letters, numbers, and spaces
}

function uniqueValues(values) {
    const seen = new Set();
    return values.filter((item) => {
        if (!item || seen.has(item)) return false;
        seen.add(item);
        return true;
    });
}

function resolveSearchImageCandidates(rawUrl) {
    const placeholder = window.pdWithBase('/public/assets/images/phonesdukan_logo.webp');
    if (!rawUrl) return [placeholder];

    const candidates = [];
    const pushCandidate = (value) => {
        if (typeof value !== 'string') return;
        const v = value.trim();
        if (!v) return;
        candidates.push(v);
    };

    let normalized = String(rawUrl).trim().replace(/\\/g, '/');
    normalized = normalized.replace(/^file:\/\/\/?/i, '');
    normalized = normalized.replace(/^[A-Za-z]:\/xampp\/htdocs\/phonesdukan\//i, '');
    normalized = normalized.replace(/^\.\/+/, '');

    if (/^https?:\/\//i.test(normalized)) {
        pushCandidate(normalized);
    }

    if (normalized.startsWith('/')) {
        pushCandidate(normalized);
        pushCandidate(window.pdWithBase(normalized));
    } else {
        pushCandidate('/' + normalized);
        pushCandidate(window.pdWithBase('/' + normalized));
    }

    const withoutBase = normalized.replace(/^\/?phonesdukan\//i, '');
    if (withoutBase !== normalized) {
        pushCandidate('/' + withoutBase);
        pushCandidate(window.pdWithBase('/' + withoutBase));
    }

    const publicUploadsIdx = normalized.toLowerCase().indexOf('public/uploads/');
    if (publicUploadsIdx >= 0) {
        const publicPath = normalized.slice(publicUploadsIdx);
        pushCandidate('/' + publicPath.replace(/^\/+/, ''));
        pushCandidate(window.pdWithBase('/' + publicPath.replace(/^\/+/, '')));
    }

    if (/^uploads\//i.test(normalized)) {
        pushCandidate(window.pdWithBase('/public/' + normalized));
    }

    if (/^public\//i.test(normalized)) {
        pushCandidate(window.pdWithBase('/' + normalized));
    }

    pushCandidate(placeholder);
    return uniqueValues(candidates);
}

function createSearchImage(product) {
    const img = document.createElement('img');
    img.alt = product.product_name || 'Product';
    img.className = 'search-img';

    const candidates = resolveSearchImageCandidates(product.image_url || '');
    let candidateIndex = 0;
    img.src = candidates[candidateIndex];
    img.onerror = function () {
        candidateIndex += 1;
        if (candidateIndex < candidates.length) {
            img.src = candidates[candidateIndex];
            return;
        }
        img.onerror = null;
    };

    return img;
}

// Live search on mobile
if (mobileSearchInput && mobileSearchResults) {
mobileSearchInput.addEventListener("input", function () {
    let query = mobileSearchInput.value;
    let sanitizedQuery = sanitizeInput(query);

    // If the sanitized query is different, update the input value
    if (sanitizedQuery !== query) {
        mobileSearchInput.value = sanitizedQuery;
    }

    // If input is less than 2 characters, clear results and exit
    if (sanitizedQuery.trim().length < 2) {
        mobileSearchResults.innerHTML = ""; // Hide results if less than 2 characters
        mobileSearchResults.style.display = "none"; // Hide the results dropdown
        return;
    }

    // Fetch search results
    fetch(window.pdWithBase(`/public/ajax/search_products.php?query=${encodeURIComponent(sanitizedQuery)}`))
        .then(response => response.json())
        .then(data => {
            mobileSearchResults.innerHTML = ""; // Clear previous results
            if (data.length > 0) {
                mobileSearchResults.style.display = "block"; // Show results if there are any
                data.forEach(product => {
                    const productUrl = window.pdWithBase(`/${product.category_slug}/${product.brand_slug}/${product.product_slug}`);
                    const resultItem = document.createElement("div");
                    resultItem.classList.add("search-item");
                    const link = document.createElement('a');
                    link.href = productUrl;
                    link.appendChild(createSearchImage(product));
                    const text = document.createElement('span');
                    text.className = 'search-text';
                    text.textContent = product.product_name || '';
                    link.appendChild(text);
                    resultItem.appendChild(link);
                    mobileSearchResults.appendChild(resultItem);
                });
            } else {
                mobileSearchResults.style.display = "block"; // Show "No results found" if no products match
                mobileSearchResults.innerHTML = "<div class='search-item'>No results found</div>";
            }
        })
        .catch(error => console.error("Error fetching mobile search results:", error));
});
}

// Redirect to search results page on Enter key press
if (mobileSearchInput) {
mobileSearchInput.addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
        let query = sanitizeInput(mobileSearchInput.value.trim());
        if (query.length >= 2) {
            window.location.href = window.pdWithBase(`/search/?query=${encodeURIComponent(query)}`);
        }
    }
});
}


    if (!searchInput || !searchResults || !closeButton) {
        return;
    }

    searchInput.addEventListener("input", function () {
        let query = searchInput.value; // Get the original input value

        // ✅ Sanitize input but allow spaces
        let sanitizedQuery = sanitizeInput(query);

        // ✅ Update input value only if different (prevents cursor jumping issues)
        if (sanitizedQuery !== query) {
            searchInput.value = sanitizedQuery;
        }

        // ✅ Trim spaces & check length
        if (sanitizedQuery.trim().length < 2) {
            searchResults.innerHTML = "";
            closeButton.style.display = "none";
            return;
        }

        // ✅ Send search request
        fetch(window.pdWithBase(`/public/ajax/search_products.php?query=${encodeURIComponent(sanitizedQuery)}`))
            .then(response => response.json())
            .then(data => {
                searchResults.innerHTML = ""; // Clear previous results
                if (data.length > 0) {
                    closeButton.style.display = "flex";
                    data.forEach(product => {
                        const productUrl = window.pdWithBase(`/${product.category_slug}/${product.brand_slug}/${product.product_slug}`);
                        const li = document.createElement("li");
                        const link = document.createElement('a');
                        link.href = productUrl;
                        link.className = 'search-item';
                        link.appendChild(createSearchImage(product));
                        const text = document.createElement('span');
                        text.className = 'search-text';
                        text.textContent = product.product_name || '';
                        link.appendChild(text);
                        li.appendChild(link);
                        searchResults.appendChild(li);
                    });
                } else {
                    searchResults.innerHTML = "<li>No results found</li>";
                }
            })
            .catch(error => console.error("Error fetching search results:", error));
    });


    // Redirect to search.php when user presses "Enter"
    searchInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            let query = searchInput.value.trim();

            // Sanitize user input before redirecting
            query = sanitizeInput(query);

            if (query.length >= 2) {
                window.location.href = window.pdWithBase(`/search/?query=${encodeURIComponent(query)}`);
            }
        }
    });

    closeButton.addEventListener("click", function () {
        searchResults.innerHTML = "";
        searchInput.value = "";
        closeButton.style.display = "none";
    });

    // Yellow search button click — redirect to search results
    const searchBtn = document.getElementById("desktop-search-btn");
    if (searchBtn) {
        searchBtn.addEventListener("click", function () {
            let query = sanitizeInput(searchInput.value.trim());
            if (query.length >= 2) {
                window.location.href = window.pdWithBase(`/search/?query=${encodeURIComponent(query)}`);
            } else {
                searchInput.focus();
            }
        });
    }
});

// Pre-loader: hide once everything is loaded + short delay
(function () {
    function hidePreloader() {
        setTimeout(function () {
            var el = document.getElementById('pd-preloader');
            if (el) el.classList.add('pd-preloader-hidden');
        }, 1200);
    }
    if (document.readyState === 'complete') {
        hidePreloader();
    } else {
        window.addEventListener('load', hidePreloader);
    }
    setTimeout(hidePreloader, 6000);
})();
