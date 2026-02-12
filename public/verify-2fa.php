<?php
require_once __DIR__ . '/../app/includes/cache-buster.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;

if (empty($_SESSION['pending_2fa_user_id'])) {
    header("Location: /login.php");
    exit;
}

$error = $_SESSION['2fa_error'] ?? null;
unset($_SESSION['2fa_error']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication – Health Tracker</title>
    
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
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
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
            font-size: 24px;
            box-sizing: border-box;
            letter-spacing: 8px;
            text-align: center;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        .back-link {
            margin-top: 16px;
            text-align: center;
        }
        .back-link a {
            color: #28a745;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>Two-Factor Authentication</h2>
            <p>Enter the code from your authenticator app or a backup code</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/verify-2fa-handler.php">
            <div class="form-group">
                <label>Authentication Code (6-digit TOTP or 8-digit backup code)</label>
                <input type="text" name="code" pattern="[0-9]{6,8}" maxlength="8" 
                       inputmode="numeric" autocomplete="one-time-code" required
                       autofocus>
            </div>
            <button type="submit" class="btn btn-accept" style="margin-top: 10px; cursor: pointer;">Verify</button>
        </form>
        
        <div class="back-link">
            <a href="/login.php">← Back to Login</a>
        </div>
    </div>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
