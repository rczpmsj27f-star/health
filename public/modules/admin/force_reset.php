<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/security.php";
Auth::requireAdmin();
require_once "../../../app/config/mailer.php";

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    die("Invalid security token");
}

// Validate ID parameter
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_POST['id'];

// Get user and verify exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die("User not found");
}

try {
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
    
    $_SESSION['success_msg'] = "Password reset email sent to " . htmlspecialchars($user['username']);
} catch (Exception $e) {
    // Log the error for debugging (in production, use proper logging)
    error_log("Password reset failed for user ID $id: " . $e->getMessage());
    $_SESSION['error_msg'] = "Failed to send password reset email. Please try again or contact support.";
}

// Determine redirect location
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'view_user';
if ($redirect === 'users') {
    header("Location: /modules/admin/users.php");
} else {
    header("Location: /modules/admin/view_user.php?id=$id");
}
exit;
