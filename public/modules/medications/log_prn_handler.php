<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get quantity taken from POST (default to 1 for backwards compatibility)
$quantityTaken = !empty($_POST['quantity_taken']) ? (int)$_POST['quantity_taken'] : 1;
// Ensure quantity is within reasonable bounds
$quantityTaken = max(1, min(10, $quantityTaken));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['medication_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: /modules/medications/log_prn.php");
    exit;
}

$medicationId = $_POST['medication_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Verify user owns this medication and it's a PRN medication
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, m.current_stock, ms.doses_per_administration, ms.max_doses_per_day, ms.min_hours_between_doses
        FROM medications m
        LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.id = ? AND m.user_id = ? AND ms.is_prn = 1
    ");
    $stmt->execute([$medicationId, $userId]);
    $medication = $stmt->fetch();
    
    if (!$medication) {
        throw new Exception("Medication not found or is not a PRN medication.");
    }
    
    $dosesPerAdmin = $medication['doses_per_administration'] ?? 1;
    
    // 2. Check if max doses reached in last 24 hours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as dose_count, MAX(taken_at) as last_taken, MIN(taken_at) as first_taken
        FROM medication_logs 
        WHERE medication_id = ? 
        AND user_id = ?
        AND taken_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND status = 'taken'
    ");
    $stmt->execute([$medicationId, $userId]);
    $logData = $stmt->fetch();
    
    $doseCount = $logData['dose_count'] ?? 0;
    $lastTaken = $logData['last_taken'];
    $firstTaken = $logData['first_taken'];
    $maxDoses = $medication['max_doses_per_day'] ?? 999;
    $minHours = $medication['min_hours_between_doses'] ?? 0;
    
    // Check max doses limit
    if ($doseCount >= $maxDoses) {
        // Calculate when the next dose will be available (24 hours after the first dose in this period)
        $nextAvailableTimestamp = strtotime($firstTaken) + (24 * 3600);
        $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        throw new Exception("Maximum daily dose limit reached. Next dose available at {$nextAvailableTime}.");
    }
    
    // 3. Check if minimum time has passed since last dose
    if ($lastTaken && $minHours > 0) {
        $lastTakenTimestamp = strtotime($lastTaken);
        $minGapSeconds = $minHours * 3600;
        $nextAvailableTimestamp = $lastTakenTimestamp + $minGapSeconds;
        $timeRemaining = $nextAvailableTimestamp - time();
        
        if ($timeRemaining > 0) {
            $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
            throw new Exception("You must wait at least {$minHours} hours between doses. Next dose available at {$nextAvailableTime}.");
        }
    }
    
    // 4. Log the dose with quantity
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status, quantity_taken, taken_at)
        VALUES (?, ?, ?, 'taken', ?, ?)
    ");
    $stmt->execute([$medicationId, $userId, $now, $quantityTaken, $now]);
    
    // 5. Decrement stock by quantity taken (quantity * doses_per_administration)
    if ($medication['current_stock'] !== null && $medication['current_stock'] > 0) {
        $stockToRemove = $quantityTaken * $dosesPerAdmin;
        $stockToRemove = min($stockToRemove, $medication['current_stock']); // Don't go below 0
        
        $stmt = $pdo->prepare("
            UPDATE medications 
            SET current_stock = GREATEST(0, current_stock - ?), stock_updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$stockToRemove, $medicationId, $userId]);
        
        // Log stock change
        $stmt = $pdo->prepare("
            INSERT INTO medication_stock_log (medication_id, user_id, quantity_change, change_type, reason)
            VALUES (?, ?, ?, 'remove', ?)
        ");
        $tabletText = $quantityTaken > 1 ? "{$quantityTaken} tablets" : "1 tablet";
        $reason = "PRN dose taken ({$tabletText})";
        $stmt->execute([$medicationId, $userId, -$stockToRemove, $reason]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Calculate next available time for success message
    $nextDoseMessage = "";
    if ($minHours > 0) {
        $nextAvailableTimestamp = time() + ($minHours * 3600);
        $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        $nextDoseMessage = " You can take the next dose at {$nextAvailableTime}.";
    }
    
    $currentTime = date('H:i');
    $tabletText = $quantityTaken > 1 ? "{$quantityTaken} tablets" : "1 tablet";
    $_SESSION['success'] = "Took {$tabletText} at {$currentTime}.{$nextDoseMessage}";
    header("Location: /modules/medications/log_prn.php");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: /modules/medications/log_prn.php");
    exit;
}
