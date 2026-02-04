<?php
session_start();
require_once "../../../app/config/database.php";

// Validate file was uploaded
if (empty($_FILES['profile_picture']['name'])) {
    $_SESSION['error'] = "No file selected.";
    header("Location: /profile/picture");
    exit;
}

// Check for upload errors
if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => "File too large (server limit).",
        UPLOAD_ERR_FORM_SIZE => "File too large.",
        UPLOAD_ERR_PARTIAL => "File upload incomplete.",
        UPLOAD_ERR_NO_FILE => "No file uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Server error: no temp directory.",
        UPLOAD_ERR_CANT_WRITE => "Server error: cannot write file.",
        UPLOAD_ERR_EXTENSION => "Upload blocked by server extension."
    ];
    $_SESSION['error'] = $errors[$_FILES['profile_picture']['error']] ?? "Upload failed.";
    header("Location: /profile/picture");
    exit;
}

// Validate file size (5MB max)
$maxSize = 5 * 1024 * 1024;
if ($_FILES['profile_picture']['size'] > $maxSize) {
    $_SESSION['error'] = "File too large. Maximum size is 5MB.";
    header("Location: /profile/picture");
    exit;
}

// Validate MIME type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    $_SESSION['error'] = "Invalid file type. Only JPG, PNG, GIF, and WebP allowed.";
    header("Location: /profile/picture");
    exit;
}

// Validate it's actually an image
$imageInfo = getimagesize($_FILES['profile_picture']['tmp_name']);
if ($imageInfo === false) {
    $_SESSION['error'] = "File is not a valid image.";
    header("Location: /profile/picture");
    exit;
}

// Sanitize extension
$ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowedExts)) {
    $ext = 'jpg';
}

$filename = uniqid("pp_") . "." . $ext;
$uploadDir = __DIR__ . "/../../../uploads/profile/";
$target = $uploadDir . $filename;

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $_SESSION['error'] = "Failed to create upload directory.";
        header("Location: /profile/picture");
        exit;
    }
}

// Check directory is writable
if (!is_writable($uploadDir)) {
    $_SESSION['error'] = "Upload directory is not writable. Contact administrator.";
    header("Location: /profile/picture");
    exit;
}

// Delete old profile picture if exists
$stmt = $pdo->prepare("SELECT profile_picture_path FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$oldPath = $stmt->fetchColumn();
if ($oldPath) {
    $oldFile = __DIR__ . "/../../../" . ltrim($oldPath, '/');
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Move uploaded file
if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
    $_SESSION['error'] = "Failed to save file. Check directory permissions.";
    header("Location: /profile/picture");
    exit;
}

// Set proper permissions
chmod($target, 0644);

$path = "/uploads/profile/" . $filename;

// Update database
$stmt = $pdo->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
$stmt->execute([$path, $_SESSION['user_id']]);

$_SESSION['success'] = "Profile picture updated successfully!";
header("Location: /profile");
exit;
