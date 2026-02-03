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
    <title>Update Picture</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
</head>
<body class="centered-page">
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
                <input type="file" name="profile_picture" accept="image/*" required>
            </div>

            <button class="btn btn-accept" type="submit">Upload Picture</button>
        </form>

        <div class="page-footer">
            <p><a href="/modules/profile/view.php">Cancel</a></p>
        </div>
    </div>
</body>
</html>
