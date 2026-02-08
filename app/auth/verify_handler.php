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

// First check if already verified to prevent double-verification
$stmt = $pdo->prepare("
    SELECT ev.*, u.is_email_verified 
    FROM email_verifications ev
    JOIN users u ON ev.user_id = u.id
    WHERE ev.token_hash = ?
    AND ev.expires_at > NOW()
");
$stmt->execute([$tokenHash]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error'] = "This verification link is invalid or expired.";
    header("Location: /login.php");
    exit;
}

// If already verified, silently redirect to login
if ($record['is_email_verified']) {
    $_SESSION['success'] = "Email already verified. Please log in.";
    header("Location: /login.php");
    exit;
}

// If already used, don't verify again
if ($record['used_at'] !== null) {
    $_SESSION['success'] = "Email already verified. Please log in.";
    header("Location: /login.php");
    exit;
}

// Mark used
$pdo->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?")
    ->execute([$record['id']]);

// Mark user verified
$pdo->prepare("UPDATE users SET is_email_verified = 1 WHERE id = ?")
    ->execute([$record['user_id']]);

$_SESSION['success'] = "Email verified! You can now log in.";
header("Location: /login.php");
exit;
