<?php
require_once __DIR__ . '/../config/database.php';

session_start();

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid verification link.";
    header("Location: /login.php");
    exit;
}

$token = $_GET['token'];
$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare("
    SELECT * FROM email_verifications
    WHERE token_hash = ?
    AND used_at IS NULL
    AND expires_at > NOW()
");
$stmt->execute([$tokenHash]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error'] = "This verification link is invalid or expired.";
    header("Location: /login.php");
    exit;
}

// Mark used
$pdo->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?")
    ->execute([$record['id']]);

// Mark user verified
$pdo->prepare("UPDATE users SET is_email_verified = 1 WHERE id = ?")
    ->execute([$record['user_id']]);

$_SESSION['success'] = "Email verified. You may now log in.";
header("Location: /login.php");
exit;
