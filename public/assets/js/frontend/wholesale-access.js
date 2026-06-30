(function () {
    'use strict';

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

    function openModal() {
        const modal = document.getElementById('ws-access-modal');
        const input = document.getElementById('ws-access-code');
        const errorEl = document.getElementById('ws-access-error');
        if (!modal) return;

        errorEl.hidden = true;
        errorEl.textContent = '';
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
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
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

            errorEl.hidden = true;
            submitBtn.disabled = true;

            verifyCode(code)
                .then(function (result) {
                    if (result.ok && result.data.status === 'success') {
                        window.__PD_WHOLESALE_ACCESS__ = true;
                        closeModal();
                        window.location.href = withBase('/wholesale');
                        return;
                    }
                    errorEl.textContent = result.data.message || 'Invalid shopkeeper code.';
                    errorEl.hidden = false;
                })
                .catch(function () {
                    errorEl.textContent = 'Something went wrong. Please try again.';
                    errorEl.hidden = false;
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

    document.addEventListener('DOMContentLoaded', function () {
        bindPasswordToggles();
        bindModal();
        bindLinks();
    });
})();
