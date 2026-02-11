<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/BiometricAuth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['credentialId']) || !isset($input['assertion'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $credentialId = $input['credentialId'];
    $assertion = $input['assertion'];
    
    // Verify the assertion
    $biometricAuth = new BiometricAuth($pdo);
    $userId = null;
    $verified = $biometricAuth->verifyAssertion($credentialId, $assertion, $userId);
    
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
        
        // Note: last_biometric_login is already updated in BiometricAuth::verifyAssertion
        // Update last_login for consistency with password login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'userId' => $userId
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
