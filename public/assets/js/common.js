const pdBasePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
window.pdWithBase = window.pdWithBase || function (path) {
    const baseUrl = pdBasePath + '/';
    if (!path) return baseUrl;
    if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
    if (path.startsWith(pdBasePath + '/')) return path;
    if (path.startsWith('/')) return pdBasePath + path;
    return pdBasePath + '/' + path;
};

window.pdGetScrollRoot = window.pdGetScrollRoot || function () {
    return document.getElementById('pd-page-scroll')
        || document.scrollingElement
        || document.documentElement;
};

window.pdGetScrollY = window.pdGetScrollY || function () {
    const root = window.pdGetScrollRoot();
    if (root === document.scrollingElement || root === document.documentElement || root === document.body) {
        return window.scrollY || window.pageYOffset || 0;
    }
    return root.scrollTop || 0;
};

window.pdScrollTo = window.pdScrollTo || function (x, y) {
    const root = window.pdGetScrollRoot();
    const top = typeof y === 'number' ? y : 0;
    const left = typeof x === 'number' ? x : 0;
    if (root === document.scrollingElement || root === document.documentElement || root === document.body) {
        window.scrollTo(left, top);
        return;
    }
    root.scrollTo(left, top);
    root.scrollTop = top;
    root.scrollLeft = left;
};

window.pdBuildProductPath = window.pdBuildProductPath || function (product) {
    const parts = [
        product.brand_slug || '',
        product.category_slug || '',
    ];
    if (product.subcategory_slug) {
        parts.push(product.subcategory_slug);
    }
    parts.push(product.product_slug || '');
    return '/' + parts.filter(Boolean).join('/');
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
    const announcementBar = document.querySelector(".pd-announcement-bar");
    const announcementTrack = document.querySelector(".pd-announcement-track");

    function initAnnouncementMarquee() {
        if (!announcementTrack) return;

        var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (reducedMotion) {
            announcementTrack.classList.remove("pd-marquee-active");
            announcementTrack.style.animation = "none";
            return;
        }

        var resizeTimer = null;
        var lastHalfWidth = 0;
        var syncAttempts = 0;

        function syncMarqueeSpeed() {
            var viewport = announcementTrack.closest(".pd-announcement-viewport");
            var viewWidth = viewport ? viewport.clientWidth : window.innerWidth;
            var totalWidth = announcementTrack.scrollWidth;
            var halfWidth = totalWidth / 2;

            if (!halfWidth || halfWidth < viewWidth * 0.4) {
                announcementTrack.classList.remove("pd-marquee-active");
                if (syncAttempts < 12) {
                    syncAttempts += 1;
                    window.requestAnimationFrame(function () {
                        window.setTimeout(syncMarqueeSpeed, 50);
                    });
                }
                return;
            }

            syncAttempts = 0;
            if (Math.abs(halfWidth - lastHalfWidth) < 1) {
                if (!announcementTrack.classList.contains("pd-marquee-active")) {
                    announcementTrack.classList.add("pd-marquee-active");
                }
                return;
            }

            lastHalfWidth = halfWidth;
            var pxPerSecond = 38;
            var duration = Math.max(18, halfWidth / pxPerSecond);
            announcementTrack.style.setProperty("--pd-ticker-shift", "-" + halfWidth + "px");
            announcementTrack.style.setProperty("--pd-ticker-duration", duration + "s");
            announcementTrack.classList.remove("pd-marquee-active");
            void announcementTrack.offsetWidth;
            announcementTrack.classList.add("pd-marquee-active");
        }

        function scheduleSync() {
            syncAttempts = 0;
            window.requestAnimationFrame(syncMarqueeSpeed);
        }

        window.PDAnnouncement = {
            sync: scheduleSync
        };

        function debouncedSync() {
            if (resizeTimer) {
                clearTimeout(resizeTimer);
            }
            resizeTimer = window.setTimeout(scheduleSync, 200);
        }

        scheduleSync();
        window.addEventListener("resize", debouncedSync, { passive: true });
        window.addEventListener("orientationchange", function () {
            lastHalfWidth = 0;
            window.setTimeout(scheduleSync, 200);
        }, { passive: true });
        window.addEventListener("pageshow", function () {
            lastHalfWidth = 0;
            window.setTimeout(scheduleSync, 80);
        }, { passive: true });

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () {
                lastHalfWidth = 0;
                scheduleSync();
            }).catch(function () {});
        }

        window.addEventListener("load", function () {
            lastHalfWidth = 0;
            scheduleSync();
        }, { passive: true });

        if (window.PDSafeArea) {
            window.setTimeout(function () {
                lastHalfWidth = 0;
                scheduleSync();
            }, 250);
        }
    }

    function readSafeAreaTop() {
        if (window.PDSafeArea && typeof window.PDSafeArea.apply === "function") {
            window.PDSafeArea.apply();
            return;
        }
        const root = document.documentElement;
        const probe = document.createElement("div");
        probe.style.cssText = "position:fixed;padding-top:constant(safe-area-inset-top);padding-top:env(safe-area-inset-top);visibility:hidden;pointer-events:none;";
        root.appendChild(probe);
        const inset = parseFloat(window.getComputedStyle(probe).paddingTop) || 0;
        probe.remove();
        root.style.setProperty("--safe-area-top", inset + "px");
    }

    function readCssPx(value) {
        return parseFloat(value) || 0;
    }

    function updateFixedChromeMetrics() {
        const root = document.documentElement;
        let annH = announcementTrack ? announcementTrack.offsetHeight : 0;
        let headerH = headerStack ? headerStack.offsetHeight : 0;

        if (announcementTrack && announcementTrack.offsetHeight) {
            annH = announcementTrack.offsetHeight;
            root.style.setProperty("--announcement-height", annH + "px");
        }
        if (headerStack && headerStack.offsetHeight) {
            headerH = headerStack.offsetHeight;
            root.style.setProperty("--header-height", headerH + "px");
        }

        const padTop = readCssPx(getComputedStyle(root).getPropertyValue("--pd-chrome-pad-top"));
        let totalOffset = Math.round(padTop + annH + headerH);

        const siteChrome = document.getElementById("pd-site-chrome");
        const isMobileLayout = window.matchMedia && window.matchMedia("(max-width: 992px)").matches;
        const scrollY = window.pdGetScrollY ? window.pdGetScrollY() : (window.scrollY || 0);
        if (scrollY < 2) {
            if (isMobileLayout && siteChrome) {
                const chromeBottom = siteChrome.getBoundingClientRect().bottom;
                if (chromeBottom > 0) {
                    totalOffset = Math.round(chromeBottom);
                }
            } else if (headerStack) {
                const headerBottom = headerStack.getBoundingClientRect().bottom;
                if (headerBottom > 0) {
                    totalOffset = Math.round(headerBottom);
                }
            }
        }

        root.style.setProperty("--pd-chrome-offset", totalOffset + "px");

        const spacer = document.querySelector(".pd-chrome-spacer");
        if (spacer) {
            spacer.style.height = totalOffset + "px";
            spacer.style.minHeight = totalOffset + "px";
        }
    }

    window.PDChromeMetrics = {
        update: updateFixedChromeMetrics
    };

    if (headerStack) {
        readSafeAreaTop();
        initAnnouncementMarquee();
        updateFixedChromeMetrics();
        window.addEventListener("resize", updateFixedChromeMetrics, { passive: true });
        window.addEventListener("orientationchange", updateFixedChromeMetrics, { passive: true });

        if (typeof ResizeObserver !== "undefined") {
            const chromeObserver = new ResizeObserver(updateFixedChromeMetrics);
            if (headerStack) chromeObserver.observe(headerStack);
            if (announcementTrack) chromeObserver.observe(announcementTrack);
            var siteChrome = document.getElementById("pd-site-chrome");
            if (siteChrome) chromeObserver.observe(siteChrome);
        }

        const setHeaderScrolledState = () => {
            const y = window.pdGetScrollY ? window.pdGetScrollY() : (window.scrollY || 0);
            headerStack.classList.toggle("is-scrolled", y > 8);
        };

        setHeaderScrolledState();
        const scrollRoot = window.pdGetScrollRoot ? window.pdGetScrollRoot() : window;
        if (scrollRoot && scrollRoot !== document.documentElement && scrollRoot !== document.body && scrollRoot !== document.scrollingElement) {
            scrollRoot.addEventListener("scroll", setHeaderScrolledState, { passive: true });
        }
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

    window.PDSidebar = {
        close: closeSidebarFunction,
    };

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

// Expandable sidebar categories
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".sb-category-item--expandable").forEach(function (item) {
        const categoryHeading = item.querySelector(".category-heading");
        const categoryContent = item.querySelector(".category-content");
        const toggleIcon = item.querySelector(".dropdown-icon");

        if (!categoryHeading || !categoryContent || !toggleIcon) {
            return;
        }

        categoryContent.style.display = "none";

        categoryHeading.addEventListener("click", function (event) {
            if (event.target.closest(".category-link")) {
                return;
            }

            if (categoryContent.style.display === "none") {
                categoryContent.style.display = "block";
                toggleIcon.innerHTML = "&#x25B2;";
            } else {
                categoryContent.style.display = "none";
                toggleIcon.innerHTML = "&#x25BC;";
            }
        });
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

// Live search on mobile (debounced — fires 300ms after user stops typing)
if (mobileSearchInput && mobileSearchResults) {
    let mobileSearchTimer = null;
    mobileSearchInput.addEventListener("input", function () {
        let query = mobileSearchInput.value;
        let sanitizedQuery = sanitizeInput(query);

        if (sanitizedQuery !== query) mobileSearchInput.value = sanitizedQuery;

        if (sanitizedQuery.trim().length < 2) {
            mobileSearchResults.innerHTML = "";
            mobileSearchResults.style.display = "none";
            return;
        }

        clearTimeout(mobileSearchTimer);
        mobileSearchTimer = setTimeout(function () {
            fetch(window.pdWithBase(`/public/ajax/search_products.php?query=${encodeURIComponent(sanitizedQuery)}`))
                .then(response => response.json())
                .then(data => {
                    mobileSearchResults.innerHTML = "";
                    if (data.length > 0) {
                        mobileSearchResults.style.display = "block";
                        data.forEach(product => {
                            const productUrl = window.pdWithBase(window.pdBuildProductPath(product));
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
                        mobileSearchResults.style.display = "block";
                        mobileSearchResults.innerHTML = "<div class='search-item search-no-results'>No results found</div>";
                    }
                })
                .catch(error => console.error("Error fetching mobile search results:", error));
        }, 300);
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

    let desktopSearchTimer = null;
    searchInput.addEventListener("input", function () {
        let query = searchInput.value;
        let sanitizedQuery = sanitizeInput(query);

        if (sanitizedQuery !== query) searchInput.value = sanitizedQuery;

        if (sanitizedQuery.trim().length < 2) {
            searchResults.innerHTML = "";
            closeButton.style.display = "none";
            return;
        }

        clearTimeout(desktopSearchTimer);
        desktopSearchTimer = setTimeout(function () {
            fetch(window.pdWithBase(`/public/ajax/search_products.php?query=${encodeURIComponent(sanitizedQuery)}`))
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = "";
                    if (data.length > 0) {
                        closeButton.style.display = "flex";
                        data.forEach(product => {
                            const productUrl = window.pdWithBase(window.pdBuildProductPath(product));
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
                        searchResults.innerHTML = "<li class=\"search-no-results\">No results found</li>";
                    }
                })
                .catch(error => console.error("Error fetching search results:", error));
        }, 300);
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
