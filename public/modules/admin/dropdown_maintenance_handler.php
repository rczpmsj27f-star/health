<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

// Require admin access
Auth::requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $category_id = $_POST['category_id'] ?? null;
            $option_value = trim($_POST['option_value'] ?? '');
            $icon_emoji = trim($_POST['icon_emoji'] ?? '');
            $display_order = $_POST['display_order'] ?? 0;
            
            if (empty($category_id) || empty($option_value)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // Verify category exists
            $stmt = $pdo->prepare("SELECT id FROM dropdown_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid category']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO dropdown_options 
                (category_id, option_value, icon_emoji, display_order, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$category_id, $option_value, $icon_emoji ?: null, $display_order]);
            
            echo json_encode(['success' => true, 'message' => 'Option added successfully']);
            break;
            
        case 'edit':
            $option_id = $_POST['option_id'] ?? null;
            $option_value = trim($_POST['option_value'] ?? '');
            $icon_emoji = trim($_POST['icon_emoji'] ?? '');
            $display_order = $_POST['display_order'] ?? 0;
            
            if (empty($option_id) || empty($option_value)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // Verify option exists
            $stmt = $pdo->prepare("SELECT id FROM dropdown_options WHERE id = ?");
            $stmt->execute([$option_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Option not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE dropdown_options 
                SET option_value = ?, icon_emoji = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$option_value, $icon_emoji ?: null, $display_order, $option_id]);
            
            echo json_encode(['success' => true, 'message' => 'Option updated successfully']);
            break;
            
        case 'toggle_active':
            $option_id = $_POST['option_id'] ?? null;
            $is_active = $_POST['is_active'] ?? 0;
            
            if (empty($option_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing option ID']);
                exit;
            }
            
            // Verify option exists
            $stmt = $pdo->prepare("SELECT id FROM dropdown_options WHERE id = ?");
            $stmt->execute([$option_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Option not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE dropdown_options SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $option_id]);
            
            echo json_encode(['success' => true, 'message' => 'Option status updated']);
            break;
            
        case 'reorder':
            $option_id = $_POST['option_id'] ?? null;
            $category_id = $_POST['category_id'] ?? null;
            $direction = $_POST['direction'] ?? '';
            $is_active = $_POST['is_active'] ?? null;
            
            if (empty($option_id) || empty($category_id) || !in_array($direction, ['up', 'down'], true) || !isset($is_active)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            // Get current option
            $stmt = $pdo->prepare("SELECT display_order, is_active, category_id FROM dropdown_options WHERE id = ?");
            $stmt->execute([$option_id]);
            $current = $stmt->fetch();
            
            if (!$current) {
                echo json_encode(['success' => false, 'message' => 'Option not found']);
                exit;
            }
            
            // Verify category ownership
            if ($current['category_id'] !== $category_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid category']);
                exit;
            }
            
            $currentOrder = $current['display_order'];
            $currentIsActive = $current['is_active'];
            
            // Find swap target within same category AND same is_active status
            if ($direction === 'up') {
                $stmt = $pdo->prepare("
                    SELECT id, display_order 
                    FROM dropdown_options 
                    WHERE category_id = ? 
                    AND is_active = ? 
                    AND display_order < ? 
                    ORDER BY display_order DESC 
                    LIMIT 1
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, display_order 
                    FROM dropdown_options 
                    WHERE category_id = ? 
                    AND is_active = ? 
                    AND display_order > ? 
                    ORDER BY display_order ASC 
                    LIMIT 1
                ");
            }
            $stmt->execute([$category_id, $currentIsActive, $currentOrder]);
            $target = $stmt->fetch();
            
            if ($target) {
                $pdo->beginTransaction();
                
                try {
                    // Swap display_order
                    $stmt = $pdo->prepare("UPDATE dropdown_options SET display_order = ? WHERE id = ?");
                    $stmt->execute([$target['display_order'], $option_id]);
                    $stmt->execute([$currentOrder, $target['id']]);
                    
                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'Order updated']);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Cannot move further in this direction']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Dropdown maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
