<?php
require_once "../../../app/includes/cache-buster.php";
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
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div style="padding: 16px 16px 40px 16px; max-width: 600px; margin: 0 auto;">
        <div class="page-card">
            <div class="page-header">
                <h2>👤 Your Profile</h2>
                <p>View and manage your account</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($user['profile_picture_path']) && !empty($user['profile_picture_path'])): ?>
                <img src="<?= htmlspecialchars($user['profile_picture_path']) ?>" class="profile-picture" alt="Profile Picture">
            <?php else: ?>
                <div class="profile-placeholder"></div>
            <?php endif; ?>

            <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value"><?= htmlspecialchars($user['username'] ?? '') ?></div>
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
            <a class="btn btn-danger" href="/logout.php">🚪 Logout</a>
            
            <div class="page-footer">
                <p><a href="/dashboard.php">⬅️ Back to Dashboard</a></p>
            </div>
        </div>
    </div>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
