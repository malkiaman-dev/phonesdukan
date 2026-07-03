(function (window, document) {
    "use strict";

    var MOBILE_QUERY = "(max-width: 992px)";

    function isMobileViewport() {
        return window.matchMedia && window.matchMedia(MOBILE_QUERY).matches;
    }

    function isPhonesDukanApp() {
        if (window.PDApp && typeof window.PDApp.is === "function") {
            return window.PDApp.is();
        }
        if (/PhonesDukanApp/i.test(navigator.userAgent || "")) {
            return true;
        }
        try {
            if (window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp()) {
                return true;
            }
        } catch (e) {}
        return document.documentElement.getAttribute("data-pd-app") === "1";
    }

    function markPdAppClient() {
        if (!isPhonesDukanApp()) {
            return;
        }
        document.documentElement.setAttribute("data-pd-app", "1");
        try { document.cookie = "pd_app=1;path=/;max-age=31536000;SameSite=Lax"; } catch (e) {}
        try { localStorage.setItem("pd_app", "1"); } catch (e) {}
    }

    function readNativeInset() {
        try {
            if (window.PhonesDukanNative && typeof window.PhonesDukanNative.getStatusBarHeight === "function") {
                var nativePx = parseFloat(window.PhonesDukanNative.getStatusBarHeight());
                if (!isNaN(nativePx) && nativePx > 0) {
                    return nativePx;
                }
            }
        } catch (e) {}
        return 0;
    }

    function resolveSafeAreaTop() {
        if (!isPhonesDukanApp()) {
            return 0;
        }

        // Android WebView already applies status bar padding to the viewport.
        return 0;
    }

    function applyChromePadding(inset) {
        var root = document.documentElement;
        var chrome = document.getElementById("pd-site-chrome");
        var safeFill = chrome ? chrome.querySelector(".pd-chrome-safe-fill") : null;
        var safeAreaTop = chrome ? chrome.querySelector(".pd-safe-area-top") : null;
        var statusSlot = chrome ? chrome.querySelector(".pd-status-bar-slot") : null;
        var insetPx = isPhonesDukanApp()
            ? Math.max(0, Math.round(readNativeInset()))
            : Math.max(0, Math.round(inset));

        if (isPhonesDukanApp()) {
            markPdAppClient();
        } else {
            root.removeAttribute("data-pd-app");
            root.style.removeProperty("--pd-app-status-inset");
        }

        root.style.setProperty("--pd-app-status-inset", insetPx + "px");
        root.style.setProperty("--pd-chrome-pad-top", insetPx + "px");
        root.style.setProperty("--safe-area-top", insetPx + "px");
        root.style.setProperty("--pd-status-inset", insetPx + "px");
        root.dataset.pdSafeArea = String(insetPx);

        if (chrome) {
            chrome.style.paddingTop = "0px";
            chrome.style.top = "";
        }

        if (safeFill) {
            safeFill.style.display = "none";
            safeFill.style.height = "0px";
            safeFill.style.minHeight = "0px";
        }

        if (safeAreaTop) {
            safeAreaTop.style.display = "none";
            safeAreaTop.style.height = "0px";
            safeAreaTop.style.minHeight = "0px";
        }

        if (statusSlot) {
            statusSlot.style.display = "none";
            statusSlot.style.height = "0px";
            statusSlot.style.minHeight = "0px";
        }

        refreshChromeOffset(insetPx);
    }

    function refreshChromeOffset(insetPx) {
        var root = document.documentElement;
        var announcementTrack = document.querySelector(".pd-announcement-track");
        var headerStack = document.querySelector(".pd-header-stack");
        var annH = announcementTrack
            ? announcementTrack.offsetHeight
            : readCssPx(getComputedStyle(root).getPropertyValue("--announcement-height"));
        var headerH = headerStack
            ? headerStack.offsetHeight
            : readCssPx(getComputedStyle(root).getPropertyValue("--header-height"));

        if (announcementTrack && announcementTrack.offsetHeight) {
            root.style.setProperty("--announcement-height", annH + "px");
        }
        if (headerStack && headerStack.offsetHeight) {
            root.style.setProperty("--header-height", headerH + "px");
        }

        var padTop = isPhonesDukanApp() ? insetPx : readCssPx(getComputedStyle(root).getPropertyValue("--pd-chrome-pad-top"));
        root.style.setProperty("--pd-chrome-offset", Math.round(padTop + annH + headerH) + "px");

        if (window.PDChromeMetrics && typeof window.PDChromeMetrics.update === "function") {
            window.PDChromeMetrics.update();
        }
    }

    function readCssPx(value) {
        return parseFloat(value) || 0;
    }

    function applySafeAreaTop() {
        applyChromePadding(resolveSafeAreaTop());
    }

    window.PDSafeArea = {
        apply: applySafeAreaTop,
        measure: resolveSafeAreaTop
    };

    applySafeAreaTop();

    window.addEventListener("load", function () {
        window.setTimeout(applySafeAreaTop, 50);
    }, { passive: true });
    window.addEventListener("resize", applySafeAreaTop, { passive: true });
    window.addEventListener("orientationchange", function () {
        window.setTimeout(applySafeAreaTop, 120);
    }, { passive: true });

    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", applySafeAreaTop, { passive: true });
    }
})(window, document);
