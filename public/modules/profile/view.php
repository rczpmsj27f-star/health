<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="centered-page">
    <div class="page-card">
        <div class="page-header">
            <h2>Your Profile</h2>
            <p>View and manage your account</p>
        </div>

        <?php if ($user['profile_picture_path']): ?>
            <img src="<?= htmlspecialchars($user['profile_picture_path']) ?>" class="profile-picture" alt="Profile Picture">
        <?php else: ?>
            <div class="profile-placeholder"></div>
        <?php endif; ?>

        <div class="info-item">
            <div class="info-label">Username</div>
            <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Name</div>
            <div class="info-value"><?= htmlspecialchars($user['first_name'] . " " . $user['surname']) ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <a class="btn btn-info" href="/modules/profile/edit.php">Edit Profile</a>
        <a class="btn btn-info" href="/modules/profile/change_password.php">Change Password</a>
        <a class="btn btn-info" href="/modules/profile/update_picture.php">Update Picture</a>
        
        <div class="page-footer">
            <p><a href="/dashboard.php">Back to Dashboard</a></p>
        </div>
    </div>
</body>
</html>
