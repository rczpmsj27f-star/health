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
    <title>Login – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div style="padding:16px;">
        <h2>Login</h2>

        <?php if ($err): ?><div style="color:red;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok): ?><div style="color:green;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <form method="POST" action="/app/auth/login_handler.php">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button class="btn btn-accept" type="submit">Login</button>
        </form>

        <p><a href="/register.php">Create an account</a></p>
    </div>
</body>
</html>
