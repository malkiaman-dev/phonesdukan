package com.phonesdukan.app;

import android.annotation.SuppressLint;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.webkit.JavascriptInterface;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsControllerCompat;

public class MainActivity extends AppCompatActivity {

  private static final String HOME_URL = "https://www.phonesdukan.com/?pd_app=1";

  private static final String APP_BOOT_JS =
      "(function(){try{"
          + "document.documentElement.setAttribute('data-pd-app','1');"
          + "localStorage.setItem('pd_app','1');"
          + "if(window.PDSafeArea&&window.PDSafeArea.apply){window.PDSafeArea.apply();}"
          + "}catch(e){}})();";

  private WebView webView;

  @SuppressLint("SetJavaScriptEnabled")
  @Override
  protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);

    WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
    getWindow().setStatusBarColor(Color.TRANSPARENT);
    getWindow().setNavigationBarColor(Color.parseColor("#111111"));
    WindowInsetsControllerCompat insetsController =
        WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView());
    if (insetsController != null) {
      insetsController.setAppearanceLightStatusBars(false);
    }

    setContentView(R.layout.activity_main);

    webView = findViewById(R.id.webView);
    webView.setBackgroundColor(Color.WHITE);
    webView.setVerticalScrollBarEnabled(false);
    webView.setHorizontalScrollBarEnabled(false);

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
        view.loadUrl(ensureAppParam(request.getUrl().toString()));
        return true;
      }

      @Override
      public void onPageFinished(WebView view, String url) {
        view.evaluateJavascript(APP_BOOT_JS, null);
      }
    });

    webView.setWebChromeClient(new WebChromeClient());

    if (savedInstanceState == null) {
      webView.loadUrl(HOME_URL);
    } else {
      webView.restoreState(savedInstanceState);
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

    @JavascriptInterface
    public float getStatusBarHeight() {
      float density = getResources().getDisplayMetrics().density;
      if (density <= 0f) {
        return 24f;
      }
      int resourceId = getResources().getIdentifier("status_bar_height", "dimen", "android");
      if (resourceId > 0) {
        return getResources().getDimensionPixelSize(resourceId) / density;
      }
      return 24f;
    }
  }
}
