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
// Check if we're in symptoms sub-pages (but not the symptom dashboard itself)
elseif (strpos($currentPath, '/modules/symptoms/') !== false && 
        basename($_SERVER['PHP_SELF']) !== 'index.php') {
    $contextShortcut = [
        'label' => 'Symptom Dashboard',
        'url' => '/modules/symptoms/index.php',
        'icon' => '🩺'
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

<!-- Footer with 4 base icons and optional context pill -->
<div class="app-footer">
    <div class="footer-content <?= $contextShortcut ? 'has-context-pill' : '' ?>">
        <div class="footer-nav-items">
            <a href="/dashboard.php" class="footer-item <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && !isset($_GET['view'])) ? 'active' : '' ?>">
                <div class="footer-icon">🏠</div>
                <div class="footer-label">Home</div>
            </a>
            
            <a href="/modules/settings/dashboard.php" class="footer-item <?= (strpos($_SERVER['REQUEST_URI'], '/modules/settings/') !== false && basename($_SERVER['PHP_SELF']) !== 'notifications.php') ? 'active' : '' ?>">
                <div class="footer-icon">⚙️</div>
                <div class="footer-label">Settings</div>
            </a>
            
            <a href="/modules/notifications/index.php" class="footer-item <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/modules/notifications/') !== false) ? 'active' : '' ?>">
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
        
        <?php if ($contextShortcut): ?>
        <a href="<?= htmlspecialchars($contextShortcut['url']) ?>" class="footer-context-pill">
            <span class="pill-icon"><?= $contextShortcut['icon'] ?></span>
            <span class="pill-label"><?= htmlspecialchars($contextShortcut['label']) ?></span>
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
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
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 8px 16px;
    gap: 16px;
}

/* When context pill is present, adjust layout */
.footer-content.has-context-pill {
    justify-content: flex-start;
}

.footer-nav-items {
    display: flex;
    justify-content: space-around;
    align-items: center;
    flex: 1;
}

/* Context pill - right-aligned, ~20% width */
.footer-context-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    background: white;
    color: #667eea;
    padding: 10px 16px;
    border-radius: 24px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    transition: all 0.2s ease;
    border: 2px solid #667eea;
    white-space: nowrap;
    min-height: 44px; /* Accessible touch target */
    flex-shrink: 0;
}

.footer-context-pill:hover {
    background: #f8f9fa;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.pill-icon {
    font-size: 18px;
    line-height: 1;
}

.pill-label {
    font-size: 13px;
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
    min-height: 44px; /* Accessible touch target */
    justify-content: center;
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
    
    .footer-content {
        padding: 8px 12px;
        gap: 12px;
    }
    
    .footer-context-pill {
        font-size: 12px;
        padding: 8px 12px;
        gap: 4px;
    }
    
    .pill-icon {
        font-size: 16px;
    }
    
    .pill-label {
        font-size: 12px;
    }
}

@media (max-width: 400px) {
    .footer-content {
        padding: 8px 8px;
        gap: 8px;
    }
    
    .footer-context-pill {
        font-size: 11px;
        padding: 6px 10px;
    }
    
    .pill-label {
        font-size: 11px;
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>
