<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/mailer.php";

// Validate ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_GET['id'];

// Get user and verify exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die("User not found");
}

// Create reset token
$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expires = date("Y-m-d H:i:s", time() + 3600);

$pdo->prepare("
    INSERT INTO password_resets (user_id, token_hash, expires_at)
    VALUES (?, ?, ?)
")->execute([$id, $tokenHash, $expires]);

// Send email
$mail = mailer();
$mail->addAddress($user['email']);
$mail->Subject = "Password Reset Requested by Admin";

$link = "https://ht.ianconroy.co.uk/reset-password.php?token=$token";

$mail->Body = "
    <p>Hello,</p>
    <p>An administrator has requested a password reset for your account.</p>
    <p><a href='$link'>Click here to reset your password</a></p>
";

$mail->send();

header("Location: /modules/admin/view_user.php?id=$id");
exit;
