<?php
session_start();
require_once "../../../app/config/database.php";

$medId = $_POST['med_id'];

$stmt = $pdo->prepare("
    INSERT INTO medication_schedules (medication_id, frequency_type, times_per_day, times_per_week, days_of_week)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $medId,
    $_POST['frequency_type'],
    $_POST['times_per_day'] ?: null,
    $_POST['times_per_week'] ?: null,
    $_POST['days_of_week'] ?: null
]);

// Handle dose times for daily medications
if ($_POST['frequency_type'] === 'per_day' && !empty($_POST['times_per_day']) && $_POST['times_per_day'] > 1) {
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
}

header("Location: /modules/medications/add_instructions.php?med=" . $medId);
exit;
