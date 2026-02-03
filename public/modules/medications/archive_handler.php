<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Validate inputs - support both GET and POST
$medId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'med_id', FILTER_VALIDATE_INT);
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$allowedActions = ['archive', 'unarchive'];

if (!$medId || !in_array($action, $allowedActions)) {
    header("Location: /modules/medications/list.php");
    exit;
}

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
    
    // Redirect back to the medication view page with success message
    $_SESSION['success'] = 'Medication archived successfully.';
    header("Location: /modules/medications/view.php?id=$medId");
    exit;
} elseif ($action === 'unarchive') {
    // Unarchive the medication
    $stmt = $pdo->prepare("UPDATE medications SET archived = 0, end_date = NULL WHERE id = ?");
    $stmt->execute([$medId]);
    
    // Redirect back to the medication view page with success message
    $_SESSION['success'] = 'Medication unarchived successfully.';
    header("Location: /modules/medications/view.php?id=$medId");
    exit;
}

header("Location: /modules/medications/list.php");
exit;
