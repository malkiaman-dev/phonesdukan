<?php
ob_start();

$pageTitle       = 'Wholesale Portal - Shopkeeper Access';
$metaDescription = 'B2B wholesale portal for registered shopkeepers at Phones Dukan Pakistan.';
$metaKeywords    = 'wholesale mobile accessories, shopkeeper portal, B2B Pakistan';
$metaRobots      = 'noindex, nofollow';

require_once dirname(__DIR__, 3) . '/includes/header.php';
?>

<section class="ws-hero">
    <div class="ws-hero-inner">
        <p class="ws-hero-eyebrow">B2B Wholesale Portal</p>
        <h1 class="ws-hero-title"><span>Shopkeeper</span> Access Only</h1>
        <p class="ws-hero-sub">This section is reserved for registered shopkeepers. Enter your shopkeeper code to continue.</p>
    </div>
</section>

<div class="ws-container">
    <div class="ws-gate-card">
        <div class="ws-gate-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h2>Enter Shopkeeper Code</h2>
        <p>Use the code provided by Phones Dukan to access wholesale pricing and bulk ordering.</p>

        <form id="ws-gate-form" class="ws-gate-form" autocomplete="off">
            <label for="ws-gate-code">Shopkeeper Code</label>
            <div class="ws-password-field">
                <input type="password" id="ws-gate-code" name="code" class="ws-password-input" placeholder="Enter your code" required autocomplete="off">
                <button type="button" class="ws-password-toggle" aria-label="Show shopkeeper code" title="Show code">
                    <svg class="ws-eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="ws-eye-closed" hidden xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <p id="ws-gate-error" class="ws-gate-error" hidden></p>
            <button type="submit" class="ws-submit-btn" id="ws-gate-submit">
                Unlock Wholesale Portal
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ws-gate-form');
    const input = document.getElementById('ws-gate-code');
    const errorEl = document.getElementById('ws-gate-error');
    const submitBtn = document.getElementById('ws-gate-submit');
    const withBase = window.pdWithBase || function (path) {
        const basePath = String(window.__PD_BASE_PATH__ || '').replace(/\/+$/, '');
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const code = input.value.trim();
        if (!code) return;

        errorEl.hidden = true;
        submitBtn.disabled = true;

        fetch(withBase('/wholesale-verify'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ code }),
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data }; }); })
            .then(function (result) {
                if (result.ok && result.data.status === 'success') {
                    window.location.reload();
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
});
</script>

<?php
ob_end_flush();
require_once dirname(__DIR__, 3) . '/includes/footer.php';
?>
