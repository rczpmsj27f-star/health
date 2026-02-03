<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../app/core/Auth.php';
$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/menu.js" defer></script>
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
        <a href="/modules/medications/list.php">💊 Medications</a>
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
            <a class="tile tile-purple" href="/modules/medications/list.php">
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
