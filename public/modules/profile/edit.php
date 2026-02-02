<?php
session_start();
require_once "../../../app/config/database.php";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Edit Profile</h2>

    <form method="POST" action="/modules/profile/edit_handler.php">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

        <label>Surname</label>
        <input type="text" name="surname" value="<?= htmlspecialchars($user['surname']) ?>" required>

        <button class="btn btn-accept" type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
