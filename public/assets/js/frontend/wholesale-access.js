(function () {
    'use strict';

    const WS_INVALID_CODE_MSG = 'Your entered password is incorrect';

    const withBase = window.pdWithBase || function (path) {
        const basePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    function hasAccess() {
        return window.__PD_WHOLESALE_ACCESS__ === true;
    }

    function isWholesaleUrl(href) {
        if (!href) return false;
        try {
            const url = new URL(href, window.location.origin);
            const path = url.pathname.replace(/\/+$/, '');
            const wholesalePath = withBase('/wholesale').replace(/\/+$/, '');
            return path === wholesalePath || path.endsWith('/wholesale');
        } catch (e) {
            return /\/wholesale\/?$/.test(href);
        }
    }

    function clearWsCodeError(input, errorEl) {
        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = '';
        }
        const field = input ? input.closest('.ws-password-field') : null;
        if (field) field.classList.remove('is-error');
        if (input) input.classList.remove('is-error');
    }

    function showWsCodeError(input, errorEl, message) {
        const msg = message || WS_INVALID_CODE_MSG;
        if (errorEl) {
            errorEl.textContent = msg;
            errorEl.hidden = false;
        }
        const field = input ? input.closest('.ws-password-field') : null;
        if (field) field.classList.add('is-error');
        if (input) {
            input.classList.add('is-error');
            input.focus();
        }
    }

    function closeSidebarIfOpen() {
        if (window.PDSidebar && typeof window.PDSidebar.close === 'function') {
            window.PDSidebar.close();
            return;
        }

        const sidebar = document.getElementById('sidebar');
        const sidebarContainer = document.getElementById('sidebar-container');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const pageContent = document.querySelector('.page-content');
        if (!sidebar || !sidebarContainer || !sidebarOverlay) return;

        sidebar.classList.remove('open');
        sidebarContainer.classList.remove('open');
        sidebarOverlay.classList.remove('open');
        if (pageContent) pageContent.classList.remove('dimmed');
        document.body.classList.remove('dimmed');
    }

    function openModal() {
        closeSidebarIfOpen();

        const modal = document.getElementById('ws-access-modal');
        const input = document.getElementById('ws-access-code');
        const errorEl = document.getElementById('ws-access-error');
        if (!modal) return;

        clearWsCodeError(input, errorEl);
        if (input) input.value = '';
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (input) input.focus();
    }

    function closeModal() {
        const modal = document.getElementById('ws-access-modal');
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function verifyCode(code) {
        return fetch(withBase('/wholesale-verify'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ code }),
        }).then(function (res) {
            return res.text().then(function (text) {
                let data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    data = {};
                }
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    }

    function bindPasswordToggles() {
        document.querySelectorAll('.ws-password-field').forEach(function (field) {
            const input = field.querySelector('.ws-password-input');
            const toggle = field.querySelector('.ws-password-toggle');
            const eyeOpen = field.querySelector('.ws-eye-open');
            const eyeClosed = field.querySelector('.ws-eye-closed');
            if (!input || !toggle) return;

            toggle.addEventListener('click', function () {
                const nowVisible = input.type === 'password';
                input.type = nowVisible ? 'text' : 'password';
                toggle.setAttribute('aria-label', nowVisible ? 'Hide shopkeeper code' : 'Show shopkeeper code');
                toggle.setAttribute('title', nowVisible ? 'Hide code' : 'Show code');
                if (eyeOpen) eyeOpen.toggleAttribute('hidden', nowVisible);
                if (eyeClosed) eyeClosed.toggleAttribute('hidden', !nowVisible);
            });
        });
    }

    function bindModal() {
        const modal = document.getElementById('ws-access-modal');
        const form = document.getElementById('ws-access-form');
        const input = document.getElementById('ws-access-code');
        const errorEl = document.getElementById('ws-access-error');
        const submitBtn = document.getElementById('ws-access-submit');

        if (!modal || !form) return;

        input.addEventListener('input', function () {
            clearWsCodeError(input, errorEl);
        });

        modal.querySelectorAll('[data-ws-access-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) closeModal();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const code = (input.value || '').trim();
            if (!code) return;

            clearWsCodeError(input, errorEl);
            submitBtn.disabled = true;

            verifyCode(code)
                .then(function (result) {
                    if (result.ok && result.data.status === 'success') {
                        window.__PD_WHOLESALE_ACCESS__ = true;
                        closeModal();
                        window.location.href = withBase('/wholesale');
                        return;
                    }
                    // Genuine wrong code: server responds 403 with a JSON error payload.
                    if (result.status === 403 && result.data && result.data.status === 'error') {
                        showWsCodeError(input, errorEl, WS_INVALID_CODE_MSG);
                        return;
                    }
                    // Anything else (missing endpoint, HTML response, 404/500) is a server problem.
                    showWsCodeError(input, errorEl, 'Something went wrong. Please try again.');
                })
                .catch(function () {
                    showWsCodeError(input, errorEl, 'Something went wrong. Please try again.');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });
    }

    function bindLinks() {
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a.js-wholesale-link, a[href*="/wholesale"]');
            if (!link) return;
            if (link.classList.contains('js-wholesale-skip-gate')) return;
            if (!isWholesaleUrl(link.href)) return;
            if (hasAccess()) return;

            e.preventDefault();
            openModal();
        });
    }

    window.pdOpenWholesaleAccess = function (onSuccess) {
        if (hasAccess()) {
            if (typeof onSuccess === 'function') onSuccess();
            else window.location.href = withBase('/wholesale');
            return;
        }
        openModal();
    };

    window.pdWsCodeForm = {
        invalidMessage: WS_INVALID_CODE_MSG,
        showError: showWsCodeError,
        clearError: clearWsCodeError,
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindPasswordToggles();
        bindModal();
        bindLinks();
    });
})();
