<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../vendor/autoload.php";

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$google2fa = new Google2FA();

// Get current user's 2FA status
$stmt = $pdo->prepare("SELECT username, two_factor_enabled, two_factor_secret FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: /dashboard.php");
    exit;
}

// Ensure two_factor_enabled key exists (default to 0 if column doesn't exist)
if (!isset($user['two_factor_enabled'])) {
    $user['two_factor_enabled'] = 0;
}
if (!isset($user['two_factor_secret'])) {
    $user['two_factor_secret'] = null;
}

// Generate secret if not exists
if (empty($user['two_factor_secret'])) {
    $secret = $google2fa->generateSecretKey();
    $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?")
        ->execute([$secret, $_SESSION['user_id']]);
} else {
    $secret = $user['two_factor_secret'];
}

// Generate QR code URL
$qrCodeUrl = $google2fa->getQRCodeUrl(
    'Health Tracker',
    $user['username'],
    $secret
);

// Generate SVG QR code
$renderer = new ImageRenderer(
    new RendererStyle(200),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);
$qrCodeSvg = $writer->writeString($qrCodeUrl);

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
$backupCodes = $_SESSION['backup_codes'] ?? null;
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['backup_codes']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px 20px 40px 20px;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #333;
        }
        .card p {
            color: #666;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code svg {
            max-width: 200px;
            height: auto;
        }
        .secret-key {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            word-break: break-all;
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
            font-size: 18px;
            box-sizing: border-box;
            text-align: center;
            letter-spacing: 4px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
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
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        .backup-codes {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .backup-codes ul {
            list-style: none;
            padding: 0;
            margin: 10px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .backup-codes li {
            font-family: monospace;
            font-size: 14px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            text-align: center;
        }
        .steps {
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    
    <div id="main-content">
    <div class="container">
        <h1>Two-Factor Authentication</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Status: 
                <?php if ($user['two_factor_enabled']): ?>
                    <span class="status-badge status-enabled">Enabled</span>
                <?php else: ?>
                    <span class="status-badge status-disabled">Disabled</span>
                <?php endif; ?>
            </h2>
            <p>Two-factor authentication adds an extra layer of security to your account by requiring a code from your authenticator app in addition to your password.</p>
        </div>
        
        <?php if (!$user['two_factor_enabled']): ?>
        <div class="card">
            <h2>Setup Two-Factor Authentication</h2>
            <p>Follow these steps to enable two-factor authentication:</p>
            
            <ol class="steps">
                <li>Download an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, Authy, or use your device's built-in authenticator)</li>
                <li>Scan the QR code below with your authenticator app:</li>
            </ol>
            
            <div class="qr-code">
                <?= $qrCodeSvg ?>
            </div>
            
            <p style="text-align: center; font-size: 14px; color: #666;">Or manually enter this secret key:</p>
            <div class="secret-key"><?= htmlspecialchars($secret) ?></div>
            
            <form method="POST" action="two_factor_handler.php">
                <input type="hidden" name="action" value="enable">
                <div class="form-group">
                    <label>Enter the 6-digit code from your authenticator app to verify setup:</label>
                    <input type="text" name="code" pattern="[0-9]{6,8}" maxlength="8" 
                           inputmode="numeric" autocomplete="one-time-code" required>
                </div>
                <button type="submit" class="btn btn-accept">Enable Two-Factor Authentication</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($backupCodes): ?>
        <div class="card">
            <h2>⚠️ Save Your Backup Codes</h2>
            <p style="color: #dc3545; font-weight: 500;">Save these backup codes in a secure location. Each code can be used once if you lose access to your authenticator app.</p>
            
            <div class="backup-codes">
                <ul>
                    <?php foreach ($backupCodes as $backupCode): ?>
                        <li><?= htmlspecialchars($backupCode) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <p style="font-size: 14px; color: #666;">After you close this page, you won't be able to see these codes again. Make sure to save them now!</p>
        </div>
        <?php endif; ?>
        
        <?php if ($user['two_factor_enabled'] && !$backupCodes): ?>
        <div class="card">
            <h2>Disable Two-Factor Authentication</h2>
            <p>To disable two-factor authentication, enter a code from your authenticator app:</p>
            
            <form method="POST" action="two_factor_handler.php">
                <input type="hidden" name="action" value="disable">
                <div class="form-group">
                    <label>Authentication Code (6-digit TOTP or 8-digit backup code):</label>
                    <input type="text" name="code" pattern="[0-9]{6,8}" maxlength="8" 
                           inputmode="numeric" autocomplete="one-time-code" required>
                </div>
                <button type="submit" class="btn btn-cancel">Disable Two-Factor Authentication</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="/modules/settings/preferences.php" class="btn">← Back to Settings</a>
        </div>
    </div>
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
