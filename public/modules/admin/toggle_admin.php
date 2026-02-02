<?php
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

$id = $_GET['id'];

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
