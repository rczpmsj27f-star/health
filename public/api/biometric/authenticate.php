<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/BiometricAuth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['credential_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing credential ID']);
        exit;
    }

    $credentialId = $input['credential_id'];

    // Verify the native biometric credential and get the user
    $biometricAuth = new BiometricAuth($pdo);
    $userId = null;
    $verified = $biometricAuth->verifyNativeCredential($credentialId, $userId);

    if ($verified && $userId) {
        // Create session
        $_SESSION['user_id'] = $userId;

        // Get user details for header caching
        $stmt = $pdo->prepare("SELECT first_name, surname, email, profile_picture_path FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Cache header display info in session (one-time lookup)
        $_SESSION['header_display_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        if (empty($_SESSION['header_display_name'])) {
            $_SESSION['header_display_name'] = explode('@', $user['email'] ?? 'User')[0];
        }
        $_SESSION['header_avatar_url'] = !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '/assets/images/default-avatar.svg';

        // Update last_login for consistency with password login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);

        // Set biometric flags and bypass 2FA
        $_SESSION['biometric_auth'] = true;
        $_SESSION['two_factor_verified'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'userId' => $userId,
            'redirect' => '/dashboard.php',
            'bypass_2fa' => true
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication failed']);
    }
} catch (Exception $e) {
    error_log("Biometric authentication error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
