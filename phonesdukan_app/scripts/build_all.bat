@echo off
REM PhonesDukan — Build all Android release artifacts (Windows)
echo ==> Installing dependencies...
call flutter pub get

echo ==> Generating splash ^& icons...
call dart run flutter_native_splash:create
call dart run flutter_launcher_icons

echo ==> Building Debug APK...
call flutter build apk --debug

echo ==> Building Release APK...
call flutter build apk --release

echo ==> Building Release AAB (Play Store)...
call flutter build appbundle --release

echo.
echo Build complete!
echo   Debug APK:   build\app\outputs\flutter-apk\app-debug.apk
echo   Release APK: build\app\outputs\flutter-apk\app-release.apk
echo   Release AAB: build\app\outputs\bundle\release\app-release.aab
