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

// Validate and update medication name, icon, and color
$medName = trim($_POST['med_name'] ?? '');
if (empty($medName) || strlen($medName) > 255) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_name");
    exit;
}

$icon = $_POST['medication_icon'] ?? 'pill';
$color = $_POST['medication_color'] ?? '#5b21b6';

// Validate color format (hex color)
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $color = '#5b21b6'; // Default to purple if invalid
}

$stmt = $pdo->prepare("UPDATE medications SET name = ?, icon = ?, color = ? WHERE id = ?");
$stmt->execute([$medName, $icon, $color, $medId]);

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
$isPrn = isset($_POST['is_prn']) && $_POST['is_prn'] == '1' ? 1 : 0;
$frequencyType = $_POST['frequency_type'] ?? '';

// Validate frequency type
if ($isPrn) {
    // PRN should not have a frequency type
    if (!empty($frequencyType)) {
        $frequencyType = null;
    }
} else {
    $allowedFrequencies = ['per_day', 'per_week', 'as_needed'];
    if (!in_array($frequencyType, $allowedFrequencies)) {
        header("Location: /modules/medications/edit.php?id=$medId&error=invalid_frequency");
        exit;
    }
}

$timesPerDay = filter_input(INPUT_POST, 'times_per_day', FILTER_VALIDATE_INT);
$timesPerWeek = filter_input(INPUT_POST, 'times_per_week', FILTER_VALIDATE_INT);
$initialDose = filter_input(INPUT_POST, 'initial_dose', FILTER_VALIDATE_INT);
$subsequentDose = filter_input(INPUT_POST, 'subsequent_dose', FILTER_VALIDATE_INT);
$maxDosesPerDay = filter_input(INPUT_POST, 'max_doses_per_day', FILTER_VALIDATE_INT);
$minHoursBetweenDoses = filter_input(INPUT_POST, 'min_hours_between_doses', FILTER_VALIDATE_FLOAT);

// Validate initial and subsequent doses
if ($initialDose !== false && $initialDose !== null && ($initialDose < 1 || $initialDose > 10)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_initial_dose");
    exit;
}
if ($subsequentDose !== false && $subsequentDose !== null && ($subsequentDose < 1 || $subsequentDose > 10)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_subsequent_dose");
    exit;
}

// Validate numeric ranges
if ($timesPerDay !== false && $timesPerDay !== null && ($timesPerDay < 1 || $timesPerDay > 24)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_times_per_day");
    exit;
}
if ($timesPerWeek !== false && $timesPerWeek !== null && ($timesPerWeek < 1 || $timesPerWeek > 100)) {
    header("Location: /modules/medications/edit.php?id=$medId&error=invalid_times_per_week");
    exit;
}

// Set appropriate values based on PRN or frequency type
if ($isPrn) {
    $timesPerDay = null;
    $timesPerWeek = null;
    $frequencyType = null;
} elseif ($frequencyType === 'per_day') {
    $timesPerWeek = null;
    $maxDosesPerDay = null;
    $minHoursBetweenDoses = null;
} elseif ($frequencyType === 'per_week') {
    $timesPerDay = null;
    $maxDosesPerDay = null;
    $minHoursBetweenDoses = null;
} else {
    $timesPerDay = null;
    $timesPerWeek = null;
    $maxDosesPerDay = null;
    $minHoursBetweenDoses = null;
}

// Validate days of week if provided
$daysOfWeek = null;
if (!empty($_POST['days_of_week']) && is_array($_POST['days_of_week'])) {
    $daysOfWeek = implode(', ', $_POST['days_of_week']);
} elseif (!empty($_POST['days_of_week'])) {
    $daysOfWeek = trim($_POST['days_of_week']);
    if (strlen($daysOfWeek) > 100) {
        $daysOfWeek = null;
    }
}

$stmt = $pdo->prepare("
    UPDATE medication_schedules 
    SET frequency_type = ?, times_per_day = ?, times_per_week = ?, days_of_week = ?, 
        is_prn = ?, initial_dose = ?, subsequent_dose = ?, max_doses_per_day = ?, min_hours_between_doses = ?
    WHERE medication_id = ?
");
$stmt->execute([
    $frequencyType,
    $timesPerDay,
    $timesPerWeek,
    $daysOfWeek,
    $isPrn,
    $isPrn && $initialDose ? $initialDose : null,
    $isPrn && $subsequentDose ? $subsequentDose : null,
    $maxDosesPerDay,
    $minHoursBetweenDoses,
    $medId
]);

// Handle dose times for daily medications
if ($frequencyType === 'per_day' && $timesPerDay && $timesPerDay >= 1) {
    // First, delete existing dose times
    $pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
    
    // Then insert new dose times
    for ($i = 1; $i <= $timesPerDay; $i++) {
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
} elseif ($frequencyType !== 'per_day') {
    // Clear dose times if frequency changed from daily
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
            // Validate length (same as form instructions)
            if (strlen($instruction) > 500) {
                continue; // Skip too long instructions
            }
            $stmt = $pdo->prepare("
                INSERT INTO medication_instructions (medication_id, instruction_text)
                VALUES (?, ?)
            ");
            $stmt->execute([$medId, $instruction]);
        }
    }
}

// Update current stock if provided
if (isset($_POST['current_stock'])) {
    $currentStock = filter_input(INPUT_POST, 'current_stock', FILTER_VALIDATE_INT);
    if ($currentStock !== false && $currentStock !== null && $currentStock >= 0) {
        $stmt = $pdo->prepare("UPDATE medications SET current_stock = ?, stock_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$currentStock, $medId]);
    }
}

header("Location: /modules/medications/view.php?id=$medId");
exit;
