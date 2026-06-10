(function () {
    'use strict';

    var OPTIONAL_MARKERS = '.field-optional, .ep-field-optional, .optional-label, [data-optional="true"]';
    var FIELD_WRAPPERS = [
        '.cp-field', '.ep-field', '.vp-field', '.form-group', '.field',
        '.attr-card .field', '.rev-field', '.mu-field', '.cus-field',
        '.set-field', '.pro-field', '.post-field', '.login-field'
    ].join(', ');

    function hasOptionalMarker(label) {
        return label && (label.matches(OPTIONAL_MARKERS) || label.querySelector(OPTIONAL_MARKERS));
    }

    function normalizePlainAsterisk(label) {
        if (!label || label.querySelector('.req-star')) {
            return;
        }

        var html = label.innerHTML;
        if (!/\*/.test(html)) {
            return;
        }

        label.innerHTML = html
            .replace(/\s*\*(?=\s*<\/)/g, ' <span class="req-star" aria-hidden="true">*</span>')
            .replace(/\s+\*(?=\s*<span[^>]*class="[^"]*(?:field-optional|ep-field-optional))/g, ' ')
            .replace(/([^>])\s*\*(?=\s*$)/, '$1 <span class="req-star" aria-hidden="true">*</span>');
    }

    function appendReqStar(label) {
        if (!label || hasOptionalMarker(label) || label.querySelector('.req-star')) {
            return;
        }

        normalizePlainAsterisk(label);
        if (label.querySelector('.req-star')) {
            return;
        }

        var star = document.createElement('span');
        star.className = 'req-star';
        star.setAttribute('aria-hidden', 'true');
        star.textContent = '*';

        var textSpan = label.querySelector(':scope > span:first-child');
        if (textSpan && !textSpan.querySelector('.req-star') && !textSpan.querySelector(OPTIONAL_MARKERS)) {
            textSpan.appendChild(document.createTextNode(' '));
            textSpan.appendChild(star);
            return;
        }

        label.appendChild(document.createTextNode(' '));
        label.appendChild(star);
    }

    function findLabelForField(field) {
        if (!field) {
            return null;
        }

        if (field.id) {
            var escaped = typeof CSS !== 'undefined' && CSS.escape
                ? CSS.escape(field.id)
                : field.id.replace(/"/g, '\\"');
            var byFor = document.querySelector('label[for="' + escaped + '"]');
            if (byFor) {
                return byFor;
            }
        }

        var wrap = field.closest(FIELD_WRAPPERS) || field.closest('.ep-field');
        if (wrap) {
            var hdrLabel = wrap.querySelector('.ep-seo-field-hdr label, .ep-img-field-header label');
            if (hdrLabel) {
                return hdrLabel;
            }
            return wrap.querySelector('label');
        }

        return null;
    }

    function markRequiredFields(root) {
        root = root || document;

        root.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
            if (field.type === 'hidden' || field.offsetParent === null && field.type !== 'radio') {
                return;
            }
            appendReqStar(findLabelForField(field));
        });

        root.querySelectorAll('label').forEach(function (label) {
            normalizePlainAsterisk(label);
        });
    }

    function init() {
        markRequiredFields(document);

        if (typeof MutationObserver !== 'undefined') {
            var pending = false;
            var observer = new MutationObserver(function (mutations) {
                if (pending) {
                    return;
                }
                pending = true;
                window.requestAnimationFrame(function () {
                    pending = false;
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                markRequiredFields(node);
                            }
                        });
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
