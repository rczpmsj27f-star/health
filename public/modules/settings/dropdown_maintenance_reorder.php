<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

$id = (int)$_POST['id'];
$direction = $_POST['direction'] ?? ''; // 'up' or 'down'

// Validate direction parameter
if (!in_array($direction, ['up', 'down'], true)) {
    http_response_code(400);
    die("Invalid direction parameter");
}

$category = $_POST['category'] ?? '';

// Get the category_id from category_key
$stmt = $pdo->prepare("SELECT id FROM dropdown_categories WHERE category_key = ?");
$stmt->execute([$category]);
$categoryRow = $stmt->fetch();

if (!$categoryRow) {
    die("Error: Invalid category");
}

$categoryId = $categoryRow['id'];

// Get current item
$stmt = $pdo->prepare("SELECT display_order, is_active FROM dropdown_options WHERE id = ?");
$stmt->execute([$id]);
$current = $stmt->fetch();

if (!$current) die("Item not found");

$currentOrder = $current['display_order'];
$isActive = $current['is_active'];

// Find swap target (same category AND same is_active status)
if ($direction === 'up') {
    $stmt = $pdo->prepare("SELECT id, display_order 
                           FROM dropdown_options 
                           WHERE category_id = ? 
                           AND is_active = ? 
                           AND display_order < ? 
                           ORDER BY display_order DESC 
                           LIMIT 1");
} else {
    $stmt = $pdo->prepare("SELECT id, display_order 
                           FROM dropdown_options 
                           WHERE category_id = ? 
                           AND is_active = ? 
                           AND display_order > ? 
                           ORDER BY display_order ASC 
                           LIMIT 1");
}
$stmt->execute([$categoryId, $isActive, $currentOrder]);
$target = $stmt->fetch();

if ($target) {
    try {
        $pdo->beginTransaction();
        
        // Swap display_order
        $stmt = $pdo->prepare("UPDATE dropdown_options SET display_order = ? WHERE id = ?");
        $stmt->execute([$target['display_order'], $id]);
        $stmt->execute([$currentOrder, $target['id']]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        die("Error: Failed to reorder items");
    }
}

header("Location: dropdown_maintenance.php?category=" . urlencode($category));
exit;
