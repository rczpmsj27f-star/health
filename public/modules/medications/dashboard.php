<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/medication_icon.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Get today's medications
$today = date('D'); // Mon, Tue, Wed, etc.

$stmt = $pdo->prepare("
    SELECT DISTINCT m.*, md.dose_amount, md.dose_unit, ms.frequency_type, ms.times_per_day, ms.days_of_week, ms.is_prn, ms.special_timing, ms.custom_instructions
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
$todayDate = date('Y-m-d');

// Get current date time for filtering
$currentDateTime = date('Y-m-d H:i:s');
$currentDateTimeStamp = strtotime($currentDateTime); // Compute once for reuse

// Get medication logs for today - only future doses OR doses with status
$stmt = $pdo->prepare("
    SELECT medication_id, scheduled_date_time, status, taken_at, skipped_reason
    FROM medication_logs
    WHERE user_id = ? 
    AND DATE(scheduled_date_time) = ?
    AND (
        scheduled_date_time >= ? 
        OR status IN ('taken', 'skipped')
    )
");
$stmt->execute([$userId, $todayDate, $currentDateTime]);
$medLogs = [];
while ($log = $stmt->fetch()) {
    $key = $log['medication_id'] . '_' . $log['scheduled_date_time'];
    $medLogs[$key] = $log;
}

// Separate medications into timed and untimed (daily without specific times)
$untimedDailyMeds = [];

foreach ($todaysMeds as $med) {
    // Skip PRN medications - they're handled separately
    if ($med['is_prn']) {
        continue;
    }
    
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
            $scheduledDateTime = $todayDate . ' ' . $timeKey . ':00';
            
            // Skip if this dose time is in the past AND has no log entry
            $logKey = $med['id'] . '_' . $scheduledDateTime;
            $hasLog = isset($medLogs[$logKey]);
            $isPastTime = strtotime($scheduledDateTime) < $currentDateTimeStamp;
            
            if ($isPastTime && !$hasLog) {
                continue; // Skip past doses without logs
            }
            
            if (!isset($scheduleByTime[$timeKey])) {
                $scheduleByTime[$timeKey] = [];
            }
            
            // Add log status to medication data
            $medWithStatus = $med;
            $medWithStatus['scheduled_date_time'] = $scheduledDateTime;
            // Safely access log data with null coalescing
            $medWithStatus['log_status'] = isset($medLogs[$logKey]) ? ($medLogs[$logKey]['status'] ?? 'pending') : 'pending';
            $medWithStatus['taken_at'] = isset($medLogs[$logKey]) ? ($medLogs[$logKey]['taken_at'] ?? null) : null;
            $medWithStatus['skipped_reason'] = isset($medLogs[$logKey]) ? ($medLogs[$logKey]['skipped_reason'] ?? null) : null;
            
            $scheduleByTime[$timeKey][] = $medWithStatus;
        }
    } else {
        // Daily medication without specific times
        // Use generic scheduled time for today
        $scheduledDateTime = $todayDate . ' 12:00:00';
        $medWithStatus = $med;
        $medWithStatus['scheduled_date_time'] = $scheduledDateTime;
        $logKey = $med['id'] . '_' . $scheduledDateTime;
        $medWithStatus['log_status'] = $medLogs[$logKey]['status'] ?? 'pending';
        $medWithStatus['taken_at'] = $medLogs[$logKey]['taken_at'] ?? null;
        $medWithStatus['skipped_reason'] = $medLogs[$logKey]['skipped_reason'] ?? null;
        
        $untimedDailyMeds[] = $medWithStatus;
    }
}

// Sort by time (earliest first)
ksort($scheduleByTime);

// Get PRN medications
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.current_stock, md.dose_amount, md.dose_unit, 
           ms.doses_per_administration, ms.max_doses_per_day, ms.min_hours_between_doses
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    WHERE m.user_id = ? 
    AND (m.archived = 0 OR m.archived IS NULL)
    AND ms.is_prn = 1
    ORDER BY m.name
");
$stmt->execute([$userId]);
$prnMedications = $stmt->fetchAll();

// For each PRN medication, get dose count in last 24 hours
$prnData = [];
foreach ($prnMedications as $med) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as dose_count, MAX(taken_at) as last_taken
        FROM medication_logs 
        WHERE medication_id = ? 
        AND user_id = ?
        AND taken_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND status = 'taken'
    ");
    $stmt->execute([$med['id'], $userId]);
    $logData = $stmt->fetch();
    
    $doseCount = $logData['dose_count'] ?? 0;
    $lastTaken = $logData['last_taken'];
    $dosesPerAdmin = $med['doses_per_administration'] ?? 1;
    $maxDoses = $med['max_doses_per_day'] ?? 999;
    $minHours = $med['min_hours_between_doses'] ?? 0;
    
    // Calculate if can take now and next available time
    $canTakeNow = true;
    $nextAvailableTime = null;
    $timeRemaining = 0;
    
    // Check max doses
    if ($doseCount >= $maxDoses) {
        $canTakeNow = false;
    }
    
    // Check minimum time between doses
    if ($lastTaken && $minHours > 0) {
        $lastTakenTimestamp = strtotime($lastTaken);
        $minGapSeconds = $minHours * 3600;
        $nextAvailableTimestamp = $lastTakenTimestamp + $minGapSeconds;
        $timeRemaining = $nextAvailableTimestamp - time();
        
        if ($timeRemaining > 0) {
            $canTakeNow = false;
            $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        }
    }
    
    $prnData[] = [
        'medication' => $med,
        'dose_count' => $doseCount,
        'doses_per_admin' => $dosesPerAdmin,
        'max_doses' => $maxDoses,
        'can_take_now' => $canTakeNow,
        'next_available_time' => $nextAvailableTime,
        'time_remaining_seconds' => max(0, $timeRemaining)
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Dashboard</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/modal.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/medication-icons.js?v=<?= time() ?>"></script>
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
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .schedule-section h3 {
            color: var(--color-primary);
            margin: 0 0 12px 0;
            font-size: 22px;
            font-weight: 600;
        }
        
        .schedule-card {
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin-bottom: 8px;
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
        
        /* Compact schedule display */
        .time-group-compact {
            margin-bottom: 12px;
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
        }
        
        .time-header-compact {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 6px;
        }
        
        .med-item-compact {
            padding: 6px 10px;
            color: var(--color-text);
            font-size: 14px;
            background: white;
            border-radius: var(--radius-sm);
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            position: relative;
        }
        
        .med-item-compact:last-child {
            margin-bottom: 0;
        }
        
        .overdue-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #f44336;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1;
        }
        
        .med-info {
            flex: 1;
            min-width: 200px;
        }
        
        .med-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn-taken {
            background: var(--color-success);
            color: white;
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-taken:hover {
            background: #28a745;
        }
        
        .btn-skipped {
            background: var(--color-warning);
            color: white;
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-skipped:hover {
            background: #e0a800;
        }
        
        .btn-untake {
            background: var(--color-danger);
            color: white;
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-left: 8px;
        }
        
        .btn-untake:hover {
            background: #c82333;
        }
        
        .status-icon {
            font-size: 20px;
            margin-right: 4px;
        }
        
        .status-taken {
            color: var(--color-success);
            font-weight: 600;
        }
        
        .status-skipped {
            color: var(--color-warning);
            font-weight: 600;
        }
        
        .status-overdue {
            color: var(--color-danger);
            font-weight: 600;
            font-size: 12px;
            background: rgba(220, 53, 69, 0.1);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            align-items: center;
            justify-content: center;
            background: none !important;
        }
        
        .modal.active {
            display: flex !important;
            background: rgba(0, 0, 0, 0.5) !important;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-md);
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header h3 {
            margin: 0 0 16px 0;
            color: var(--color-primary);
        }
        
        .modal-body {
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 16px;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-secondary {
            background: var(--color-secondary);
            color: white;
        }
        
        .schedule-date {
            margin: 0 0 12px 0;
            color: var(--color-text-secondary);
            font-size: 14px;
        }
        
        .no-meds {
            text-align: center;
            padding: 20px;
            color: var(--color-text-secondary);
        }
        
        .dashboard-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 32px;
        }
        
        /* Half-screen tiles for My Medications and Medication Stock */
        .dashboard-tiles-half {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 32px;
        }
        
        .dashboard-tiles-half .tile {
            flex: 1 1 48%;
            max-width: 48%;
            min-width: 280px;
        }
        
        @media (max-width: 600px) {
            .dashboard-tiles-half .tile {
                flex: 1 1 100%;
                max-width: 100%;
            }
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
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

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
            <?php else: ?>
                <!-- Display untimed daily medications first -->
                <?php if (!empty($untimedDailyMeds)): ?>
                    <div class="time-group-compact">
                        <div class="time-header-compact">Daily Medications</div>
                        <?php foreach ($untimedDailyMeds as $med): ?>
                            <div class="med-item-compact">
                                <div class="med-info">
                                    <?= renderMedicationIcon($med['icon'] ?? 'pill', $med['color'] ?? '#5b21b6', '20px', $med['secondary_color'] ?? null) ?> <?= htmlspecialchars($med['name']) ?> • <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                </div>
                                
                                <div class="med-actions">
                                    <?php if ($med['log_status'] === 'taken'): ?>
                                        <span class="status-taken">
                                            <span class="status-icon">✓</span> Taken
                                        </span>
                                        <button type="button" class="btn-untake" 
                                            onclick="untakeMedication(<?= $med['id'] ?>, '<?= $med['scheduled_date_time'] ?>')">
                                            ↶ Untake
                                        </button>
                                    <?php elseif ($med['log_status'] === 'skipped'): ?>
                                        <span class="status-skipped">
                                            <span class="status-icon">⊘</span> Skipped
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="/modules/medications/take_medication_handler.php" style="display: inline;">
                                            <input type="hidden" name="medication_id" value="<?= $med['id'] ?>">
                                            <input type="hidden" name="scheduled_date_time" value="<?= $med['scheduled_date_time'] ?>">
                                            <button type="submit" class="btn-taken">✓ Taken</button>
                                        </form>
                                        <button type="button" class="btn-skipped" 
                                            onclick="showSkipModal(<?= $med['id'] ?>, '<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>', '<?= $med['scheduled_date_time'] ?>')">
                                            ⊘ Skipped
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Display medications grouped by time in compact format -->
                <?php if (!empty($scheduleByTime)): ?>
                <?php 
                $currentTime = strtotime(date('H:i'));
                foreach ($scheduleByTime as $time => $meds): 
                    $scheduleTime = strtotime($time);
                    
                    // Check if this is a special time with custom overdue threshold
                    $isOverdue = false;
                    
                    // Check if any medication in this time slot has special timing
                    $hasSpecialTiming = false;
                    $specialTimingType = null;
                    foreach ($meds as $checkMed) {
                        if (!empty($checkMed['special_timing'])) {
                            $hasSpecialTiming = true;
                            $specialTimingType = $checkMed['special_timing'];
                            break;
                        }
                    }
                    
                    if ($hasSpecialTiming && $specialTimingType === 'on_waking') {
                        // Show overdue after 9am for "On waking"
                        $isOverdue = $currentTime > strtotime('09:00');
                    } elseif ($hasSpecialTiming && $specialTimingType === 'before_bed') {
                        // Show overdue after 10pm for "Before bed"
                        $isOverdue = $currentTime > strtotime('22:00');
                    } else {
                        // Regular time - show overdue immediately after scheduled time
                        $isOverdue = $currentTime > $scheduleTime;
                    }
                ?>
                    <div class="time-group-compact">
                        <div class="time-header-compact"><?= $time ?></div>
                        <?php foreach ($meds as $med): ?>
                            <div class="med-item-compact">
                                <?php 
                                // Check if this specific medication dose is overdue and pending
                                $medIsOverdue = $isOverdue && $med['log_status'] === 'pending';
                                ?>
                                <?php if ($medIsOverdue): ?>
                                    <span class="overdue-badge">⚠️ OVERDUE</span>
                                <?php endif; ?>
                                
                                <div class="med-info">
                                    <?= renderMedicationIcon($med['icon'] ?? 'pill', $med['color'] ?? '#5b21b6', '20px', $med['secondary_color'] ?? null) ?> <?= htmlspecialchars($med['name']) ?> • <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                                    
                                    <?php if (!empty($med['special_timing'])): ?>
                                        <div class="special-timing-badge" style="background: #ffd54f; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 11px; display: inline-block; margin-left: 8px;">
                                            <?php
                                            switch($med['special_timing']) {
                                                case 'on_waking': echo '🌅 On Waking'; break;
                                                case 'before_bed': echo '🌙 Before Bed'; break;
                                                case 'with_meal': echo '🍽️ With Meal'; break;
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($med['custom_instructions'])): ?>
                                        <div style="font-size: 11px; color: #666; margin-top: 4px;">
                                            📝 <?= htmlspecialchars($med['custom_instructions']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="med-actions">
                                    <?php if ($med['log_status'] === 'taken'): ?>
                                        <span class="status-taken">
                                            <span class="status-icon">✓</span> Taken
                                        </span>
                                        <button type="button" class="btn-untake" 
                                            onclick="untakeMedication(<?= $med['id'] ?>, '<?= $med['scheduled_date_time'] ?>')">
                                            ↶ Untake
                                        </button>
                                    <?php elseif ($med['log_status'] === 'skipped'): ?>
                                        <span class="status-skipped">
                                            <span class="status-icon">⊘</span> Skipped
                                        </span>
                                    <?php else: ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="status-overdue">⚠ Overdue</span>
                                        <?php endif; ?>
                                        <button type="button" class="btn-taken" 
                                            onclick="markAsTaken(<?= $med['id'] ?>, '<?= $med['scheduled_date_time'] ?>')">
                                            ✓ Taken
                                        </button>
                                        <button type="button" class="btn-skipped" 
                                            onclick="showSkipModal(<?= $med['id'] ?>, '<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>', '<?= $med['scheduled_date_time'] ?>')">
                                            ⊘ Skipped
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- PRN Medications Section -->
        <?php if (!empty($prnMedications)): ?>
        <div class="schedule-section">
            <h3>Take PRN Medication</h3>
            <p style="color: var(--color-text-secondary); margin: 0 0 20px 0;">As-needed medications available to take</p>
            
            <?php foreach ($prnData as $idx => $data): ?>
                <?php 
                $med = $data['medication'];
                $doseCount = $data['dose_count'];
                $dosesPerAdmin = $data['doses_per_admin'];
                $maxDoses = $data['max_doses'];
                $canTake = $data['can_take_now'];
                $remainingDoses = max(0, $maxDoses - $doseCount);
                $nextTime = $data['next_available_time'];
                $timeRemaining = $data['time_remaining_seconds'];
                ?>
                <div class="med-item-compact" style="margin-bottom: 12px;">
                    <div class="med-info">
                        <?= renderMedicationIcon($med['icon'] ?? 'pill', $med['color'] ?? '#5b21b6', '20px', $med['secondary_color'] ?? null) ?> <?= htmlspecialchars($med['name']) ?> • <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?>
                        <?php if ($dosesPerAdmin > 1): ?>
                            <span style="color: var(--color-primary); font-weight: 600;">(Take <?= $dosesPerAdmin ?>)</span>
                        <?php endif; ?>
                        <br>
                        <small style="color: var(--color-text-secondary);">
                            <?= $doseCount ?> of <?= $maxDoses ?> doses taken today
                            <?php if ($canTake && $remainingDoses > 0): ?>
                                • <?= $remainingDoses ?> remaining
                            <?php elseif (!$canTake && $nextTime): ?>
                                • Next dose at <?= $nextTime ?> 
                                <span id="countdown-<?= $idx ?>" data-seconds="<?= $timeRemaining ?>"></span>
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <div class="med-actions">
                        <?php if ($canTake): ?>
                            <a href="/modules/medications/prn_calculator.php?medication_id=<?= $med['id'] ?>" class="btn-taken" style="text-decoration: none; display: inline-block;">
                                ✓ Take Dose
                            </a>
                        <?php else: ?>
                            <span class="status-skipped">
                                <span class="status-icon">⊘</span> Not Available
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Dashboard Tiles -->
        <div class="dashboard-tiles-half">
            <a class="tile tile-purple" href="/modules/medications/list.php">
                <span class="tile-icon">💊</span>
                <div class="tile-title">My Medications</div>
                <div class="tile-desc">View current & archived</div>
            </a>
            
            <a class="tile tile-green" href="/modules/medications/stock.php">
                <span class="tile-icon">📦</span>
                <div class="tile-title">Medication Stock</div>
                <div class="tile-desc">Manage your stock levels</div>
            </a>
        </div>
    </div>
    
    <!-- Skip Medication Modal -->
    <div id="skipModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⊘ Skip Medication</h3>
            </div>
            <form method="POST" action="/modules/medications/skip_medication_handler.php" id="skipForm">
                <div class="modal-body">
                    <input type="hidden" name="medication_id" id="skip_medication_id">
                    <input type="hidden" name="scheduled_date_time" id="skip_scheduled_date_time">
                    
                    <p style="margin-bottom: 16px; color: var(--color-text-secondary);">
                        Why are you skipping <strong id="skip_medication_name"></strong>?
                    </p>
                    
                    <div class="form-group">
                        <label>Reason *</label>
                        <select name="skipped_reason" id="skipped_reason" required>
                            <option value="">Select a reason...</option>
                            <option value="Unwell">Unwell</option>
                            <option value="Forgot">Forgot</option>
                            <option value="Did not have them with me">Did not have them with me</option>
                            <option value="Lost">Lost</option>
                            <option value="Side effects">Side effects</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSkipModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Skip</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Generic Confirmation Modal -->
    <div id="confirmModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3 id="confirmModalTitle">Confirm Action</h3>
            <p id="confirmModalMessage">Are you sure?</p>
            <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn btn-primary" id="confirmModalAction">Confirm</button>
            </div>
        </div>
    </div>
    
    <style>
    /* Generic modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        align-items: center;
        justify-content: center;
        background: none !important;
    }
    
    .modal.active {
        display: flex !important;
        background: rgba(0, 0, 0, 0.5) !important;
    }
    
    .modal-content {
        background: var(--color-bg-white);
        padding: 32px;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        max-width: 500px;
        width: 90%;
    }
    </style>
    
    <script>
    function markAsTaken(medId, scheduledDateTime) {
        fetch('/modules/medications/take_medication_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'medication_id': medId,
                'scheduled_date_time': scheduledDateTime,
                'ajax': '1'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message, 2000, () => {
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to mark medication as taken');
            }
        })
        .catch(error => {
            showErrorModal('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    }
    
    function untakeMedication(medId, scheduledDateTime) {
        showConfirmModal(
            'Undo Medication',
            'Are you sure you want to undo taking this medication? This will remove the log entry and restore 1 unit to your stock.',
            function() {
                fetch('/modules/medications/untake_medication_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'medication_id': medId,
                        'scheduled_date_time': scheduledDateTime,
                        'ajax': '1'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal(data.message, 2000, () => {
                            window.location.reload();
                        });
                    } else {
                        showErrorModal(data.message || 'Failed to untake medication');
                    }
                })
                .catch(error => {
                    showErrorModal('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
            }
        );
    }
    
    function showConfirmModal(title, message, onConfirm) {
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        document.getElementById('confirmModalAction').onclick = function() {
            closeConfirmModal();
            onConfirm();
        };
        const confirmModal = document.getElementById('confirmModal');
        confirmModal.style.display = 'flex';
        confirmModal.style.background = 'rgba(0, 0, 0, 0.5)';
        
        // Close on outside click
        confirmModal.onclick = function(e) {
            if (e.target === this) {
                closeConfirmModal();
            }
        };
    }
    
    function closeConfirmModal() {
        const confirmModal = document.getElementById('confirmModal');
        confirmModal.style.display = 'none';
        confirmModal.style.background = 'none';
    }
    
    function showSkipModal(medId, medName, scheduledDateTime) {
        document.getElementById('skip_medication_id').value = medId;
        document.getElementById('skip_medication_name').textContent = medName;
        document.getElementById('skip_scheduled_date_time').value = scheduledDateTime;
        document.getElementById('skipped_reason').value = '';
        document.getElementById('skipModal').classList.add('active');
    }
    
    function closeSkipModal() {
        document.getElementById('skipModal').classList.remove('active');
    }
    
    // Handle skip form submission with AJAX
    document.getElementById('skipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('ajax', '1');
        
        fetch('/modules/medications/skip_medication_handler.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeSkipModal();
                showSuccessModal(data.message, 2000, () => {
                    window.location.reload();
                });
            } else {
                closeSkipModal();
                showErrorModal(data.message || 'Failed to skip medication');
            }
        })
        .catch(error => {
            closeSkipModal();
            showErrorModal('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('skipModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSkipModal();
        }
    });
    
    // Show success/error messages if present using modal.js
    <?php if (isset($_SESSION['success'])): ?>
        showSuccessModal('<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES) ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        showErrorModal('<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES) ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    // Countdown timers for PRN next dose time
    function updateCountdowns() {
        document.querySelectorAll('[id^="countdown-"]').forEach(function(elem) {
            let seconds = parseInt(elem.getAttribute('data-seconds'));
            
            if (seconds > 0) {
                // Calculate hours and minutes
                let hours = Math.floor(seconds / 3600);
                let minutes = Math.floor((seconds % 3600) / 60);
                let secs = seconds % 60;
                
                let display = '(';
                if (hours > 0) {
                    display += hours + 'h ';
                }
                if (minutes > 0 || hours > 0) {
                    display += minutes + 'm ';
                }
                display += secs + 's)';
                
                elem.textContent = display;
                elem.setAttribute('data-seconds', seconds - 1);
            } else {
                // Time is up, reload page to update availability
                window.location.reload();
            }
        });
    }
    
    // Update countdowns every second
    if (document.querySelectorAll('[id^="countdown-"]').length > 0) {
        setInterval(updateCountdowns, 1000);
        updateCountdowns(); // Initial call
    }
    </script>
</body>
</html>
