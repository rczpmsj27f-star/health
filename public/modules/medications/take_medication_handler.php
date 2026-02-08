<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/NotificationHelper.php";

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

$linkedHelper = new LinkedUserHelper($pdo);
$notificationHelper = new NotificationHelper($pdo);

// Check if taking for linked user
$forUserId = $_POST['for_user_id'] ?? $_SESSION['user_id'];
$isForLinkedUser = $forUserId != $_SESSION['user_id'];
$linkedUser = null;

if ($isForLinkedUser) {
    // Verify permission
    $linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);
    if (!$linkedUser || $linkedUser['linked_user_id'] != $forUserId) {
        $errorMsg = "Invalid user";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
        $_SESSION['error'] = $errorMsg;
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
    
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    if (!$myPermissions || !$myPermissions['can_mark_taken']) {
        $errorMsg = "You don't have permission to mark medications as taken";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
        $_SESSION['error'] = $errorMsg;
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
}

$userId = $forUserId; // Use the target user ID for all medication operations
$medicationId = $_POST['medication_id'] ?? null;
$scheduledDateTime = $_POST['scheduled_date_time'] ?? null;

// Sanitize and validate late_logging_reason
$lateLoggingReason = null;
if (!empty($_POST['late_logging_reason'])) {
    $lateLoggingReason = trim($_POST['late_logging_reason']);
    // Limit to VARCHAR(255) constraint
    if (strlen($lateLoggingReason) > 255) {
        $lateLoggingReason = substr($lateLoggingReason, 0, 255);
    }
}

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
            SET status = 'taken', taken_at = NOW(), skipped_reason = NULL, late_logging_reason = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$lateLoggingReason, $existingLog['id']]);
        
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
            INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status, taken_at, late_logging_reason)
            VALUES (?, ?, ?, 'taken', NOW(), ?)
        ");
        $stmt->execute([$medicationId, $userId, $scheduledDateTime, $lateLoggingReason]);
        
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
    
    // Send notification if taking for linked user
    if ($isForLinkedUser) {
        // Check if they want to be notified
        $theirPermissions = $linkedHelper->getPermissions($linkedUser['id'], $forUserId);
        if ($theirPermissions && $theirPermissions['notify_on_medication_taken']) {
            // Get sender name
            $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $myNameRow = $stmt->fetch();
            
            if ($myNameRow) {
                $myName = $myNameRow['first_name'];
                
                // Send notification
                $notificationHelper->create(
                    $forUserId,
                    'partner_took_med',
                    'Medication Taken',
                    $myName . ' marked "' . $medication['name'] . '" as taken for you',
                    $_SESSION['user_id'],
                    $medicationId
                );
            }
        }
    }
    
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
