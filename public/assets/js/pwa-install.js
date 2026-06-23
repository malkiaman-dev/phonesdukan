document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var installBtn     = document.getElementById('pd-install-app-btn');
    var installPanel   = document.getElementById('pd-install-app-panel');
    var closeBtn       = document.getElementById('pd-install-app-close');
    var redownloadBtn  = document.getElementById('pd-install-redownload');
    var androidSection = document.getElementById('pd-install-android');
    var iosSection     = document.getElementById('pd-install-ios');
    var downloadUrl    = installBtn ? installBtn.getAttribute('data-download-url') : '';

    if (!installBtn || !installPanel || !downloadUrl) return;

    var isOpen = false;
    var isDownloading = false;

    function withBase(path) {
        return window.pdWithBase ? window.pdWithBase(path) : path;
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent)
            && !window.MSStream;
    }

    function showSection(section) {
        if (androidSection) androidSection.hidden = true;
        if (iosSection) iosSection.hidden = true;
        if (section) section.hidden = false;
    }

    function openPanel(section) {
        isOpen = true;
        showSection(section);
        installPanel.classList.add('pd-install-open');
        installPanel.setAttribute('aria-hidden', 'false');
    }

    function closePanel() {
        isOpen = false;
        installPanel.classList.remove('pd-install-open');
        installPanel.setAttribute('aria-hidden', 'true');
    }

    function triggerApkDownload() {
        if (isDownloading) return;
        isDownloading = true;

        var link = document.createElement('a');
        link.href = withBase(downloadUrl);
        link.download = 'PhonesDukan.apk';
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.setTimeout(function () {
            isDownloading = false;
        }, 2000);
    }

    installBtn.addEventListener('click', function () {
        if (isIos()) {
            if (isOpen) {
                closePanel();
            } else {
                openPanel(iosSection);
            }
            return;
        }

        if (!isOpen) {
            triggerApkDownload();
            openPanel(androidSection);
            return;
        }

        closePanel();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
    }

    if (redownloadBtn) {
        redownloadBtn.addEventListener('click', function () {
            triggerApkDownload();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) {
            closePanel();
        }
    });

    document.addEventListener('click', function (e) {
        if (!isOpen) return;
        if (installPanel.contains(e.target) || installBtn.contains(e.target)) return;
        closePanel();
    });
});
