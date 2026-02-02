<?php
session_start();
require_once "../../../app/config/database.php";

$stmt = $pdo->prepare("
    INSERT INTO medication_schedules (medication_id, frequency_type, times_per_day, times_per_week, days_of_week)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $_POST['med_id'],
    $_POST['frequency_type'],
    $_POST['times_per_day'] ?: null,
    $_POST['times_per_week'] ?: null,
    $_POST['days_of_week'] ?: null
]);

header("Location: /modules/medications/add_instructions.php?med=" . $_POST['med_id']);
exit;
