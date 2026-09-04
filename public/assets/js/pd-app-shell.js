(function (window, document) {
    "use strict";

    var prefetched = Object.create(null);

    function isApp() {
        return window.PDApp && typeof window.PDApp.is === "function" && window.PDApp.is();
    }

    function refreshShellUi() {
        if (window.PDSafeArea && typeof window.PDSafeArea.apply === "function") {
            window.PDSafeArea.apply();
        }
        if (window.PDChromeMetrics && typeof window.PDChromeMetrics.update === "function") {
            window.PDChromeMetrics.update();
        }
        if (window.PDAnnouncement && typeof window.PDAnnouncement.sync === "function") {
            window.PDAnnouncement.sync();
        }
    }

    function prefetchUrl(href) {
        if (!href || prefetched[href]) {
            return;
        }
        try {
            var url = new URL(href, window.location.href);
            if (!/phonesdukan\.com$/i.test(url.hostname.replace(/^www\./i, ""))) {
                return;
            }
        } catch (e) {
            return;
        }
        prefetched[href] = true;
        var link = document.createElement("link");
        link.rel = "prefetch";
        link.as = "document";
        link.href = href;
        document.head.appendChild(link);
    }

    function onPageReady() {
        if (!isApp()) {
            return;
        }
        window.requestAnimationFrame(function () {
            refreshShellUi();
            window.setTimeout(refreshShellUi, 120);
        });
    }

    function initPrefetch() {
        document.addEventListener("touchstart", function (event) {
            var target = event.target;
            if (!target || !target.closest) {
                return;
            }
            var anchor = target.closest("a[href]");
            if (anchor && anchor.href) {
                prefetchUrl(anchor.href);
            }
        }, { passive: true, capture: true });
    }

    window.PDAppShell = {
        onPageReady: onPageReady,
        refresh: refreshShellUi
    };

    if (!isApp()) {
        return;
    }

    initPrefetch();

    window.addEventListener("pageshow", function () {
        onPageReady();
    }, { passive: true });

    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) {
            onPageReady();
        }
    }, { passive: true });
})(window, document);
