import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';

import '../../core/config/app_config.dart';
import '../../core/theme/app_theme.dart';
import '../../services/connectivity/connectivity_service.dart';
import '../../services/external_link/external_link_handler.dart';
import '../offline/offline_screen.dart';
import '../widgets/whatsapp_fab.dart';

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key, this.initialUrl});

  final String? initialUrl;

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  final ConnectivityService _connectivity = ConnectivityService();
  final ExternalLinkHandler _links = ExternalLinkHandler();

  InAppWebViewController? _controller;
  bool _isOffline = false;
  bool _showSplash = true;
  double _progress = 0;

  static const String _bootJs = r'''
(function(){try{
document.documentElement.setAttribute('data-pd-app','1');
localStorage.setItem('pd_app','1');
var c='pd_app=1;path=/;max-age=31536000;SameSite=Lax';
if(/phonesdukan\.com$/i.test(location.hostname)){c+=';domain=.phonesdukan.com';}
document.cookie=c;
['pd-install-app-btn','pd-install-app-panel','pd-download-app-btn','pd-chatbot-toggle'].forEach(function(id){
var el=document.getElementById(id);if(el&&el.parentNode){el.parentNode.removeChild(el);}
});
if(window.PDSafeArea&&window.PDSafeArea.apply){window.PDSafeArea.apply();}
}catch(e){}})();
''';

  @override
  void initState() {
    super.initState();
    _checkConnectivity();
  }

  Future<void> _checkConnectivity() async {
    final online = await _connectivity.hasConnection();
    if (!mounted) return;
    setState(() => _isOffline = !online);
  }

  bool _isAllowedHost(String? host) {
    if (host == null || host.isEmpty) return false;
    final normalized = host.toLowerCase();
    return AppConfig.allowedHosts.any(
      (allowed) => normalized == allowed || normalized.endsWith('.$allowed'),
    );
  }

  Future<NavigationActionPolicy> _onNavigation(NavigationAction action) async {
    final uri = action.request.url;
    if (uri == null) return NavigationActionPolicy.ALLOW;

    final scheme = uri.scheme.toLowerCase();
    if (scheme == 'http' || scheme == 'https') {
      if (_isAllowedHost(uri.host)) {
        return NavigationActionPolicy.ALLOW;
      }
      await _links.openUrl(uri.toString());
      return NavigationActionPolicy.CANCEL;
    }

    if (scheme == 'tel' || scheme == 'mailto' || scheme == 'whatsapp') {
      await _links.openUrl(uri.toString());
      return NavigationActionPolicy.CANCEL;
    }

    return NavigationActionPolicy.CANCEL;
  }

  Future<void> _reload() async {
    final online = await _connectivity.hasConnection();
    if (!mounted) return;

    if (!online) {
      setState(() => _isOffline = true);
      return;
    }

    setState(() {
      _isOffline = false;
      _showSplash = true;
      _progress = 0;
    });

    await _controller?.loadUrl(
      urlRequest: URLRequest(
        url: WebUri(widget.initialUrl ?? AppConfig.homeUrl),
        headers: const {'X-PhonesDukan-App': '1'},
      ),
    );
  }

  Future<bool> _onBackPressed() async {
    if (_controller != null && await _controller!.canGoBack()) {
      await _controller!.goBack();
      return false;
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    if (_isOffline) {
      return OfflineScreen(onRetry: _reload);
    }

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        final shouldPop = await _onBackPressed();
        if (shouldPop && context.mounted) {
          await SystemNavigator.pop();
        }
      },
      child: Scaffold(
        backgroundColor: AppTheme.brandBlack,
        body: SafeArea(
          top: false,
          child: Stack(
            children: [
              InAppWebView(
                initialUrlRequest: URLRequest(
                  url: WebUri(widget.initialUrl ?? AppConfig.homeUrl),
                  headers: const {'X-PhonesDukan-App': '1'},
                ),
                initialSettings: InAppWebViewSettings(
                  javaScriptEnabled: true,
                  domStorageEnabled: true,
                  databaseEnabled: true,
                  useWideViewPort: true,
                  supportZoom: false,
                  builtInZoomControls: false,
                  displayZoomControls: false,
                  mediaPlaybackRequiresUserGesture: false,
                  allowsInlineMediaPlayback: true,
                  transparentBackground: true,
                  userAgent: Platform.isAndroid
                      ? 'Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36${AppConfig.userAgentSuffix}'
                      : null,
                ),
                onWebViewCreated: (controller) {
                  _controller = controller;
                },
                onLoadStart: (controller, url) {
                  if (!mounted) return;
                  setState(() {
                    _showSplash = true;
                    _progress = 0;
                  });
                },
                onProgressChanged: (controller, progress) {
                  if (!mounted) return;
                  setState(() => _progress = progress / 100);
                },
                onLoadStop: (controller, url) async {
                  await controller.evaluateJavascript(source: _bootJs);
                  if (!mounted) return;
                  setState(() => _showSplash = false);
                },
                shouldOverrideUrlLoading: (controller, action) async {
                  return _onNavigation(action);
                },
                onReceivedError: (controller, request, error) async {
                  if (request.isForMainFrame == true && mounted) {
                    setState(() => _isOffline = true);
                  }
                },
              ),
              if (_showSplash)
                Container(
                  color: AppTheme.brandBlack,
                  alignment: Alignment.center,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Image.asset(
                        'assets/images/app-icon.png',
                        width: 120,
                        height: 120,
                      ),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: 180,
                        child: LinearProgressIndicator(
                          value: _progress > 0 ? _progress : null,
                          backgroundColor: Colors.white12,
                          color: AppTheme.brandYellow,
                          minHeight: 4,
                        ),
                      ),
                    ],
                  ),
                ),
              WhatsAppFab(),
            ],
          ),
        ),
      ),
    );
  }
}
