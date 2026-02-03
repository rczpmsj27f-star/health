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
$skippedReason = $_POST['skipped_reason'] ?? null;

if (!$medicationId || !$scheduledDateTime || !$skippedReason) {
    $errorMsg = "Invalid medication, schedule, or reason information.";
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
    $stmt = $pdo->prepare("SELECT id, name FROM medications WHERE id = ? AND user_id = ?");
    $stmt->execute([$medicationId, $userId]);
    $medication = $stmt->fetch();
    
    if (!$medication) {
        throw new Exception("Medication not found.");
    }
    
    // Check if already logged
    $stmt = $pdo->prepare("
        SELECT id FROM medication_logs 
        WHERE medication_id = ? AND user_id = ? AND scheduled_date_time = ?
    ");
    $stmt->execute([$medicationId, $userId, $scheduledDateTime]);
    $existingLog = $stmt->fetch();
    
    if ($existingLog) {
        // Update existing log
        $stmt = $pdo->prepare("
            UPDATE medication_logs 
            SET status = 'skipped', skipped_reason = ?, taken_at = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$skippedReason, $existingLog['id']]);
    } else {
        // Create new log entry
        $stmt = $pdo->prepare("
            INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status, skipped_reason)
            VALUES (?, ?, ?, 'skipped', ?)
        ");
        $stmt->execute([$medicationId, $userId, $scheduledDateTime, $skippedReason]);
    }
    
    $pdo->commit();
    
    $successMsg = htmlspecialchars($medication['name']) . " marked as skipped";
    
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
