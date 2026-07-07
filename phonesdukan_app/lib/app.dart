import 'package:flutter/material.dart';

import 'core/theme/app_theme.dart';
import 'presentation/webview/webview_screen.dart';

class PhonesDukanApp extends StatelessWidget {
  const PhonesDukanApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Phones Dukan',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      home: const WebViewScreen(),
    );
  }
}
