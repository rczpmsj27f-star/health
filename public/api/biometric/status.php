<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/BiometricAuth.php';

// Require authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $biometricAuth = new BiometricAuth($pdo);
    $userId = $_SESSION['user_id'];
    
    $isEnabled = $biometricAuth->isEnabled($userId);
    $credential = null;
    
    if ($isEnabled) {
        $credential = $biometricAuth->getCredential($userId);
    }
    
    echo json_encode([
        'success' => true,
        'enabled' => $isEnabled,
        'credential' => $credential
    ]);
} catch (Exception $e) {
    error_log("Biometric status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
