<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/BiometricAuth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $biometricAuth = new BiometricAuth($pdo);
    $challenge = $biometricAuth->generateChallenge();
    
    // Store challenge in session for later verification
    $biometricAuth->storeChallenge($challenge);
    
    echo json_encode([
        'success' => true,
        'challenge' => $challenge
    ]);
} catch (Exception $e) {
    error_log("Challenge generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
