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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    $biometricAuth = new BiometricAuth($pdo);
    $success = $biometricAuth->disable($userId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Biometric authentication disabled successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to disable biometric authentication']);
    }
} catch (Exception $e) {
    error_log("Biometric disable error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
