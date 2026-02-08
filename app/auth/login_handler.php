<?php
require_once __DIR__ . '/../config/database.php';

session_start();

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

$_SESSION['user_id'] = $user['id'];

header("Location: /dashboard.php");
exit;
