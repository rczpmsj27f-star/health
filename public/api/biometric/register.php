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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['password']) || !isset($input['credential'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $password = $input['password'];
    $credential = $input['credential'];
    
    // Verify password before enabling biometric
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
        exit;
    }
    
    // Register the credential
    $biometricAuth = new BiometricAuth($pdo);
    $success = $biometricAuth->registerCredential($userId, $credential);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Biometric authentication enabled successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to register credential']);
    }
} catch (Exception $e) {
    error_log("Biometric registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
