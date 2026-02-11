<?php
session_start();
require_once "../../../app/config/database.php";

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/profile/edit.php");
    exit;
}

// Trim all inputs first
$username = trim($_POST['username'] ?? '');
$firstName = trim($_POST['first_name'] ?? '');
$surname = trim($_POST['surname'] ?? '');

// Server-side validation for empty fields (after trimming)
if ($username === '' || $firstName === '' || $surname === '') {
    $_SESSION['error'] = "All fields are required.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Validate username (3-50 characters, alphanumeric start/end, allows underscores/hyphens in middle)
// Pattern allows: 3 chars (e.g., "abc"), up to 50 chars with special chars in middle
if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9_-]{0,48}[a-zA-Z0-9]|[a-zA-Z0-9]?)$/', $username)) {
    $_SESSION['error'] = "Username must be 3-50 characters, start and end with a letter or number, and contain only letters, numbers, underscores, or hyphens.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Additional length check (for edge case validation)
if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
    $_SESSION['error'] = "Username must be between 3 and 50 characters.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Validate name lengths (using mb_strlen for multibyte character support)
if (mb_strlen($firstName) > 100 || mb_strlen($surname) > 100) {
    $_SESSION['error'] = "Name fields must not exceed 100 characters.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Check if username is already taken by another user
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "Username is already taken. Please choose a different username.";
    header("Location: /modules/profile/edit.php");
    exit;
}

// Update user profile
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
