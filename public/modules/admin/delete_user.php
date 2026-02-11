<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/security.php";
Auth::requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    die("Invalid security token");
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

// Delete user
// NOTE: This assumes the database has proper CASCADE DELETE constraints configured
// for related records (user_role_map, password_resets, etc.). If CASCADE constraints
// are not set up, this will fail or leave orphaned records. Verify schema before use.
try {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    $_SESSION['success_msg'] = "User " . htmlspecialchars($user['username']) . " has been deleted successfully.";
} catch (Exception $e) {
    $_SESSION['error_msg'] = "Failed to delete user: " . $e->getMessage();
}

// Redirect back to users list
header("Location: /modules/admin/users.php");
exit;
