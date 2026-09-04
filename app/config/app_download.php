<?php
/**
 * PhonesDukan Android App — Download Configuration (Flutter)
 *
 * The mobile app is built with Flutter in phonesdukan_app/.
 * After building, run phonesdukan_app/scripts/deploy_apk.bat (Windows)
 * or phonesdukan_app/scripts/build_all.sh (macOS/Linux).
 *
 * That copies the release APK to public/downloads/phonesdukan-app.apk
 * and updates app-version.properties. The Get App button serves that file.
 */

define('APK_STORAGE_PATH', 'public/downloads/phonesdukan-app.apk');
define('APK_DOWNLOAD_FILENAME', 'phonesdukan-app.apk');
define('APK_DOWNLOADS_DIR', 'public/downloads');
define('APK_VERSION_FILE', 'public/downloads/app-version.properties');

define('APP_DOWNLOAD_ROUTE', 'download-app');
define('APK_DOWNLOAD_URL', APP_DOWNLOAD_ROUTE);

define('PLAY_STORE_URL', '');
define('APP_DOWNLOAD_BUTTON_TEXT', 'Get App');
define('APP_DOWNLOAD_SHOW_ON_DESKTOP', true);
