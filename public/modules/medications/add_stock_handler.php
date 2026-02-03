<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

try {
    if (!isset($_POST['medication_id']) || !isset($_POST['quantity'])) {
        throw new Exception("Missing required fields");
    }
    
    $medId = intval($_POST['medication_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        throw new Exception("Quantity must be greater than 0");
    }
    
    // Verify medication belongs to user
    $stmt = $pdo->prepare("SELECT id, current_stock FROM medications WHERE id = ? AND user_id = ?");
    $stmt->execute([$medId, $userId]);
    $medication = $stmt->fetch();
    
    if (!$medication) {
        throw new Exception("Medication not found or access denied");
    }
    
    // Update stock
    $currentStock = $medication['current_stock'] ?? 0;
    $newStock = $currentStock + $quantity;
    
    $stmt = $pdo->prepare("
        UPDATE medications 
        SET current_stock = ?, stock_updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$newStock, $medId, $userId]);
    
    $_SESSION['success'] = "Stock updated successfully! Added $quantity units.";
    header("Location: /modules/medications/stock.php");
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to update stock: " . $e->getMessage();
    header("Location: /modules/medications/stock.php");
    exit;
}
