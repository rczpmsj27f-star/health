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
</body>
</html>
