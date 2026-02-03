<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Get today's medications
$today = date('D'); // Mon, Tue, Wed, etc.

$stmt = $pdo->prepare("
    SELECT DISTINCT m.*, md.dose_amount, md.dose_unit, ms.frequency_type, ms.times_per_day, ms.days_of_week, ms.is_prn
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    WHERE m.user_id = ? 
    AND (m.archived = 0 OR m.archived IS NULL)
    AND (
        ms.frequency_type = 'per_day' 
        OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE ?)
        OR ms.is_prn = 1
    )
    ORDER BY m.name
");
$stmt->execute([$userId, "%$today%"]);
$todaysMeds = $stmt->fetchAll();

// Get dose times for each medication and build schedule by time
$medDoseTimes = [];
$scheduleByTime = [];

foreach ($todaysMeds as $med) {
    $stmt = $pdo->prepare("
        SELECT dose_number, dose_time 
        FROM medication_dose_times 
        WHERE medication_id = ? 
        ORDER BY dose_time
    ");
    $stmt->execute([$med['id']]);
    $doseTimes = $stmt->fetchAll();
    $medDoseTimes[$med['id']] = $doseTimes;
    
    // Group medications by time slot
    if (!empty($doseTimes)) {
        foreach ($doseTimes as $doseTime) {
            $timeKey = date('H:i', strtotime($doseTime['dose_time']));
            if (!isset($scheduleByTime[$timeKey])) {
                $scheduleByTime[$timeKey] = [];
            }
            $scheduleByTime[$timeKey][] = $med;
        }
    }
}

// Sort by time (earliest first)
ksort($scheduleByTime);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Dashboard</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 16px 16px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 32px;
            color: var(--color-primary);
            font-weight: 600;
        }
        
        .page-title p {
            margin: 0;
            color: var(--color-text-secondary);
        }
        
        .schedule-section {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .schedule-section h3 {
            color: var(--color-primary);
            margin: 0 0 20px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .schedule-card {
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 16px;
            border-left: 4px solid var(--color-primary);
        }
        
        .schedule-card:last-child {
            margin-bottom: 0;
        }
        
        .med-name {
            font-weight: 600;
            font-size: 18px;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .dose-time {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            color: var(--color-text-secondary);
        }
        
        .dose-time .time {
            font-weight: 600;
            color: var(--color-primary);
            min-width: 60px;
        }
        
        .prn-badge {
            display: inline-block;
            background: var(--color-warning);
            color: var(--color-bg-white);
            padding: 4px 12px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .no-meds {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-text-secondary);
        }
        
        .dashboard-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 32px;
        }
        
        .tile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px 24px;
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: #ffffff;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .tile:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .tile-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .tile-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
        }
        
        .tile-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .tile-blue {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
        }
        
        .tile-green {
            background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        
        <div class="menu-parent">
            <a href="/modules/medications/dashboard.php" class="menu-parent-link">💊 Medications</a>
            <div class="menu-children">
                <a href="/modules/medications/list.php">My Medications</a>
                <a href="/modules/medications/stock.php">Medication Stock</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="page-content">
        <div class="page-title">
            <h2>💊 Medication Dashboard</h2>
            <p>Today's schedule and medication management</p>
        </div>
        
        <!-- Today's Schedule Section -->
        <div class="schedule-section">
            <h3>Today's Schedule</h3>
            <p class="schedule-date"><?= date('l j F Y') ?></p>
            
            <?php if (empty($todaysMeds)): ?>
                <div class="no-meds">
                    <p>No medications scheduled for today</p>
                </div>
            <?php elseif (!empty($scheduleByTime)): ?>
                <!-- Display medications grouped by time -->
                <?php foreach ($scheduleByTime as $time => $meds): ?>
                    <div class="time-group">
                        <div class="time-group-header">
                            ⏰ <?= $time ?>
                        </div>
                        <div class="time-group-medications">
                            <?php foreach ($meds as $med): ?>
                                <div class="schedule-card">
                                    <div class="med-name">
                                        💊 <?= htmlspecialchars($med['name']) ?>
                                        <?php if ($med['is_prn']): ?>
                                            <span class="prn-badge">PRN</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dose-time">
                                        <span><?= htmlspecialchars($med['dose_amount'] . ' ' . $med['dose_unit']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback for PRN or medications without specific times -->
                <?php foreach ($todaysMeds as $med): ?>
                    <div class="schedule-card">
                        <div class="med-name">
                            💊 <?= htmlspecialchars($med['name']) ?>
                            <?php if ($med['is_prn']): ?>
                                <span class="prn-badge">PRN</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($med['is_prn']): ?>
                            <div class="dose-time">
                                <span class="time">As needed</span>
                                <span><?= htmlspecialchars($med['dose_amount'] . ' ' . $med['dose_unit']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="dose-time">
                                <span><?= htmlspecialchars($med['dose_amount'] . ' ' . $med['dose_unit']) ?></span>
                                <?php if ($med['times_per_day']): ?>
                                    <span>(<?= $med['times_per_day'] ?> time<?= $med['times_per_day'] > 1 ? 's' : '' ?> per day)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Dashboard Tiles -->
        <div class="dashboard-tiles">
            <a class="tile tile-blue" href="/modules/medications/list.php">
                <div>
                    <span class="tile-icon">📋</span>
                    <div class="tile-title">My Medications</div>
                    <div class="tile-desc">View and manage your medications</div>
                </div>
            </a>
            
            <a class="tile tile-green" href="/modules/medications/stock.php">
                <div>
                    <span class="tile-icon">📦</span>
                    <div class="tile-title">Medication Stock</div>
                    <div class="tile-desc">Track and update stock levels</div>
                </div>
            </a>
        </div>
    </div>
</body>
</html>
