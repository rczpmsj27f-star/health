<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/mailer.php';

session_start();

// Rate limiting - prevent spam registrations
if (!isset($_SESSION['last_register_attempt'])) {
    $_SESSION['last_register_attempt'] = 0;
}

$timeSinceLastAttempt = time() - $_SESSION['last_register_attempt'];
if ($timeSinceLastAttempt < 5) {
    $_SESSION['error'] = "Please wait a moment before submitting again.";
    header("Location: /register.php");
    exit;
}

$_SESSION['last_register_attempt'] = time();

// Input validation for empty fields
if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['first_name']) || 
    empty($_POST['surname']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: /register.php");
    exit;
}

$username   = trim($_POST['username']);
$email      = trim($_POST['email']);
$first      = trim($_POST['first_name']);
$surname    = trim($_POST['surname']);
$password   = $_POST['password'];
$confirm    = $_POST['confirm_password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    header("Location: /register.php");
    exit;
}

if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: /register.php");
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Profile picture upload
$profilePath = null;
if (!empty($_FILES['profile_picture']['name'])) {
    // Validate file upload
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload failed. Please try again.";
        header("Location: /register.php");
        exit;
    }
    
    // Validate file type (images only)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $_SESSION['error'] = "Invalid file type. Please upload an image (JPEG, PNG, GIF, or WebP).";
        header("Location: /register.php");
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File too large. Maximum size is 5MB.";
        header("Location: /register.php");
        exit;
    }
    
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = uniqid("pp_") . "." . $ext;
    $uploadDir = __DIR__ . "/../uploads/profile/";
    $target = $uploadDir . $filename;
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
        $profilePath = "/uploads/profile/" . $filename;
    } else {
        $_SESSION['error'] = "Failed to save profile picture. Please try again.";
        header("Location: /register.php");
        exit;
    }
}

// Insert user and assign role
try {
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
    
    if (!$roleId) {
        throw new Exception("User role not found in database");
    }
    
    $map = $pdo->prepare("INSERT INTO user_role_map (user_id, role_id) VALUES (?, ?)");
    $map->execute([$userId, $roleId]);
} catch (PDOException $e) {
    // Check if it's a duplicate entry error (code 23000)
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = "Username or email already exists.";
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
    }
    header("Location: /register.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Registration failed: " . $e->getMessage();
    header("Location: /register.php");
    exit;
}

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
try {
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
} catch (Exception $e) {
    // Email failed, but user is registered - they can request a new verification email
    error_log("Failed to send verification email to $email: " . $e->getMessage());
    $_SESSION['success'] = "Registration successful! However, we couldn't send the verification email. Please contact support.";
    header("Location: /login.php");
    exit;
}

$_SESSION['success'] = "Registration successful! Please check your email.";
header("Location: /login.php");
exit;
