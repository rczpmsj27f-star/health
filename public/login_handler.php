<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';

// Input validation for empty fields
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Username and password are required.";
    header("Location: /login.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = "Invalid login details.";
    header("Location: /login.php");
    exit;
}

if (!$user['is_email_verified']) {
    $_SESSION['error'] = "Please verify your email first.";
    header("Location: /login.php");
    exit;
}

// Check if 2FA is enabled for this user
if (!empty($user['two_factor_enabled'])) {
    // Store user ID temporarily and redirect to 2FA verification
    $_SESSION['pending_2fa_user_id'] = $user['id'];
    header("Location: /verify-2fa.php");
    exit;
}

// Normal login if 2FA not enabled
$_SESSION['user_id'] = $user['id'];

// Cache header display info in session (one-time lookup)
$_SESSION['header_display_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
if (empty($_SESSION['header_display_name'])) {
    $_SESSION['header_display_name'] = explode('@', $user['email'] ?? 'User')[0];
}
$_SESSION['header_avatar_url'] = !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '/assets/images/default-avatar.svg';

// Update last login time
$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

header("Location: /dashboard.php");
exit;
