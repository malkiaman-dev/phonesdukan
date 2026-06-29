@echo off
REM PhonesDukan — Build release APK and deploy to website downloads folder
REM Requires: Flutter in PATH (C:\src\flutter\bin) and 5+ GB free disk space

setlocal
set PATH=C:\src\flutter\bin;%PATH%

cd /d "%~dp0.."
echo.
echo === PhonesDukan APK Build and Deploy ===
echo.

flutter --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Flutter not found. Add C:\src\flutter\bin to your PATH.
    exit /b 1
)

echo [1/4] Getting dependencies...
call flutter pub get
if errorlevel 1 exit /b 1

echo [2/4] Generating app icon and splash...
call dart run flutter_launcher_icons
call dart run flutter_native_splash:create

echo [3/4] Building release APK (this may take 10-20 minutes)...
call flutter build apk --release
if errorlevel 1 (
    echo.
    echo BUILD FAILED. Common fixes:
    echo   - Free at least 5 GB on C: drive
    echo   - Close Android Studio and other heavy apps
    echo   - Run: flutter doctor
    exit /b 1
)

echo [4/4] Copying APK to website downloads folder...
if not exist "..\public\downloads" mkdir "..\public\downloads"
copy /Y "build\app\outputs\flutter-apk\app-release.apk" "..\public\downloads\phonesdukan-app.apk"

echo.
echo SUCCESS!
echo   APK: public\downloads\phonesdukan-app.apk
echo   URL: https://phonesdukan.com/public/downloads/phonesdukan-app.apk
echo.
echo Upload phonesdukan-app.apk to your live server if testing locally.
echo The Download App button on your website will serve this file.
echo.
endlocal
