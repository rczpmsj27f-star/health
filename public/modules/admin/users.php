<?php
require_once "../../../app/core/auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

session_start();

$search = $_GET['q'] ?? "";

if ($search) {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE email LIKE ? OR username LIKE ? OR first_name LIKE ? OR surname LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}

$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin – Users</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>User Management</h2>

    <form method="GET">
        <input type="text" name="q" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-info">Search</button>
    </form>

    <div class="dashboard-grid">
        <?php foreach ($users as $u): ?>
            <a class="tile" href="/modules/admin/view_user.php?id=<?= $u['id'] ?>">
                <?= htmlspecialchars($u['username']) ?><br>
                <small><?= htmlspecialchars($u['email']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>

    <p><a href="/dashboard.php">Back to Dashboard</a></p>
</div>

</body>
</html>
