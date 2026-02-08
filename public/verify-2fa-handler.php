<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;

if (empty($_SESSION['pending_2fa_user_id'])) {
    header("Location: /login.php");
    exit;
}

$code = $_POST['code'] ?? '';

// Validate code format - accept both 6-digit TOTP and 8-digit backup codes
if (!preg_match('/^[0-9]{6}$/', $code) && !preg_match('/^[0-9]{8}$/', $code)) {
    $_SESSION['2fa_error'] = "Authentication code must be 6 or 8 digits.";
    header("Location: /verify-2fa.php");
    exit;
}

$userId = $_SESSION['pending_2fa_user_id'];

// Get user's secret
$stmt = $pdo->prepare("SELECT two_factor_secret, two_factor_backup_codes FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['pending_2fa_user_id']);
    $_SESSION['error'] = "User not found.";
    header("Location: /login.php");
    exit;
}

$google2fa = new Google2FA();

// Verify TOTP code
$valid = $google2fa->verifyKey($user['two_factor_secret'], $code);

// Also check backup codes if TOTP fails
if (!$valid && !empty($user['two_factor_backup_codes'])) {
    $backupCodes = json_decode($user['two_factor_backup_codes'], true);
    if (is_array($backupCodes) && in_array($code, $backupCodes, true)) {
        $valid = true;
        // Remove used backup code
        $backupCodes = array_diff($backupCodes, [$code]);
        $pdo->prepare("UPDATE users SET two_factor_backup_codes = ? WHERE id = ?")
            ->execute([json_encode(array_values($backupCodes)), $userId]);
    }
}

if (!$valid) {
    $_SESSION['2fa_error'] = "Invalid authentication code. Please try again.";
    header("Location: /verify-2fa.php");
    exit;
}

// Success - complete login
unset($_SESSION['pending_2fa_user_id']);
$_SESSION['user_id'] = $userId;

// Update last login
$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);

header("Location: /dashboard.php");
exit;
