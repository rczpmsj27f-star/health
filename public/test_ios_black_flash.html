<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iOS Black Flash Fix - Test Page</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <style>
        body {
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .status-box {
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            border: 2px solid #ccc;
        }
        .status-box.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-box.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-box.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .test-link {
            display: inline-block;
            padding: 12px 24px;
            margin: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .test-link:hover {
            opacity: 0.9;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 iOS Black Flash Fix - Test Page</h1>
        
        <div class="status-box info">
            <strong>Purpose:</strong> This page helps verify that the iOS black flash fix is working correctly.
        </div>

        <h2>Environment Detection</h2>
        <div id="environment-status" class="status-box"></div>

        <h2>Splash Screen Status</h2>
        <div id="splash-status" class="status-box"></div>

        <h2>Configuration Check</h2>
        <div id="config-status" class="status-box"></div>

        <h2>Test Navigation</h2>
        <p>Navigate between these pages to test for black flash:</p>
        <div>
            <a href="/dashboard.php" class="test-link">Dashboard</a>
            <a href="/modules/medications/dashboard.php" class="test-link">Medications</a>
            <a href="/modules/profile/view.php" class="test-link">Profile</a>
            <a href="/test_ios_black_flash.html" class="test-link">Back to Test Page</a>
        </div>

        <h2>Expected Behavior</h2>
        <ul>
            <li>✅ No black flash during page navigation</li>
            <li>✅ Smooth transitions between pages</li>
            <li>✅ Splash screen visible briefly on initial load</li>
            <li>✅ White background visible if WebView is transparent</li>
        </ul>

        <h2>Manual Test Steps</h2>
        <ol>
            <li>Open this page in the iOS Capacitor app (not Safari)</li>
            <li>Navigate to different pages using the links above</li>
            <li>Watch carefully during the transition</li>
            <li>Enable dark mode on iOS and test again</li>
            <li>Verify no black flash appears at any point</li>
        </ol>

        <h2>Debugging Information</h2>
        <p>Check the browser console for these messages:</p>
        <ul>
            <li><code>Splash screen hidden successfully</code> - Splash screen was hidden after page load</li>
            <li><code>SplashScreen hide skipped</code> - Normal in web browser or if already hidden</li>
        </ul>
    </div>

    <script>
        // Environment detection
        const environmentStatus = document.getElementById('environment-status');
        const splashStatus = document.getElementById('splash-status');
        const configStatus = document.getElementById('config-status');

        // Check Capacitor environment
        if (window.Capacitor) {
            environmentStatus.className = 'status-box success';
            environmentStatus.innerHTML = `
                <strong>✅ Capacitor Detected</strong><br>
                Running in: ${window.Capacitor.isNativePlatform() ? 'Native App' : 'Web View'}<br>
                Platform: ${window.Capacitor.getPlatform ? window.Capacitor.getPlatform() : 'Unknown'}
            `;

            // Check SplashScreen plugin
            if (window.Capacitor.Plugins && window.Capacitor.Plugins.SplashScreen) {
                splashStatus.className = 'status-box success';
                splashStatus.innerHTML = `
                    <strong>✅ SplashScreen Plugin Available</strong><br>
                    The splash screen handler script should work correctly.
                `;
            } else {
                splashStatus.className = 'status-box error';
                splashStatus.innerHTML = `
                    <strong>❌ SplashScreen Plugin Not Found</strong><br>
                    The plugin may not be installed or initialized yet.
                `;
            }

            // Configuration check
            configStatus.className = 'status-box info';
            configStatus.innerHTML = `
                <strong>ℹ️ Configuration Status</strong><br>
                • launchAutoHide: Should be <code>false</code><br>
                • Window background: Should be white<br>
                • Check <code>capacitor.config.json</code> and <code>AppDelegate.swift</code>
            `;

        } else {
            environmentStatus.className = 'status-box info';
            environmentStatus.innerHTML = `
                <strong>ℹ️ Web Browser Environment</strong><br>
                Capacitor not detected. This test page should be opened in the iOS app.
            `;

            splashStatus.className = 'status-box info';
            splashStatus.innerHTML = `
                <strong>ℹ️ Browser Mode</strong><br>
                Splash screen functionality is only available in the Capacitor app.
            `;

            configStatus.className = 'status-box info';
            configStatus.innerHTML = `
                <strong>ℹ️ Browser Testing</strong><br>
                The black flash issue only occurs in the iOS Capacitor app, not in web browsers.<br>
                To test properly, build and run the iOS app using: <code>npm run ios:run</code>
            `;
        }

        // Log for debugging
        console.log('=== iOS Black Flash Fix Test ===');
        console.log('Capacitor available:', !!window.Capacitor);
        console.log('SplashScreen plugin:', window.Capacitor?.Plugins?.SplashScreen ? 'Available' : 'Not available');
        console.log('Platform:', window.Capacitor?.getPlatform ? window.Capacitor.getPlatform() : 'Web');
    </script>
</body>
</html>
