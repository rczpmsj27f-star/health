<?php
session_start();

// Include database FIRST
require_once __DIR__ . '/../app/config/database.php';

// Then include other dependencies
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/core/auth.php';

// Check authentication
if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Check if user is admin
$isAdmin = Auth::isAdmin();

// Get overdue medication count
$todayDayOfWeek = date('D'); // Day of week: Mon, Tue, etc.
$todayDate = date('Y-m-d');
$currentDateTime = date('Y-m-d H:i:s');

// Query for overdue medications with special time handling
// This query retrieves ONLY overdue medications scheduled for today
$stmt = $pdo->prepare("
    SELECT DISTINCT
        m.id, 
        mdt.dose_time, 
        ms.special_timing
    FROM medications m
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    LEFT JOIN medication_dose_times mdt ON m.id = mdt.medication_id
    WHERE m.user_id = :user_id
    AND (m.archived = 0 OR m.archived IS NULL)
    AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
    AND (
        ms.frequency_type = 'per_day' 
        OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE :day_of_week)
    )
    AND mdt.dose_time IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM medication_logs ml2 
        WHERE ml2.medication_id = m.id 
        AND DATE(ml2.scheduled_date_time) = :today_date
        AND TIME(ml2.scheduled_date_time) = mdt.dose_time
        AND ml2.status IN ('taken', 'skipped')
    )
    AND (
        (ms.special_timing = 'on_waking' AND CONCAT(:today_date, ' 09:00:00') < NOW())
        OR (ms.special_timing = 'before_bed' AND CONCAT(:today_date, ' 22:00:00') < NOW())
        OR ((ms.special_timing IS NULL OR ms.special_timing NOT IN ('on_waking', 'before_bed')) AND CONCAT(:today_date, ' ', mdt.dose_time) < NOW())
    )
    AND (
        (ms.special_timing = 'on_waking' AND CONCAT(:today_date, ' 09:00:00') >= m.created_at)
        OR (ms.special_timing = 'before_bed' AND CONCAT(:today_date, ' 22:00:00') >= m.created_at)
        OR ((ms.special_timing IS NULL OR ms.special_timing NOT IN ('on_waking', 'before_bed')) AND CONCAT(:today_date, ' ', mdt.dose_time) >= m.created_at)
    )
");
$stmt->execute([
    'user_id' => $_SESSION['user_id'],
    'day_of_week' => "%$todayDayOfWeek%",
    'today_date' => $todayDate
]);
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count overdue medications - query already filtered to only overdue doses
$overdueCount = count($medications);
$firstOverdueMedId = !empty($medications) ? $medications[0]['id'] : null;

// Fetch user details for profile header (Issue #51)
$userStmt = $pdo->prepare("SELECT first_name, surname, email, profile_picture_path FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
if (empty($displayName)) {
    // Fallback to email if no name is set
    $displayName = explode('@', $user['email'] ?? 'User')[0];
}

// Default avatar if none set
$avatarUrl = !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '/assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard – Health Tracker</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <style>
        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px;
        }
        
        .dashboard-title {
            text-align: center;
            padding: 20px 0;
            color: #333;
        }
        
        .dashboard-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        
        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        
        .tile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: #ffffff;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .tile-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .tile-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
        }
        
        .tile-desc {
            font-size: 14px;
            margin-top: 8px;
            opacity: 0.9;
            color: #ffffff;
        }
        
        .tile-gray {
            background: #e9ecef;
            cursor: not-allowed;
        }
        
        .tile-gray .tile-title,
        .tile-gray .tile-desc {
            color: #6c757d;
        }
        
        .tile-gray:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* High-specificity override for coming-soon tiles */
        .dashboard-grid .tile.tile--coming-soon {
            background: #e9ecef !important;
            pointer-events: none !important;
        }
        
        .dashboard-grid .tile.tile--coming-soon .tile-title,
        .dashboard-grid .tile.tile--coming-soon .tile-desc {
            color: #6c757d !important;
        }
        
        .dashboard-grid .tile.tile--coming-soon:hover {
            transform: none !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        .tile-red {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        
        .overdue-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 10;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../app/includes/header.php'; ?>
    
    <!-- Push Notification Permission Banner -->
    <div id="push-notification-banner" style="display: none; background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 16px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: start; gap: 12px;">
            <div style="font-size: 24px;">🔔</div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;">Enable Push Notifications</div>
                <div style="color: #92400e; font-size: 14px; margin-bottom: 12px;" id="push-banner-message">
                    Get medication reminders and important alerts even when the app is closed.
                </div>
                <button id="enable-push-banner-btn" onclick="requestPushPermissions()" style="background: #f59e0b; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    Enable Notifications
                </button>
                <button onclick="dismissPushBanner()" style="background: transparent; color: #92400e; border: 1px solid #f59e0b; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; margin-left: 8px; font-size: 14px;">
                    Not Now
                </button>
            </div>
            <button onclick="dismissPushBanner()" style="background: none; border: none; font-size: 20px; color: #92400e; cursor: pointer; padding: 0; line-height: 1;">×</button>
        </div>
    </div>
    
    <!-- OneSignal Native Plugin Only - Web SDK completely removed -->
    <!-- This app uses ONLY the native Capacitor plugin (onesignal-cordova-plugin) -->
    <!-- No conditional loading needed - native plugin works in both web and native contexts -->
    <script src="/assets/js/onesignal-capacitor.js?v=<?= time() ?>" defer></script>
    
    <!-- Request OneSignal permissions for authenticated users only -->
    <!-- This script only runs on authenticated pages, preventing prompts on login page -->
    <script src="/assets/js/onesignal-permission-request.js?v=<?= time() ?>" defer></script>
    
    <script>
    // Check push notification status and show banner if needed
    function checkPushNotificationStatus() {
        const banner = document.getElementById('push-notification-banner');
        const bannerMessage = document.getElementById('push-banner-message');
        const enableBtn = document.getElementById('enable-push-banner-btn');
        
        // Check if user dismissed the banner
        if (localStorage.getItem('push_banner_dismissed') === 'true') {
            return;
        }
        
        // Check if running in Capacitor
        const isCapacitor = typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
        
        if (isCapacitor) {
            // Check Capacitor push notification permissions
            if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.PushNotifications) {
                window.Capacitor.Plugins.PushNotifications.checkPermissions().then(permStatus => {
                    if (permStatus.receive === 'granted') {
                        console.log('✅ Push notifications already enabled');
                    } else if (permStatus.receive === 'denied') {
                        // Show banner with instructions to enable in system settings
                        bannerMessage.textContent = 'Push notifications are disabled. To enable them, go to your device Settings > Notifications and allow notifications for Health Tracker.';
                        enableBtn.style.display = 'none';
                        banner.style.display = 'block';
                    } else {
                        // Permission not yet requested - show banner
                        banner.style.display = 'block';
                    }
                }).catch(err => {
                    console.log('Could not check push permissions:', err);
                });
            }
        } else {
            // Web browser - check Notification API
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    console.log('✅ Push notifications already enabled');
                } else if (Notification.permission === 'denied') {
                    // Show banner with instructions
                    bannerMessage.textContent = 'Push notifications are blocked. To enable them, click the lock icon in your browser\'s address bar and allow notifications.';
                    enableBtn.style.display = 'none';
                    banner.style.display = 'block';
                } else {
                    // Permission not yet requested - show banner
                    banner.style.display = 'block';
                }
            }
        }
    }
    
    // Request push permissions when user clicks the button
    async function requestPushPermissions() {
        const isCapacitor = typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
        
        if (isCapacitor) {
            // Use Capacitor push notifications
            if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.PushNotifications) {
                try {
                    const permStatus = await window.Capacitor.Plugins.PushNotifications.requestPermissions();
                    if (permStatus.receive === 'granted') {
                        console.log('✅ Push notification permission granted');
                        dismissPushBanner();
                        // Initialize push notifications
                        if (window.CapacitorPush && window.CapacitorPush.initialize) {
                            window.CapacitorPush.initialize();
                        }
                    } else {
                        alert('Permission denied. Please enable notifications in your device settings.');
                    }
                } catch (err) {
                    console.error('Error requesting permissions:', err);
                    alert('Failed to request notification permissions. Please try again.');
                }
            }
        } else {
            // Web browser - use Notification API or OneSignal
            if (window.OneSignal && window.OneSignal.Notifications && 
                typeof window.OneSignal.Notifications.requestPermission === 'function') {
                try {
                    const accepted = await window.OneSignal.Notifications.requestPermission();
                    if (accepted) {
                        console.log('✅ Notification permission granted');
                        dismissPushBanner();
                    } else {
                        alert('Permission denied. Please enable notifications in your browser settings.');
                    }
                } catch (err) {
                    console.error('Error requesting permissions:', err);
                    alert('Failed to request notification permissions. Please try again.');
                }
            }
        }
    }
    
    // Dismiss the push notification banner
    function dismissPushBanner() {
        document.getElementById('push-notification-banner').style.display = 'none';
        localStorage.setItem('push_banner_dismissed', 'true');
    }
    
    // Check status when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkPushNotificationStatus, 2000);
        });
    } else {
        setTimeout(checkPushNotificationStatus, 2000);
    }
    </script>

    <div class="dashboard-container">
        <div class="dashboard-title">
            <h2>Health Tracker Dashboard</h2>
        </div>
        
        <div class="dashboard-grid">
            <a class="tile" href="/modules/medications/medication_dashboard.php">
                <?php if ($overdueCount > 0): ?>
                    <span class="overdue-badge"><?= $overdueCount ?></span>
                <?php endif; ?>
                <div class="tile-icon">💊</div>
                <div class="tile-title">Medication</div>
                <div class="tile-desc">Manage your medications</div>
            </a>
            
            <div class="tile tile-gray tile--coming-soon">
                <div class="tile-icon">🩺</div>
                <div class="tile-title">Symptom Tracker</div>
                <div class="tile-desc">Coming soon</div>
            </div>
            
            <div class="tile tile-gray tile--coming-soon">
                <div class="tile-icon">🚽</div>
                <div class="tile-title">Bowel and Urine Tracker</div>
                <div class="tile-desc">Coming soon</div>
            </div>
            
            <div class="tile tile-gray tile--coming-soon">
                <div class="tile-icon">🍽️</div>
                <div class="tile-title">Food Diary</div>
                <div class="tile-desc">Coming soon</div>
            </div>
            
            <?php if ($isAdmin): ?>
            <a class="tile tile-red" href="/modules/admin/dashboard.php">
                <div class="tile-icon">🔐</div>
                <div class="tile-title">Admin Panel</div>
                <div class="tile-desc">Manage system settings</div>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/../app/includes/footer.php'; ?>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
