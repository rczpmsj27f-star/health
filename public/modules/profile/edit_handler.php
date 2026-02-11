<?php
session_start();
require_once "../../../app/config/database.php";

// Server-side validation
if (empty($_POST['username']) || empty($_POST['first_name']) || empty($_POST['surname'])) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: /modules/profile/edit.php");
    exit;
}

$stmt = $pdo->prepare("
    UPDATE users
    SET username = ?, first_name = ?, surname = ?
    WHERE id = ?
");

$stmt->execute([
    trim($_POST['username']),
    trim($_POST['first_name']),
    trim($_POST['surname']),
    $_SESSION['user_id']
]);

$_SESSION['success'] = "Profile updated successfully.";
header("Location: /modules/profile/view.php");
exit;
