<?php
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

// Validate ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_GET['id'];

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – View User</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        <a href="/modules/medications/list.php">💊 Medications</a>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div style="padding: 80px 16px 40px 16px; max-width: 800px; margin: 0 auto;">
        <div class="page-card">
        <div class="page-header">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p>User Details & Actions</p>
        </div>

        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Name</div>
            <div class="info-value"><?= htmlspecialchars($user['first_name'] . " " . $user['surname']) ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Email Verified</div>
            <div class="info-value">
                <span style="color: <?= $user['is_email_verified'] ? '#28a745' : '#dc3545' ?>">
                    <?= $user['is_email_verified'] ? "✓ Yes" : "✗ No" ?>
                </span>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Active</div>
            <div class="info-value">
                <span style="color: <?= $user['is_active'] ? '#28a745' : '#dc3545' ?>">
                    <?= $user['is_active'] ? "✓ Yes" : "✗ No" ?>
                </span>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Roles</div>
            <div class="info-value"><?= empty($roleList) ? "None" : implode(", ", $roleList) ?></div>
        </div>

        <div style="margin-top: 24px;">
            <h3 style="margin-bottom: 16px; color: #333;">Admin Actions</h3>

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
        </div>

        <div class="page-footer">
            <p><a href="/modules/admin/users.php">Back to User Management</a></p>
        </div>
    </div>
    </div>
</body>
</html>
