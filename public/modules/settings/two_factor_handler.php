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
$code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');

if (strlen($code) !== 6) {
    $_SESSION['error'] = "Invalid code format.";
    header("Location: two_factor.php");
    exit;
}

// Get user's 2FA secret
$stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_secret FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || empty($user['two_factor_secret'])) {
    $_SESSION['error'] = "Two-factor authentication not set up.";
    header("Location: two_factor.php");
    exit;
}

$google2fa = new Google2FA();

// Verify the code
$valid = $google2fa->verifyKey($user['two_factor_secret'], $code);

if (!$valid) {
    $_SESSION['error'] = "Invalid authentication code. Please try again.";
    header("Location: two_factor.php");
    exit;
}

if ($action === 'enable') {
    // Generate backup codes
    $backupCodes = [];
    for ($i = 0; $i < 10; $i++) {
        $backupCodes[] = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
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
