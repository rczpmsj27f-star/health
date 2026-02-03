<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

// Check if this is an AJAX request
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if (empty($_SESSION['user_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    header("Location: /login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    header("Location: /modules/medications/dashboard.php");
    exit;
}

$userId = $_SESSION['user_id'];
$medicationId = $_POST['medication_id'] ?? null;
$scheduledDateTime = $_POST['scheduled_date_time'] ?? null;

if (!$medicationId || !$scheduledDateTime) {
    $errorMsg = "Invalid medication or schedule information.";
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    $_SESSION['error'] = $errorMsg;
    header("Location: /modules/medications/dashboard.php");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify the medication belongs to the user
    $stmt = $pdo->prepare("SELECT id, name, current_stock FROM medications WHERE id = ? AND user_id = ?");
    $stmt->execute([$medicationId, $userId]);
    $medication = $stmt->fetch();
    
    if (!$medication) {
        throw new Exception("Medication not found.");
    }
    
    // Check if already logged
    $stmt = $pdo->prepare("
        SELECT id, status FROM medication_logs 
        WHERE medication_id = ? AND user_id = ? AND scheduled_date_time = ?
    ");
    $stmt->execute([$medicationId, $userId, $scheduledDateTime]);
    $existingLog = $stmt->fetch();
    
    if ($existingLog) {
        // Update existing log
        $stmt = $pdo->prepare("
            UPDATE medication_logs 
            SET status = 'taken', taken_at = NOW(), skipped_reason = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$existingLog['id']]);
        
        // Only decrement stock if previously not taken
        if ($existingLog['status'] !== 'taken' && $medication['current_stock'] !== null && $medication['current_stock'] > 0) {
            $stmt = $pdo->prepare("UPDATE medications SET current_stock = current_stock - 1, stock_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$medicationId]);
            
            // Log the stock change
            $stmt = $pdo->prepare("
                INSERT INTO medication_stock_log (medication_id, user_id, quantity_change, change_type, reason)
                VALUES (?, ?, -1, 'remove', 'Medication taken')
            ");
            $stmt->execute([$medicationId, $userId]);
        }
    } else {
        // Create new log entry
        $stmt = $pdo->prepare("
            INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status, taken_at)
            VALUES (?, ?, ?, 'taken', NOW())
        ");
        $stmt->execute([$medicationId, $userId, $scheduledDateTime]);
        
        // Decrement stock by 1 if stock tracking is enabled
        if ($medication['current_stock'] !== null && $medication['current_stock'] > 0) {
            $stmt = $pdo->prepare("UPDATE medications SET current_stock = current_stock - 1, stock_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$medicationId]);
            
            // Log the stock change
            $stmt = $pdo->prepare("
                INSERT INTO medication_stock_log (medication_id, user_id, quantity_change, change_type, reason)
                VALUES (?, ?, -1, 'remove', 'Medication taken')
            ");
            $stmt->execute([$medicationId, $userId]);
        }
    }
    
    $pdo->commit();
    
    $successMsg = htmlspecialchars($medication['name']) . " marked as taken";
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $successMsg]);
        exit;
    }
    
    $_SESSION['success'] = $successMsg;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $errorMsg = "Error logging medication: " . $e->getMessage();
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    
    $_SESSION['error'] = $errorMsg;
}

header("Location: /modules/medications/dashboard.php");
exit;
