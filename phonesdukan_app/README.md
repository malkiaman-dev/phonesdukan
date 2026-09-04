# PhonesDukan Flutter App

Official Android app for [phonesdukan.com](https://www.phonesdukan.com/). Built with Flutter — loads the live website in a WebView.

## Build and deploy to website

**Windows:**
```bat
phonesdukan_app\scripts\deploy_apk.bat
```

**macOS / Linux:**
```sh
cd phonesdukan_app
./scripts/build_all.sh
```

This builds a release APK and copies it to `public/downloads/phonesdukan-app.apk`. The website **Get App** button serves that file automatically.

## Requirements

- Flutter SDK 3.2+ (`flutter doctor`)
- Android SDK (via Android Studio or command-line tools)
- ~5 GB free disk space for release builds

## Upload to live server

After building locally, upload:
- `public/downloads/phonesdukan-app.apk`
- `public/downloads/app-version.properties`

Do **not** upload the `phonesdukan_app/` source folder to the web server.
