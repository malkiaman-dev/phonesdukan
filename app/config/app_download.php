<?php
/**
 * PhonesDukan Android App — Download Configuration
 *
 * HOW IT WORKS
 * ────────────
 * • The app is a WebView shell around your live website (phonesdukan.com).
 *   Website changes (products, prices, pages, design) appear in the app
 *   automatically — no APK rebuild needed.
 *
 * • Rebuild the APK only when you change native app code (Flutter/Android).
 *   After building, copy the release APK to public/downloads/phonesdukan-app.apk
 *   (or run phonesdukan_app/scripts/deploy_apk.bat). The Get App button always
 *   serves whatever file is there, with cache-busting so users get the latest build.
 */

// Relative path to the built APK inside the project root.
define('APK_STORAGE_PATH', 'public/downloads/phonesdukan-app.apk');
define('APK_DOWNLOAD_FILENAME', 'phonesdukan-app.apk');

// Clean URL served by AppDownloadController (forces browser download).
define('APP_DOWNLOAD_ROUTE', 'download-app');

// Legacy alias — kept for backwards compatibility.
define('APK_DOWNLOAD_URL', APP_DOWNLOAD_ROUTE);

// Play Store URL (leave empty — app is distributed via website download only)
define('PLAY_STORE_URL', '');

// Button label
define('APP_DOWNLOAD_BUTTON_TEXT', 'Get App');

// Show button on desktop too (true) or mobile only (false)
define('APP_DOWNLOAD_SHOW_ON_DESKTOP', true);
