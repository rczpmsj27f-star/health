<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
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
    <title>Edit Profile</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Edit Profile</h2>
            <p>Update your account information</p>
        </div>

        <form method="POST" action="/modules/profile/edit_handler.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Surname</label>
                <input type="text" name="surname" value="<?= htmlspecialchars($user['surname']) ?>" required>
            </div>

            <button class="btn btn-accept" type="submit">Save Changes</button>
        </form>

        <div class="page-footer">
            <p><a href="/modules/profile/view.php">Cancel</a></p>
        </div>
    </div>
    </div>
</body>
</html>
