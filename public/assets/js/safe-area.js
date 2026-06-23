(function (window, document) {
    "use strict";

    var MOBILE_QUERY = "(max-width: 992px)";
    var MOBILE_MIN_INSET = 32;

    function hasPdAppCookie() {
        try {
            return /(?:^|;\s*)pd_app=1(?:;|$)/.test(document.cookie || "");
        } catch (e) {}
        return false;
    }

    function isMobileViewport() {
        return window.matchMedia && window.matchMedia(MOBILE_QUERY).matches;
    }

    function isPhonesDukanApp() {
        if (/PhonesDukanApp/i.test(navigator.userAgent || "")) {
            return true;
        }
        if (/[?&]pd_app=1(?:&|$)/.test(window.location.search || "")) {
            return true;
        }
        if (hasPdAppCookie()) {
            return true;
        }
        try {
            if (window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp()) {
                return true;
            }
        } catch (e) {}
        return document.documentElement.getAttribute("data-pd-app") === "1";
    }

    function isTouchMobile() {
        if (isMobileViewport()) {
            return true;
        }
        return (navigator.maxTouchPoints || 0) > 0 && window.innerWidth <= 992;
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
                if (nativePx > 0) {
                    return nativePx;
                }
            }
        } catch (e) {}
        return 0;
    }

    function readVisualViewportInset() {
        if (!window.visualViewport) {
            return 0;
        }
        return Math.max(0, Math.round(window.visualViewport.offsetTop || 0));
    }

    function measureEnvSafeArea() {
        var root = document.documentElement;
        var probe = document.createElement("div");
        probe.style.cssText =
            "position:fixed;top:0;left:0;width:0;" +
            "padding-top:constant(safe-area-inset-top);" +
            "padding-top:env(safe-area-inset-top);" +
            "visibility:hidden;pointer-events:none;";
        root.appendChild(probe);
        var inset = probe.offsetHeight;
        if (!inset) {
            inset = parseFloat(window.getComputedStyle(probe).paddingTop) || 0;
        }
        probe.remove();
        return inset;
    }

    function estimateAndroidSafeArea() {
        var dpr = window.devicePixelRatio || 1;
        if (dpr >= 3) {
            return 36;
        }
        if (dpr >= 2.5) {
            return 34;
        }
        return MOBILE_MIN_INSET;
    }

    function estimateIosSafeArea() {
        var longSide = Math.max(window.screen.width, window.screen.height);
        var portrait = window.innerHeight >= window.innerWidth;
        if (longSide >= 812) {
            return portrait ? 47 : 21;
        }
        return portrait ? 20 : MOBILE_MIN_INSET;
    }

    function estimateMobileSafeArea() {
        var ua = navigator.userAgent || "";
        if (/Android/i.test(ua)) {
            return estimateAndroidSafeArea();
        }
        if (/iPhone|iPod|iPad/i.test(ua)) {
            return estimateIosSafeArea();
        }
        return MOBILE_MIN_INSET;
    }

    function resolveSafeAreaTop() {
        if (!isPhonesDukanApp()) {
            return 0;
        }
        // Native app uses a black system status bar; web content starts below it.
        return 0;
    }

    function applyChromePadding(inset) {
        var root = document.documentElement;
        var chrome = document.getElementById("pd-site-chrome");
        var slot = chrome ? chrome.querySelector(".pd-status-bar-slot") : null;
        var safeFill = chrome ? chrome.querySelector(".pd-chrome-safe-fill") : null;
        var insetPx = Math.max(0, Math.round(inset));

        if (isPhonesDukanApp()) {
            markPdAppClient();
            var viewportMeta = document.querySelector('meta[name="viewport"]');
            if (viewportMeta && !/viewport-fit=cover/i.test(viewportMeta.content || "")) {
                viewportMeta.content = "width=device-width, initial-scale=1.0, viewport-fit=cover";
            }
        } else {
            root.removeAttribute("data-pd-app");
        }

        root.style.setProperty("--pd-chrome-pad-top", insetPx + "px");
        root.style.setProperty("--safe-area-top", insetPx + "px");
        root.style.setProperty("--pd-status-inset", insetPx + "px");
        root.dataset.pdSafeArea = String(insetPx);

        if (chrome) {
            chrome.style.paddingTop = "0px";
        }
        if (safeFill) {
            safeFill.style.display = "none";
            safeFill.style.height = "0px";
            safeFill.style.minHeight = "0px";
        }
        if (slot) {
            slot.style.display = "none";
            slot.style.height = "0px";
        }
    }

    function applySafeAreaTop() {
        applyChromePadding(resolveSafeAreaTop());
    }

    window.PDSafeArea = {
        apply: applySafeAreaTop,
        measure: resolveSafeAreaTop
    };

    applySafeAreaTop();

    window.addEventListener("resize", applySafeAreaTop, { passive: true });
    window.addEventListener("orientationchange", function () {
        window.setTimeout(applySafeAreaTop, 120);
    }, { passive: true });

    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", applySafeAreaTop, { passive: true });
        window.visualViewport.addEventListener("scroll", applySafeAreaTop, { passive: true });
    }
})(window, document);
