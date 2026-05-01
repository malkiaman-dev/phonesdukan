document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.querySelector('.search-btn');
    const searchModal = document.querySelector('.search-modal');
    const closeBtn = document.querySelector('.close-btn');
    const logoCol = document.querySelector('.logo-col');
    const navCol = document.querySelector('.nav-col');
    const searchInput = document.querySelector('.search-input');
    const searchSuggestions = document.querySelector('.search-suggestions');
    const menuToggle = document.querySelector('.menu-toggle');
    const menuClose = document.querySelector('.menu-close');

    // Toggle mobile menu
    if (menuToggle && navCol) {
        menuToggle.addEventListener('click', () => {
            navCol.classList.toggle('active');
            if (navCol.classList.contains('active') && searchModal) {
                searchModal.classList.remove('active');
                if (searchSuggestions) searchSuggestions.style.display = 'none';
            }
        });
    }

    // Close mobile menu
    if (menuClose && navCol) {
        menuClose.addEventListener('click', () => {
            navCol.classList.remove('active');
        });
    }

    // Toggle search modal
    if (searchBtn && searchModal) {
        searchBtn.addEventListener('click', () => {
            searchModal.classList.toggle('active');
            if (logoCol) logoCol.style.display = searchModal.classList.contains('active') ? 'none' : 'block';
            if (navCol) navCol.classList.remove('active');
            if (searchModal.classList.contains('active') && searchInput) {
                searchInput.focus();
            }
        });
    }

    // Close search modal
    if (closeBtn && searchModal) {
        closeBtn.addEventListener('click', () => {
            searchModal.classList.remove('active');
            if (logoCol) logoCol.style.display = 'block';
            if (searchSuggestions) searchSuggestions.style.display = 'none';
        });
    }

    // Close modal or menu when clicking outside
    document.addEventListener('click', (e) => {
        if (searchModal && searchBtn && !searchModal.contains(e.target) && !searchBtn.contains(e.target)) {
            searchModal.classList.remove('active');
            if (logoCol) logoCol.style.display = 'block';
            if (searchSuggestions) searchSuggestions.style.display = 'none';
        }
        if (navCol && menuToggle && !navCol.contains(e.target) && !menuToggle.contains(e.target)) {
            navCol.classList.remove('active');
        }
    });

    // Fetch search suggestions
    if (searchInput && searchSuggestions) {
        searchInput.addEventListener('input', async () => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                searchSuggestions.style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`${window.location.origin}/?action=get_search_suggestions&s=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                searchSuggestions.innerHTML = '';

                if (suggestions.length > 0) {
                    const ul = document.createElement('ul');
                    suggestions.forEach(suggestion => {
                        const li = document.createElement('li');
                        li.textContent = suggestion.title;
                        li.addEventListener('click', () => {
                            window.location.href = `${window.location.origin}/blog/${suggestion.category_slug}/${suggestion.slug}`;
                        });
                        ul.appendChild(li);
                    });
                    searchSuggestions.appendChild(ul);
                    searchSuggestions.style.display = 'block';
                } else {
                    searchSuggestions.style.display = 'none';
                }
            } catch (error) {
                console.error('Error fetching suggestions:', error);
                searchSuggestions.style.display = 'none';
            }
        });
    }
});