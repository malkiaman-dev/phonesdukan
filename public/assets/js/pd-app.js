(function (window, document) {
    "use strict";

    /**
     * True only inside the native Android app shell (not mobile/desktop browser).
     * Relies on UA suffix, native bridge, or server-rendered data-pd-app="1".
     */
    function isApp() {
        var ua = navigator.userAgent || "";
        if (/PhonesDukanApp/i.test(ua)) {
            return true;
        }
        try {
            if (window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp()) {
                return true;
            }
        } catch (e) {}
        return document.documentElement.getAttribute("data-pd-app") === "1";
    }

    function persistAppSession() {
        try {
            var cookie = "pd_app=1;path=/;max-age=31536000;SameSite=Lax";
            if (/phonesdukan\.com$/i.test(location.hostname)) {
                cookie += ";domain=.phonesdukan.com";
            }
            document.cookie = cookie;
        } catch (e) {}
        try {
            localStorage.setItem("pd_app", "1");
        } catch (e) {}
    }

    function markApp() {
        if (!isApp()) {
            return;
        }
        document.documentElement.setAttribute("data-pd-app", "1");
        persistAppSession();
    }

    function removeInstallWidget() {
        if (!isApp()) {
            return;
        }
        markApp();
        ["pd-install-app-btn", "pd-install-app-panel", "pd-download-app-btn", "pd-chatbot-toggle", "pd-chatbot-win"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        });
        document.querySelectorAll(".pd-chatbot-desktop, .pd-chatbot-fab, #pd-chatbot-form").forEach(function (el) {
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        });
    }

    function init() {
        if (!isApp()) {
            return;
        }
        markApp();
        removeInstallWidget();
        if (typeof MutationObserver !== "undefined") {
            new MutationObserver(removeInstallWidget).observe(document.documentElement, {
                childList: true,
                subtree: true
            });
        }
    }

    window.PDApp = {
        is: isApp,
        mark: markApp,
        removeInstallWidget: removeInstallWidget
    };

    init();

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", removeInstallWidget);
    } else {
        removeInstallWidget();
    }
})(window, document);
