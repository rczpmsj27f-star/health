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

// Get current view from query parameter (default: daily)
$view = $_GET['view'] ?? 'daily';
$validViews = ['daily', 'weekly', 'monthly', 'annual'];
if (!in_array($view, $validViews)) {
    $view = 'daily';
}

// For monthly view, get current month/year or from parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month/year
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = (int)date('m');
}
if ($currentYear < 2020 || $currentYear > 2100) {
    $currentYear = (int)date('Y');
}

// Get all active medications
$stmt = $pdo->prepare("
    SELECT m.id, m.name, md.dose_amount, md.dose_unit
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL)
    ORDER BY m.name
");
$stmt->execute([$userId]);
$medications = $stmt->fetchAll();

// Calculate the week range (last 7 days)
$daysOfWeek = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayLabel = date('D', strtotime($date)); // Mon, Tue, Wed, etc.
    $daysOfWeek[] = [
        'date' => $date,
        'label' => $dayLabel,
        'is_today' => $date === date('Y-m-d')
    ];
}

// Get compliance data for each medication
$complianceData = [];
foreach ($medications as $med) {
    // Get scheduled times for this medication
    $stmt = $pdo->prepare("
        SELECT dose_time FROM medication_dose_times 
        WHERE medication_id = ? 
        ORDER BY dose_time
    ");
    $stmt->execute([$med['id']]);
    $doseTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get logs for the past 7 days
    $stmt = $pdo->prepare("
        SELECT DATE(scheduled_date_time) as log_date, COUNT(*) as taken_count
        FROM medication_logs
        WHERE medication_id = ? 
        AND user_id = ?
        AND status = 'taken'
        AND DATE(scheduled_date_time) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(scheduled_date_time)
    ");
    $stmt->execute([$med['id'], $userId]);
    $takenLogs = [];
    while ($row = $stmt->fetch()) {
        $takenLogs[$row['log_date']] = $row['taken_count'];
    }
    
    // Get skipped logs
    $stmt = $pdo->prepare("
        SELECT DATE(scheduled_date_time) as log_date, COUNT(*) as skipped_count
        FROM medication_logs
        WHERE medication_id = ? 
        AND user_id = ?
        AND status = 'skipped'
        AND DATE(scheduled_date_time) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(scheduled_date_time)
    ");
    $stmt->execute([$med['id'], $userId]);
    $skippedLogs = [];
    while ($row = $stmt->fetch()) {
        $skippedLogs[$row['log_date']] = $row['skipped_count'];
    }
    
    // Get medication schedule to know expected doses per day
    $stmt = $pdo->prepare("
        SELECT frequency_type, times_per_day, days_of_week, is_prn
        FROM medication_schedules
        WHERE medication_id = ?
    ");
    $stmt->execute([$med['id']]);
    $schedule = $stmt->fetch();
    
    $complianceData[$med['id']] = [
        'medication' => $med,
        'schedule' => $schedule,
        'dose_times' => $doseTimes,
        'taken_logs' => $takenLogs,
        'skipped_logs' => $skippedLogs
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Compliance</title>
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
        
        .compliance-section {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .med-header {
            margin-bottom: 20px;
        }
        
        .med-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }
        
        .med-dose {
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        .week-view {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        
        .day-column {
            flex: 1;
            text-align: center;
        }
        
        .day-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 12px;
            padding: 8px 4px;
            border-radius: var(--radius-sm);
        }
        
        .day-label.today {
            background: var(--color-primary);
            color: white;
        }
        
        .compliance-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .compliance-circle.compliant {
            background: var(--color-success);
            color: white;
        }
        
        .compliance-circle.non-compliant {
            background: var(--color-danger);
            color: white;
        }
        
        .compliance-circle.pending {
            background: #e0e0e0;
            color: #999;
        }
        
        .compliance-circle.future {
            background: #f5f5f5;
            border: 2px dashed #ccc;
        }
        
        .no-meds {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-secondary);
        }
        
        /* Tab Navigation */
        .compliance-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--color-border);
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--color-text-secondary);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin-bottom: -2px;
        }
        
        .tab-button:hover {
            color: var(--color-primary);
        }
        
        .tab-button.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            font-weight: 600;
        }
        
        /* Monthly Calendar */
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
        }
        
        .calendar-nav button {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .calendar-nav h3 {
            margin: 0;
            color: var(--color-text);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 8px;
            color: var(--color-text-secondary);
            font-size: 14px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 8px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            background: white;
            min-height: 60px;
        }
        
        .calendar-day:hover:not(.empty) {
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
        }
        
        .calendar-day.empty {
            background: var(--color-bg-gray);
            cursor: default;
        }
        
        .calendar-day.today {
            border-color: var(--color-primary);
            border-width: 2px;
        }
        
        .calendar-date {
            font-weight: 600;
            font-size: 14px;
            color: var(--color-text);
        }
        
        .compliance-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .compliance-dot.green {
            background: var(--color-success);
        }
        
        .compliance-dot.red {
            background: var(--color-danger);
        }
        
        .compliance-dot.gray {
            background: #ccc;
        }
        
        /* Annual View */
        .annual-list {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 24px;
        }
        
        .annual-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--color-border);
        }
        
        .annual-item:last-child {
            border-bottom: none;
        }
        
        .annual-med-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .annual-compliance {
            font-size: 24px;
            font-weight: 700;
        }
        
        .annual-compliance.high {
            color: var(--color-success);
        }
        
        .annual-compliance.medium {
            color: var(--color-warning);
        }
        
        .annual-compliance.low {
            color: var(--color-danger);
        }
        
        /* Weekly View */
        .week-item {
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .week-item:hover {
            box-shadow: var(--shadow-sm);
        }
        
        .week-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .week-details {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--color-border);
        }
        
        .week-day-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .week-day-item {
            text-align: center;
        }
        
        .week-day-label {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-bottom: 4px;
        }
        
        @media (max-width: 768px) {
            .compliance-circle {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .day-label {
                font-size: 12px;
                padding: 4px 2px;
            }
        }
    </style>
</head>
<body>
    <a href="/modules/medications/dashboard.php" class="back-to-dashboard" title="Back to Medication Dashboard">
        ← Back to Dashboard
    </a>
    
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
                <a href="/modules/medications/compliance.php">Compliance</a>
                <a href="/modules/medications/log_prn.php">Log PRN</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="page-content">
        <div class="page-title">
            <h2>📊 Medication Compliance</h2>
            <p>Track your medication adherence</p>
        </div>
        
        <!-- Tab Navigation -->
        <div class="compliance-tabs">
            <a href="?view=daily" class="tab-button <?= $view === 'daily' ? 'active' : '' ?>">Daily</a>
            <a href="?view=weekly" class="tab-button <?= $view === 'weekly' ? 'active' : '' ?>">Weekly</a>
            <a href="?view=monthly" class="tab-button <?= $view === 'monthly' ? 'active' : '' ?>">Monthly</a>
            <a href="?view=annual" class="tab-button <?= $view === 'annual' ? 'active' : '' ?>">Annual</a>
        </div>
        
        <?php if (empty($medications)): ?>
            <div class="no-meds">
                <p>You don't have any active medications yet.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add Medication</a>
            </div>
        <?php else: ?>
            
            <?php if ($view === 'daily'): ?>
                <!-- DAILY VIEW -->
                <?php foreach ($complianceData as $medId => $data): ?>
                    <?php 
                    $med = $data['medication'];
                    $schedule = $data['schedule'];
                    $doseTimes = $data['dose_times'];
                    $takenLogs = $data['taken_logs'];
                    $skippedLogs = $data['skipped_logs'];
                    
                    // Calculate expected doses per day
                    $expectedDosesPerDay = count($doseTimes);
                    if ($schedule && $schedule['frequency_type'] === 'per_day') {
                        $expectedDosesPerDay = $schedule['times_per_day'] ?? count($doseTimes);
                    }
                    ?>
                    <div class="compliance-section">
                        <div class="med-header">
                            <div class="med-name">
                                💊 <?= htmlspecialchars($med['name']) ?>
                            </div>
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div class="med-dose">
                                    <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="week-view">
                            <?php foreach ($daysOfWeek as $day): ?>
                                <?php
                                $date = $day['date'];
                                $takenCount = $takenLogs[$date] ?? 0;
                                $skippedCount = $skippedLogs[$date] ?? 0;
                                $isFuture = strtotime($date) > strtotime(date('Y-m-d'));
                                $isToday = $day['is_today'];
                                
                                // Determine compliance status
                                $status = 'pending';
                                $icon = '';
                                
                                if ($isFuture) {
                                    $status = 'future';
                                    $icon = '';
                                } elseif ($schedule && $schedule['is_prn']) {
                                    // PRN medications - if taken, show compliant
                                    if ($takenCount > 0) {
                                        $status = 'compliant';
                                        $icon = '✓';
                                    } else {
                                        $status = 'pending';
                                        $icon = '';
                                    }
                                } elseif ($schedule && $schedule['frequency_type'] === 'per_week') {
                                    // Weekly medications - check if today is a scheduled day
                                    $dayOfWeek = date('D', strtotime($date));
                                    $isScheduledDay = strpos($schedule['days_of_week'], $dayOfWeek) !== false;
                                    
                                    if (!$isScheduledDay) {
                                        $status = 'pending';
                                        $icon = '-';
                                    } elseif ($takenCount > 0) {
                                        $status = 'compliant';
                                        $icon = '✓';
                                    } else {
                                        $status = 'non-compliant';
                                        $icon = '✗';
                                    }
                                } else {
                                    // Daily medications
                                    if ($takenCount >= $expectedDosesPerDay && $expectedDosesPerDay > 0) {
                                        $status = 'compliant';
                                        $icon = '✓';
                                    } elseif ($skippedCount > 0 || ($takenCount > 0 && $takenCount < $expectedDosesPerDay)) {
                                        $status = 'non-compliant';
                                        $icon = '✗';
                                    } elseif (!$isToday && !$isFuture) {
                                        $status = 'non-compliant';
                                        $icon = '✗';
                                    } else {
                                        $status = 'pending';
                                        $icon = '';
                                    }
                                }
                                ?>
                                <div class="day-column">
                                    <div class="day-label <?= $isToday ? 'today' : '' ?>">
                                        <?= htmlspecialchars($day['label']) ?>
                                    </div>
                                    <div class="compliance-circle <?= $status ?>">
                                        <?= $icon ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            
            <?php elseif ($view === 'weekly'): ?>
                <!-- WEEKLY VIEW -->
                <?php
                // Calculate last 4 weeks
                $weeks = [];
                $today = strtotime(date('Y-m-d'));
                $currentDayOfWeek = date('N', $today); // 1=Mon, 7=Sun
                
                // Find the most recent Sunday (start of current/last week)
                $lastSunday = strtotime('-' . ($currentDayOfWeek % 7) . ' days', $today);
                
                for ($i = 0; $i < 4; $i++) {
                    $weekStart = date('Y-m-d', strtotime('-' . ($i * 7) . ' days', $lastSunday));
                    $weekEnd = date('Y-m-d', strtotime('+6 days', strtotime($weekStart)));
                    $weeks[] = [
                        'start' => $weekStart,
                        'end' => $weekEnd,
                        'label' => date('M j', strtotime($weekStart)) . ' - ' . date('M j, Y', strtotime($weekEnd))
                    ];
                }
                ?>
                
                <?php foreach ($complianceData as $medId => $data): ?>
                    <?php
                    $med = $data['medication'];
                    $schedule = $data['schedule'];
                    ?>
                    <div class="compliance-section">
                        <div class="med-header">
                            <div class="med-name">
                                💊 <?= htmlspecialchars($med['name']) ?>
                            </div>
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div class="med-dose">
                                    <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php foreach ($weeks as $week): ?>
                            <?php
                            // Calculate compliance for this week
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as taken_count
                                FROM medication_logs
                                WHERE medication_id = ? 
                                AND user_id = ?
                                AND status = 'taken'
                                AND DATE(scheduled_date_time) >= ?
                                AND DATE(scheduled_date_time) <= ?
                            ");
                            $stmt->execute([$medId, $userId, $week['start'], $week['end']]);
                            $weekTaken = $stmt->fetchColumn();
                            
                            // Calculate expected doses for the week
                            $expectedDosesPerDay = count($data['dose_times']);
                            if ($schedule && $schedule['frequency_type'] === 'per_day') {
                                $expectedDosesPerDay = $schedule['times_per_day'] ?? count($data['dose_times']);
                            }
                            
                            $daysInWeek = 7;
                            $expectedWeekTotal = $expectedDosesPerDay * $daysInWeek;
                            
                            // For PRN, show doses taken
                            if ($schedule && $schedule['is_prn']) {
                                $compliancePercent = 'N/A';
                                $complianceText = "$weekTaken doses taken";
                                $complianceClass = 'medium';
                            } else {
                                $compliancePercent = $expectedWeekTotal > 0 ? round(($weekTaken / $expectedWeekTotal) * 100) : 0;
                                $complianceText = "$compliancePercent%";
                                
                                if ($compliancePercent >= 90) {
                                    $complianceClass = 'high';
                                } elseif ($compliancePercent >= 70) {
                                    $complianceClass = 'medium';
                                } else {
                                    $complianceClass = 'low';
                                }
                            }
                            ?>
                            <div class="week-item" onclick="this.querySelector('.week-details').style.display = this.querySelector('.week-details').style.display === 'none' ? 'block' : 'none';">
                                <div class="week-header">
                                    <div>
                                        <strong><?= htmlspecialchars($week['label']) ?></strong>
                                    </div>
                                    <div class="annual-compliance <?= $complianceClass ?>" style="font-size: 18px;">
                                        <?= htmlspecialchars($complianceText) ?>
                                    </div>
                                </div>
                                <div class="week-details">
                                    <div class="week-day-grid">
                                        <?php
                                        for ($d = 0; $d < 7; $d++) {
                                            $dayDate = date('Y-m-d', strtotime("+$d days", strtotime($week['start'])));
                                            $dayLabel = date('D', strtotime($dayDate));
                                            
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) as taken_count
                                                FROM medication_logs
                                                WHERE medication_id = ? 
                                                AND user_id = ?
                                                AND status = 'taken'
                                                AND DATE(scheduled_date_time) = ?
                                            ");
                                            $stmt->execute([$medId, $userId, $dayDate]);
                                            $dayTaken = $stmt->fetchColumn();
                                            
                                            $dayCompliant = $dayTaken >= $expectedDosesPerDay;
                                            $dayFuture = strtotime($dayDate) > strtotime(date('Y-m-d'));
                                            ?>
                                            <div class="week-day-item">
                                                <div class="week-day-label"><?= $dayLabel ?></div>
                                                <div class="compliance-circle <?= $dayFuture ? 'future' : ($dayCompliant ? 'compliant' : 'non-compliant') ?>" style="width: 32px; height: 32px; font-size: 16px; margin: 4px auto;">
                                                    <?= $dayFuture ? '' : ($dayCompliant ? '✓' : '✗') ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            
            <?php elseif ($view === 'monthly'): ?>
                <!-- MONTHLY CALENDAR VIEW -->
                <?php
                // Calculate prev/next month
                $prevMonth = $currentMonth - 1;
                $prevYear = $currentYear;
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear--;
                }
                
                $nextMonth = $currentMonth + 1;
                $nextYear = $currentYear;
                if ($nextMonth > 12) {
                    $nextMonth = 1;
                    $nextYear++;
                }
                
                // Get first day of month and total days
                $firstDay = date('N', strtotime("$currentYear-$currentMonth-01")); // 1=Mon, 7=Sun
                $totalDays = date('t', strtotime("$currentYear-$currentMonth-01"));
                $monthName = date('F Y', strtotime("$currentYear-$currentMonth-01"));
                
                // Get all compliance data for the month
                $monthStart = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-01";
                $monthEnd = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-" . str_pad($totalDays, 2, '0', STR_PAD_LEFT);
                ?>
                
                <div class="calendar-nav">
                    <a href="?view=monthly&month=<?= $prevMonth ?>&year=<?= $prevYear ?>">
                        <button>← Previous</button>
                    </a>
                    <h3><?= htmlspecialchars($monthName) ?></h3>
                    <a href="?view=monthly&month=<?= $nextMonth ?>&year=<?= $nextYear ?>">
                        <button>Next →</button>
                    </a>
                </div>
                
                <?php foreach ($complianceData as $medId => $data): ?>
                    <?php
                    $med = $data['medication'];
                    $schedule = $data['schedule'];
                    
                    // Get all logs for this month
                    $stmt = $pdo->prepare("
                        SELECT DATE(scheduled_date_time) as log_date, 
                               COUNT(*) as taken_count
                        FROM medication_logs
                        WHERE medication_id = ? 
                        AND user_id = ?
                        AND status = 'taken'
                        AND DATE(scheduled_date_time) >= ?
                        AND DATE(scheduled_date_time) <= ?
                        GROUP BY DATE(scheduled_date_time)
                    ");
                    $stmt->execute([$medId, $userId, $monthStart, $monthEnd]);
                    $monthLogs = [];
                    while ($row = $stmt->fetch()) {
                        $monthLogs[$row['log_date']] = $row['taken_count'];
                    }
                    
                    $expectedDosesPerDay = count($data['dose_times']);
                    if ($schedule && $schedule['frequency_type'] === 'per_day') {
                        $expectedDosesPerDay = $schedule['times_per_day'] ?? count($data['dose_times']);
                    }
                    ?>
                    <div class="compliance-section">
                        <div class="med-header">
                            <div class="med-name">
                                💊 <?= htmlspecialchars($med['name']) ?>
                            </div>
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div class="med-dose">
                                    <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="calendar-grid">
                            <!-- Day headers -->
                            <div class="calendar-day-header">Mon</div>
                            <div class="calendar-day-header">Tue</div>
                            <div class="calendar-day-header">Wed</div>
                            <div class="calendar-day-header">Thu</div>
                            <div class="calendar-day-header">Fri</div>
                            <div class="calendar-day-header">Sat</div>
                            <div class="calendar-day-header">Sun</div>
                            
                            <!-- Empty cells before first day -->
                            <?php for ($i = 1; $i < $firstDay; $i++): ?>
                                <div class="calendar-day empty"></div>
                            <?php endfor; ?>
                            
                            <!-- Days of month -->
                            <?php for ($day = 1; $day <= $totalDays; $day++): ?>
                                <?php
                                $dateStr = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $takenCount = $monthLogs[$dateStr] ?? 0;
                                $isToday = $dateStr === date('Y-m-d');
                                $isFuture = strtotime($dateStr) > strtotime(date('Y-m-d'));
                                
                                // Determine dot color
                                $dotClass = 'gray';
                                if ($isFuture) {
                                    $dotClass = 'gray';
                                } elseif ($schedule && $schedule['is_prn']) {
                                    $dotClass = $takenCount > 0 ? 'green' : 'gray';
                                } else {
                                    $dotClass = $takenCount >= $expectedDosesPerDay ? 'green' : 'red';
                                }
                                ?>
                                <div class="calendar-day <?= $isToday ? 'today' : '' ?>">
                                    <div class="calendar-date"><?= $day ?></div>
                                    <div class="compliance-dot <?= $dotClass ?>"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            
            <?php elseif ($view === 'annual'): ?>
                <!-- ANNUAL VIEW -->
                <div class="annual-list">
                    <?php foreach ($complianceData as $medId => $data): ?>
                        <?php
                        $med = $data['medication'];
                        $schedule = $data['schedule'];
                        
                        // Get total taken this year
                        $yearStart = date('Y') . "-01-01";
                        $yearEnd = date('Y') . "-12-31";
                        
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as taken_count
                            FROM medication_logs
                            WHERE medication_id = ? 
                            AND user_id = ?
                            AND status = 'taken'
                            AND DATE(scheduled_date_time) >= ?
                            AND DATE(scheduled_date_time) <= ?
                        ");
                        $stmt->execute([$medId, $userId, $yearStart, $yearEnd]);
                        $yearTaken = $stmt->fetchColumn();
                        
                        // Calculate expected doses for the year
                        $expectedDosesPerDay = count($data['dose_times']);
                        if ($schedule && $schedule['frequency_type'] === 'per_day') {
                            $expectedDosesPerDay = $schedule['times_per_day'] ?? count($data['dose_times']);
                        }
                        
                        // Days from start of year to today
                        $daysThisYear = (strtotime(date('Y-m-d')) - strtotime($yearStart)) / 86400 + 1;
                        $expectedYearTotal = $expectedDosesPerDay * $daysThisYear;
                        
                        // Calculate compliance
                        if ($schedule && $schedule['is_prn']) {
                            $complianceText = "$yearTaken doses";
                            $complianceClass = 'medium';
                        } else {
                            $compliancePercent = $expectedYearTotal > 0 ? round(($yearTaken / $expectedYearTotal) * 100) : 0;
                            $complianceText = "$compliancePercent%";
                            
                            if ($compliancePercent >= 90) {
                                $complianceClass = 'high';
                            } elseif ($compliancePercent >= 70) {
                                $complianceClass = 'medium';
                            } else {
                                $complianceClass = 'low';
                            }
                        }
                        ?>
                        <div class="annual-item">
                            <div>
                                <div class="annual-med-name">
                                    💊 <?= htmlspecialchars($med['name']) ?>
                                </div>
                                <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                    <div style="font-size: 14px; color: var(--color-text-secondary); margin-top: 4px;">
                                        <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="annual-compliance <?= $complianceClass ?>">
                                <?= htmlspecialchars($complianceText) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>
