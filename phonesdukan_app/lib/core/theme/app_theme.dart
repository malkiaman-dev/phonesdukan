import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppTheme {
  static const Color brandYellow = Color(0xFFF9B000);
  static const Color brandBlack = Color(0xFF111111);

  static ThemeData get light {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      scaffoldBackgroundColor: brandBlack,
      colorScheme: const ColorScheme.dark(
        primary: brandYellow,
        surface: brandBlack,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: brandBlack,
        foregroundColor: Colors.white,
        systemOverlayStyle: SystemUiOverlayStyle(
          statusBarColor: brandBlack,
          statusBarIconBrightness: Brightness.light,
          statusBarBrightness: Brightness.dark,
        ),
      ),
    );
  }
}
