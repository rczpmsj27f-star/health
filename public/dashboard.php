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

// Fetch user details for profile header (Issue #51)
$userStmt = $pdo->prepare("SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($displayName)) {
    // Fallback to email if no name is set
    $displayName = explode('@', $user['email'] ?? 'User')[0];
}

// Default avatar if none set
$avatarUrl = !empty($user['profile_picture']) ? $user['profile_picture'] : '/assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Health Tracker</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <!-- OneSignal Initialization -->
    <script>
        // v16 initialization pattern
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        
        OneSignalDeferred.push(async function(OneSignal) {
            // Get App ID from server-side config
            const appId = '<?php echo htmlspecialchars(ONESIGNAL_APP_ID, ENT_QUOTES, 'UTF-8'); ?>';
            
            if (!appId || appId === 'YOUR_ONESIGNAL_APP_ID') {
                console.warn('OneSignal App ID not configured. Set ONESIGNAL_APP_ID in config.php');
                return;
            }
            
            await OneSignal.init({
                appId: appId,
                allowLocalhostAsSecureOrigin: true,
                serviceWorkerPath: '/OneSignalSDKWorker.js', // Absolute path from root
                serviceWorkerParam: { scope: '/' },
                notifyButton: {
                    enable: false
                }
            });
            
            // Make available globally
            window.OneSignal = OneSignal;
            console.log('✅ OneSignal initialized');
        });
    </script>
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
/* Critical inline styles - fallback if external CSS doesn't load */
.tile {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 24px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: #ffffff;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.tile .tile-title, .tile .tile-desc, .tile .tile-icon {
    color: #ffffff;
}
.btn {
    padding: 14px 20px;
    border-radius: 6px;
    border: none;
    font-size: 16px;
    color: #ffffff;
    display: block;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    min-height: 48px;
}
.btn-primary { background: #2563eb; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-info { background: #007bff; color: #fff; }
    </style>
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
    <?php include __DIR__ . '/../app/includes/menu.php'; ?>

    <div class="dashboard-container">
        <!-- User Profile Header (Issue #51) -->
        <div style="display: flex; align-items: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <img src="<?= htmlspecialchars($avatarUrl) ?>" 
                 alt="Profile" 
                 onerror="this.src='/assets/images/default-avatar.svg'"
                 style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid white; object-fit: cover; margin-right: 16px; background: white;">
            <div>
                <h2 style="margin: 0; font-size: 24px; font-weight: 600;">
                    Welcome back, <?= htmlspecialchars($displayName) ?>!
                </h2>
                <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 14px;">
                    <?= date('l, F j, Y') ?>
                </p>
            </div>
        </div>
        
        <div class="dashboard-title">
            <h2>Health Tracker Dashboard</h2>
            <p>Welcome back! Manage your health and medications</p>
        </div>
        
        <div class="dashboard-grid">
            <a class="tile tile-purple" href="/modules/medications/dashboard.php">
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
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
