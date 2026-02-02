<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';

session_start();

$username   = trim($_POST['username']);
$email      = trim($_POST['email']);
$first      = trim($_POST['first_name']);
$surname    = trim($_POST['surname']);
$password   = $_POST['password'];
$confirm    = $_POST['confirm_password'];

if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: /register.php");
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Profile picture upload
$profilePath = null;
if (!empty($_FILES['profile_picture']['name'])) {
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = uniqid("pp_") . "." . $ext;
    $target = __DIR__ . "/../../uploads/profile/" . $filename;

    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target);
    $profilePath = "/uploads/profile/" . $filename;
}

// Insert user
$stmt = $pdo->prepare("
    INSERT INTO users (username, email, first_name, surname, password_hash, profile_picture_path)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$username, $email, $first, $surname, $hash, $profilePath]);

$userId = $pdo->lastInsertId();

// Assign role "user"
$roleStmt = $pdo->prepare("SELECT id FROM user_roles WHERE role_name = 'user'");
$roleStmt->execute();
$roleId = $roleStmt->fetchColumn();

$map = $pdo->prepare("INSERT INTO user_role_map (user_id, role_id) VALUES (?, ?)");
$map->execute([$userId, $roleId]);

// Create verification token
$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);

$expires = date("Y-m-d H:i:s", time() + 86400); // 24 hours

$ver = $pdo->prepare("
    INSERT INTO email_verifications (user_id, token_hash, expires_at)
    VALUES (?, ?, ?)
");
$ver->execute([$userId, $tokenHash, $expires]);

// Send email
$mail = mailer();
$mail->addAddress($email);
$mail->Subject = "Verify your email address";

$link = "https://ht.ianconroy.co.uk/verify-email.php?token=" . $token;

$mail->Body = "
    <p>Hello $first,</p>
    <p>Please verify your email by clicking the link below:</p>
    <p><a href='$link'>$link</a></p>
    <p>If you did not create this account, you can ignore this email.</p>
";

$mail->send();

$_SESSION['success'] = "Registration successful! Please check your email.";
header("Location: /login.php");
exit;
