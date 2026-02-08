<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');

try {
    // Check session first
    if (empty($_SESSION['user_id'])) {
        error_log("Notifications API: No user_id in session");
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'debug' => 'No session']);
        exit;
    }
    
    // Include database
    require_once "../../app/config/database.php";
    
    if (!isset($pdo)) {
        error_log("Notifications API: PDO not available");
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    require_once "../../app/core/NotificationHelper.php";
    
    $notificationHelper = new NotificationHelper($pdo);
    $userId = $_SESSION['user_id'];

    // GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_recent':
                $notifications = $notificationHelper->getRecent($userId, 10);
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;
                
            case 'get_count':
                $count = $notificationHelper->getUnreadCount($userId);
                echo json_encode([
                    'success' => true,
                    'count' => $count
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action: ' . $action]);
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
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action: ' . $action]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    
} catch (Exception $e) {
    error_log("Notifications API Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
