import 'dart:async';
import 'dart:collection';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';

import '../../core/config/app_config.dart';
import '../../core/theme/app_theme.dart';
import '../../services/connectivity/connectivity_service.dart';
import '../../services/external_link/external_link_handler.dart';
import '../offline/offline_screen.dart';

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

  static const String _bootJs = r'''
(function(){try{
document.documentElement.setAttribute('data-pd-app','1');
localStorage.setItem('pd_app','1');
var c='pd_app=1;path=/;max-age=31536000;SameSite=Lax';
if(/phonesdukan\.com$/i.test(location.hostname)){c+=';domain=.phonesdukan.com';}
document.cookie=c;
['pd-install-app-btn','pd-install-app-panel','pd-download-app-btn','pd-chatbot-toggle','pd-chatbot-win'].forEach(function(id){
var el=document.getElementById(id);if(el&&el.parentNode){el.parentNode.removeChild(el);}
});
document.querySelectorAll('.pd-chatbot-desktop,.pd-chatbot-fab,#pd-chatbot-form').forEach(function(el){
if(el&&el.parentNode){el.parentNode.removeChild(el);}
});
if(window.PDApp&&window.PDApp.removeInstallWidget){window.PDApp.removeInstallWidget();}
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

    setState(() => _isOffline = false);

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

  Future<void> _injectBootJs(InAppWebViewController controller) async {
    await controller.evaluateJavascript(source: _bootJs);
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
          top: true,
          bottom: false,
          child: InAppWebView(
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
              cacheEnabled: true,
              underPageBackgroundColor: AppTheme.brandBlack,
              userAgent: Platform.isAndroid
                  ? 'Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36${AppConfig.userAgentSuffix}'
                  : null,
            ),
            initialUserScripts: UnmodifiableListView<UserScript>([
              UserScript(
                source: _bootJs,
                injectionTime: UserScriptInjectionTime.AT_DOCUMENT_START,
              ),
            ]),
            onWebViewCreated: (controller) {
              _controller = controller;
            },
            onLoadStop: (controller, url) async {
              await _injectBootJs(controller);
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
        ),
      ),
    );
  }
}
