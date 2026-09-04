#!/bin/bash
# PhonesDukan — Build all Android release artifacts
set -e

echo "==> Installing dependencies..."
flutter pub get

echo "==> Generating splash & icons..."
dart run flutter_native_splash:create
dart run flutter_launcher_icons

echo "==> Building Debug APK..."
flutter build apk --debug

echo "==> Building Release APK..."
flutter build apk --release

echo "==> Building Release AAB (Play Store)..."
flutter build appbundle --release

echo ""
echo "Build complete!"
echo "  Debug APK:   build/app/outputs/flutter-apk/app-debug.apk"
echo "  Release APK: build/app/outputs/flutter-apk/app-release.apk"
echo "  Release AAB: build/app/outputs/bundle/release/app-release.aab"
