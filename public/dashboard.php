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
    <title>Dashboard – Health Tracker</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/menu.js" defer></script>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/modules/profile/view.php">Profile</a><br>
        <a href="/modules/medications/list.php">Medication Management</a><br>
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">User Management</a><br>
        <?php endif; ?>
        <a href="/logout.php">Logout</a>
    </div>

    <div class="dashboard-grid">
        <a class="tile" href="/modules/medications/list.php">Medication Management</a>
        <?php if ($isAdmin): ?>
        <a class="tile" href="/modules/admin/users.php">User Management</a>
        <?php endif; ?>
    </div>
</body>
</html>
