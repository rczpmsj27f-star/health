<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../app/core/auth.php';
$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
/* Critical inline styles - fallback if external CSS doesn't load */
.tile {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 24px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: #ffffff;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.tile .tile-title, .tile .tile-desc, .tile .tile-icon {
    color: #ffffff;
}
.btn {
    padding: 14px 20px;
    border-radius: 6px;
    border: none;
    font-size: 16px;
    color: #ffffff;
    display: block;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    min-height: 48px;
}
.btn-primary { background: #2563eb; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-info { background: #007bff; color: #fff; }
    </style>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 60px;
        }
        
        .dashboard-title {
            text-align: center;
            padding: 20px 16px;
            color: #333;
        }
        
        .dashboard-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
        }
        
        .dashboard-title p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        
        <div class="menu-parent">
            <a href="/modules/medications/dashboard.php" class="menu-parent-link">💊 Medications</a>
            <div class="menu-children">
                <a href="/modules/medications/list.php">My Medications</a>
                <a href="/modules/medications/stock.php">Medication Stock</a>
                <a href="/modules/medications/compliance.php">Compliance</a>
                <a href="/modules/medications/log_prn.php">Log PRN</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="dashboard-container">
        <div class="dashboard-title">
            <h2>Health Tracker Dashboard</h2>
            <p>Welcome back! Manage your health and medications</p>
        </div>
        
        <div class="dashboard-grid">
            <a class="tile tile-purple" href="/modules/medications/dashboard.php">
                <div>
                    <span class="tile-icon">💊</span>
                    <div class="tile-title">Medication Management</div>
                    <div class="tile-desc">Track your medications</div>
                </div>
            </a>
            
            <?php if ($isAdmin): ?>
            <a class="tile tile-orange" href="/modules/admin/users.php">
                <div>
                    <span class="tile-icon">⚙️</span>
                    <div class="tile-title">User Management</div>
                    <div class="tile-desc">Manage system users</div>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
