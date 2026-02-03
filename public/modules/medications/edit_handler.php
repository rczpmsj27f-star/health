<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Validate input
$medId = filter_input(INPUT_POST, 'med_id', FILTER_VALIDATE_INT);
if (!$medId) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medId, $_SESSION['user_id']]);
$med = $stmt->fetch();

if (!$med) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Validate and update medication name
$medName = trim($_POST['med_name'] ?? '');
if (empty($medName) || strlen($medName) > 255) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_name");
    exit;
}
$stmt = $pdo->prepare("UPDATE medications SET name = ? WHERE id = ?");
$stmt->execute([$medName, $medId]);

// Validate and update dose
$doseAmount = filter_input(INPUT_POST, 'dose_amount', FILTER_VALIDATE_FLOAT);
$doseUnit = trim($_POST['dose_unit'] ?? '');
if ($doseAmount === false || $doseAmount <= 0 || empty($doseUnit) || strlen($doseUnit) > 50) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_dose");
    exit;
}
$stmt = $pdo->prepare("UPDATE medication_doses SET dose_amount = ?, dose_unit = ? WHERE medication_id = ?");
$stmt->execute([$doseAmount, $doseUnit, $medId]);

// Validate and update schedule
$frequencyType = $_POST['frequency_type'] ?? '';
$allowedFrequencies = ['per_day', 'per_week', 'as_needed'];
if (!in_array($frequencyType, $allowedFrequencies)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_frequency");
    exit;
}

$timesPerDay = filter_input(INPUT_POST, 'times_per_day', FILTER_VALIDATE_INT);
$timesPerWeek = filter_input(INPUT_POST, 'times_per_week', FILTER_VALIDATE_INT);

// Validate numeric ranges
if ($timesPerDay !== false && $timesPerDay !== null && ($timesPerDay < 1 || $timesPerDay > 24)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_times_per_day");
    exit;
}
if ($timesPerWeek !== false && $timesPerWeek !== null && ($timesPerWeek < 1 || $timesPerWeek > 100)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_times_per_week");
    exit;
}

// Validate days of week if provided
$daysOfWeek = $_POST['days_of_week'] ?? null;
if ($daysOfWeek !== null) {
    $daysOfWeek = trim($daysOfWeek);
    if (strlen($daysOfWeek) > 100) {
        $daysOfWeek = null;
    }
}

$stmt = $pdo->prepare("
    UPDATE medication_schedules 
    SET frequency_type = ?, times_per_day = ?, times_per_week = ?, days_of_week = ?
    WHERE medication_id = ?
");
$stmt->execute([
    $frequencyType,
    $timesPerDay ?: null,
    $timesPerWeek ?: null,
    $daysOfWeek,
    $medId
]);

// Handle dose times for daily medications
if ($_POST['frequency_type'] === 'per_day' && !empty($_POST['times_per_day']) && $_POST['times_per_day'] > 1) {
    // First, delete existing dose times
    $pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
    
    // Then insert new dose times
    for ($i = 1; $i <= $_POST['times_per_day']; $i++) {
        $timeKey = "dose_time_$i";
        if (!empty($_POST[$timeKey])) {
            // Validate time format (HH:MM)
            $doseTime = trim($_POST[$timeKey]);
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $doseTime)) {
                continue; // Skip invalid time formats
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO medication_dose_times (medication_id, dose_number, dose_time)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$medId, $i, $doseTime]);
        }
    }
} else {
    // Clear dose times if not applicable
    $pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
}

// Update instructions - delete existing and insert new ones
$pdo->prepare("DELETE FROM medication_instructions WHERE medication_id = ?")->execute([$medId]);

if (!empty($_POST['instructions'])) {
    foreach ($_POST['instructions'] as $instruction) {
        $instruction = trim($instruction);
        if (empty($instruction) || strlen($instruction) > 500) {
            continue; // Skip empty or too long instructions
        }
        $stmt = $pdo->prepare("
            INSERT INTO medication_instructions (medication_id, instruction_text)
            VALUES (?, ?)
        ");
        $stmt->execute([$medId, $instruction]);
    }
}

if (!empty($_POST['other_instructions'])) {
    $otherInstructions = explode("\n", $_POST['other_instructions']);
    foreach ($otherInstructions as $instruction) {
        $instruction = trim($instruction);
        if (!empty($instruction)) {
            $stmt = $pdo->prepare("
                INSERT INTO medication_instructions (medication_id, instruction_text)
                VALUES (?, ?)
            ");
            $stmt->execute([$medId, $instruction]);
        }
    }
}

header("Location: /modules/medications/view.php?id=$medId");
exit;
