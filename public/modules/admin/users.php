<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Users</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 16px 16px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: #333;
        }
        
        .search-form {
            max-width: 600px;
            margin: 0 auto 24px;
            display: flex;
            gap: 8px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .search-form button {
            width: auto;
            min-width: 100px;
            padding: 12px 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="page-content">
        <div class="page-title">
            <h2>User Management</h2>
            <p>Search and manage system users</p>
        </div>

        <form method="GET" class="search-form">
            <input type="text" name="q" placeholder="Search by name, email, or username..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-info" type="submit">Search</button>
        </form>

        <?php if (empty($users)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: #666; margin: 0;">No users found.</p>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($users as $u): ?>
                    <a class="tile" href="/modules/admin/view_user.php?id=<?= $u['id'] ?>">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 4px;"><?= htmlspecialchars($u['username']) ?></div>
                            <div style="font-size: 13px; color: #666;"><?= htmlspecialchars($u['email']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="page-footer">
            <p><a class="btn btn-info" href="/dashboard.php" style="max-width: 300px; display: inline-block;">Back to Dashboard</a></p>
        </div>
    </div>
</body>
</html>
