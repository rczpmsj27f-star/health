<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Get current user info
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: /dashboard.php");
    exit;
}

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Authentication – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/biometric-auth.js?v=<?= time() ?>"></script>
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8f9fa;
            color: #6c757d;
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
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #28a745;
            color: #fff;
        }
        .btn-primary:hover {
            background: #218838;
        }
        .btn-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .biometric-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .feature-list li {
            padding: 8px 0;
            color: #666;
        }
        .feature-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
        #enableSection, #disableSection {
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #28a745;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div class="container">
        <a href="/modules/settings/preferences.php" class="back-link">← Back to Settings</a>

        <div class="card">
            <h2>Biometric Authentication</h2>
            <p>Use Face ID or Touch ID to quickly and securely sign in to your account.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div id="notSupportedAlert" class="alert alert-warning" style="display: none;">
                Biometric authentication is not supported on this device or browser. Please use Safari on iOS 14+ or a compatible device with Face ID or Touch ID.
            </div>

            <div id="statusLoading" class="loading">
                <p>Checking biometric availability...</p>
            </div>

            <div id="statusSection" style="display: none;">
                <div class="biometric-icon">🔐</div>
                
                <div id="statusBadge"></div>

                <div id="enableSection">
                    <p>Enable biometric authentication to sign in faster and more securely using Face ID or Touch ID.</p>
                    
                    <ul class="feature-list">
                        <li>Quick sign-in without typing your password</li>
                        <li>Secure authentication using device biometrics</li>
                        <li>Your biometric data never leaves your device</li>
                    </ul>

                    <div class="form-group">
                        <label for="passwordInput">Enter your password to enable biometric authentication:</label>
                        <input type="password" id="passwordInput" placeholder="Password" autocomplete="current-password">
                    </div>

                    <button id="enableBtn" class="btn btn-primary">Enable Face ID / Touch ID</button>
                </div>

                <div id="disableSection">
                    <p>Biometric authentication is currently enabled. You can sign in using Face ID or Touch ID.</p>
                    
                    <p style="font-size: 14px; color: #856404; background: #fff3cd; padding: 12px; border-radius: 6px; margin: 20px 0;">
                        <strong>Note:</strong> You can still use your password to sign in even with biometric authentication enabled.
                    </p>

                    <button id="disableBtn" class="btn btn-danger">Disable Face ID / Touch ID</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const statusLoading = document.getElementById('statusLoading');
            const statusSection = document.getElementById('statusSection');
            const statusBadge = document.getElementById('statusBadge');
            const enableSection = document.getElementById('enableSection');
            const disableSection = document.getElementById('disableSection');
            const notSupportedAlert = document.getElementById('notSupportedAlert');
            const enableBtn = document.getElementById('enableBtn');
            const disableBtn = document.getElementById('disableBtn');
            const passwordInput = document.getElementById('passwordInput');

            // Check if biometric is supported
            const isSupported = BiometricAuth.isSupported();
            const isPlatformAvailable = await BiometricAuth.isPlatformAuthenticatorAvailable();

            if (!isSupported || !isPlatformAvailable) {
                statusLoading.style.display = 'none';
                notSupportedAlert.style.display = 'block';
                return;
            }

            // Load current status
            try {
                const status = await BiometricAuth.getStatus();
                
                statusLoading.style.display = 'none';
                statusSection.style.display = 'block';

                if (status.enabled) {
                    statusBadge.innerHTML = '<div class="status-badge status-enabled">✓ Enabled</div>';
                    disableSection.style.display = 'block';
                    enableSection.style.display = 'none';
                    
                    // Store credential ID in localStorage
                    if (status.credential && status.credential.credentialId) {
                        localStorage.setItem('biometric_credential_id', status.credential.credentialId);
                    }
                } else {
                    statusBadge.innerHTML = '<div class="status-badge status-disabled">Not Enabled</div>';
                    enableSection.style.display = 'block';
                    disableSection.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading status:', error);
                statusLoading.style.display = 'none';
                notSupportedAlert.textContent = 'Error loading biometric status. Please try again.';
                notSupportedAlert.style.display = 'block';
            }

            // Enable biometric authentication
            enableBtn.addEventListener('click', async function() {
                const password = passwordInput.value.trim();
                
                if (!password) {
                    alert('Please enter your password');
                    return;
                }

                enableBtn.disabled = true;
                enableBtn.textContent = 'Enabling...';

                try {
                    const result = await BiometricAuth.register('<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', <?= intval($_SESSION['user_id']) ?>, password);
                    
                    // Store credential ID in localStorage for login page
                    const status = await BiometricAuth.getStatus();
                    if (status.credential && status.credential.credentialId) {
                        localStorage.setItem('biometric_credential_id', status.credential.credentialId);
                    }
                    
                    // Success - reload page to show updated status
                    window.location.href = '/modules/settings/biometric.php?success=1';
                } catch (error) {
                    alert('Failed to enable biometric authentication: ' + error.message);
                    enableBtn.disabled = false;
                    enableBtn.textContent = 'Enable Face ID / Touch ID';
                }
            });

            // Disable biometric authentication
            disableBtn.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to disable biometric authentication? You will need to use your password to sign in.')) {
                    return;
                }

                disableBtn.disabled = true;
                disableBtn.textContent = 'Disabling...';

                try {
                    await BiometricAuth.disable();
                    
                    // Remove credential ID from localStorage
                    localStorage.removeItem('biometric_credential_id');
                    
                    // Success - reload page to show updated status
                    window.location.href = '/modules/settings/biometric.php?disabled=1';
                } catch (error) {
                    alert('Failed to disable biometric authentication: ' + error.message);
                    disableBtn.disabled = false;
                    disableBtn.textContent = 'Disable Face ID / Touch ID';
                }
            });
        });
    </script>
    </div> <!-- #main-content -->
</body>
</html>
