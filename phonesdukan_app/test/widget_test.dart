import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:phonesdukan_app/app.dart';

void main() {
  testWidgets('App boots to webview shell', (WidgetTester tester) async {
    await tester.pumpWidget(const PhonesDukanApp());
    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
