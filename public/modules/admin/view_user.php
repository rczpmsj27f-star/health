<?php
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

$roles = $pdo->prepare("
    SELECT r.role_name
    FROM user_role_map m
    JOIN user_roles r ON r.id = m.role_id
    WHERE m.user_id = ?
");
$roles->execute([$id]);
$roleList = $roles->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin – View User</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>User: <?= htmlspecialchars($user['username']) ?></h2>

    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . " " . $user['surname']) ?></p>
    <p><strong>Email Verified:</strong> <?= $user['is_email_verified'] ? "Yes" : "No" ?></p>
    <p><strong>Active:</strong> <?= $user['is_active'] ? "Yes" : "No" ?></p>
    <p><strong>Roles:</strong> <?= implode(", ", $roleList) ?></p>

    <h3>Actions</h3>

    <a class="btn btn-info" href="/modules/admin/toggle_verify.php?id=<?= $id ?>">
        <?= $user['is_email_verified'] ? "Unverify Email" : "Verify Email" ?>
    </a>

    <a class="btn btn-info" href="/modules/admin/toggle_active.php?id=<?= $id ?>">
        <?= $user['is_active'] ? "Deactivate Account" : "Activate Account" ?>
    </a>

    <a class="btn btn-info" href="/modules/admin/toggle_admin.php?id=<?= $id ?>">
        <?= in_array("admin", $roleList) ? "Remove Admin" : "Make Admin" ?>
    </a>

    <a class="btn btn-deny" href="/modules/admin/force_reset.php?id=<?= $id ?>">
        Force Password Reset
    </a>

    <p><a href="/modules/admin/users.php">Back to User Management</a></p>

</div>

</body>
</html>

