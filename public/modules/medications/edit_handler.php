<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = $_POST['med_id'];

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medId, $_SESSION['user_id']]);
$med = $stmt->fetch();

if (!$med) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Update medication name
$stmt = $pdo->prepare("UPDATE medications SET name = ? WHERE id = ?");
$stmt->execute([$_POST['med_name'], $medId]);

// Update dose
$stmt = $pdo->prepare("UPDATE medication_doses SET dose_amount = ?, dose_unit = ? WHERE medication_id = ?");
$stmt->execute([$_POST['dose_amount'], $_POST['dose_unit'], $medId]);

// Update schedule
$stmt = $pdo->prepare("
    UPDATE medication_schedules 
    SET frequency_type = ?, times_per_day = ?, times_per_week = ?, days_of_week = ?
    WHERE medication_id = ?
");
$stmt->execute([
    $_POST['frequency_type'],
    $_POST['times_per_day'] ?: null,
    $_POST['times_per_week'] ?: null,
    $_POST['days_of_week'] ?: null,
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
            $stmt = $pdo->prepare("
                INSERT INTO medication_dose_times (medication_id, dose_number, dose_time)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$medId, $i, $_POST[$timeKey]]);
        }
    }
} else {
    // Clear dose times if not applicable
    $pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
}

// Update instructions - delete existing and insert new ones
$pdo->prepare("DELETE FROM medication_instructions WHERE medication_id = ?")->execute([$medId]);

if (!empty($_POST['instructions'])) {
    foreach ($_POST['instructions'] as $i) {
        $stmt = $pdo->prepare("
            INSERT INTO medication_instructions (medication_id, instruction_text)
            VALUES (?, ?)
        ");
        $stmt->execute([$medId, $i]);
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
