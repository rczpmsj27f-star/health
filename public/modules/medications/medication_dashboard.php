<?php
require_once "../../../app/includes/cache-buster.php";

// Include database FIRST
require_once __DIR__ . '/../../../app/config/database.php';

// Then include other dependencies
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../app/core/auth.php';

// Check authentication
if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Check if user is admin
$isAdmin = Auth::isAdmin();

// Get overdue medication count for the logged-in user
$overdueCheckDow  = date('D');
$overdueCheckDate = date('Y-m-d');
$stmtOverdue = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(m.id, '_', mdt.dose_time)) as cnt
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
        (ms.special_timing = 'on_waking'  AND CONCAT(:today_date2, ' 09:00:00') < NOW())
        OR (ms.special_timing = 'before_bed' AND CONCAT(:today_date3, ' 22:00:00') < NOW())
        OR ((ms.special_timing IS NULL OR ms.special_timing NOT IN ('on_waking','before_bed'))
            AND CONCAT(:today_date4, ' ', mdt.dose_time) < NOW())
    )
");
$stmtOverdue->execute([
    'user_id'     => $_SESSION['user_id'],
    'day_of_week' => "%$overdueCheckDow%",
    'today_date'  => $overdueCheckDate,
    'today_date2' => $overdueCheckDate,
    'today_date3' => $overdueCheckDate,
    'today_date4' => $overdueCheckDate,
]);
$overdueCount = (int)$stmtOverdue->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication – Health Tracker</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
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

        .tile-overdue-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ef4444;
            color: white;
            border-radius: 12px;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 700;
            min-width: 22px;
            text-align: center;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <div id="main-content">
    
    <div class="dashboard-container">
        <div class="dashboard-title">
            <h2>Medication</h2>
        </div>
        
        <div class="dashboard-grid">
            <a class="tile" href="/modules/medications/dashboard.php">
                <?php if ($overdueCount > 0): ?>
                    <span class="tile-overdue-badge"><?= $overdueCount ?></span>
                <?php endif; ?>
                <div class="tile-icon">📅</div>
                <div class="tile-title">View Schedule</div>
                <div class="tile-desc">
                    See today's medications
                    <?php if ($overdueCount > 0): ?>
                        <span style="display:block; margin-top:4px; font-weight:600; color:#ffebee;">• <?= $overdueCount ?> overdue</span>
                    <?php endif; ?>
                </div>
            </a>
            
            <a class="tile" href="/modules/medications/list.php">
                <div class="tile-icon">💊</div>
                <div class="tile-title">Manage Medications</div>
                <div class="tile-desc">View and edit all medications</div>
            </a>
            
            <a class="tile" href="/modules/medications/log_prn.php">
                <div class="tile-icon">✏️</div>
                <div class="tile-title">Log PRN Medication</div>
                <div class="tile-desc">Record as-needed medications</div>
            </a>
            
            <a class="tile" href="/modules/medications/activity_compliance.php">
                <div class="tile-icon">📊</div>
                <div class="tile-title">Activity & Compliance</div>
                <div class="tile-desc">View reports and analytics</div>
            </a>
        </div>
    </div>
    
    </div> <!-- #main-content -->
    <?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
