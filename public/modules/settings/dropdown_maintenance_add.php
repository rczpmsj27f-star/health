<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

$category = $_POST['category'] ?? '';
$optionText = trim($_POST['option_text'] ?? '');
$emoji = trim($_POST['emoji'] ?? '');

// Validation
if (empty($category) || empty($optionText)) {
    die("Error: Missing required fields");
}

// Get the category_id from category_key
$stmt = $pdo->prepare("SELECT id FROM dropdown_categories WHERE category_key = ?");
$stmt->execute([$category]);
$categoryRow = $stmt->fetch();

if (!$categoryRow) {
    die("Error: Invalid category");
}

$categoryId = $categoryRow['id'];

// Get max display_order for this category
$stmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 AS next_order 
                       FROM dropdown_options 
                       WHERE category_id = ?");
$stmt->execute([$categoryId]);
$nextOrder = $stmt->fetchColumn();

// Insert new option (always active by default - admins can deactivate later if needed)
$stmt = $pdo->prepare("INSERT INTO dropdown_options 
                       (category_id, option_value, icon_emoji, is_active, display_order) 
                       VALUES (?, ?, ?, TRUE, ?)");
$stmt->execute([$categoryId, $optionText, $emoji ?: null, $nextOrder]);

header("Location: dropdown_maintenance.php?category=" . urlencode($category));
exit;
