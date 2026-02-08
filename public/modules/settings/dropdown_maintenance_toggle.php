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
$category = $_POST['category'] ?? '';

// Toggle is_active
$stmt = $pdo->prepare("UPDATE dropdown_options 
                       SET is_active = NOT is_active 
                       WHERE id = ?");
$stmt->execute([$id]);

header("Location: dropdown_maintenance.php?category=" . urlencode($category));
exit;
