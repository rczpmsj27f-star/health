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
// This query retrieves medications scheduled for today with their dose times
$stmt = $pdo->prepare("
    SELECT DISTINCT
        m.id, 
        mdt.dose_time, 
        ms.special_timing
    FROM medications m
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    LEFT JOIN medication_dose_times mdt ON m.id = mdt.medication_id
    WHERE m.user_id = ?
    AND (m.archived = 0 OR m.archived IS NULL)
    AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
    AND (
        ms.frequency_type = 'per_day' 
        OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE ?)
    )
    AND mdt.dose_time IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM medication_logs ml2 
        WHERE ml2.medication_id = m.id 
        AND DATE(ml2.scheduled_date_time) = ?
        AND TIME(ml2.scheduled_date_time) = mdt.dose_time
        AND ml2.status IN ('taken', 'skipped')
    )
");
$stmt->execute([$_SESSION['user_id'], "%$todayDayOfWeek%", $todayDate]);
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count overdue medications - only those with scheduled_date_time in the past AND still pending
$overdueCount = 0;
$firstOverdueMedId = null;
$currentTime = time(); // Current unix timestamp
$countedDoses = []; // Track already-counted doses to prevent duplicates

foreach ($medications as $med) {
    if (empty($med['dose_time'])) {
        continue;
    }
    
    // Create deduplication key: medication_id + dose_time
    $dedupeKey = $med['id'] . '_' . $med['dose_time'];
    if (isset($countedDoses[$dedupeKey])) {
        // Already counted this dose, skip to avoid duplicate counting
        continue;
    }
    
    // Handle special timing overrides
    if ($med['special_timing'] === 'on_waking') {
        // "On waking" medications are considered overdue after 9:00 AM
        $scheduledTimestamp = strtotime($todayDate . ' 09:00:00');
    } elseif ($med['special_timing'] === 'before_bed') {
        // "Before bed" medications are considered overdue after 10:00 PM
        $scheduledTimestamp = strtotime($todayDate . ' 22:00:00');
    } else {
        // Regular timed medications use their actual dose_time
        // Build the scheduled timestamp from today's date + dose_time
        $scheduledTimestamp = strtotime($todayDate . ' ' . $med['dose_time']);
    }
    
    // Use simple strtotime/time comparison (matching medication_item.php line 48)
    $isOverdue = $scheduledTimestamp < $currentTime;
    
    if ($isOverdue) {
        $overdueCount++;
        $countedDoses[$dedupeKey] = true; // Mark as counted
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
    
    <!-- OneSignal Native Plugin Only - Web SDK completely removed -->
    <!-- This app uses ONLY the native Capacitor plugin (onesignal-cordova-plugin) -->
    <!-- No conditional loading needed - native plugin works in both web and native contexts -->
    <script src="/assets/js/onesignal-capacitor.js?v=<?= time() ?>" defer></script>
    
    <!-- Request OneSignal permissions for authenticated users only -->
    <!-- This script only runs on authenticated pages, preventing prompts on login page -->
    <script src="/assets/js/onesignal-permission-request.js?v=<?= time() ?>" defer></script>

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
