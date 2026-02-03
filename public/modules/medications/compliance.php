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
            <p>Track your medication adherence over the past week</p>
        </div>
        
        <?php if (empty($medications)): ?>
            <div class="no-meds">
                <p>You don't have any active medications yet.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add Medication</a>
            </div>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</body>
</html>
