/**
 * PhonesDukan — Get App floating button (downloads latest APK)
 */
(function () {
    'use strict';

    var btn = document.getElementById('pd-download-app-btn');
    if (!btn) return;

    var apkUrl = btn.getAttribute('data-apk-url') || btn.getAttribute('href') || '';
    var playStoreUrl = btn.getAttribute('data-play-store-url') || '';

    function withCacheBust(url) {
        if (!url) return url;
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        return url + sep + 't=' + Date.now();
    }

    btn.addEventListener('click', function (e) {
        if (!apkUrl) {
            e.preventDefault();
            return;
        }

        var isAndroid = /Android/i.test(navigator.userAgent);
        if (isAndroid && playStoreUrl) {
            e.preventDefault();
            window.open(playStoreUrl, '_blank', 'noopener');
            return;
        }

        e.preventDefault();
        window.location.assign(withCacheBust(apkUrl));
    });
})();
