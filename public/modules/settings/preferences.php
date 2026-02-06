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

// Create default preferences if none exist
if (!$preferences) {
    $createStmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, theme_mode, time_format, stock_notification_threshold, stock_notification_enabled, notify_linked_users) 
        VALUES (?, 'device', '12h', 10, 1, 0)
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
<body class="<?php 
$themeMode = $preferences['theme_mode'] ?? 'device';
if ($themeMode === 'light') echo 'theme-light';
elseif ($themeMode === 'dark') echo 'theme-dark';
// theme_mode === 'device' gets no class (uses media query)
?>">
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

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
                <div class="section-header">Appearance</div>

                <div class="form-group">
                    <label>Theme Mode</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="theme_mode" value="light" <?= ($preferences['theme_mode'] ?? 'device') === 'light' ? 'checked' : '' ?>>
                            <span>☀️ Light Mode</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="theme_mode" value="dark" <?= ($preferences['theme_mode'] ?? 'device') === 'dark' ? 'checked' : '' ?>>
                            <span>🌙 Dark Mode</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="theme_mode" value="device" <?= ($preferences['theme_mode'] ?? 'device') === 'device' ? 'checked' : '' ?>>
                            <span>📱 Device Mode (Auto)</span>
                        </label>
                    </div>
                    <p class="help-text">Choose your preferred color scheme. Device mode automatically matches your system settings.</p>
                </div>

                <div class="section-header">Time Display</div>

                <div class="form-group">
                    <label>Time Format</label>
                    <select name="time_format" class="form-control">
                        <option value="12h" <?= $preferences['time_format'] === '12h' ? 'selected' : '' ?>>12-hour (AM/PM)</option>
                        <option value="24h" <?= $preferences['time_format'] === '24h' ? 'selected' : '' ?>>24-hour</option>
                    </select>
                    <p class="help-text">Choose how times are displayed throughout the app</p>
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
                           min="1" max="90" class="form-control">
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
    </script>
</body>
</html>
