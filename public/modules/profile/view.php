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
    <title>Your Profile</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Your Profile</h2>

    <?php if ($user['profile_picture_path']): ?>
        <img src="<?= $user['profile_picture_path'] ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover;">
    <?php else: ?>
        <div style="width:120px; height:120px; border-radius:50%; background:#ccc;"></div>
    <?php endif; ?>

    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . " " . $user['surname']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

    <a class="btn btn-info" href="/modules/profile/edit.php">Edit Profile</a>
    <a class="btn btn-info" href="/modules/profile/change_password.php">Change Password</a>
    <a class="btn btn-info" href="/modules/profile/update_picture.php">Update Picture</a>
</div>

</body>
</html>
