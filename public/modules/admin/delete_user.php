<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

// Validate ID parameter
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_POST['id'];

// Prevent self-deletion
if ($id === $_SESSION['user_id']) {
    http_response_code(400);
    die("You cannot delete your own account");
}

// Get user and verify exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die("User not found");
}

// Delete user (cascading deletes should handle related records)
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

// Redirect back to users list
header("Location: /modules/admin/users.php");
exit;
