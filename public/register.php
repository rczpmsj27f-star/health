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
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
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

        <form method="POST" action="/app/auth/register_handler.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
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
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label>Profile Picture (optional)</label>
                <input type="file" name="profile_picture" accept="image/*">
            </div>

            <button class="btn btn-accept" type="submit">Register</button>
        </form>

        <div class="page-footer">
            <p>Already have an account? <a href="/login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
