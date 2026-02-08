<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../vendor/autoload.php";

use PragmaRX\Google2FA\Google2FA;

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$action = $_POST['action'] ?? '';
$code = $_POST['code'] ?? '';

// Validate code format - accept both 6-digit TOTP and 8-digit backup codes
if (!preg_match('/^[0-9]{6}$/', $code) && !preg_match('/^[0-9]{8}$/', $code)) {
    $_SESSION['error'] = "Authentication code must be 6 or 8 digits.";
    header("Location: two_factor.php");
    exit;
}

// Get user's 2FA secret and backup codes
$stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_secret, two_factor_backup_codes FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || empty($user['two_factor_secret'])) {
    $_SESSION['error'] = "Two-factor authentication not set up.";
    header("Location: two_factor.php");
    exit;
}

$google2fa = new Google2FA();

// Verify TOTP code
$valid = $google2fa->verifyKey($user['two_factor_secret'], $code);

// Also check backup codes if TOTP fails
$usedBackupCode = false;
if (!$valid && !empty($user['two_factor_backup_codes'])) {
    $backupCodes = json_decode($user['two_factor_backup_codes'], true);
    if (is_array($backupCodes) && in_array($code, $backupCodes, true)) {
        $valid = true;
        $usedBackupCode = true;
        // Remove used backup code
        $backupCodes = array_diff($backupCodes, [$code]);
        $pdo->prepare("UPDATE users SET two_factor_backup_codes = ? WHERE id = ?")
            ->execute([json_encode(array_values($backupCodes)), $_SESSION['user_id']]);
    }
}

if (!$valid) {
    $_SESSION['error'] = "Invalid authentication code. Please try again.";
    header("Location: two_factor.php");
    exit;
}

if ($action === 'enable') {
    // Generate backup codes - using 10000000-99999999 range for consistent 8-digit codes
    $backupCodes = [];
    for ($i = 0; $i < 10; $i++) {
        $backupCodes[] = (string)random_int(10000000, 99999999);
    }
    
    // Enable 2FA and save backup codes
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_backup_codes = ? WHERE id = ?");
    $stmt->execute([json_encode($backupCodes), $_SESSION['user_id']]);
    
    $_SESSION['success'] = "Two-factor authentication has been enabled successfully!";
    $_SESSION['backup_codes'] = $backupCodes; // Store temporarily to display
    
} elseif ($action === 'disable') {
    // Disable 2FA but keep the secret for easy re-enabling
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_backup_codes = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    $_SESSION['success'] = "Two-factor authentication has been disabled.";
} else {
    $_SESSION['error'] = "Invalid action.";
}

header("Location: two_factor.php");
exit;
