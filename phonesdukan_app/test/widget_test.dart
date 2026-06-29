import 'package:flutter_test/flutter_test.dart';
import 'package:phonesdukan_app/core/config/app_config.dart';
import 'package:phonesdukan_app/services/external_link/external_link_handler.dart';

void main() {
  test('internal URLs are recognized', () {
    expect(
      ExternalLinkHandler.isInternalUrl(Uri.parse('https://phonesdukan.com/')),
      isTrue,
    );
    expect(
      ExternalLinkHandler.isInternalUrl(Uri.parse('https://www.phonesdukan.com/mobiles/')),
      isTrue,
    );
    expect(
      ExternalLinkHandler.isInternalUrl(Uri.parse('https://google.com/')),
      isFalse,
    );
  });

  test('website URL is configured', () {
    expect(AppConfig.websiteUrl, 'https://phonesdukan.com');
    expect(AppConfig.packageName, 'com.phonesdukan.app');
  });
}
