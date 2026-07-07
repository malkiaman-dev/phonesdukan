# Phones Dukan Android App

This app is a **WebView wrapper** for https://www.phonesdukan.com/

## Live website sync

The app does **not** bundle a copy of your website. It loads the live site from your server, so when you update products, prices, or design on the website, users see those changes in the app automatically (on the next page load or refresh).

Deploy website changes to production (`www.phonesdukan.com`) for them to appear in the installed app.

## Build the APK

1. Open Android Studio.
2. Choose **Open** and select `tools/android-app`.
3. Wait for Gradle sync to finish.
4. Go to **Build > Build Bundle(s) / APK(s) > Build APK(s)**.
   Or from the command line:
   ```bash
   cd tools/android-app
   ./gradlew assembleDebug
   ```
5. The build copies the APK automatically to:
   `public/downloads/phonesdukan-app.apk`

The website **Get App** button serves that file through `public/download-app.php`.
Each build gets a new version code, and `public/downloads/app-version.properties` records the latest build.

## Notes

- APK installs only on Android phones.
- iPhone users see Safari "Add to Home Screen" instructions instead.
- For production, sign the APK before uploading it.
