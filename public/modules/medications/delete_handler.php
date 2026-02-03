<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = $_POST['med_id'];

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

if (!$med || $med['user_id'] != $_SESSION['user_id']) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Delete related records first (foreign key constraints)
$pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_alerts WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_instructions WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_schedules WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_doses WHERE medication_id = ?")->execute([$medId]);

// Delete the medication itself
$stmt = $pdo->prepare("DELETE FROM medications WHERE id = ?");
$stmt->execute([$medId]);

header("Location: /modules/medications/list.php");
exit;
