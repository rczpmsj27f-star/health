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
    <title>Register – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div style="padding:16px;">
        <h2>Create Account</h2>

        <?php if ($err): ?><div style="color:red;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok): ?><div style="color:green;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <form method="POST" action="/app/auth/register_handler.php" enctype="multipart/form-data">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>First Name</label>
            <input type="text" name="first_name" required>

            <label>Surname</label>
            <input type="text" name="surname" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <label>Profile Picture (optional)</label>
            <input type="file" name="profile_picture" accept="image/*">

            <button class="btn btn-accept" type="submit">Register</button>
        </form>

        <p><a href="/login.php">Already have an account? Login</a></p>
    </div>
</body>
</html>
