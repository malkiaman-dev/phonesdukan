import 'package:flutter/material.dart';

import '../../core/config/app_config.dart';
import '../../services/external_link/external_link_handler.dart';

class WhatsAppFab extends StatelessWidget {
  WhatsAppFab({super.key});

  final ExternalLinkHandler _links = ExternalLinkHandler();

  static const Color _whatsappGreen = Color(0xFF25D366);

  Future<void> _openWhatsApp() async {
    await _links.openUrl(AppConfig.whatsappUrl);
  }

  @override
  Widget build(BuildContext context) {
    return Positioned(
      right: 16,
      bottom: 16,
      child: FloatingActionButton(
        onPressed: _openWhatsApp,
        backgroundColor: _whatsappGreen,
        foregroundColor: Colors.white,
        child: const Icon(Icons.chat),
      ),
    );
  }
}
