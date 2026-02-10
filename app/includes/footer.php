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

<!-- Dynamic context button - appears above footer when in sub-area -->
<?php if ($contextShortcut): ?>
<div class="context-button-container">
    <a href="<?= htmlspecialchars($contextShortcut['url']) ?>" class="context-button">
        <span><?= $contextShortcut['icon'] ?></span>
        <span><?= htmlspecialchars($contextShortcut['label']) ?></span>
    </a>
</div>
<?php endif; ?>

<!-- Footer with 4 base icons -->
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
/* Context button - positioned above footer, right-aligned */
.context-button-container {
    position: fixed;
    bottom: 70px; /* Just above footer height */
    right: 16px;
    z-index: 999;
}

.context-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: white;
    color: #667eea;
    padding: 8px 18px;
    border-radius: 24px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    transition: all 0.2s ease;
    box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3);
    border: 2px solid #667eea;
}

.context-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 14px rgba(102, 126, 234, 0.4);
    background: #f8f9fa;
}

/* Footer styles */
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

/* Mobile responsive */
@media (max-width: 576px) {
    .footer-label {
        font-size: 11px;
    }
    
    .footer-icon {
        font-size: 22px;
    }
    
    .context-button {
        font-size: 12px;
        padding: 6px 14px;
    }
    
    .context-button-container {
        right: 12px;
        bottom: 65px;
    }
}

@media (max-width: 400px) {
    .context-button {
        font-size: 11px;
        padding: 5px 12px;
    }
    
    .context-button span:last-child {
        /* Keep label visible on very small screens */
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .context-button-container {
        right: 8px;
    }
}
</style>
