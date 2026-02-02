<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Change Password</h2>

    <form method="POST" action="/modules/profile/change_password_handler.php">
        <label>Current Password</label>
        <input type="password" name="current_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button class="btn btn-accept" type="submit">Update Password</button>
    </form>
</div>

</body>
</html>
