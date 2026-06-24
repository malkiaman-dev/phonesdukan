(function (window, document) {
    "use strict";

    var MAX_CACHE = 16;
    var pageCache = new Map();
    var pendingFetches = new Map();
    var navStack = [];
    var booted = false;

    function isApp() {
        return window.PDApp && typeof window.PDApp.is === "function" && window.PDApp.is();
    }

    function normalizeUrl(href) {
        var url = new URL(href, window.location.href);
        url.hash = "";
        return url.toString();
    }

    function isSameSite(href) {
        try {
            var url = new URL(href, window.location.href);
            return /phonesdukan\.com$/i.test(url.hostname.replace(/^www\./i, ""));
        } catch (e) {
            return false;
        }
    }

    function shouldUseSpa(href) {
        try {
            var url = new URL(href, window.location.href);
            var path = url.pathname || "/";
            if (!isSameSite(href)) {
                return false;
            }
            if (/^\/(checkout|cart|login|register|verify|thankyou|admin|my-account)(\/|$)/i.test(path)) {
                return false;
            }
            if (url.protocol !== "http:" && url.protocol !== "https:") {
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    function isNavAnchor(anchor) {
        if (!anchor || !anchor.href) {
            return false;
        }
        if (anchor.hasAttribute("download") || anchor.target === "_blank") {
            return false;
        }
        if (anchor.getAttribute("href") === "#" || (anchor.getAttribute("href") || "").charAt(0) === "#") {
            return false;
        }
        if (anchor.dataset.pdNoSpa === "1") {
            return false;
        }
        return shouldUseSpa(anchor.href);
    }

    function trimCache() {
        if (pageCache.size <= MAX_CACHE) {
            return;
        }
        var first = pageCache.keys().next().value;
        pageCache.delete(first);
    }

    function captureCurrentPage() {
        var main = document.querySelector("main.content");
        if (!main) {
            return null;
        }
        return {
            html: main.innerHTML,
            title: document.title,
            scrollY: window.scrollY || 0
        };
    }

    function storePage(url, snapshot) {
        if (!snapshot) {
            return;
        }
        pageCache.set(normalizeUrl(url), snapshot);
        trimCache();
    }

    function setLoading(active) {
        if (active) {
            document.documentElement.setAttribute("data-pd-nav-loading", "1");
        } else {
            document.documentElement.removeAttribute("data-pd-nav-loading");
        }
    }

    function loadStyles(doc) {
        var links = doc.querySelectorAll('link[rel="stylesheet"][href]');
        Array.prototype.forEach.call(links, function (link) {
            var href = link.getAttribute("href");
            if (!href) {
                return;
            }
            var absolute = link.href;
            if (document.querySelector('link[rel="stylesheet"][href="' + absolute + '"]')) {
                return;
            }
            document.head.appendChild(link.cloneNode(true));
        });
    }

    function loadScripts(doc) {
        var scripts = doc.querySelectorAll("script[src]");
        var chain = Promise.resolve();
        Array.prototype.forEach.call(scripts, function (script) {
            var src = script.getAttribute("src");
            if (!src) {
                return;
            }
            var absolute = script.src;
            if (document.querySelector('script[src="' + absolute + '"]')) {
                return;
            }
            chain = chain.then(function () {
                return new Promise(function (resolve) {
                    var tag = document.createElement("script");
                    tag.src = absolute;
                    tag.async = false;
                    tag.onload = tag.onerror = resolve;
                    document.body.appendChild(tag);
                });
            });
        });
        return chain;
    }

    function parseSnapshot(html, url) {
        var doc = new DOMParser().parseFromString(html, "text/html");
        var main = doc.querySelector("main.content");
        if (!main) {
            return null;
        }
        return {
            html: main.innerHTML,
            title: doc.title || document.title,
            scrollY: 0,
            doc: doc
        };
    }

    function fetchSnapshot(url) {
        var key = normalizeUrl(url);
        if (pageCache.has(key)) {
            return Promise.resolve(pageCache.get(key));
        }
        if (pendingFetches.has(key)) {
            return pendingFetches.get(key);
        }

        var request = fetch(key, {
            credentials: "include",
            headers: {
                "X-PhonesDukan-App": "1",
                "X-PD-App-Nav": "1"
            },
            cache: "force-cache"
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }
            return response.text();
        }).then(function (html) {
            var parsed = parseSnapshot(html, key);
            if (!parsed) {
                throw new Error("Missing main.content");
            }
            return loadScripts(parsed.doc).then(function () {
                loadStyles(parsed.doc);
                var snapshot = {
                    html: parsed.html,
                    title: parsed.title,
                    scrollY: 0
                };
                storePage(key, snapshot);
                pendingFetches.delete(key);
                return snapshot;
            });
        }).catch(function (err) {
            pendingFetches.delete(key);
            throw err;
        });

        pendingFetches.set(key, request);
        return request;
    }

    function prefetchUrl(href) {
        if (!isApp() || !shouldUseSpa(href)) {
            return;
        }
        fetchSnapshot(href).catch(function () {});
    }

    function applySnapshot(snapshot, scrollY) {
        var main = document.querySelector("main.content");
        if (!main || !snapshot) {
            return;
        }
        main.innerHTML = snapshot.html;
        if (snapshot.title) {
            document.title = snapshot.title;
        }
        window.scrollTo(0, typeof scrollY === "number" ? scrollY : 0);
        document.dispatchEvent(new CustomEvent("pd:page-view", {
            detail: { url: window.location.href }
        }));
        if (window.PDAppShell && typeof window.PDAppShell.onPageReady === "function") {
            window.PDAppShell.onPageReady();
        }
    }

    function navigateTo(href, options) {
        options = options || {};
        var key = normalizeUrl(href);
        var currentKey = normalizeUrl(window.location.href);

        if (!options.fromPop && key === currentKey) {
            return Promise.resolve();
        }

        if (!options.fromPop) {
            storePage(currentKey, captureCurrentPage());
        }

        setLoading(true);
        return fetchSnapshot(key).then(function (snapshot) {
            applySnapshot(snapshot, options.fromPop ? snapshot.scrollY : 0);
            if (!options.fromPop) {
                history.pushState({ pdAppNav: 1, url: key }, snapshot.title || "", key);
                navStack.push(key);
            }
        }).catch(function () {
            window.location.href = href;
        }).finally(function () {
            setLoading(false);
        });
    }

    function boot() {
        if (booted) {
            return;
        }
        booted = true;
        var start = normalizeUrl(window.location.href);
        navStack = [start];
        history.replaceState({ pdAppNav: 1, url: start }, document.title, start);
        storePage(start, captureCurrentPage());

        document.addEventListener("click", function (event) {
            var anchor = event.target && event.target.closest ? event.target.closest("a[href]") : null;
            if (!anchor || !isNavAnchor(anchor)) {
                return;
            }
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            navigateTo(anchor.href);
        }, true);

        document.addEventListener("touchstart", function (event) {
            var anchor = event.target && event.target.closest ? event.target.closest("a[href]") : null;
            if (anchor && isNavAnchor(anchor)) {
                prefetchUrl(anchor.href);
            }
        }, { passive: true, capture: true });

        window.addEventListener("popstate", function (event) {
            var state = event.state || {};
            var key = normalizeUrl(state.url || window.location.href);
            var snapshot = pageCache.get(key);
            if (!snapshot) {
                window.location.reload();
                return;
            }
            while (navStack.length > 1 && navStack[navStack.length - 1] !== key) {
                navStack.pop();
            }
            if (navStack[navStack.length - 1] !== key) {
                navStack.push(key);
            }
            applySnapshot(snapshot, snapshot.scrollY || 0);
        });

        window.addEventListener("load", function () {
            storePage(normalizeUrl(window.location.href), captureCurrentPage());
        }, { passive: true });
    }

    function back() {
        if (navStack.length <= 1) {
            return false;
        }
        history.back();
        return true;
    }

    function canGoBack() {
        return navStack.length > 1;
    }

    window.PDAppNav = {
        boot: boot,
        back: back,
        canGoBack: canGoBack,
        prefetch: prefetchUrl,
        navigate: navigateTo
    };

    if (!isApp()) {
        return;
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }
})(window, document);
