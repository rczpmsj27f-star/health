<?php
require_once "../../../app/config/database.php";

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    INSERT INTO medications (user_id, nhs_medication_id, name)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $userId,
    $_POST['nhs_med_id'] ?: null,
    $_POST['med_name']
]);

$medId = $pdo->lastInsertId();

header("Location: /modules/medications/add_dose.php?med=$medId");
exit;
