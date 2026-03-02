<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

    if (!isset($input['password']) || !isset($input['credential_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $password = $input['password'];
    $credentialId = $input['credential_id'];
    $biometricType = isset($input['biometric_type']) ? (int)$input['biometric_type'] : 0;

    // Verify password before enabling biometric
    $stmt = $pdo->prepare("SELECT password_hash, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
        exit;
    }

    // Register the native biometric credential
    $biometricAuth = new BiometricAuth($pdo);
    $success = $biometricAuth->registerNativeCredential($userId, $credentialId, $biometricType);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Biometric authentication enabled successfully',
            'username' => $user['username']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to store credential']);
    }
} catch (Exception $e) {
    error_log("Biometric registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
