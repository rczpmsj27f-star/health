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
$optionText = trim($_POST['option_text'] ?? '');
$emoji = trim($_POST['emoji'] ?? '');
$category = $_POST['category'] ?? '';

if (empty($optionText)) {
    die("Error: Option text required");
}

// Verify the option exists and belongs to the specified category
$stmt = $pdo->prepare("
    SELECT o.id 
    FROM dropdown_options o
    INNER JOIN dropdown_categories c ON o.category_id = c.id
    WHERE o.id = ? AND c.category_key = ?
");
$stmt->execute([$id, $category]);

if (!$stmt->fetch()) {
    http_response_code(404);
    die("Option not found or does not belong to this category");
}

$stmt = $pdo->prepare("UPDATE dropdown_options 
                       SET option_value = ?, icon_emoji = ? 
                       WHERE id = ?");
$stmt->execute([$optionText, $emoji ?: null, $id]);

header("Location: dropdown_maintenance.php?category=" . urlencode($category));
exit;
