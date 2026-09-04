import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'app.dart';
import 'core/theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: AppTheme.brandBlack,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: AppTheme.brandBlack,
      systemNavigationBarIconBrightness: Brightness.light,
    ),
  );

  runApp(const PhonesDukanApp());
}
