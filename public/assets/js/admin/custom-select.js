(function () {
    function initCustomSelect(root) {
        const select = root.querySelector('select.native-select');
        const btn = root.querySelector('.custom-select-btn');
        const valueEl = root.querySelector('.custom-select-value');
        const menu = root.querySelector('.custom-select-menu');
        if (!select || !btn || !valueEl || !menu) return;

        function renderOptions() {
            menu.innerHTML = '';
            Array.from(select.options).forEach(function (opt, idx) {
                const item = document.createElement('div');
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = opt.value;
                item.dataset.index = String(idx);
                item.textContent = opt.textContent;
                if (opt.selected) item.classList.add('is-selected');
                menu.appendChild(item);
            });
        }

        function syncFromNative() {
            const selected = select.options[select.selectedIndex];
            valueEl.textContent = selected ? selected.textContent : 'Select';
            Array.from(menu.querySelectorAll('.custom-select-option')).forEach(function (el) {
                el.classList.toggle('is-selected', el.dataset.value === (selected ? selected.value : ''));
            });
        }

        function openMenu() {
            root.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            menu.focus();
            syncFromNative();
        }

        function closeMenu() {
            root.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }

        renderOptions();
        syncFromNative();

        btn.addEventListener('click', function () {
            if (root.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        menu.addEventListener('click', function (e) {
            const optEl = e.target.closest('.custom-select-option');
            if (!optEl) return;
            select.value = optEl.dataset.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncFromNative();
            closeMenu();
            btn.focus();
        });

        menu.addEventListener('keydown', function (e) {
            const options = Array.from(menu.querySelectorAll('.custom-select-option'));
            const selectedIndex = Math.max(0, options.findIndex(function (o) {
                return o.classList.contains('is-selected');
            }));
            let nextIndex = selectedIndex;

            if (e.key === 'Escape') {
                e.preventDefault();
                closeMenu();
                btn.focus();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                nextIndex = Math.min(options.length - 1, selectedIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                nextIndex = Math.max(0, selectedIndex - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const chosen = options[selectedIndex];
                if (chosen) chosen.click();
                return;
            } else {
                return;
            }

            const next = options[nextIndex];
            if (next) {
                options.forEach(function (o) { o.classList.remove('is-selected'); });
                next.classList.add('is-selected');
                next.scrollIntoView({ block: 'nearest' });
            }
        });

        select.addEventListener('change', syncFromNative);

        root._customSelectRefresh = function () {
            renderOptions();
            syncFromNative();
        };

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closeMenu();
            }
        });
    }

    window.refreshCustomSelect = function (selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const root = select.closest('.custom-select');
        if (root && typeof root._customSelectRefresh === 'function') {
            root._customSelectRefresh();
        }
    };

    function boot() {
        document.querySelectorAll('.custom-select').forEach(initCustomSelect);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
