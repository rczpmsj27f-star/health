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
$stmt = $pdo->prepare("
    SELECT 
        m.id, 
        mdt.dose_time, 
        ms.special_timing,
        ml.status
    FROM medications m
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    LEFT JOIN medication_dose_times mdt ON m.id = mdt.medication_id
    LEFT JOIN medication_logs ml ON m.id = ml.medication_id 
        AND DATE(ml.scheduled_date_time) = ?
        AND TIME(ml.scheduled_date_time) = mdt.dose_time
    WHERE m.user_id = ?
    AND (m.archived = 0 OR m.archived IS NULL)
    AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
    AND (
        ms.frequency_type = 'per_day' 
        OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE ?)
    )
    AND mdt.dose_time IS NOT NULL
    AND (ml.status IS NULL OR ml.status = 'pending')
    AND NOT EXISTS (
        SELECT 1 FROM medication_logs ml2 
        WHERE ml2.medication_id = m.id 
        AND DATE(ml2.scheduled_date_time) = ?
        AND TIME(ml2.scheduled_date_time) = mdt.dose_time
        AND ml2.status = 'taken'
    )
");
$stmt->execute([$todayDate, $_SESSION['user_id'], "%$todayDayOfWeek%", $todayDate]);
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count overdue medications
$overdueCount = 0;
$firstOverdueMedId = null;
$currentTimeStamp = strtotime(date('H:i'));

foreach ($medications as $med) {
    // Note: dose_time is already validated as NOT NULL in query at line 45
    // This check is kept as defensive programming practice
    if (empty($med['dose_time'])) {
        continue;
    }
    
    $doseTime = strtotime($med['dose_time']);
    $isOverdue = false;
    
    // Apply special timing rules
    if ($med['special_timing'] === 'on_waking') {
        // Overdue after 9am
        $isOverdue = $currentTimeStamp > strtotime('09:00');
    } elseif ($med['special_timing'] === 'before_bed') {
        // Overdue after 10pm
        $isOverdue = $currentTimeStamp > strtotime('22:00');
    } else {
        // Regular time - overdue after scheduled time
        $isOverdue = $currentTimeStamp > $doseTime;
    }
    
    // Count if overdue - status is already filtered in query (line 46: status IS NULL OR pending)
    // and taken medications are excluded via NOT EXISTS subquery (lines 47-53)
    if ($isOverdue) {
        $overdueCount++;
        if ($firstOverdueMedId === null) {
            $firstOverdueMedId = $med['id'];
        }
    }
}

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
    <?php include __DIR__ . '/../app/includes/menu.php'; ?>
    
    <!-- OneSignal Native Plugin Only - Web SDK completely removed -->
    <!-- This app uses ONLY the native Capacitor plugin (onesignal-cordova-plugin) -->
    <!-- No conditional loading needed - native plugin works in both web and native contexts -->
    <script src="/assets/js/onesignal-capacitor.js?v=<?= time() ?>" defer></script>

    <div class="dashboard-container">
        <!-- User Profile Header (Issue #51) -->
        <div style="display: flex; align-items: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; margin: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
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
            <a class="tile tile-purple" href="<?= $overdueCount === 1 && $firstOverdueMedId ? '/modules/medications/view.php?id=' . $firstOverdueMedId : '/modules/medications/dashboard.php' ?>" style="position: relative;">
                <?php if ($overdueCount > 0): ?>
                    <span class="overdue-badge"><?= $overdueCount ?></span>
                <?php endif; ?>
                <div>
                    <span class="tile-icon">💊</span>
                    <div class="tile-title">Medication Management</div>
                    <div class="tile-desc">
                        Track your medications
                        <?php if ($overdueCount > 0): ?>
                            <span style="color: #ffebee; font-weight: 600; display: block; margin-top: 4px; text-decoration: underline;">
                                • <?= $overdueCount ?> overdue<?= $overdueCount === 1 ? ' - View now' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
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
