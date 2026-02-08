<?php
session_start();
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$userId = $_SESSION['user_id'];

// Get the use_24_hour preference from POST
$use24Hour = isset($_POST['use_24_hour']) ? intval($_POST['use_24_hour']) : 0;

try {
    // Update the use_24_hour preference
    $stmt = $pdo->prepare("
        INSERT INTO user_preferences 
            (user_id, use_24_hour) 
        VALUES 
            (?, ?)
        ON DUPLICATE KEY UPDATE
            use_24_hour = ?,
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$userId, $use24Hour, $use24Hour]);
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Failed to save time preference for user $userId: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save preference']);
}
