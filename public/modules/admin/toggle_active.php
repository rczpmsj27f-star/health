<?php
require_once "../../../app/core/Auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

// Validate ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_GET['id'];

// Verify user exists
$userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$userCheck->execute([$id]);
if (!$userCheck->fetch()) {
    http_response_code(404);
    die("User not found");
}

$stmt = $pdo->prepare("
    UPDATE users
    SET is_active = IF(is_active = 1, 0, 1)
    WHERE id = ?
");
$stmt->execute([$id]);

header("Location: /modules/admin/view_user.php?id=$id");
exit;
