<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Date format constant for next dose time display
define('NEXT_DOSE_DATE_FORMAT', 'H:i \o\n d M');  // e.g., "14:30 on 06 Feb"

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
        SELECT m.id, m.name, m.current_stock, ms.initial_dose, ms.subsequent_dose, ms.max_doses_per_day, ms.min_hours_between_doses
        FROM medications m
        LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.id = ? AND m.user_id = ? AND ms.is_prn = 1
    ");
    $stmt->execute([$medicationId, $userId]);
    $medication = $stmt->fetch();
    
    if (!$medication) {
        throw new Exception("Medication not found or is not a PRN medication.");
    }
    
    $initialDose = $medication['initial_dose'] ?? 1;
    $subsequentDose = $medication['subsequent_dose'] ?? 1;
    
    // 2. Check if max doses reached in last 24 hours
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity_taken), 0) as dose_count, MAX(taken_at) as last_taken, MIN(taken_at) as first_taken
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
    
    // Determine if this is the first dose in the 24-hour period
    $isFirstDose = ($doseCount == 0);
    $tabletsPerDose = $isFirstDose ? $initialDose : $subsequentDose;
    
    // Check max doses limit
    if ($doseCount >= $maxDoses) {
        // Calculate when the next dose will be available (24 hours after the first dose in this period)
        $nextAvailableTimestamp = strtotime($firstTaken) + (24 * 3600);
        
        // Format time with date if it's on a different day than today
        $todayEnd = strtotime('tomorrow') - 1;
        if ($nextAvailableTimestamp > $todayEnd) {
            $nextAvailableTime = date('H:i, j M', $nextAvailableTimestamp);
        } else {
            $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        }
        
        throw new Exception("Maximum daily dose limit reached. Next dose available at {$nextAvailableTime}.");
    }
    
    // 3. Check if minimum time has passed since last dose
    if ($lastTaken && $minHours > 0) {
        $lastTakenTimestamp = strtotime($lastTaken);
        $minGapSeconds = $minHours * 3600;
        $nextAvailableTimestamp = $lastTakenTimestamp + $minGapSeconds;
        $timeRemaining = $nextAvailableTimestamp - time();
        
        if ($timeRemaining > 0) {
            // Show date if next dose is on a different day
            $todayEnd = strtotime('tomorrow') - 1;
            if ($nextAvailableTimestamp > $todayEnd) {
                $nextAvailableTime = date(NEXT_DOSE_DATE_FORMAT, $nextAvailableTimestamp);
            } else {
                $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
            }
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
    
    // 5. Decrement stock by (doses_taken × tablets_per_dose)
    if ($medication['current_stock'] !== null && $medication['current_stock'] > 0) {
        $stockToRemove = $quantityTaken * $tabletsPerDose;
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
        $doseText = $quantityTaken > 1 ? "{$quantityTaken} doses" : "1 dose";
        $reason = "PRN dose taken ({$doseText}, {$stockToRemove} tablets)";
        $stmt->execute([$medicationId, $userId, -$stockToRemove, $reason]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Calculate next available time for success message
    $nextDoseMessage = "";
    if ($minHours > 0) {
        $nextAvailableTimestamp = time() + ($minHours * 3600);
        // Show date if next dose is on a different day
        $todayEnd = strtotime('tomorrow') - 1;
        if ($nextAvailableTimestamp > $todayEnd) {
            $nextAvailableTime = date(NEXT_DOSE_DATE_FORMAT, $nextAvailableTimestamp);
        } else {
            $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        }
        $nextDoseMessage = " You can take the next dose at {$nextAvailableTime}.";
    }
    
    $currentTime = date('H:i');
    $doseText = $quantityTaken > 1 ? "{$quantityTaken} doses" : "1 dose";
    $_SESSION['success'] = "Took {$doseText} at {$currentTime}.{$nextDoseMessage}";
    header("Location: /modules/medications/log_prn.php");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: /modules/medications/log_prn.php");
    exit;
}
