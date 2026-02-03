<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = $_POST['med_id'];
$action = $_POST['action'];

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

if (!$med || $med['user_id'] != $_SESSION['user_id']) {
    header("Location: /modules/medications/list.php");
    exit;
}

if ($action === 'archive') {
    // Archive the medication
    $stmt = $pdo->prepare("UPDATE medications SET archived = 1, end_date = NOW() WHERE id = ?");
    $stmt->execute([$medId]);
} elseif ($action === 'unarchive') {
    // Unarchive the medication
    $stmt = $pdo->prepare("UPDATE medications SET archived = 0, end_date = NULL WHERE id = ?");
    $stmt->execute([$medId]);
}

header("Location: /modules/medications/list.php");
exit;
