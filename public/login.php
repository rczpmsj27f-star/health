<?php
require_once __DIR__ . '/../app/includes/cache-buster.php';
$err = $_SESSION['error'] ?? null;
$ok  = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Health Tracker</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <script src="/assets/js/biometric-auth.js?v=<?= time() ?>"></script>
    <style>
        /* Force Light Mode */
        html {
            color-scheme: light;
        }
        
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
            background-color: #f5f5f5 !important;
            color: #333 !important;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: #333;
        }
        .login-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-footer p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .login-footer a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .biometric-section {
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .biometric-section p {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin: 0 0 15px 0;
        }
        .btn-biometric {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-biometric:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-biometric:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .biometric-icon {
            font-size: 18px;
        }
        #biometricSection {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please login to your account</p>
        </div>

        <?php if ($err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <!-- Biometric Login Section -->
        <div id="biometricSection" class="biometric-section">
            <p>Sign in with biometric authentication</p>
            <button type="button" id="biometricBtn" class="btn-biometric">
                <span class="biometric-icon">🔐</span>
                <span id="biometricBtnText">Sign in with Face ID / Touch ID</span>
            </button>
        </div>

        <form method="POST" action="login_handler.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn btn-accept" type="submit" style="margin-top: 10px; cursor: pointer;">Login</button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="/register">Create an account</a></p>
        </div>
    </div>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }

    // Biometric authentication on login page
    document.addEventListener('DOMContentLoaded', async function() {
        const biometricSection = document.getElementById('biometricSection');
        const biometricBtn = document.getElementById('biometricBtn');
        const biometricBtnText = document.getElementById('biometricBtnText');

        // Check if biometric is supported and available
        if (!BiometricAuth.isSupported()) {
            return; // Don't show biometric option
        }

        const isPlatformAvailable = await BiometricAuth.isPlatformAuthenticatorAvailable();
        if (!isPlatformAvailable) {
            return; // Don't show biometric option
        }

        // Get stored credential ID from localStorage
        const credentialId = localStorage.getItem('biometric_credential_id');
        
        // If no credential stored, don't show biometric option
        if (!credentialId) {
            return;
        }

        // Verify credential is valid by checking with server (without authentication)
        // We show the button if credential exists in localStorage
        // The server will validate it during authentication
        biometricSection.style.display = 'block';

        // Handle biometric login
        biometricBtn.addEventListener('click', async function() {
            biometricBtn.disabled = true;
            biometricBtnText.textContent = 'Authenticating...';

            try {
                const result = await BiometricAuth.authenticate(credentialId);
                
                if (result.success) {
                    // Redirect to dashboard
                    window.location.href = '/dashboard.php';
                } else {
                    throw new Error(result.error || 'Authentication failed');
                }
            } catch (error) {
                console.error('Biometric authentication error:', error);
                
                // If credential is invalid, remove it from localStorage
                if (error.message && error.message.includes('not found')) {
                    localStorage.removeItem('biometric_credential_id');
                }
                
                alert('Biometric authentication failed. Please use your password to sign in.');
                biometricBtn.disabled = false;
                biometricBtnText.textContent = 'Sign in with Face ID / Touch ID';
            }
        });
    });
    </script>
</body>
</html>
