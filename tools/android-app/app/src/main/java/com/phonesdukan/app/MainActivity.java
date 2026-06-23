package com.phonesdukan.app;

import android.annotation.SuppressLint;
import android.graphics.Color;
import android.os.Bundle;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsControllerCompat;

public class MainActivity extends AppCompatActivity {

  private static final String HOME_URL = "https://www.phonesdukan.com/";

  private WebView webView;

  @SuppressLint("SetJavaScriptEnabled")
  @Override
  protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);

    WindowCompat.setDecorFitsSystemWindows(getWindow(), true);
    getWindow().setStatusBarColor(Color.parseColor("#111111"));
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

    webView.setWebViewClient(new WebViewClient() {
      @Override
      public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
        view.loadUrl(request.getUrl().toString());
        return true;
      }
    });

    webView.setWebChromeClient(new WebChromeClient());

    if (savedInstanceState == null) {
      webView.loadUrl(HOME_URL);
    } else {
      webView.restoreState(savedInstanceState);
    }
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
}
