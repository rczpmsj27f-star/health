<?php
// Persistent footer navigation component for UI redesign
// Footer nav: Home, Settings, Notifications, Profile

// Check if user is admin for notifications badge
$unreadCount = 0;
if (isset($pdo) && !empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../core/NotificationHelper.php';
    try {
        $notificationHelper = new NotificationHelper($pdo);
        $unreadCount = $notificationHelper->getUnreadCount($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Footer notification error: " . $e->getMessage());
        $unreadCount = 0;
    }
}

// Determine context shortcut based on current location
$contextShortcut = null;
$currentPath = $_SERVER['REQUEST_URI'];

// Check if we're in medication sub-pages (but not the medication dashboard itself)
if (strpos($currentPath, '/modules/medications/') !== false && 
    basename($_SERVER['PHP_SELF']) !== 'medication_dashboard.php') {
    $contextShortcut = [
        'label' => 'Medication Dashboard',
        'url' => '/modules/medications/medication_dashboard.php',
        'icon' => '💊'
    ];
}
// Check if we're in admin sub-pages (but not the admin dashboard itself)
elseif (strpos($currentPath, '/modules/admin/') !== false && 
        basename($_SERVER['PHP_SELF']) !== 'dashboard.php') {
    $contextShortcut = [
        'label' => 'Admin Dashboard',
        'url' => '/modules/admin/dashboard.php',
        'icon' => '🔐'
    ];
}
// Check if we're in reports sub-pages
elseif (strpos($currentPath, '/modules/reports/') !== false) {
    $contextShortcut = [
        'label' => 'Activity & Compliance',
        'url' => '/modules/medications/activity_compliance.php',
        'icon' => '📊'
    ];
}
?>

<?php if ($contextShortcut): ?>
<div class="context-shortcut-bar">
    <a href="<?= htmlspecialchars($contextShortcut['url']) ?>" class="context-shortcut">
        <span><?= $contextShortcut['icon'] ?></span>
        <span><?= htmlspecialchars($contextShortcut['label']) ?></span>
    </a>
</div>
<?php endif; ?>

<div class="app-footer">
    <div class="footer-content">
        <a href="/dashboard.php" class="footer-item <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && !isset($_GET['view'])) ? 'active' : '' ?>">
            <div class="footer-icon">🏠</div>
            <div class="footer-label">Home</div>
        </a>
        
        <a href="/modules/settings/dashboard.php" class="footer-item <?= (strpos($_SERVER['REQUEST_URI'], '/modules/settings/') !== false && basename($_SERVER['PHP_SELF']) !== 'notifications.php') ? 'active' : '' ?>">
            <div class="footer-icon">⚙️</div>
            <div class="footer-label">Settings</div>
        </a>
        
        <a href="/modules/settings/notifications.php" class="footer-item <?= (basename($_SERVER['PHP_SELF']) === 'notifications.php') ? 'active' : '' ?>">
            <div class="footer-icon" style="position: relative;">
                🔔
                <?php if ($unreadCount > 0): ?>
                    <span class="footer-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                <?php endif; ?>
            </div>
            <div class="footer-label">Notifications</div>
        </a>
        
        <a href="/modules/profile/view.php" class="footer-item <?= (strpos($_SERVER['REQUEST_URI'], '/modules/profile/') !== false) ? 'active' : '' ?>">
            <div class="footer-icon">👤</div>
            <div class="footer-label">Profile</div>
        </a>
    </div>
</div>

<style>
.context-shortcut-bar {
    position: fixed;
    bottom: 70px;
    left: 0;
    right: 0;
    background: rgba(102, 126, 234, 0.95);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    z-index: 999;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: center;
}

.context-shortcut {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: white;
    color: #667eea;
    padding: 8px 20px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.context-shortcut:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    background: #f8f9fa;
}

.app-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e0e0e0;
    z-index: 1000;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.footer-content {
    display: flex;
    justify-content: space-around;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 8px 0;
}

.footer-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #666;
    flex: 1;
    padding: 8px;
    transition: all 0.2s ease;
    position: relative;
}

.footer-item:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.footer-item.active {
    color: #667eea;
    font-weight: 600;
}

.footer-item.active .footer-icon {
    transform: scale(1.1);
}

.footer-icon {
    font-size: 24px;
    margin-bottom: 4px;
    transition: transform 0.2s ease;
}

.footer-label {
    font-size: 12px;
    font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
}

.footer-badge {
    position: absolute;
    top: -4px;
    right: -8px;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 600;
    min-width: 18px;
    text-align: center;
}

@media (max-width: 576px) {
    .footer-label {
        font-size: 11px;
    }
    
    .footer-icon {
        font-size: 22px;
    }
}
</style>
