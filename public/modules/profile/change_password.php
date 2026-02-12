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
    <title>Change Password</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Change Password</h2>
            <p>Update your account password</p>
        </div>

        <?php if ($err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <form method="POST" action="/modules/profile/change_password_handler.php">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button class="btn btn-accept" type="submit">Update Password</button>
        </form>

        <div class="page-footer">
            <p><a href="/modules/profile/view.php">Cancel</a></p>
        </div>
    </div>
    </div>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
