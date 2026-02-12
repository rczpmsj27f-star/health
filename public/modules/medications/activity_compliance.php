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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity & Compliance – Health Tracker</title>
    
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
        
        .tile-gray:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .tile-gray .tile-title,
        .tile-gray .tile-desc {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    
    <div id="main-content">
    <div class="dashboard-container">
        <div class="dashboard-title">
            <h2>Activity & Compliance</h2>
        </div>
        
        <div class="dashboard-grid">
            <a class="tile" href="/modules/reports/activity.php">
                <div class="tile-icon">📈</div>
                <div class="tile-title">Activity Report</div>
                <div class="tile-desc">View medication activity</div>
            </a>
            
            <a class="tile" href="/modules/reports/compliance.php">
                <div class="tile-icon">📊</div>
                <div class="tile-title">Compliance Report</div>
                <div class="tile-desc">Track adherence rates</div>
            </a>
            
            <div class="tile tile-gray">
                <div class="tile-icon">📜</div>
                <div class="tile-title">History</div>
                <div class="tile-desc">Coming soon</div>
            </div>
            
            <a class="tile" href="/modules/reports/exports.php">
                <div class="tile-icon">📥</div>
                <div class="tile-title">Exports</div>
                <div class="tile-desc">Export data and reports</div>
            </a>
        </div>
    </div>
    </div> <!-- #main-content -->
    
    <?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
