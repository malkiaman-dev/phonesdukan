(function (window, document) {
    "use strict";

    var MOBILE_QUERY = "(max-width: 992px)";
    var MOBILE_MIN_INSET = 36;

    function isMobileViewport() {
        return window.matchMedia && window.matchMedia(MOBILE_QUERY).matches;
    }

    function isPhonesDukanApp() {
        return /PhonesDukanApp/i.test(navigator.userAgent || "");
    }

    function isTouchMobile() {
        if (isMobileViewport()) {
            return true;
        }
        return (navigator.maxTouchPoints || 0) > 0 && window.innerWidth <= 992;
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
        var nativeInset = readNativeInset();
        if (nativeInset > 0) {
            return nativeInset;
        }
        var ua = navigator.userAgent || "";
        if (/Android/i.test(ua)) {
            return estimateAndroidSafeArea();
        }
        if (/iPhone|iPod|iPad/i.test(ua)) {
            return estimateIosSafeArea();
        }
        return MOBILE_MIN_INSET;
    }

    function applyChromePadding(inset) {
        var root = document.documentElement;
        var chrome = document.getElementById("pd-site-chrome");
        var slot = chrome ? chrome.querySelector(".pd-status-bar-slot") : null;

        if (isPhonesDukanApp()) {
            root.dataset.pdApp = "1";
            root.style.setProperty("--pd-chrome-pad-top", "0px");
            root.style.setProperty("--safe-area-top", "0px");
            root.style.setProperty("--pd-status-inset", "0px");
            root.dataset.pdSafeArea = "0";
            if (chrome) {
                chrome.style.paddingTop = "0px";
            }
            if (slot) {
                slot.style.display = "none";
                slot.style.height = "0px";
            }
            var viewportMeta = document.querySelector('meta[name="viewport"]');
            if (viewportMeta && /viewport-fit=cover/i.test(viewportMeta.content || "")) {
                viewportMeta.content = "width=device-width, initial-scale=1.0";
            }
            return;
        }

        root.removeAttribute("data-pd-app");

        root.style.setProperty("--pd-chrome-pad-top", inset + "px");
        root.style.setProperty("--safe-area-top", inset + "px");
        root.style.setProperty("--pd-status-inset", inset + "px");
        root.dataset.pdSafeArea = String(inset);

        if (chrome) {
            chrome.style.paddingTop = inset + "px";
        }
        if (slot) {
            slot.style.display = "";
            slot.style.height = inset + "px";
        }
    }

    function resolveSafeAreaTop() {
        if (isPhonesDukanApp()) {
            return 0;
        }

        if (!isTouchMobile()) {
            return measureEnvSafeArea();
        }

        var measured = measureEnvSafeArea();
        var estimated = estimateMobileSafeArea();
        return Math.max(measured, estimated, MOBILE_MIN_INSET);
    }

    function applySafeAreaTop() {
        var inset = resolveSafeAreaTop();
        applyChromePadding(inset);
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
    }
})(window, document);
