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
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        
        <div class="menu-section">
            <div class="menu-section-header" onclick="toggleSubmenu('medications-menu')">
                <span>💊 Medications</span>
                <span id="medications-menu-icon">▶</span>
            </div>
            <div class="menu-section-children" id="medications-menu">
                <a href="/modules/medications/compliance.php">Compliance</a>
                <a href="/modules/medications/log_prn.php">Log PRN</a>
                <a href="/modules/medications/stock.php">Medication Stock</a>
                <a href="/modules/medications/list.php">My Medications</a>
            </div>
        </div>
        
        <a href="/modules/profile/view.php">👤 My Profile</a>
        
        <div class="menu-section">
            <div class="menu-section-header" onclick="toggleSubmenu('settings-menu')">
                <span>⚙️ Settings</span>
                <span id="settings-menu-icon">▶</span>
            </div>
            <div class="menu-section-children" id="settings-menu">
                <?php if ($isAdmin): ?>
                <div class="menu-section" style="margin-left: 0; padding-left: 0;">
                    <div class="menu-section-header" onclick="toggleSubmenu('admin-menu'); event.stopPropagation();" style="padding: 8px 16px;">
                        <span>🔐 Admin Panel</span>
                        <span id="admin-menu-icon">▶</span>
                    </div>
                    <div class="menu-section-children" id="admin-menu">
                        <a href="/modules/admin/users.php">User Management</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="/logout.php">🚪 Logout</a>
    </div>

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
