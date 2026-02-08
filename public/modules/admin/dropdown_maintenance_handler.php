<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

// Require admin access
Auth::requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $category_id = $_POST['category_id'] ?? null;
            $option_value = $_POST['option_value'] ?? '';
            $option_label = $_POST['option_label'] ?? '';
            $icon_emoji = $_POST['icon_emoji'] ?? null;
            $display_order = $_POST['display_order'] ?? 0;
            
            if (empty($category_id) || empty($option_value) || empty($option_label)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO dropdown_options 
                (category_id, option_value, option_label, icon_emoji, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$category_id, $option_value, $option_label, $icon_emoji, $display_order]);
            
            echo json_encode(['success' => true, 'message' => 'Option added successfully']);
            break;
            
        case 'edit':
            $option_id = $_POST['option_id'] ?? null;
            $option_value = $_POST['option_value'] ?? '';
            $option_label = $_POST['option_label'] ?? '';
            $icon_emoji = $_POST['icon_emoji'] ?? null;
            $display_order = $_POST['display_order'] ?? 0;
            
            if (empty($option_id) || empty($option_value) || empty($option_label)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE dropdown_options 
                SET option_value = ?, option_label = ?, icon_emoji = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$option_value, $option_label, $icon_emoji, $display_order, $option_id]);
            
            echo json_encode(['success' => true, 'message' => 'Option updated successfully']);
            break;
            
        case 'toggle_active':
            $option_id = $_POST['option_id'] ?? null;
            $is_active = $_POST['is_active'] ?? 0;
            
            if (empty($option_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing option ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE dropdown_options SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $option_id]);
            
            echo json_encode(['success' => true, 'message' => 'Option status updated']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Dropdown maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
