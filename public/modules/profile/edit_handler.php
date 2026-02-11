<?php
session_start();
require_once "../../../app/config/database.php";

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/profile/edit.php");
    exit;
}

// Server-side validation
if (empty($_POST['username']) || empty($_POST['first_name']) || empty($_POST['surname'])) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Validate username (alphanumeric, underscores, hyphens, 3-50 characters)
$username = trim($_POST['username']);
if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
    $_SESSION['error'] = "Username must be 3-50 characters and contain only letters, numbers, underscores, or hyphens.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Validate name lengths
$firstName = trim($_POST['first_name']);
$surname = trim($_POST['surname']);
if (strlen($firstName) > 100 || strlen($surname) > 100) {
    $_SESSION['error'] = "Name fields must not exceed 100 characters.";
    header("Location: /modules/profile/edit.php");
    exit;
}

$stmt = $pdo->prepare("
    UPDATE users
    SET username = ?, first_name = ?, surname = ?
    WHERE id = ?
");

$stmt->execute([
    $username,
    $firstName,
    $surname,
    $_SESSION['user_id']
]);

$_SESSION['success'] = "Profile updated successfully.";
header("Location: /modules/profile/view.php");
exit;
