<?php
session_start();
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Fetch user preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$userId]);
$preferences = $stmt->fetch(PDO::FETCH_ASSOC);

// DARK MODE TEMPORARILY DISABLED - 2026-02-06
// Dark mode implementation incomplete - causing usability issues
// TODO: Properly implement dark mode with correct text colors

// Create default preferences if none exist
if (!$preferences) {
    $createStmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, time_format, stock_notification_threshold, stock_notification_enabled, notify_linked_users) 
        VALUES (?, '12h', 10, 1, 0)
    ");
    $createStmt->execute([$userId]);
    
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
}

$err = $_SESSION['error'] ?? null;
$ok  = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferences – Health Tracker</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div class="container">
        <div class="content-card">
            <div class="card-header">
                <h2>User Preferences</h2>
                <p>Customize your Health Tracker experience</p>
            </div>

            <?php if ($err): ?>
                <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
            <?php endif; ?>
            <?php if ($ok): ?>
                <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
            <?php endif; ?>

            <form method="POST" action="/modules/settings/save_preferences_handler.php" id="preferences-form">
                <!-- DARK MODE TEMPORARILY DISABLED - 2026-02-06 -->
                <!-- Theme mode section removed - dark mode causing usability issues -->
                
                <div class="section-header">Time Display</div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">Time Format</strong>
                        <small style="color: var(--color-text-secondary);">
                            Choose how times are displayed throughout the app
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 8px; background: var(--color-bg-light); padding: 4px; border-radius: 8px;">
                        <button type="button" 
                                onclick="setTimeFormat(false)" 
                                id="time-12h"
                                class="time-format-btn <?= !($preferences['use_24_hour'] ?? 0) ? 'active' : '' ?>"
                                style="padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; background: <?= !($preferences['use_24_hour'] ?? 0) ? 'var(--color-primary)' : 'transparent' ?>; color: <?= !($preferences['use_24_hour'] ?? 0) ? 'white' : 'var(--color-text)' ?>;">
                            12-hour
                        </button>
                        <button type="button" 
                                onclick="setTimeFormat(true)" 
                                id="time-24h"
                                class="time-format-btn <?= ($preferences['use_24_hour'] ?? 0) ? 'active' : '' ?>"
                                style="padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; background: <?= ($preferences['use_24_hour'] ?? 0) ? 'var(--color-primary)' : 'transparent' ?>; color: <?= ($preferences['use_24_hour'] ?? 0) ? 'white' : 'var(--color-text)' ?>;">
                            24-hour
                        </button>
                    </div>
                    
                    <input type="hidden" name="use_24_hour" id="use_24_hour_input" value="<?= ($preferences['use_24_hour'] ?? 0) ?>">
                </div>

                <div class="section-header">Stock Notifications</div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="stock_notification_enabled" value="1" <?= $preferences['stock_notification_enabled'] ? 'checked' : '' ?>>
                        <span>Enable Low Stock Notifications</span>
                    </label>
                    <p class="help-text">Get notified when medication stock is running low</p>
                </div>

                <div class="form-group">
                    <label>Stock Notification Threshold (days)</label>
                    <input type="number" name="stock_notification_threshold" 
                           value="<?= htmlspecialchars($preferences['stock_notification_threshold']) ?>" 
                           min="1" max="90" class="form-control" inputmode="numeric">
                    <p class="help-text">Notify me when I have less than this many days of medication remaining</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notify_linked_users" value="1" <?= $preferences['notify_linked_users'] ? 'checked' : '' ?>>
                        <span>Notify Linked Users</span>
                    </label>
                    <p class="help-text">Send low stock notifications to users who can view your medications</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-accept">Save Preferences</button>
                    <a href="/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/menu.js"></script>
    <script>
    // Show success message if redirected after save
    <?php if ($ok): ?>
        showSuccessModal('<?= addslashes($ok) ?>');
    <?php endif; ?>
    
    // Time format toggle functionality
    function setTimeFormat(use24Hour) {
        document.getElementById('use_24_hour_input').value = use24Hour ? '1' : '0';
        
        // Update button styles
        updateButtonStyles(use24Hour);
        
        // Auto-save the preference
        const formData = new FormData();
        formData.append('use_24_hour', use24Hour ? '1' : '0');
        
        fetch('/modules/settings/preferences_handler.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to apply changes
                window.location.reload();
            }
        }).catch(error => {
            console.error('Error saving time format:', error);
        });
    }
    
    function updateButtonStyles(use24Hour) {
        const btn12 = document.getElementById('time-12h');
        const btn24 = document.getElementById('time-24h');
        
        if (use24Hour) {
            btn24.style.background = 'var(--color-primary)';
            btn24.style.color = 'white';
            btn12.style.background = 'transparent';
            btn12.style.color = 'var(--color-text)';
        } else {
            btn12.style.background = 'var(--color-primary)';
            btn12.style.color = 'white';
            btn24.style.background = 'transparent';
            btn24.style.color = 'var(--color-text)';
        }
    }
    </script>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
