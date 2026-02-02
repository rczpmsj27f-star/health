<?php
session_start();
require_once "../../../app/config/database.php";

$stmt = $pdo->prepare("
    INSERT INTO medication_doses (medication_id, dose_amount, dose_unit)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $_POST['med_id'],
    $_POST['dose_amount'],
    $_POST['dose_unit']
]);

header("Location: /modules/medications/add_schedule.php?med=" . $_POST['med_id']);
exit;
