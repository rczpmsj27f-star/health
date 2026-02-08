<?php
session_start();
require_once "../../app/config/database.php";
require_once "../../app/core/NotificationHelper.php";

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$notificationHelper = new NotificationHelper($pdo);
$userId = $_SESSION['user_id'];

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_recent':
            $notifications = $notificationHelper->getRecent($userId, 10);
            echo json_encode(['notifications' => $notifications]);
            break;
            
        case 'get_count':
            $count = $notificationHelper->getUnreadCount($userId);
            echo json_encode(['count' => $count]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

// POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $notificationId = $input['notification_id'] ?? 0;
            $notificationHelper->markAsRead($notificationId, $userId);
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_all_read':
            $notificationHelper->markAllAsRead($userId);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
