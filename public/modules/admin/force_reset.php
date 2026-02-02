<?php
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";
require_once "../../../app/config/mailer.php";

$id = $_GET['id'];

// Get user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

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
