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
