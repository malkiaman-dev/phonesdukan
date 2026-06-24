package com.phonesdukan.app;

import android.annotation.SuppressLint;
import android.graphics.Color;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.view.Window;
import android.view.WindowInsetsController;
import android.view.WindowManager;
import android.webkit.JavascriptInterface;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.core.view.WindowInsetsControllerCompat;

import java.util.HashMap;
import java.util.Map;

public class MainActivity extends AppCompatActivity {

  private static final String HOME_URL = "https://www.phonesdukan.com/?pd_app=1";
  private static final int STATUS_BAR_COLOR = Color.parseColor("#111111");

  private static final String APP_BOOT_JS =
      "(function(){try{"
          + "document.documentElement.setAttribute('data-pd-app','1');"
          + "localStorage.setItem('pd_app','1');"
          + "var c='pd_app=1;path=/;max-age=31536000;SameSite=Lax';"
          + "if(/phonesdukan\\.com$/i.test(location.hostname)){c+=';domain=.phonesdukan.com';}"
          + "document.cookie=c;"
          + "if(window.PDApp&&window.PDApp.removeInstallWidget){window.PDApp.removeInstallWidget();}"
          + "else{['pd-install-app-btn','pd-install-app-panel'].forEach(function(id){"
          + "var el=document.getElementById(id);"
          + "if(el&&el.parentNode){el.parentNode.removeChild(el);}"
          + "});}"
          + "if(window.PDSafeArea&&window.PDSafeArea.apply){window.PDSafeArea.apply();}"
          + "}catch(e){}})();";

  private static final String APP_EARLY_JS =
      "(function(){try{"
          + "document.documentElement.setAttribute('data-pd-app','1');"
          + "if(window.PDSafeArea&&window.PDSafeArea.apply){window.PDSafeArea.apply();}"
          + "var c='pd_app=1;path=/;max-age=31536000;SameSite=Lax';"
          + "if(/phonesdukan\\.com$/i.test(location.hostname)){c+=';domain=.phonesdukan.com';}"
          + "document.cookie=c;"
          + "}catch(e){}})();";

  private WebView webView;

  private Map<String, String> appHeaders() {
    Map<String, String> headers = new HashMap<>();
    headers.put("X-PhonesDukan-App", "1");
    return headers;
  }

  @SuppressLint("SetJavaScriptEnabled")
  @Override
  protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);

    Window window = getWindow();
    // Android 15+ (targetSdk 35) draws edge-to-edge; handle status bar insets on the WebView.
    WindowCompat.setDecorFitsSystemWindows(window, false);
    window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_STATUS);
    window.addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);
    window.setStatusBarColor(STATUS_BAR_COLOR);
    window.setNavigationBarColor(Color.parseColor("#111111"));
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
      window.setStatusBarContrastEnforced(false);
      window.setNavigationBarContrastEnforced(false);
    }
    applyStatusBarStyle();

    setContentView(R.layout.activity_main);

    webView = findViewById(R.id.webView);
    webView.setBackgroundColor(STATUS_BAR_COLOR);
    webView.setVerticalScrollBarEnabled(false);
    webView.setHorizontalScrollBarEnabled(false);

    ViewCompat.setOnApplyWindowInsetsListener(webView, (view, windowInsets) -> {
      Insets statusBars = windowInsets.getInsets(WindowInsetsCompat.Type.statusBars());
      view.setPadding(0, statusBars.top, 0, 0);
      return WindowInsetsCompat.CONSUMED;
    });
    ViewCompat.requestApplyInsets(webView);

    WebSettings settings = webView.getSettings();
    settings.setJavaScriptEnabled(true);
    settings.setUserAgentString(settings.getUserAgentString() + " PhonesDukanApp/1.0");
    settings.setDomStorageEnabled(true);
    settings.setDatabaseEnabled(true);
    settings.setLoadWithOverviewMode(true);
    settings.setUseWideViewPort(true);
    settings.setBuiltInZoomControls(false);
    settings.setDisplayZoomControls(false);
    settings.setCacheMode(WebSettings.LOAD_DEFAULT);

    webView.addJavascriptInterface(new AppBridge(), "PhonesDukanNative");

    webView.setWebViewClient(new WebViewClient() {
      @Override
      public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
        view.loadUrl(ensureAppParam(request.getUrl().toString()), appHeaders());
        return true;
      }

      @Override
      public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
        view.evaluateJavascript(APP_EARLY_JS, null);
        applyStatusBarStyle();
      }

      @Override
      public void onPageFinished(WebView view, String url) {
        view.evaluateJavascript(APP_BOOT_JS, null);
        applyStatusBarStyle();
        view.post(() -> {
          applyStatusBarStyle();
          view.postDelayed(MainActivity.this::applyStatusBarStyle, 300);
        });
      }
    });

    webView.setWebChromeClient(new WebChromeClient());

    if (savedInstanceState == null) {
      webView.loadUrl(HOME_URL, appHeaders());
    } else {
      webView.restoreState(savedInstanceState);
      webView.evaluateJavascript(APP_BOOT_JS, null);
    }
  }

  @Override
  protected void onResume() {
    super.onResume();
    applyStatusBarStyle();
  }

  @Override
  public void onWindowFocusChanged(boolean hasFocus) {
    super.onWindowFocusChanged(hasFocus);
    if (hasFocus) {
      applyStatusBarStyle();
    }
  }

  private void applyStatusBarStyle() {
    Window window = getWindow();
    if (window == null) {
      return;
    }

    window.setStatusBarColor(STATUS_BAR_COLOR);

    WindowInsetsControllerCompat controller =
        WindowCompat.getInsetsController(window, window.getDecorView());
    if (controller != null) {
      controller.setAppearanceLightStatusBars(false);
      if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        controller.setAppearanceLightNavigationBars(false);
      }
    }

    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
      WindowInsetsController platformController = window.getInsetsController();
      if (platformController != null) {
        platformController.setSystemBarsAppearance(
            0,
            WindowInsetsController.APPEARANCE_LIGHT_STATUS_BARS
                | WindowInsetsController.APPEARANCE_LIGHT_NAVIGATION_BARS
        );
      }
    }

    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
      View decorView = window.getDecorView();
      int flags = decorView.getSystemUiVisibility();
      flags &= ~View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR;
      if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        flags &= ~View.SYSTEM_UI_FLAG_LIGHT_NAVIGATION_BAR;
      }
      decorView.setSystemUiVisibility(flags);
    }
  }

  private String ensureAppParam(String url) {
    if (url == null || url.isEmpty()) {
      return HOME_URL;
    }
    Uri uri = Uri.parse(url);
    String host = uri.getHost();
    if (host == null || !host.contains("phonesdukan.com")) {
      return url;
    }
    if ("1".equals(uri.getQueryParameter("pd_app"))) {
      return url;
    }
    Uri.Builder builder = uri.buildUpon();
    builder.appendQueryParameter("pd_app", "1");
    return builder.build().toString();
  }

  @Override
  protected void onSaveInstanceState(Bundle outState) {
    super.onSaveInstanceState(outState);
    webView.saveState(outState);
  }

  @Override
  public void onBackPressed() {
    if (webView.canGoBack()) {
      webView.goBack();
    } else {
      super.onBackPressed();
    }
  }

  private class AppBridge {
    @JavascriptInterface
    public boolean isApp() {
      return true;
    }

    /** Always 0 — WebView top padding from native status bar insets positions web content. */
    @JavascriptInterface
    public float getStatusBarHeight() {
      return 0f;
    }
  }
}
