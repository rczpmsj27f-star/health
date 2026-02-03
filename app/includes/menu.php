<?php
// Ensure $isAdmin is set - auto-detect if not provided
if (!isset($isAdmin)) {
    require_once __DIR__ . '/../core/auth.php';
    $isAdmin = Auth::isAdmin();
}
?>
<div class="hamburger" onclick="toggleMenu()">
    <div></div><div></div><div></div>
</div>

<div class="menu" id="menu">
    <h3>Menu</h3>
    <a href="/dashboard.php">🏠 Dashboard</a>
    
    <div class="menu-section">
        <div class="menu-section-header" onclick="toggleSubmenu('medications-menu')">
            <span>💊 Medications</span>
            <span class="menu-toggle-icon" id="medications-menu-icon">▶</span>
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
            <span class="menu-toggle-icon" id="settings-menu-icon">▶</span>
        </div>
        <div class="menu-section-children" id="settings-menu">
            <?php if ($isAdmin): ?>
            <div class="menu-section nested">
                <div class="menu-section-header nested-header" onclick="toggleSubmenu('admin-menu'); event.stopPropagation();">
                    <span>🔐 Admin Panel</span>
                    <span class="menu-toggle-icon" id="admin-menu-icon">▶</span>
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
