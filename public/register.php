<?php
session_start();
$err = $_SESSION['error'] ?? null;
$ok  = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Health Tracker</title>
    
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
</head>
<body class="centered-page">
    <div class="page-card">
        <div class="page-header">
            <h2>Create Account</h2>
            <p>Join Health Tracker today</p>
        </div>

        <?php if ($err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register_handler.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" inputmode="email" name="email" required>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>

            <div class="form-group">
                <label>Surname</label>
                <input type="text" name="surname" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <div id="password-match-message" style="display: none; margin-top: 8px; font-size: 14px;"></div>
            </div>

            <div class="form-group">
                <label>Profile Picture (optional)</label>
                <input type="file" name="profile_picture" accept="image/*">
            </div>

            <button class="btn btn-accept" type="submit">Register</button>
        </form>

        <div class="page-footer">
            <p>Already have an account? <a href="/login">Login</a></p>
        </div>
    </div>
    
    <script>
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchMessage = document.getElementById('password-match-message');
    const form = document.querySelector('form');

    function checkPasswordMatch() {
        if (confirmPassword.value === '') {
            matchMessage.style.display = 'none';
            return;
        }

        matchMessage.style.display = 'block';
        if (password.value === confirmPassword.value) {
            matchMessage.textContent = '✓ Passwords match';
            matchMessage.style.color = '#28a745';
            confirmPassword.style.borderColor = '#28a745';
        } else {
            matchMessage.textContent = '✗ Passwords do not match';
            matchMessage.style.color = '#dc3545';
            confirmPassword.style.borderColor = '#dc3545';
        }
    }

    password.addEventListener('input', checkPasswordMatch);
    confirmPassword.addEventListener('input', checkPasswordMatch);

    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match. Please make sure both password fields are identical.');
            confirmPassword.focus();
        }
    });

    // Service Worker registration
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
