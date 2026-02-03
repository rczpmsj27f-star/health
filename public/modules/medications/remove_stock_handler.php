<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Validate inputs
$medId = filter_input(INPUT_POST, 'medication_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$reason = $_POST['reason'] ?? '';
$otherReason = $_POST['other_reason'] ?? '';

if (!$medId || !$quantity || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid medication or quantity.';
    header("Location: /modules/medications/stock.php");
    exit;
}

// If reason is "Other", use the other_reason field
if ($reason === 'Other' && !empty($otherReason)) {
    $reason = $otherReason;
}

if (empty($reason)) {
    $_SESSION['error'] = 'Please provide a reason for removing stock.';
    header("Location: /modules/medications/stock.php");
    exit;
}

// Verify the medication belongs to the current user
$stmt = $pdo->prepare("SELECT current_stock FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medId, $userId]);
$med = $stmt->fetch();

if (!$med) {
    $_SESSION['error'] = 'Medication not found.';
    header("Location: /modules/medications/stock.php");
    exit;
}

// Check if there's enough stock
$currentStock = $med['current_stock'] ?? 0;
if ($currentStock < $quantity) {
    $_SESSION['error'] = 'Not enough stock to remove. Current stock: ' . $currentStock;
    header("Location: /modules/medications/stock.php");
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Decrease stock
    $stmt = $pdo->prepare("UPDATE medications SET current_stock = current_stock - ?, stock_updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$quantity, $medId, $userId]);
    
    // Log the change
    $stmt = $pdo->prepare("INSERT INTO medication_stock_log (medication_id, user_id, quantity_change, change_type, reason) VALUES (?, ?, ?, 'remove', ?)");
    $stmt->execute([$medId, $userId, -$quantity, $reason]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = 'Stock removed successfully.';
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Failed to remove stock: ' . $e->getMessage();
}

header("Location: /modules/medications/stock.php");
exit;
