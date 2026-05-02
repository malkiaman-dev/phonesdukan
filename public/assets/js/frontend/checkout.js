// ================================================================
// INLINE FIELD-LEVEL VALIDATION HELPERS
// ================================================================

function showFieldError(fieldEl, message) {
    var formGroup = fieldEl ? fieldEl.closest('.form-group') : null;
    if (!formGroup) return;

    // Remove any existing error in this group
    var existing = formGroup.querySelector('.co-field-error');
    if (existing) existing.remove();

    // For <select> (hidden real select behind the custom dropdown),
    // apply the red border to the visible trigger div instead.
    var visualEl = fieldEl;
    if (fieldEl.tagName === 'SELECT') {
        var trigger = formGroup.querySelector('.co-select-trigger');
        if (trigger) visualEl = trigger;
    }
    visualEl.classList.add('co-input--invalid');

    var errEl = document.createElement('p');
    errEl.className = 'co-field-error';
    errEl.textContent = message;
    formGroup.appendChild(errEl);
}

function clearFieldError(fieldEl) {
    var formGroup = fieldEl ? fieldEl.closest('.form-group') : null;
    if (!formGroup) return;
    var existing = formGroup.querySelector('.co-field-error');
    if (existing) existing.remove();
    formGroup.querySelectorAll('.co-input--invalid').forEach(function (el) {
        el.classList.remove('co-input--invalid');
    });
}

function clearAllErrors() {
    document.querySelectorAll('.co-field-error').forEach(function (el) { el.remove(); });
    document.querySelectorAll('.co-input--invalid').forEach(function (el) {
        el.classList.remove('co-input--invalid');
    });
}

// ================================================================
// FORM VALIDATION  —  called from onsubmit="return validateAll()"
// ================================================================

function validateAll() {
    clearAllErrors();

    var hasError     = false;
    var firstErrorEl = null;

    function flag(fieldEl, msg) {
        showFieldError(fieldEl, msg);
        if (!firstErrorEl) firstErrorEl = fieldEl;
        hasError = true;
    }

    // ── Full Name ──
    var nameEl = document.querySelector('[name="customer_name"]');
    if (nameEl && !/^[A-Za-z\s]{3,50}$/.test(nameEl.value.trim())) {
        flag(nameEl, 'Please enter a valid name (letters and spaces, 3–50 characters).');
    }

    // ── Email ──
    var emailEl = document.getElementById('customer_email');
    if (emailEl && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim())) {
        flag(emailEl, 'Please enter a valid email address.');
    }

    // ── Phone ──
    var phoneEl = document.getElementById('customer_phone');
    if (phoneEl && !/^03\d{9}$/.test(phoneEl.value.trim())) {
        flag(phoneEl, 'Enter an 11-digit phone number starting with 03.');
    }

    // ── Shipping Address ──
    var addressEl = document.querySelector('textarea[name="shipping_address"]');
    if (addressEl && !addressEl.value.trim()) {
        flag(addressEl, 'Shipping address is required.');
    }

    // ── City ──
    var cityEl = document.querySelector('input[name="shipping_city"]');
    if (cityEl && !cityEl.value.trim()) {
        flag(cityEl, 'City is required.');
    }

    // ── Country (reads from hidden real <select>) ──
    var countryEl = document.querySelector('select[name="shipping_country"]');
    if (countryEl && !countryEl.value.trim()) {
        flag(countryEl, 'Please select a country.');
    }

    // ── Payment screenshot (prepaid only) ──
    var pmEl = document.querySelector('input[name="payment_method"]');
    if (pmEl && pmEl.value === 'prepaid') {
        var screenshotEl = document.getElementById('payment_screenshot');
        if (screenshotEl && !screenshotEl.value) {
            flag(screenshotEl, 'Payment screenshot is required for prepaid.');
        }
    }

    if (hasError) {
        // Scroll to first invalid field — for the custom select, scroll to its trigger
        var scrollTarget = firstErrorEl;
        if (firstErrorEl && firstErrorEl.tagName === 'SELECT') {
            var fg = firstErrorEl.closest('.form-group');
            var trig = fg ? fg.querySelector('.co-select-trigger') : null;
            if (trig) scrollTarget = trig;
        }
        if (scrollTarget) {
            scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }

    return true;
}

// ================================================================
// MODAL FUNCTIONS
// showModal is a no-op — validation is now inline.
// closeModal kept functional for the HTML onclick attributes.
// ================================================================

function showModal(message) {
    // Replaced by inline field errors — intentional no-op.
}

function closeModal() {
    var vm = document.getElementById('validationModal');
    var pm = document.getElementById('phoneModal');
    if (vm) vm.style.display = 'none';
    if (pm) pm.style.display = 'none';
}

// ================================================================
// COPY ACCOUNT NUMBER  (unchanged)
// ================================================================

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number (handles nesting like your <ol>)
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        console.error('No account number found—check data-copy attr');
        return;
    }

    console.log('Attempting copy:', accountNum); // Debug: See in DevTools

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            console.log('Copied:', accountNum);
            feedbackSuccess(icon);
        }).catch(err => {
            console.error('Clipboard API failed:', err);
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function feedbackSuccess(icon) {
    const originalTitle = icon.title || 'Copy Account Number';
    icon.title = 'Copied!';
    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }
    setTimeout(() => { icon.title = originalTitle; }, 2000);
}

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number (handles nesting like your <ol>)
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        return;
    }

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            feedbackSuccess(icon);
        }).catch(err => {
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function feedbackSuccess(icon) {
    const originalTitle = icon.title || 'Copy Account Number';
    icon.title = 'Copied!';
    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }
    setTimeout(() => { icon.title = originalTitle; }, 2000);
}

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        return;
    }

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            showCopyFeedback(icon);
        }).catch(err => {
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function showCopyFeedback(icon) {
    // Create/remove tooltip
    let tooltip = icon.parentNode.querySelector('.copied-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('span');
        tooltip.className = 'copied-tooltip';
        tooltip.textContent = 'Copied';
        icon.parentNode.appendChild(tooltip); // Append to wrapper
    }
    tooltip.classList.add('show');

    // Green icon tint
    icon.classList.add('copied');

    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }

    // Reset after 2s
    setTimeout(() => {
        tooltip.classList.remove('show');
        setTimeout(() => { icon.parentNode.removeChild(tooltip); }, 300); // Clean up
        icon.classList.remove('copied');
    }, 2000);
}

function fallbackCopy(text, icon) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px'; // Off-screen
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(icon);
        } else {
            alert('Copy failed—select and copy manually: ' + text); // Last resort
        }
    } catch (err) {
        // Silent fail on catch
    } finally {
        document.body.removeChild(textArea);
    }
}

// ================================================================
// DOM READY  —  real-time error clearing + custom country select
// ================================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Real-time error clearing ──────────────────────────────
    // Each field clears its own error as the user types/changes it.

    function bindClear(el) {
        if (!el) return;
        var evt = (el.tagName === 'SELECT' || el.type === 'file') ? 'change' : 'input';
        el.addEventListener(evt, function () { clearFieldError(this); });
    }

    bindClear(document.querySelector('[name="customer_name"]'));
    bindClear(document.getElementById('customer_email'));
    bindClear(document.getElementById('customer_phone'));
    bindClear(document.querySelector('textarea[name="shipping_address"]'));
    bindClear(document.querySelector('input[name="shipping_city"]'));
    bindClear(document.getElementById('payment_screenshot'));
    // Country is cleared inside pickOption below

    // ================================================================
    // CUSTOM COUNTRY SELECT
    // Syncs selected value back to the hidden <select name="shipping_country">
    // so form submission and validateAll() remain untouched.
    // ================================================================

    var wrapper  = document.getElementById('coCountrySelect');
    if (!wrapper) return;

    var trigger      = document.getElementById('co-country-trigger');
    var panel        = document.getElementById('co-country-panel');
    var displayEl    = document.getElementById('co-country-display');
    var realSelect   = document.getElementById('shipping_country');
    var options      = panel.querySelectorAll('.co-select-option');
    var isOpen       = false;
    var focusedIdx   = -1;

    // ── Open / close ──────────────────────────────────────────
    function openPanel() {
        isOpen = true;
        panel.classList.add('co-select-panel--open');
        trigger.classList.add('co-select-trigger--open');
        trigger.setAttribute('aria-expanded', 'true');
        var sel = panel.querySelector('.co-select-option--selected');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    }

    function closePanel() {
        isOpen = false;
        panel.classList.remove('co-select-panel--open');
        trigger.classList.remove('co-select-trigger--open');
        trigger.setAttribute('aria-expanded', 'false');
        focusedIdx = -1;
        options.forEach(function (o) { o.classList.remove('co-select-option--focused'); });
    }

    // ── Select an option ──────────────────────────────────────
    function pickOption(value, label, isPlaceholder) {
        displayEl.textContent = label;

        if (isPlaceholder) {
            displayEl.classList.remove('co-select-display--chosen');
        } else {
            displayEl.classList.add('co-select-display--chosen');
        }

        realSelect.value = value;

        options.forEach(function (opt) {
            var isThis = opt.dataset.value === value;
            opt.classList.toggle('co-select-option--selected', isThis);
            opt.setAttribute('aria-selected', isThis ? 'true' : 'false');
        });

        closePanel();

        // Clear country validation error as soon as a real option is picked
        if (value) {
            clearFieldError(realSelect);
        }
    }

    // ── Keyboard navigation ───────────────────────────────────
    function moveFocus(delta) {
        focusedIdx = Math.min(Math.max(focusedIdx + delta, 0), options.length - 1);
        options.forEach(function (o) { o.classList.remove('co-select-option--focused'); });
        options[focusedIdx].classList.add('co-select-option--focused');
        options[focusedIdx].scrollIntoView({ block: 'nearest' });
    }

    // ── Events ────────────────────────────────────────────────

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        isOpen ? closePanel() : openPanel();
    });

    options.forEach(function (opt) {
        opt.addEventListener('click', function (e) {
            e.stopPropagation();
            pickOption(opt.dataset.value, opt.textContent.trim(), opt.dataset.value === '');
        });

        opt.addEventListener('mouseenter', function () {
            options.forEach(function (o) { o.classList.remove('co-select-option--focused'); });
            opt.classList.add('co-select-option--focused');
            focusedIdx = Array.prototype.indexOf.call(options, opt);
        });
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) { closePanel(); }
    });

    trigger.addEventListener('keydown', function (e) {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                if (!isOpen) {
                    openPanel();
                } else if (focusedIdx >= 0) {
                    var opt = options[focusedIdx];
                    pickOption(opt.dataset.value, opt.textContent.trim(), opt.dataset.value === '');
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (!isOpen) { openPanel(); focusedIdx = -1; }
                moveFocus(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (!isOpen) { openPanel(); focusedIdx = options.length; }
                moveFocus(-1);
                break;
            case 'Escape':
            case 'Tab':
                closePanel();
                break;
        }
    });

    // If PHP pre-fills a value (e.g. on validation error re-render), reflect it
    if (realSelect.value) {
        var preLabel = realSelect.options[realSelect.selectedIndex]
                       ? realSelect.options[realSelect.selectedIndex].text : '';
        if (preLabel) pickOption(realSelect.value, preLabel, false);
    }
});
