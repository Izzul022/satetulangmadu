package com.satetulangmadu.app;

import android.Manifest;
import android.content.Context;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.os.Build;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.WindowManager;
import android.webkit.PermissionRequest;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

public class MainActivity extends AppCompatActivity {

    private static final String PREFS_NAME = "SatayAppPrefs";
    private static final String KEY_SERVER_URL = "server_url";
    private static final String DEFAULT_SERVER_URL = "https://satetulangmadu.onrender.com";
    private static final int PERMISSION_REQ_CODE = 101;

    private WebView webView;
    private SwipeRefreshLayout swipeRefresh;
    private ProgressBar progressBar;
    private LinearLayout errorView;
    private TextView tvErrorDetail;
    private Button btnRetry;
    private Button btnConfigServer;
    private Button btnKitchen;
    private Button btnAdmin;
    private ImageButton btnServerSettings;

    private SharedPreferences prefs;
    private String currentServerUrl;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        prefs = getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        currentServerUrl = prefs.getString(KEY_SERVER_URL, DEFAULT_SERVER_URL);

        initViews();
        checkPermissions();
        setupWebView();
        loadAppUrl("");
    }

    private void initViews() {
        webView = findViewById(R.id.webView);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        progressBar = findViewById(R.id.progressBar);
        errorView = findViewById(R.id.errorView);
        tvErrorDetail = findViewById(R.id.tvErrorDetail);
        btnRetry = findViewById(R.id.btnRetry);
        btnConfigServer = findViewById(R.id.btnConfigServer);
        btnKitchen = findViewById(R.id.btnKitchen);
        btnAdmin = findViewById(R.id.btnAdmin);
        btnServerSettings = findViewById(R.id.btnServerSettings);

        swipeRefresh.setOnRefreshListener(() -> webView.reload());
        swipeRefresh.setColorSchemeResources(R.color.primary, R.color.accent);

        btnRetry.setOnClickListener(v -> {
            errorView.setVisibility(View.GONE);
            webView.setVisibility(View.VISIBLE);
            webView.reload();
        });

        btnConfigServer.setOnClickListener(v -> showServerSettingsDialog());
        btnServerSettings.setOnClickListener(v -> showServerSettingsDialog());

        btnKitchen.setOnClickListener(v -> loadAppUrl("kitchen.php"));
        btnAdmin.setOnClickListener(v -> loadAppUrl("admin.php"));
    }

    private void checkPermissions() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this,
                    new String[]{Manifest.permission.CAMERA, Manifest.permission.WAKE_LOCK},
                    PERMISSION_REQ_CODE);
        }
    }

    private void setupWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setLoadsImagesAutomatically(true);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setUseWideViewPort(true);
        settings.setLoadWithOverviewMode(true);
        settings.setSupportZoom(false);
        settings.setUserAgentString(settings.getUserAgentString() + " SatayAndroidApp/1.0");

        webView.addJavascriptInterface(new WebAppInterface(this), "AndroidApp");

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                if (newProgress < 100) {
                    progressBar.setVisibility(View.VISIBLE);
                    progressBar.setProgress(newProgress);
                } else {
                    progressBar.setVisibility(View.GONE);
                    swipeRefresh.setRefreshing(false);
                }
            }

            @Override
            public void onPermissionRequest(final PermissionRequest request) {
                MainActivity.this.runOnUiThread(() -> {
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                        request.grant(request.getResources());
                    }
                });
            }
        });

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                super.onPageStarted(view, url, favicon);
                errorView.setVisibility(View.GONE);
                webView.setVisibility(View.VISIBLE);

                // If opening Kitchen KDS, keep screen awake
                if (url != null && url.contains("kitchen.php")) {
                    setKeepScreenOn(true);
                }
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                super.onReceivedError(view, request, error);
                if (request.isForMainFrame()) {
                    showErrorView("Unable to connect to: " + request.getUrl());
                }
            }
        });
    }

    public void loadAppUrl(String path) {
        String base = currentServerUrl.trim();
        if (!base.endsWith("/")) {
            base += "/";
        }
        String target = base + path;
        webView.loadUrl(target);
    }

    public void setKeepScreenOn(boolean keepOn) {
        if (keepOn) {
            getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        } else {
            getWindow().clearFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        }
    }

    private void showErrorView(String message) {
        webView.setVisibility(View.GONE);
        errorView.setVisibility(View.VISIBLE);
        if (tvErrorDetail != null) {
            tvErrorDetail.setText(message + "\n\nPlease ensure your online backend is running or configure the server URL.");
        }
    }

    private void showServerSettingsDialog() {
        LayoutInflater inflater = LayoutInflater.from(this);
        View dialogView = inflater.inflate(R.layout.dialog_server_url, null);
        EditText etUrl = dialogView.findViewById(R.id.etServerUrl);
        etUrl.setText(currentServerUrl);

        new AlertDialog.Builder(this)
                .setTitle("Server Backend URL")
                .setView(dialogView)
                .setPositiveButton("Save & Connect", (dialog, which) -> {
                    String input = etUrl.getText().toString().trim();
                    if (!input.isEmpty()) {
                        if (!input.startsWith("http://") && !input.startsWith("https://")) {
                            input = "https://" + input;
                        }
                        currentServerUrl = input;
                        prefs.edit().putString(KEY_SERVER_URL, currentServerUrl).apply();
                        Toast.makeText(this, "Server URL updated!", Toast.LENGTH_SHORT).show();
                        loadAppUrl("");
                    }
                })
                .setNeutralButton("Reset Default", (dialog, which) -> {
                    currentServerUrl = DEFAULT_SERVER_URL;
                    prefs.edit().putString(KEY_SERVER_URL, DEFAULT_SERVER_URL).apply();
                    Toast.makeText(this, "Reset to default Render URL", Toast.LENGTH_SHORT).show();
                    loadAppUrl("");
                })
                .setNegativeButton("Cancel", null)
                .show();
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
