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

// Get current medication type (scheduled or prn)
$medType = $_GET['type'] ?? 'scheduled';
$validTypes = ['scheduled', 'prn'];
if (!in_array($medType, $validTypes)) {
    $medType = 'scheduled';
}

// Get current view from query parameter (default: daily)
$view = $_GET['view'] ?? 'daily';
$validViews = ['daily', 'weekly', 'monthly', 'annual'];
if (!in_array($view, $validViews)) {
    $view = 'daily';
}

// For monthly view, get current month/year or from parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get expanded medication IDs from URL parameter
$expandedParam = $_GET['expanded'] ?? '';
$expandedMeds = !empty($expandedParam) ? explode(',', $expandedParam) : [];

// Validate month/year
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = (int)date('m');
}
if ($currentYear < 2026 || $currentYear > 2100) {
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
}
// Don't allow months before Feb 2026
if ($currentYear == 2026 && $currentMonth < 2) {
    $currentMonth = 2; // February
}

// Get all active medications (exclude PRN medications from compliance)
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.end_date, m.created_at, md.dose_amount, md.dose_unit
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL) AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
    ORDER BY m.name
");
$stmt->execute([$userId]);
$medications = $stmt->fetchAll();

// Calculate date ranges based on view
if ($view === 'daily') {
    // For daily view: only today's data
    $todayDate = date('Y-m-d');
    $daysToShow = [
        [
            'date' => $todayDate,
            'label' => 'Today',
            'is_today' => true
        ]
    ];
} else {
    // For weekly view: last 7 days
    $daysOfWeek = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D', strtotime($date));
        $daysOfWeek[] = [
            'date' => $date,
            'label' => $dayLabel,
            'is_today' => $date === date('Y-m-d')
        ];
    }
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
    
    // Get logs based on view type
    if ($view === 'daily') {
        $dateFilter = "DATE(scheduled_date_time) = CURDATE()";
    } else {
        $dateFilter = "DATE(scheduled_date_time) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
    }
    
    // Get taken logs
    $stmt = $pdo->prepare("
        SELECT DATE(scheduled_date_time) as log_date, COUNT(*) as taken_count
        FROM medication_logs
        WHERE medication_id = ? 
        AND user_id = ?
        AND status = 'taken'
        AND $dateFilter
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
        AND $dateFilter
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

// Get PRN medications and their data
$prnMedications = [];
$prnData = [];

if ($medType === 'prn') {
    // Get all active PRN medications
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, m.end_date, m.created_at, md.dose_amount, md.dose_unit
        FROM medications m
        LEFT JOIN medication_doses md ON m.id = md.medication_id
        INNER JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL) AND ms.is_prn = 1
        ORDER BY m.name
    ");
    $stmt->execute([$userId]);
    $prnMedications = $stmt->fetchAll();
    
    // Get PRN data for each medication based on view
    foreach ($prnMedications as $med) {
        $medId = $med['id'];
        
        // Set date range based on view
        if ($view === 'daily') {
            $dateFilter = "DATE(taken_at) = CURDATE()";
            $intervalStart = date('Y-m-d 00:00:00');
        } elseif ($view === 'weekly') {
            $dateFilter = "DATE(taken_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
            $intervalStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
        } elseif ($view === 'monthly') {
            $dateFilter = "YEAR(taken_at) = ? AND MONTH(taken_at) = ?";
            $intervalStart = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
        } else { // annual
            $dateFilter = "YEAR(taken_at) = YEAR(CURDATE())";
            $intervalStart = date('Y') . "-01-01 00:00:00";
        }
        
        // Get dose logs
        if ($view === 'monthly') {
            $stmt = $pdo->prepare("
                SELECT DATE(taken_at) as log_date, TIME(taken_at) as log_time, 
                       COUNT(*) as dose_count, SUM(quantity_taken) as total_quantity
                FROM medication_logs 
                WHERE medication_id = ? AND user_id = ? AND status = 'taken' AND $dateFilter
                GROUP BY DATE(taken_at)
                ORDER BY taken_at
            ");
            $stmt->execute([$medId, $userId, $currentYear, $currentMonth]);
        } else {
            $stmt = $pdo->prepare("
                SELECT DATE(taken_at) as log_date, TIME(taken_at) as log_time, 
                       COUNT(*) as dose_count, SUM(quantity_taken) as total_quantity
                FROM medication_logs 
                WHERE medication_id = ? AND user_id = ? AND status = 'taken' AND $dateFilter
                GROUP BY DATE(taken_at)
                ORDER BY taken_at
            ");
            $stmt->execute([$medId, $userId]);
        }
        $doseLogs = $stmt->fetchAll();
        
        // Get detailed times for daily view
        $detailedTimes = [];
        if ($view === 'daily') {
            $stmt = $pdo->prepare("
                SELECT TIME(taken_at) as time_taken, quantity_taken
                FROM medication_logs 
                WHERE medication_id = ? AND user_id = ? AND status = 'taken' AND DATE(taken_at) = CURDATE()
                ORDER BY taken_at
            ");
            $stmt->execute([$medId, $userId]);
            $detailedTimes = $stmt->fetchAll();
        }
        
        // Calculate summary statistics
        $totalDoses = 0;
        $totalQuantity = 0;
        $dailyBreakdown = [];
        
        foreach ($doseLogs as $log) {
            $totalDoses += $log['dose_count'];
            $totalQuantity += $log['total_quantity'];
            $dailyBreakdown[$log['log_date']] = [
                'doses' => $log['dose_count'],
                'quantity' => $log['total_quantity']
            ];
        }
        
        $prnData[$medId] = [
            'medication' => $med,
            'total_doses' => $totalDoses,
            'total_quantity' => $totalQuantity,
            'daily_breakdown' => $dailyBreakdown,
            'detailed_times' => $detailedTimes,
            'dose_logs' => $doseLogs
        ];
    }
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
            margin-bottom: 24px;
        }
        
        .page-title h2 {
            margin: 0 0 6px 0;
            font-size: 28px;
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
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        
        .med-header {
            margin-bottom: 16px;
        }
        
        .med-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 3px;
        }
        
        .med-dose {
            font-size: 13px;
            color: var(--color-text-secondary);
        }
        
        .compliance-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        
        .compliance-status-icon {
            width: 24px;
            height: 24px;
            font-size: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .compliance-status-icon.compliant {
            background: var(--color-success);
            color: white;
        }
        
        .compliance-status-icon.non-compliant {
            background: var(--color-danger);
            color: white;
        }
        
        .compliance-details {
            text-align: left;
            font-size: 14px;
            color: var(--color-text-secondary);
            margin-top: 4px;
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
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 10px;
            padding: 6px 3px;
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
        .compliance-controls {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .med-type-toggle {
            display: flex;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            padding: 4px;
        }
        
        .toggle-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-radius: var(--radius-sm);
            color: var(--color-text-secondary);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .toggle-btn:hover {
            color: var(--color-primary);
        }
        
        .toggle-btn.active {
            background: var(--color-primary);
            color: white;
        }
        
        .view-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-selector label {
            font-size: 15px;
            font-weight: 500;
            color: var(--color-text);
        }
        
        .view-dropdown {
            padding: 10px 16px;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 500;
            color: var(--color-text);
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 140px;
        }
        
        .view-dropdown:hover {
            border-color: var(--color-primary);
        }
        
        .view-dropdown:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
        }
        
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
            margin-bottom: 16px;
            padding: 12px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
        }
        
        .calendar-nav button {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }
        
        .calendar-nav h3 {
            margin: 0;
            color: var(--color-text);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            margin-bottom: 16px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 6px;
            color: var(--color-text-secondary);
            font-size: 13px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 6px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            background: white;
            min-height: 55px;
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
            padding: 16px;
        }
        
        .annual-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--color-border);
        }
        
        .annual-item:last-child {
            border-bottom: none;
        }
        
        .annual-med-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .annual-compliance {
            font-size: 20px;
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
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
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
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--color-border);
        }
        
        .week-day-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }
        
        .week-day-item {
            text-align: center;
        }
        
        .week-day-label {
            font-size: 11px;
            color: var(--color-text-secondary);
            margin-bottom: 3px;
        }
        
        /* PRN Summary Styles */
        .prn-summary-card {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .prn-card-header {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--color-border);
        }
        
        .prn-med-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }
        
        .prn-med-dose {
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        .prn-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .prn-stat-box {
            text-align: center;
            padding: 16px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
        }
        
        .prn-stat-label {
            font-size: 13px;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
        }
        
        .prn-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .prn-time-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .prn-time-item {
            padding: 8px 12px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--color-text);
        }
        
        .prn-daily-breakdown {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--color-border);
        }
        
        .prn-breakdown-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 12px;
        }
        
        .prn-breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .prn-breakdown-item:last-child {
            border-bottom: none;
        }
        
        .prn-breakdown-date {
            font-size: 14px;
            color: var(--color-text);
        }
        
        .prn-breakdown-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-primary);
        }
        
        /* Expandable medication sections */
        .expandable-med-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            transition: background 0.2s;
        }
        
        .expandable-med-header:hover {
            background: #e8e8e8;
        }
        
        .expandable-med-title {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .expandable-med-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .expandable-med-total {
            font-size: 14px;
            color: var(--color-text-secondary);
            margin-right: 8px;
        }
        
        .expand-indicator {
            font-size: 18px;
            color: var(--color-primary);
            transition: transform 0.3s ease;
            user-select: none;
        }
        
        .expand-indicator.expanded {
            transform: rotate(90deg);
        }
        
        .expandable-med-content {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
        }
        
        .expandable-med-content.collapsed {
            max-height: 0;
            opacity: 0;
        }
        
        .expandable-med-content.expanded {
            max-height: 5000px;
            opacity: 1;
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
        
        <!-- Medication Type Toggle and View Selector -->
        <div class="compliance-controls">
            <div class="med-type-toggle">
                <a href="?type=scheduled&view=<?= $view ?>" class="toggle-btn <?= $medType === 'scheduled' ? 'active' : '' ?>">
                    📅 Scheduled
                </a>
                <a href="?type=prn&view=<?= $view ?>" class="toggle-btn <?= $medType === 'prn' ? 'active' : '' ?>">
                    💊 PRN
                </a>
            </div>
            
            <div class="view-selector">
                <label for="viewSelect">View:</label>
                <select id="viewSelect" class="view-dropdown" onchange="window.location.href='?type=<?= htmlspecialchars($medType, ENT_QUOTES) ?>&view=' + this.value<?= $view === 'monthly' ? " + '&month=<?= htmlspecialchars($currentMonth, ENT_QUOTES) ?>&year=<?= htmlspecialchars($currentYear, ENT_QUOTES) ?>'" : '' ?>">
                    <option value="daily" <?= $view === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $view === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $view === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="annual" <?= $view === 'annual' ? 'selected' : '' ?>>Annual</option>
                </select>
            </div>
        </div>
        
        <?php if ($medType === 'scheduled'): ?>
        <?php if (empty($medications)): ?>
            <div class="no-meds">
                <p>You don't have any active medications yet.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add Medication</a>
            </div>
        <?php else: ?>
            
            <?php if ($view === 'daily'): ?>
                <!-- DAILY VIEW - Show only today's percentage -->
                <?php 
                $todayDate = date('Y-m-d');
                $totalCompliantToday = 0;
                $totalMedsToday = 0;
                
                foreach ($complianceData as $medId => $data):
                    $med = $data['medication'];
                    $schedule = $data['schedule'];
                    $doseTimes = $data['dose_times'];
                    $takenLogs = $data['taken_logs'];
                    $skippedLogs = $data['skipped_logs'];
                    
                    // Check if medication was active today
                    $medStartDate = $med['created_at'];
                    $medEndDate = $med['end_date'];
                    $isMedActiveToday = true;
                    
                    // Use date comparison only (ignore time)
                    if ($medStartDate && date('Y-m-d', strtotime($medStartDate)) > $todayDate) {
                        $isMedActiveToday = false;
                    }
                    if ($medEndDate && date('Y-m-d', strtotime($medEndDate)) < $todayDate) {
                        $isMedActiveToday = false;
                    }
                    
                    if (!$isMedActiveToday) {
                        continue; // Skip inactive medications
                    }
                    
                    // Calculate expected doses per day
                    $expectedDosesPerDay = count($doseTimes);
                    if ($schedule && $schedule['frequency_type'] === 'per_day') {
                        $expectedDosesPerDay = $schedule['times_per_day'] ?? count($doseTimes);
                    }
                    // Ensure at least 1 dose per day for daily medications
                    $expectedDosesPerDay = max(1, $expectedDosesPerDay);
                    
                    $takenCount = $takenLogs[$todayDate] ?? 0;
                    $skippedCount = $skippedLogs[$todayDate] ?? 0;
                    
                    // Determine compliance for today
                    $isCompliant = false;
                    if ($schedule && $schedule['frequency_type'] === 'per_week') {
                        $dayOfWeek = date('D', strtotime($todayDate));
                        $isScheduledDay = strpos($schedule['days_of_week'], $dayOfWeek) !== false;
                        if ($isScheduledDay && $takenCount > 0) {
                            $isCompliant = true;
                        }
                    } else {
                        if ($takenCount >= $expectedDosesPerDay && $expectedDosesPerDay > 0) {
                            $isCompliant = true;
                        }
                    }
                    
                    if ($isCompliant) {
                        $totalCompliantToday++;
                    }
                    $totalMedsToday++;
                ?>
                    <div class="compliance-section">
                        <div class="compliance-card-header">
                            <div>
                                <div class="med-name">
                                    💊 <?= htmlspecialchars($med['name']) ?>
                                    <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                        <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="compliance-status-icon <?= $isCompliant ? 'compliant' : 'non-compliant' ?>">
                                <?= $isCompliant ? '✓' : '✗' ?>
                            </div>
                        </div>
                        <div class="compliance-details">
                            <?= $takenCount ?>/<?= $expectedDosesPerDay ?> doses taken • <?= $isCompliant ? 'All doses completed' : ($takenCount > 0 ? 'Partially completed' : 'Not taken yet') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($totalMedsToday > 0): ?>
                    <div class="compliance-section" style="background: var(--color-primary); color: white; text-align: center;">
                        <h3 style="margin: 0 0 8px 0; color: white;">Today's Compliance</h3>
                        <div style="font-size: 48px; font-weight: bold; margin: 8px 0;">
                            <?= round(($totalCompliantToday / $totalMedsToday) * 100) ?>%
                        </div>
                        <div style="font-size: 16px; opacity: 0.9;">
                            <?= $totalCompliantToday ?> of <?= $totalMedsToday ?> medications completed
                        </div>
                    </div>
                <?php endif; ?>
            
            <?php elseif ($view === 'weekly'): ?>
                <!-- WEEKLY VIEW - Show last 7 days with expandable previous 4 weeks -->
                
                <!-- Current Week (Last 7 days) -->
                <div style="margin-bottom: 32px;">
                    <h3 style="color: var(--color-primary); margin-bottom: 20px;">📅 Last 7 Days</h3>
                    
                    <?php foreach ($complianceData as $medId => $data): ?>
                        <?php
                        $med = $data['medication'];
                        $schedule = $data['schedule'];
                        $doseTimes = $data['dose_times'];
                        $takenLogs = $data['taken_logs'];
                        $skippedLogs = $data['skipped_logs'];
                        
                        $expectedDosesPerDay = count($doseTimes);
                        if ($schedule && $schedule['frequency_type'] === 'per_day') {
                            $expectedDosesPerDay = $schedule['times_per_day'] ?? count($doseTimes);
                        }
                        // Ensure at least 1 dose per day for daily medications
                        $expectedDosesPerDay = max(1, $expectedDosesPerDay);
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
                                    
                                    // Check if medication was active on this date
                                    $medStartDate = $med['created_at'];
                                    $medEndDate = $med['end_date'];
                                    $isMedActive = true;
                                    
                                    // Use date comparison only (ignore time)
                                    if ($medStartDate && date('Y-m-d', strtotime($medStartDate)) > $date) {
                                        $isMedActive = false;
                                    }
                                    if ($medEndDate && date('Y-m-d', strtotime($medEndDate)) < $date) {
                                        $isMedActive = false;
                                    }
                                    
                                    // Determine compliance status
                                    $status = 'pending';
                                    $icon = '';
                                    
                                    if (!$isMedActive) {
                                        // Medication not active on this date - show blank
                                        $status = 'future';
                                        $icon = '';
                                    } elseif ($isFuture) {
                                        $status = 'future';
                                        $icon = '';
                                    } elseif ($schedule && $schedule['frequency_type'] === 'per_week') {
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
                                        <a href="?view=daily&date=<?= $date ?>" style="text-decoration: none; color: inherit;">
                                            <div class="day-label <?= $isToday ? 'today' : '' ?>" style="cursor: pointer;">
                                                <?= htmlspecialchars($day['label']) ?>
                                            </div>
                                            <div class="compliance-circle <?= $status ?>" style="cursor: pointer;">
                                                <?= $icon ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Previous 4 Weeks (Expandable) -->
                <div class="compliance-section">
                    <div style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="togglePreviousWeeks()">
                        <h3 style="margin: 0; color: var(--color-primary);">📊 Previous 4 Weeks</h3>
                        <span id="previousWeeksToggle" style="font-size: 24px; font-weight: bold; color: var(--color-primary);">+</span>
                    </div>
                    
                    <div id="previousWeeksContent" style="display: none; margin-top: 20px;">
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
                            // Ensure at least 1 dose per day for daily medications
                            $expectedDosesPerDay = max(1, $expectedDosesPerDay);
                            
                            // Count only active days in the week
                            $activeDaysInWeek = 0;
                            for ($d = 0; $d < 7; $d++) {
                                $dayDate = date('Y-m-d', strtotime("+$d days", strtotime($week['start'])));
                                $medStartDate = $med['created_at'];
                                $medEndDate = $med['end_date'];
                                $isMedActive = true;
                                
                                if ($medStartDate && strtotime($medStartDate) > strtotime($dayDate)) {
                                    $isMedActive = false;
                                }
                                if ($medEndDate && strtotime($medEndDate) < strtotime($dayDate)) {
                                    $isMedActive = false;
                                }
                                // Don't count future dates
                                if (strtotime($dayDate) > strtotime(date('Y-m-d'))) {
                                    $isMedActive = false;
                                }
                                
                                if ($isMedActive) {
                                    $activeDaysInWeek++;
                                }
                            }
                            
                            $expectedWeekTotal = $expectedDosesPerDay * $activeDaysInWeek;
                            
                            // Skip this week if medication was not active at all during this period
                            if ($activeDaysInWeek === 0) {
                                continue;
                            }
                            
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
                    </div>
                </div>
            
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
                
                // Check if previous month is before Feb 2026
                $isPrevBeforeFeb2026 = ($prevYear < 2026) || ($prevYear == 2026 && $prevMonth < 2);
                
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
                    <?php if (!$isPrevBeforeFeb2026): ?>
                        <a href="?view=monthly&month=<?= $prevMonth ?>&year=<?= $prevYear ?>&expanded=<?= htmlspecialchars($expandedParam) ?>">
                            <button>← Previous</button>
                        </a>
                    <?php else: ?>
                        <button disabled style="opacity: 0.3; cursor: not-allowed;">← Previous</button>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($monthName) ?></h3>
                    <a href="?view=monthly&month=<?= $nextMonth ?>&year=<?= $nextYear ?>&expanded=<?= htmlspecialchars($expandedParam) ?>">
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
                    // Ensure at least 1 dose per day for daily medications
                    $expectedDosesPerDay = max(1, $expectedDosesPerDay);
                    ?>
                    <div class="compliance-section" id="med-section-<?= $medId ?>">
                        <div class="med-header" style="cursor: pointer;" onclick="toggleMonthlyMed(<?= $medId ?>)">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <div>
                                    <div class="med-name">
                                        💊 <?= htmlspecialchars($med['name']) ?>
                                    </div>
                                    <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                        <div class="med-dose">
                                            <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span id="toggle-icon-<?= $medId ?>" style="font-size: 24px; font-weight: bold; color: var(--color-primary);">+</span>
                            </div>
                        </div>
                        
                        <div id="calendar-content-<?= $medId ?>" style="display: none; margin-top: 20px;">
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
                                
                                // Check if medication was active on this date
                                $medStartDate = $med['created_at'];
                                $medEndDate = $med['end_date'];
                                $isMedActive = true;
                                
                                // Don't show compliance for dates before Feb 2026
                                if (strtotime($dateStr) < strtotime('2026-02-01')) {
                                    $isMedActive = false;
                                }
                                
                                // Use date comparison only (ignore time)
                                if ($medStartDate && date('Y-m-d', strtotime($medStartDate)) > $dateStr) {
                                    $isMedActive = false;
                                }
                                if ($medEndDate && date('Y-m-d', strtotime($medEndDate)) < $dateStr) {
                                    $isMedActive = false;
                                }
                                
                                // Determine dot color
                                $dotClass = 'gray';
                                if ($isFuture) {
                                    $dotClass = 'gray';
                                } elseif (!$isMedActive) {
                                    // Medication not active - show gray/blank
                                    $dotClass = 'gray';
                                } else {
                                    $dotClass = $takenCount >= $expectedDosesPerDay ? 'green' : 'red';
                                }
                                ?>
                                <a href="?view=daily&date=<?= $dateStr ?>" style="text-decoration: none; color: inherit;">
                                    <div class="calendar-day <?= $isToday ? 'today' : '' ?>" style="cursor: pointer;">
                                        <div class="calendar-date"><?= $day ?></div>
                                        <div class="compliance-dot <?= $dotClass ?>"></div>
                                    </div>
                                </a>
                            <?php endfor; ?>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            
            <?php elseif ($view === 'annual'): ?>
                <!-- ANNUAL VIEW - With year picker and expandable medications -->
                <?php
                // Get selected year from query parameter (default: current year)
                $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
                if ($selectedYear < 2026 || $selectedYear > 2100) {
                    $selectedYear = (int)date('Y');
                }
                
                // Set year boundaries - no data before Feb 2026
                $yearStart = "$selectedYear-01-01";
                // Enforce February 2026 minimum
                if ($selectedYear == 2026) {
                    $yearStart = "2026-02-01";
                }
                $yearEnd = "$selectedYear-12-31";
                ?>
                
                <!-- Year Picker -->
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-flex; align-items: center; gap: 12px; background: var(--color-bg-white); padding: 8px 20px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                        <a href="?view=annual&year=<?= $selectedYear - 1 ?>" style="text-decoration: none; font-size: 20px; color: var(--color-primary);">←</a>
                        <select onchange="window.location.href='?view=annual&year=' + this.value" style="font-size: 16px; font-weight: 600; border: none; background: transparent; cursor: pointer; color: var(--color-primary);">
                            <?php for ($y = date('Y'); $y >= 2026; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <a href="?view=annual&year=<?= $selectedYear + 1 ?>" style="text-decoration: none; font-size: 20px; color: var(--color-primary); <?= $selectedYear >= date('Y') ? 'opacity: 0.3; pointer-events: none;' : '' ?>">→</a>
                    </div>
                </div>
                
                <div class="annual-list">
                    <?php foreach ($complianceData as $medId => $data): ?>
                        <?php
                        $med = $data['medication'];
                        $schedule = $data['schedule'];
                        
                        // Get total taken this year
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
                        // Ensure at least 1 dose per day for daily medications
                        $expectedDosesPerDay = max(1, $expectedDosesPerDay);
                        
                        // Count only active days in the year (from med start to today or year end, excluding future and before med creation)
                        $medStartDate = $med['created_at'];
                        $medEndDate = $med['end_date'];
                        
                        // Determine the actual start date for counting
                        $countStartDate = $yearStart;
                        if ($medStartDate && strtotime($medStartDate) > strtotime($yearStart)) {
                            $countStartDate = date('Y-m-d', strtotime($medStartDate));
                        }
                        // Enforce Feb 2026 minimum
                        if (strtotime($countStartDate) < strtotime('2026-02-01')) {
                            $countStartDate = '2026-02-01';
                        }
                        
                        // Determine the actual end date for counting
                        $countEndDate = min(strtotime(date('Y-m-d')), strtotime($yearEnd));
                        if ($medEndDate && strtotime($medEndDate) < $countEndDate) {
                            $countEndDate = strtotime($medEndDate);
                        }
                        
                        // Calculate active days
                        $activeDaysThisYear = 0;
                        if (strtotime($countStartDate) <= $countEndDate) {
                            $activeDaysThisYear = ($countEndDate - strtotime($countStartDate)) / 86400 + 1;
                        }
                        
                        $expectedYearTotal = $expectedDosesPerDay * $activeDaysThisYear;
                        
                        // Calculate compliance
                        $compliancePercent = $expectedYearTotal > 0 ? round(($yearTaken / $expectedYearTotal) * 100) : 0;
                        $complianceText = "$compliancePercent%";
                        
                        if ($compliancePercent >= 90) {
                            $complianceClass = 'high';
                        } elseif ($compliancePercent >= 70) {
                            $complianceClass = 'medium';
                        } else {
                            $complianceClass = 'low';
                        }
                        
                        // Calculate weeks for expandable view
                        $weeks = [];
                        $weeksInYear = 52;
                        for ($w = 0; $w < $weeksInYear; $w++) {
                            $weekStart = date('Y-m-d', strtotime("$yearStart +$w weeks"));
                            if (strtotime($weekStart) > strtotime($yearEnd)) break;
                            $weekEnd = date('Y-m-d', min(strtotime("+6 days", strtotime($weekStart)), strtotime($yearEnd)));
                            
                            // Get taken count for this week
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as taken_count
                                FROM medication_logs
                                WHERE medication_id = ? 
                                AND user_id = ?
                                AND status = 'taken'
                                AND DATE(scheduled_date_time) >= ?
                                AND DATE(scheduled_date_time) <= ?
                            ");
                            $stmt->execute([$medId, $userId, $weekStart, $weekEnd]);
                            $weekTaken = $stmt->fetchColumn();
                            
                            // Count only active days in this week
                            $activeDaysInWeek = 0;
                            for ($d = 0; $d < 7; $d++) {
                                $dayDate = date('Y-m-d', strtotime("+$d days", strtotime($weekStart)));
                                // Break if beyond week end
                                if (strtotime($dayDate) > strtotime($weekEnd)) break;
                                
                                $isMedActive = true;
                                
                                // Don't count dates before Feb 2026
                                if (strtotime($dayDate) < strtotime('2026-02-01')) {
                                    $isMedActive = false;
                                }
                                if ($medStartDate && strtotime($medStartDate) > strtotime($dayDate)) {
                                    $isMedActive = false;
                                }
                                if ($medEndDate && strtotime($medEndDate) < strtotime($dayDate)) {
                                    $isMedActive = false;
                                }
                                // Don't count future dates
                                if (strtotime($dayDate) > strtotime(date('Y-m-d'))) {
                                    $isMedActive = false;
                                }
                                
                                if ($isMedActive) {
                                    $activeDaysInWeek++;
                                }
                            }
                            
                            // Skip this week if medication was not active at all during this period
                            if ($activeDaysInWeek === 0) {
                                continue;
                            }
                            
                            $expectedWeekTotal = $expectedDosesPerDay * $activeDaysInWeek;
                            $weekPercent = $expectedWeekTotal > 0 ? round(($weekTaken / $expectedWeekTotal) * 100) : 0;
                            
                            $weeks[] = [
                                'number' => $w + 1,
                                'start' => $weekStart,
                                'end' => $weekEnd,
                                'percent' => $weekPercent,
                                'label' => date('M j', strtotime($weekStart)) . ' - ' . date('M j', strtotime($weekEnd))
                            ];
                        }
                        ?>
                        <div class="annual-item" style="display: block; padding: 16px; cursor: pointer;" onclick="toggleMedicationWeeks('med-<?= $medId ?>')">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div class="annual-med-name" style="display: flex; align-items: center; gap: 6px;">
                                        <span id="toggle-med-<?= $medId ?>" style="font-weight: bold; color: var(--color-primary);">+</span>
                                        💊 <?= htmlspecialchars($med['name']) ?>
                                    </div>
                                    <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                        <div style="font-size: 13px; color: var(--color-text-secondary); margin-top: 2px; margin-left: 24px;">
                                            <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="annual-compliance <?= $complianceClass ?>" style="font-size: 20px; font-weight: bold;">
                                    <?= htmlspecialchars($complianceText) ?>
                                </div>
                            </div>
                            
                            <!-- Expandable weeks section -->
                            <div id="med-<?= $medId ?>-weeks" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border);">
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px;">
                                    <?php foreach ($weeks as $week): ?>
                                        <?php
                                        $weekClass = 'low';
                                        if ($week['percent'] >= 90) {
                                            $weekClass = 'high';
                                        } elseif ($week['percent'] >= 70) {
                                            $weekClass = 'medium';
                                        }
                                        ?>
                                        <a href="?view=weekly&week_start=<?= $week['start'] ?>" style="text-decoration: none; color: inherit;" onclick="event.stopPropagation();">
                                            <div style="padding: 10px; background: var(--color-bg-gray); border-radius: var(--radius-sm); text-align: center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                                <div style="font-size: 11px; color: var(--color-text-secondary); margin-bottom: 2px;">Week <?= $week['number'] ?></div>
                                                <div style="font-size: 10px; color: var(--color-text-secondary); margin-bottom: 6px;"><?= $week['label'] ?></div>
                                                <div class="annual-compliance <?= $weekClass ?>" style="font-size: 18px; font-weight: bold;">
                                                    <?= $week['percent'] ?>%
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            
            <?php endif; ?>
            
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($medType === 'prn'): ?>
        <?php if (empty($prnMedications)): ?>
            <div class="no-meds">
                <p>You don't have any PRN medications yet.</p>
                <p>PRN medications are taken as and when needed, not on a regular schedule.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add PRN Medication</a>
            </div>
        <?php else: ?>
            
            <?php if ($view === 'daily'): ?>
                <!-- PRN DAILY VIEW -->
                <?php foreach ($prnData as $medId => $data): ?>
                    <?php 
                    $med = $data['medication'];
                    $totalDoses = $data['total_doses'];
                    $totalQuantity = $data['total_quantity'];
                    $detailedTimes = $data['detailed_times'];
                    $isExpanded = in_array($medId, $expandedMeds);
                    ?>
                    <div class="prn-summary-card">
                        <!-- Expandable Header -->
                        <div class="expandable-med-header" onclick="toggleExpandableMed(<?= $medId ?>, 'prn-daily')">
                            <div class="expandable-med-title">
                                <span class="expandable-med-name">💊 <?= htmlspecialchars($med['name']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="expandable-med-total">[Total: <?= $totalDoses ?>]</span>
                                <span class="expand-indicator <?= $isExpanded ? 'expanded' : '' ?>" id="expand-indicator-prn-daily-<?= $medId ?>">▶</span>
                            </div>
                        </div>
                        
                        <!-- Expandable Content -->
                        <div class="expandable-med-content <?= $isExpanded ? 'expanded' : 'collapsed' ?>" id="expandable-content-prn-daily-<?= $medId ?>">
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div style="margin-bottom: 12px; color: var(--color-text-secondary); font-size: 14px;">
                                    <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="prn-stats-grid">
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Doses Taken Today</div>
                                    <div class="prn-stat-value"><?= $totalDoses ?></div>
                                </div>
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Quantity</div>
                                    <div class="prn-stat-value"><?= $totalQuantity ?><?= $med['dose_unit'] ? ' ' . htmlspecialchars($med['dose_unit']) : '' ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($detailedTimes)): ?>
                                <div class="prn-daily-breakdown">
                                    <div class="prn-breakdown-title">⏰ Times Taken</div>
                                    <ul class="prn-time-list">
                                        <?php foreach ($detailedTimes as $time): ?>
                                            <li class="prn-time-item">
                                                <?= date('h:i A', strtotime($time['time_taken'])) ?> 
                                                (<?= $time['quantity_taken'] ?> <?= $med['dose_unit'] ? htmlspecialchars($med['dose_unit']) : 'unit' ?><?= $time['quantity_taken'] > 1 ? 's' : '' ?>)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: var(--color-text-secondary);">
                                    No doses taken today
                                </div>
                            <?php endif; ?>
                        </div><!-- /.expandable-med-content -->
                    </div><!-- /.prn-summary-card -->
                <?php endforeach; ?>
            
            <?php elseif ($view === 'weekly'): ?>
                <!-- PRN WEEKLY VIEW -->
                <?php foreach ($prnData as $medId => $data): ?>
                    <?php 
                    $med = $data['medication'];
                    $totalDoses = $data['total_doses'];
                    $dailyBreakdown = $data['daily_breakdown'];
                    
                    // Calculate average per day
                    $avgPerDay = $totalDoses > 0 ? round($totalDoses / 7, 1) : 0;
                    $isExpanded = in_array($medId, $expandedMeds);
                    ?>
                    <div class="prn-summary-card">
                        <!-- Expandable Header -->
                        <div class="expandable-med-header" onclick="toggleExpandableMed(<?= $medId ?>, 'prn-weekly')">
                            <div class="expandable-med-title">
                                <span class="expandable-med-name">💊 <?= htmlspecialchars($med['name']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="expandable-med-total">[Total: <?= $totalDoses ?>]</span>
                                <span class="expand-indicator <?= $isExpanded ? 'expanded' : '' ?>" id="expand-indicator-prn-weekly-<?= $medId ?>">▶</span>
                            </div>
                        </div>
                        
                        <!-- Expandable Content -->
                        <div class="expandable-med-content <?= $isExpanded ? 'expanded' : 'collapsed' ?>" id="expandable-content-prn-weekly-<?= $medId ?>">
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div style="margin-bottom: 12px; color: var(--color-text-secondary); font-size: 14px;">
                                    <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="prn-stats-grid">
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Doses (7 days)</div>
                                    <div class="prn-stat-value"><?= $totalDoses ?></div>
                                </div>
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Average per Day</div>
                                    <div class="prn-stat-value"><?= $avgPerDay ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($dailyBreakdown)): ?>
                                <div class="prn-daily-breakdown">
                                    <div class="prn-breakdown-title">📊 Daily Breakdown</div>
                                    <?php
                                    // Show last 7 days
                                    for ($i = 6; $i >= 0; $i--) {
                                        $date = date('Y-m-d', strtotime("-$i days"));
                                        $dayLabel = date('D, M j', strtotime($date));
                                        $doses = $dailyBreakdown[$date]['doses'] ?? 0;
                                        $quantity = $dailyBreakdown[$date]['quantity'] ?? 0;
                                    ?>
                                        <div class="prn-breakdown-item">
                                            <span class="prn-breakdown-date"><?= $dayLabel ?></span>
                                            <span class="prn-breakdown-value"><?= $doses ?> dose<?= $doses != 1 ? 's' : '' ?> (<?= $quantity ?> <?= $med['dose_unit'] ? htmlspecialchars($med['dose_unit']) : 'units' ?>)</span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: var(--color-text-secondary);">
                                    No doses taken in the last 7 days
                                </div>
                            <?php endif; ?>
                        </div><!-- /.expandable-med-content -->
                    </div><!-- /.prn-summary-card -->
                <?php endforeach; ?>
            
            <?php elseif ($view === 'monthly'): ?>
                <!-- PRN MONTHLY VIEW -->
                <?php foreach ($prnData as $medId => $data): ?>
                    <?php 
                    $med = $data['medication'];
                    $totalDoses = $data['total_doses'];
                    $totalQuantity = $data['total_quantity'];
                    $dailyBreakdown = $data['daily_breakdown'];
                    $isExpanded = in_array($medId, $expandedMeds);
                    
                    // Calculate weekly trends
                    $weeklyTrends = [];
                    $weekNum = 1;
                    $weekStart = 1;
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                    
                    while ($weekStart <= $daysInMonth) {
                        $weekEnd = min($weekStart + 6, $daysInMonth);
                        $weekDoses = 0;
                        
                        for ($day = $weekStart; $day <= $weekEnd; $day++) {
                            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            if (isset($dailyBreakdown[$date])) {
                                $weekDoses += $dailyBreakdown[$date]['doses'];
                            }
                        }
                        
                        if ($weekDoses > 0 || $weekNum <= 4) {
                            $weeklyTrends["Week $weekNum"] = $weekDoses;
                        }
                        
                        $weekStart += 7;
                        $weekNum++;
                    }
                    ?>
                    <div class="prn-summary-card">
                        <!-- Expandable Header -->
                        <div class="expandable-med-header" onclick="toggleExpandableMed(<?= $medId ?>, 'prn-monthly')">
                            <div class="expandable-med-title">
                                <span class="expandable-med-name">💊 <?= htmlspecialchars($med['name']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="expandable-med-total">[Total: <?= $totalDoses ?>]</span>
                                <span class="expand-indicator <?= $isExpanded ? 'expanded' : '' ?>" id="expand-indicator-prn-monthly-<?= $medId ?>">▶</span>
                            </div>
                        </div>
                        
                        <!-- Expandable Content -->
                        <div class="expandable-med-content <?= $isExpanded ? 'expanded' : 'collapsed' ?>" id="expandable-content-prn-monthly-<?= $medId ?>">
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div class="prn-med-dose"><?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?></div>
                            <?php endif; ?>
                            
                            <div class="prn-stats-grid">
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Doses This Month</div>
                                    <div class="prn-stat-value"><?= $totalDoses ?></div>
                                </div>
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Quantity</div>
                                    <div class="prn-stat-value"><?= $totalQuantity ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($weeklyTrends)): ?>
                                <div class="prn-daily-breakdown">
                                    <div class="prn-breakdown-title">📈 Weekly Trends</div>
                                    <?php foreach ($weeklyTrends as $week => $doses): ?>
                                        <div class="prn-breakdown-item">
                                            <span class="prn-breakdown-date"><?= $week ?></span>
                                            <span class="prn-breakdown-value"><?= $doses ?> dose<?= $doses != 1 ? 's' : '' ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: var(--color-text-secondary);">
                                    No doses taken this month
                                </div>
                            <?php endif; ?>
                        </div><!-- /.expandable-med-content -->
                    </div><!-- /.prn-summary-card -->
                <?php endforeach; ?>
            
            <?php elseif ($view === 'annual'): ?>
                <!-- PRN ANNUAL VIEW -->
                <?php foreach ($prnData as $medId => $data): ?>
                    <?php 
                    $med = $data['medication'];
                    $isExpanded = in_array($medId, $expandedMeds);
                    
                    // Get monthly breakdown for the year
                    $stmt = $pdo->prepare("
                        SELECT MONTH(taken_at) as month, 
                               COUNT(*) as dose_count, 
                               SUM(quantity_taken) as total_quantity
                        FROM medication_logs 
                        WHERE medication_id = ? AND user_id = ? AND status = 'taken' 
                        AND YEAR(taken_at) = YEAR(CURDATE())
                        GROUP BY MONTH(taken_at)
                        ORDER BY month
                    ");
                    $stmt->execute([$medId, $userId]);
                    $monthlyData = $stmt->fetchAll();
                    
                    $totalDosesYear = 0;
                    $totalQuantityYear = 0;
                    $monthlyBreakdown = [];
                    
                    foreach ($monthlyData as $row) {
                        $totalDosesYear += $row['dose_count'];
                        $totalQuantityYear += $row['total_quantity'];
                        $monthlyBreakdown[$row['month']] = [
                            'doses' => $row['dose_count'],
                            'quantity' => $row['total_quantity']
                        ];
                    }
                    ?>
                    <div class="prn-summary-card">
                        <!-- Expandable Header -->
                        <div class="expandable-med-header" onclick="toggleExpandableMed(<?= $medId ?>, 'prn-annual')">
                            <div class="expandable-med-title">
                                <span class="expandable-med-name">💊 <?= htmlspecialchars($med['name']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="expandable-med-total">[Total: <?= $totalDosesYear ?>]</span>
                                <span class="expand-indicator <?= $isExpanded ? 'expanded' : '' ?>" id="expand-indicator-prn-annual-<?= $medId ?>">▶</span>
                            </div>
                        </div>
                        
                        <!-- Expandable Content -->
                        <div class="expandable-med-content <?= $isExpanded ? 'expanded' : 'collapsed' ?>" id="expandable-content-prn-annual-<?= $medId ?>">
                            <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                                <div class="prn-med-dose"><?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?></div>
                            <?php endif; ?>
                            
                            <div class="prn-stats-grid">
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Doses This Year</div>
                                    <div class="prn-stat-value"><?= $totalDosesYear ?></div>
                                </div>
                                <div class="prn-stat-box">
                                    <div class="prn-stat-label">Total Quantity</div>
                                    <div class="prn-stat-value"><?= $totalQuantityYear ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($monthlyBreakdown)): ?>
                                <div class="prn-daily-breakdown">
                                    <div class="prn-breakdown-title">📅 Monthly Breakdown</div>
                                    <?php
                                    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                                                 'July', 'August', 'September', 'October', 'November', 'December'];
                                    for ($m = 1; $m <= 12; $m++) {
                                        if (isset($monthlyBreakdown[$m])) {
                                            $doses = $monthlyBreakdown[$m]['doses'];
                                            $quantity = $monthlyBreakdown[$m]['quantity'];
                                    ?>
                                        <div class="prn-breakdown-item">
                                            <span class="prn-breakdown-date"><?= $monthNames[$m] ?></span>
                                            <span class="prn-breakdown-value"><?= $doses ?> dose<?= $doses != 1 ? 's' : '' ?> (<?= $quantity ?> <?= $med['dose_unit'] ? htmlspecialchars($med['dose_unit']) : 'units' ?>)</span>
                                        </div>
                                    <?php 
                                        }
                                    } 
                                    ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: var(--color-text-secondary);">
                                    No doses taken this year
                                </div>
                            <?php endif; ?>
                        </div><!-- /.expandable-med-content -->
                    </div><!-- /.prn-summary-card -->
                <?php endforeach; ?>
            
            <?php endif; ?>
            
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
    function togglePreviousWeeks() {
        const content = document.getElementById('previousWeeksContent');
        const toggle = document.getElementById('previousWeeksToggle');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            toggle.textContent = '−';
        } else {
            content.style.display = 'none';
            toggle.textContent = '+';
        }
    }
    
    function toggleMedicationWeeks(medId) {
        const content = document.getElementById(medId + '-weeks');
        const toggle = document.getElementById('toggle-' + medId);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            toggle.textContent = '−';
        } else {
            content.style.display = 'none';
            toggle.textContent = '+';
        }
    }
    
    function toggleMonthlyMed(medId) {
        const content = document.getElementById('calendar-content-' + medId);
        const toggle = document.getElementById('toggle-icon-' + medId);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            toggle.textContent = '−';
            // Add to expanded list
            updateExpandedMeds(medId, true);
        } else {
            content.style.display = 'none';
            toggle.textContent = '+';
            // Remove from expanded list
            updateExpandedMeds(medId, false);
        }
    }
    
    function toggleExpandableMed(medId, viewType) {
        const content = document.getElementById('expandable-content-' + viewType + '-' + medId);
        const indicator = document.getElementById('expand-indicator-' + viewType + '-' + medId);
        
        if (!content || !indicator) return;
        
        const isCurrentlyExpanded = content.classList.contains('expanded');
        
        if (isCurrentlyExpanded) {
            content.classList.remove('expanded');
            content.classList.add('collapsed');
            indicator.classList.remove('expanded');
            updateExpandedMeds(medId, false);
        } else {
            content.classList.remove('collapsed');
            content.classList.add('expanded');
            indicator.classList.add('expanded');
            updateExpandedMeds(medId, true);
        }
    }
    
    function updateExpandedMeds(medId, isExpanded) {
        const urlParams = new URLSearchParams(window.location.search);
        let expanded = urlParams.get('expanded');
        let expandedList = expanded ? expanded.split(',').filter(id => id) : [];
        
        const medIdStr = medId.toString();
        
        if (isExpanded) {
            if (!expandedList.includes(medIdStr)) {
                expandedList.push(medIdStr);
            }
        } else {
            expandedList = expandedList.filter(id => id !== medIdStr);
        }
        
        if (expandedList.length > 0) {
            urlParams.set('expanded', expandedList.join(','));
        } else {
            urlParams.delete('expanded');
        }
        
        // Update URL without reload
        const params = urlParams.toString();
        const newUrl = window.location.pathname + (params ? '?' + params : '');
        window.history.replaceState({}, '', newUrl);
    }
    
    // Auto-expand medications on page load
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const expanded = urlParams.get('expanded');
        if (expanded) {
            expanded.split(',').forEach(medId => {
                if (medId) {
                    // Try monthly view
                    const monthlyContent = document.getElementById('calendar-content-' + medId);
                    const monthlyToggle = document.getElementById('toggle-icon-' + medId);
                    if (monthlyContent && monthlyToggle) {
                        monthlyContent.style.display = 'block';
                        monthlyToggle.textContent = '−';
                    }
                    
                    // Try expandable sections (PRN and weekly)
                    const viewTypes = ['prn-daily', 'prn-weekly', 'prn-monthly', 'prn-annual', 'weekly', 'scheduled'];
                    viewTypes.forEach(viewType => {
                        const content = document.getElementById('expandable-content-' + viewType + '-' + medId);
                        const indicator = document.getElementById('expand-indicator-' + viewType + '-' + medId);
                        if (content && indicator) {
                            content.classList.remove('collapsed');
                            content.classList.add('expanded');
                            indicator.classList.add('expanded');
                        }
                    });
                }
            });
        }
    });
    </script>
</body>
</html>
