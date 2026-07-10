(function (window, document) {
    "use strict";

    var MAX_CACHE = 16;
    var STACK_KEY = "pd-nav-stack";
    var ASSET_VERSION_KEY = "pd-asset-version";
    var pageCache = new Map();
    var pendingFetches = new Map();
    var loadedScripts = new Set();
    var navStack = [];
    var booted = false;
    var navigating = false;
    var activeNavToken = 0;

    function syncAssetVersion() {
        var current = String(window.__PD_ASSET_VERSION__ || "");
        if (!current) {
            return;
        }
        try {
            var previous = sessionStorage.getItem(ASSET_VERSION_KEY);
            if (previous && previous !== current) {
                pageCache.clear();
                pendingFetches.clear();
                loadedScripts.clear();
            }
            sessionStorage.setItem(ASSET_VERSION_KEY, current);
        } catch (e) {}
    }

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
            if (/^\/(checkout|login|register|verify|thankyou|admin|my-account)(\/|$)/i.test(path)) {
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

    function persistStack() {
        try {
            sessionStorage.setItem(STACK_KEY, JSON.stringify(navStack));
        } catch (e) {}
    }

    function restoreStack(fallback) {
        try {
            var raw = sessionStorage.getItem(STACK_KEY);
            if (!raw) {
                return fallback.slice();
            }
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed) || !parsed.length) {
                return fallback.slice();
            }
            return parsed;
        } catch (e) {
            return fallback.slice();
        }
    }

    function alignStackToUrl(url) {
        var key = normalizeUrl(url);
        if (!navStack.length) {
            navStack = [key];
            persistStack();
            return;
        }
        var top = navStack[navStack.length - 1];
        if (top === key) {
            return;
        }
        var idx = navStack.lastIndexOf(key);
        if (idx >= 0) {
            navStack = navStack.slice(0, idx + 1);
        } else {
            navStack.push(key);
        }
        persistStack();
    }

    function syncStackToUrl(url) {
        alignStackToUrl(url);
    }

    function goToStackEntry(index, options) {
        options = options || {};
        if (index < 0 || index >= navStack.length) {
            return false;
        }
        navStack = navStack.slice(0, index + 1);
        var key = navStack[index];
        var snapshot = pageCache.get(key);
        if (!snapshot) {
            persistStack();
            window.location.href = key;
            return true;
        }
        history.replaceState({ pdAppNav: 1, url: key, stackIndex: index }, snapshot.title || "", key);
        applySnapshot(snapshot, { restoreScroll: options.restoreScroll !== false });
        persistStack();
        return true;
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
            scrollY: (window.pdGetScrollY && window.pdGetScrollY()) || window.scrollY || 0
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

    function scrollToTop() {
        if (window.pdScrollTo) {
            window.pdScrollTo(0, 0);
        } else {
            window.scrollTo(0, 0);
        }
        if (document.documentElement) {
            document.documentElement.scrollTop = 0;
        }
        if (document.body) {
            document.body.scrollTop = 0;
        }
        var root = window.pdGetScrollRoot ? window.pdGetScrollRoot() : null;
        if (root && root !== document.documentElement && root !== document.body) {
            root.scrollTop = 0;
        }
        window.requestAnimationFrame(function () {
            if (window.pdScrollTo) {
                window.pdScrollTo(0, 0);
            } else {
                window.scrollTo(0, 0);
            }
            window.requestAnimationFrame(function () {
                if (window.pdScrollTo) {
                    window.pdScrollTo(0, 0);
                } else {
                    window.scrollTo(0, 0);
                }
            });
        });
        window.setTimeout(function () {
            if (window.pdScrollTo) {
                window.pdScrollTo(0, 0);
            } else {
                window.scrollTo(0, 0);
            }
        }, 0);
    }

    function restoreScroll(y) {
        var target = typeof y === "number" ? y : 0;
        if (window.pdScrollTo) {
            window.pdScrollTo(0, target);
        } else {
            window.scrollTo(0, target);
        }
        window.requestAnimationFrame(function () {
            if (window.pdScrollTo) {
                window.pdScrollTo(0, target);
            } else {
                window.scrollTo(0, target);
            }
        });
    }

    function stylePath(href) {
        try {
            return new URL(href, window.location.href).pathname;
        } catch (e) {
            return href;
        }
    }

    function hasStylesheet(href) {
        var path = stylePath(href);
        var links = document.querySelectorAll('link[rel="stylesheet"][href]');
        for (var i = 0; i < links.length; i++) {
            if (stylePath(links[i].href) === path) {
                return true;
            }
        }
        return false;
    }

    function loadStyles(doc) {
        var links = doc.querySelectorAll('link[rel="stylesheet"][href]');
        var pending = [];
        Array.prototype.forEach.call(links, function (link) {
            var href = link.getAttribute("href");
            if (!href) {
                return;
            }
            var absolute = link.href;
            if (hasStylesheet(absolute)) {
                return;
            }
            pending.push(new Promise(function (resolve) {
                var tag = document.createElement("link");
                tag.rel = "stylesheet";
                tag.href = absolute;
                tag.onload = tag.onerror = resolve;
                document.head.appendChild(tag);
            }));
        });
        if (!pending.length) {
            return Promise.resolve();
        }
        return Promise.all(pending);
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
            if (loadedScripts.has(absolute) || document.querySelector('script[src="' + absolute + '"]')) {
                loadedScripts.add(absolute);
                return;
            }
            chain = chain.then(function () {
                return new Promise(function (resolve) {
                    var tag = document.createElement("script");
                    tag.src = absolute;
                    tag.async = false;
                    tag.onload = tag.onerror = function () {
                        loadedScripts.add(absolute);
                        resolve();
                    };
                    document.body.appendChild(tag);
                });
            });
        });
        return chain;
    }

    function parseSnapshot(html) {
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

    function fetchSnapshot(url, options) {
        options = options || {};
        var key = normalizeUrl(url);
        if (options.bypassCache) {
            pageCache.delete(key);
            pendingFetches.delete(key);
        }
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
            cache: "no-store"
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }
            return response.text();
        }).then(function (html) {
            var parsed = parseSnapshot(html);
            if (!parsed) {
                throw new Error("Missing main.content");
            }
            return loadScripts(parsed.doc).then(function () {
                return loadStyles(parsed.doc);
            }).then(function () {
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

    function applySnapshot(snapshot, options) {
        options = options || {};
        var main = document.querySelector("main.content");
        if (!main || !snapshot) {
            return;
        }
        main.innerHTML = snapshot.html;
        if (snapshot.title) {
            document.title = snapshot.title;
        }
        if (options.restoreScroll) {
            restoreScroll(snapshot.scrollY || 0);
        } else {
            scrollToTop();
        }
        document.dispatchEvent(new CustomEvent("pd:page-view", {
            detail: { url: window.location.href }
        }));
        if (!options.restoreScroll) {
            window.setTimeout(warmVisibleNavTargets, 100);
        }
        if (window.PDAppShell && typeof window.PDAppShell.onPageReady === "function") {
            window.PDAppShell.onPageReady();
        }
        if (!options.restoreScroll) {
            window.setTimeout(scrollToTop, 50);
        }
    }

    function navigateTo(href, options) {
        options = options || {};
        var key = normalizeUrl(href);
        var currentKey = normalizeUrl(window.location.href);
        var token = ++activeNavToken;

        if (!options.fromPop && key === currentKey) {
            scrollToTop();
            return Promise.resolve();
        }

        if (!options.fromPop) {
            storePage(currentKey, captureCurrentPage());
            alignStackToUrl(currentKey);
        }

        navigating = true;
        setLoading(true);
        var bypassCache = /\/cart(\/|$)/i.test(new URL(key).pathname);
        return fetchSnapshot(key, { bypassCache: bypassCache && !options.fromPop }).then(function (snapshot) {
            if (token !== activeNavToken) {
                return;
            }
            applySnapshot(snapshot, { restoreScroll: !!options.fromPop });
            if (!options.fromPop) {
                alignStackToUrl(currentKey);
                if (navStack[navStack.length - 1] !== key) {
                    navStack.push(key);
                }
                history.replaceState({
                    pdAppNav: 1,
                    url: key,
                    stackIndex: navStack.length - 1
                }, snapshot.title || "", key);
                persistStack();
            }
        }).catch(function () {
            if (token === activeNavToken) {
                beforeFullPageLeave();
                window.location.href = href;
            }
        }).finally(function () {
            if (token === activeNavToken) {
                navigating = false;
                setLoading(false);
            }
        });
    }

    function beforeFullPageLeave() {
        var currentKey = normalizeUrl(window.location.href);
        storePage(currentKey, captureCurrentPage());
        persistStack();
    }

    function warmVisibleNavTargets() {
        var seen = Object.create(null);
        var anchors = document.querySelectorAll(".cat-card[href], .h-category-item[href], .na-view-all[href], a[href]");
        Array.prototype.forEach.call(anchors, function (anchor) {
            var href = anchor.getAttribute("href");
            if (!href || seen[href]) {
                return;
            }
            if (!isNavAnchor(anchor)) {
                return;
            }
            seen[href] = 1;
            prefetchUrl(anchor.href);
        });
    }

    function boot() {
        if (booted) {
            return;
        }
        booted = true;
        syncAssetVersion();

        if ("scrollRestoration" in history) {
            history.scrollRestoration = "manual";
        }

        var start = normalizeUrl(window.location.href);
        navStack = restoreStack([start]);
        alignStackToUrl(start);

        if (!history.state || !history.state.pdAppNav) {
            history.replaceState({ pdAppNav: 1, url: start, stackIndex: navStack.length - 1 }, document.title, start);
        }
        storePage(start, captureCurrentPage());
        window.setTimeout(warmVisibleNavTargets, 250);

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

            if (!state.pdAppNav) {
                alignStackToUrl(key);
                return;
            }

            if (typeof state.stackIndex === "number" && state.stackIndex >= 0 && state.stackIndex < navStack.length) {
                navStack = navStack.slice(0, state.stackIndex + 1);
            } else {
                alignStackToUrl(key);
            }
            persistStack();

            var snapshot = pageCache.get(key);
            if (!snapshot) {
                fetchSnapshot(key).then(function (fetched) {
                    applySnapshot(fetched, { restoreScroll: true });
                }).catch(function () {
                    window.location.reload();
                });
                return;
            }
            applySnapshot(snapshot, { restoreScroll: true });
        });

        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
                alignStackToUrl(window.location.href);
            }
        }, { passive: true });

        window.addEventListener("load", function () {
            if (navigating) {
                return;
            }
            storePage(normalizeUrl(window.location.href), captureCurrentPage());
        }, { passive: true });

        Array.prototype.forEach.call(document.querySelectorAll("script[src]"), function (script) {
            if (script.src) {
                loadedScripts.add(script.src);
            }
        });
    }

    function back() {
        alignStackToUrl(normalizeUrl(window.location.href));
        if (navStack.length <= 1) {
            return false;
        }
        return goToStackEntry(navStack.length - 2, { restoreScroll: true });
    }

    function canGoBack() {
        return navStack.length > 1;
    }

    function invalidate(url) {
        var key = normalizeUrl(url);
        pageCache.delete(key);
        pendingFetches.delete(key);
    }

    window.PDAppNav = {
        boot: boot,
        back: back,
        canGoBack: canGoBack,
        prefetch: prefetchUrl,
        navigate: navigateTo,
        beforeFullPageLeave: beforeFullPageLeave,
        invalidate: invalidate
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
