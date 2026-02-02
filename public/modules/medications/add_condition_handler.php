<?php
session_start();
require_once "../../../app/config/database.php";

$medId = $_POST['med_id'];
$name  = trim($_POST['condition_name']);

$stmt = $pdo->prepare("INSERT INTO conditions (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
$stmt->execute([$name]);

$condId = $pdo->lastInsertId();

$link = $pdo->prepare("
    INSERT INTO medication_conditions (medication_id, condition_id)
    VALUES (?, ?)
");
$link->execute([$medId, $condId]);

header("Location: /modules/medications/view.php?id=$medId");
exit;
