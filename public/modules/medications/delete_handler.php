<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/helpers/security.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Require POST method for delete operation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_msg'] = 'Invalid request method';
    header("Location: /modules/medications/medication_dashboard.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error_msg'] = 'Invalid security token. Please try again.';
    header("Location: /modules/medications/medication_dashboard.php");
    exit;
}

// Validate medication ID
$medId = filter_input(INPUT_POST, 'med_id', FILTER_VALIDATE_INT);
if (!$medId) {
    $_SESSION['error_msg'] = 'Invalid medication ID';
    header("Location: /modules/medications/medication_dashboard.php");
    exit;
}

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

if (!$med || $med['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error_msg'] = 'Medication not found or access denied';
    header("Location: /modules/medications/medication_dashboard.php");
    exit;
}

// Delete related records first (foreign key constraints)
try {
    $pdo->prepare("DELETE FROM medication_dose_times WHERE medication_id = ?")->execute([$medId]);
} catch (PDOException $e) {
    // Table doesn't exist yet (SQLSTATE 42S02) - continue with other deletions
    // For other errors, we could log them, but for now we'll continue gracefully
}
$pdo->prepare("DELETE FROM medication_alerts WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_instructions WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_schedules WHERE medication_id = ?")->execute([$medId]);
$pdo->prepare("DELETE FROM medication_doses WHERE medication_id = ?")->execute([$medId]);

// Delete the medication itself
$stmt = $pdo->prepare("DELETE FROM medications WHERE id = ?");
$stmt->execute([$medId]);

$_SESSION['success'] = 'Medication deleted successfully.';
header("Location: /modules/medications/medication_dashboard.php");
exit;
