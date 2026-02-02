<?php require_once "../app/core/Auth.php"; Auth::requireLogin(); ?>

<div class="hamburger" onclick="toggleMenu()">
    <div></div><div></div><div></div>
</div>

<div class="menu" id="menu">
    <h3>Menu</h3>
    <a href="/modules/profile/view.php">Profile</a><br>
    <a href="/modules/medications/list.php">Medication Management</a><br>

    <?php if (Auth::isAdmin()): ?>
        <a href="/modules/admin/users.php">Admin Panel</a><br>
    <?php endif; ?>

    <a href="/logout.php">Logout</a>
</div>

<div class="dashboard-grid">
    <a class="tile" href="/modules/medications/list.php">Medication Management</a>
    <a class="tile" href="#">Coming Soon</a>
</div>

<script src="/assets/js/menu.js"></script>
