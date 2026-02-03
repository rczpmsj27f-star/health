<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$isAdmin = Auth::isAdmin();

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
    <script src="/assets/js/menu.js" defer></script>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        <a href="/modules/medications/list.php">💊 Medications</a>
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div style="padding: 80px 16px 40px 16px; max-width: 600px; margin: 0 auto;">
        <div class="page-card">
            <div class="page-header">
                <h2>👤 Your Profile</h2>
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

            <a class="btn btn-primary" href="/modules/profile/edit.php">✏️ Edit Profile</a>
            <a class="btn btn-info" href="/modules/profile/change_password.php">🔒 Change Password</a>
            <a class="btn btn-info" href="/modules/profile/update_picture.php">📷 Update Picture</a>
            
            <div class="page-footer">
                <p><a href="/dashboard.php">⬅️ Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
