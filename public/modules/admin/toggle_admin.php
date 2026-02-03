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

// Get admin role ID
$role = $pdo->query("SELECT id FROM user_roles WHERE role_name = 'admin'")->fetchColumn();

// Check if user already admin
$check = $pdo->prepare("SELECT * FROM user_role_map WHERE user_id = ? AND role_id = ?");
$check->execute([$id, $role]);

if ($check->rowCount()) {
    // Remove admin
    $pdo->prepare("DELETE FROM user_role_map WHERE user_id = ? AND role_id = ?")
        ->execute([$id, $role]);
} else {
    // Add admin
    $pdo->prepare("INSERT INTO user_role_map (user_id, role_id) VALUES (?, ?)")
        ->execute([$id, $role]);
}

header("Location: /modules/admin/view_user.php?id=$id");
exit;
