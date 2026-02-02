<?php
session_start();
require_once "../../../app/config/database.php";

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!password_verify($_POST['current_password'], $user['password_hash'])) {
    $_SESSION['error'] = "Current password incorrect.";
    header("Location: /modules/profile/change_password.php");
    exit;
}

if ($_POST['new_password'] !== $_POST['confirm_password']) {
    $_SESSION['error'] = "New passwords do not match.";
    header("Location: /modules/profile/change_password.php");
    exit;
}

$newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

$update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$update->execute([$newHash, $_SESSION['user_id']]);

$_SESSION['success'] = "Password updated.";
header("Location: /modules/profile/view.php");
exit;
