<?php 
session_start();
require_once "../../../app/core/auth.php";
$isAdmin = Auth::isAdmin();
$err = $_SESSION['error'] ?? null;
$ok  = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Picture</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body class="centered-page">
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="page-card">
        <div class="page-header">
            <h2>Update Profile Picture</h2>
            <p>Upload a new profile picture</p>
        </div>

        <?php if ($err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <form method="POST" action="/modules/profile/update_picture_handler.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Image</label>
                <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*" required>
                <small style="display: block; margin-top: 4px; color: #666;">
                    Allowed: JPG, PNG, GIF, WebP. Maximum size: 5MB
                </small>
            </div>

            <div id="imagePreview" style="display: none; text-align: center; margin: 20px 0;">
                <img id="previewImg" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Preview">
            </div>

            <button class="btn btn-accept" type="submit">Upload Picture</button>
        </form>

        <script>
        document.getElementById('profilePictureInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagePreview').style.display = 'none';
            }
        });
        </script>

        <div class="page-footer">
            <p><a href="/profile">Cancel</a></p>
        </div>
    </div>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
