<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/medication_icon.php";
require_once "../../../app/core/TimeFormatter.php";
require_once "../../../app/helpers/dropdown_helper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Check for linked user
require_once __DIR__ . '/../../../app/core/LinkedUserHelper.php';
$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);

$viewingLinkedUser = isset($_GET['view']) && $_GET['view'] === 'linked' && $linkedUser;
$targetUserId = $viewingLinkedUser ? $linkedUser['linked_user_id'] : $_SESSION['user_id'];

// Initialize TimeFormatter - use current user's preferences, NOT the target user's
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

// Get permissions if viewing linked user
$myPermissions = null;
$theirPermissions = null;
if ($viewingLinkedUser) {
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    $theirPermissions = $linkedHelper->getPermissions($linkedUser['id'], $targetUserId);
    
    // Check if user has permission to view
    if (!$myPermissions || !$myPermissions['can_view_schedule']) {
        $_SESSION['error_msg'] = "You don't have permission to view their medications";
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
}

// Get date from query parameter or default to today
$viewDate = $_GET['date'] ?? date('Y-m-d');
$viewDate = date('Y-m-d', strtotime($viewDate)); // Validate format
$isToday = $viewDate === date('Y-m-d');

// Calculate navigation dates
$prevDate = date('Y-m-d', strtotime($viewDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($viewDate . ' +1 day'));

// Get today's medications (adjust for view date)
$today = date('D', strtotime($viewDate)); // Mon, Tue, Wed, etc.

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
$stmt->execute([$targetUserId, "%$today%"]);
$todaysMeds = $stmt->fetchAll();

// Get dose times for each medication and build schedule by time
$medDoseTimes = [];
$scheduleByTime = [];
$todayDate = $viewDate; // Use the view date instead of today

// Get current date time for filtering
$currentDateTime = date('Y-m-d H:i:s');
$currentDateTimeStamp = strtotime($currentDateTime); // Compute once for reuse

// Get medication logs for the view date
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
$stmt->execute([$targetUserId, $todayDate, $currentDateTime]);
$medLogs = [];
while ($log = $stmt->fetch()) {
    $key = $log['medication_id'] . '_' . $log['scheduled_date_time'];
    $medLogs[$key] = $log;
}

// Replace "Other instructions" logic with dynamic grouping
$dailyMedications = [];
$timedMedications = [];

foreach ($todaysMeds as $med) {
    if ($med['is_prn']) continue;
    
    $stmt = $pdo->prepare("SELECT dose_number, dose_time, special_time FROM medication_dose_times WHERE medication_id = ? ORDER BY dose_time");
    $stmt->execute([$med['id']]);
    $doseTimes = $stmt->fetchAll();
    
    if (!empty($doseTimes)) {
        foreach ($doseTimes as $doseTime) {
            $scheduledDateTime = $todayDate . ' ' . date('H:i:s', strtotime($doseTime['dose_time']));
            
            // Skip if this dose time is in the past AND has no log entry
            $logKey = $med['id'] . '_' . $scheduledDateTime;
            $hasLog = isset($medLogs[$logKey]);
            $isPastTime = strtotime($scheduledDateTime) < $currentDateTimeStamp;
            
            if ($isPastTime && !$hasLog) {
                continue; // Skip past doses without logs
            }
            
            $medWithStatus = $med;
            $medWithStatus['special_time'] = $doseTime['special_time'] ?? null;
            $medWithStatus['dose_time'] = $doseTime['dose_time'];
            $medWithStatus['scheduled_date_time'] = $scheduledDateTime;
            $medWithStatus['log_status'] = $medLogs[$logKey]['status'] ?? 'pending';
            $medWithStatus['taken_at'] = $medLogs[$logKey]['taken_at'] ?? null;
            $medWithStatus['skipped_reason'] = $medLogs[$logKey]['skipped_reason'] ?? null;
            
            if (!empty($doseTime['special_time'])) {
                $specialTime = trim($doseTime['special_time']);
                if (!isset($dailyMedications[$specialTime])) {
                    $dailyMedications[$specialTime] = [];
                }
                $dailyMedications[$specialTime][] = $medWithStatus;
            } else {
                $timeOnly = date('H:i', strtotime($doseTime['dose_time']));
                $timeDisplay = $timeFormatter->formatTime($doseTime['dose_time']);
                if ($timeOnly === '00:00' || $timeOnly === '23:59') {
                    if (!isset($dailyMedications['Daily meds - no instructions'])) {
                        $dailyMedications['Daily meds - no instructions'] = [];
                    }
                    $dailyMedications['Daily meds - no instructions'][] = $medWithStatus;
                } else {
                    if (!isset($timedMedications[$timeOnly])) {
                        $timedMedications[$timeOnly] = [
                            'meds' => [],
                            'display' => $timeDisplay
                        ];
                    }
                    $timedMedications[$timeOnly]['meds'][] = $medWithStatus;
                }
            }
        }
    } else {
        // Daily medication without dose times
        $scheduledDateTime = $todayDate . ' 12:00:00';
        $logKey = $med['id'] . '_' . $scheduledDateTime;
        
        $medWithStatus = $med;
        $medWithStatus['scheduled_date_time'] = $scheduledDateTime;
        $medWithStatus['log_status'] = $medLogs[$logKey]['status'] ?? 'pending';
        $medWithStatus['taken_at'] = $medLogs[$logKey]['taken_at'] ?? null;
        $medWithStatus['skipped_reason'] = $medLogs[$logKey]['skipped_reason'] ?? null;
        
        // Categorize based on special_timing even without dose times
        if (!empty($med['special_timing'])) {
            $specialTime = trim($med['special_timing']);
            
            if ($specialTime === 'on_waking') {
                $specialTime = 'On waking';
            } elseif ($specialTime === 'before_bed') {
                $specialTime = 'Before bed';
            } elseif ($specialTime === 'with_meal') {
                $specialTime = 'With meals';
            }
            
            if (!isset($dailyMedications[$specialTime])) {
                $dailyMedications[$specialTime] = [];
            }
            $dailyMedications[$specialTime][] = $medWithStatus;
        } else {
            if (!isset($dailyMedications['Daily meds - no instructions'])) {
                $dailyMedications['Daily meds - no instructions'] = [];
            }
            $dailyMedications['Daily meds - no instructions'][] = $medWithStatus;
        }
    }
}

// Sort daily medications
$preferredOrder = ['On waking', 'With breakfast', 'With lunch', 'With dinner', 'Before bed', 'Daily meds - no instructions'];
uksort($dailyMedications, function($a, $b) use ($preferredOrder) {
    $posA = array_search($a, $preferredOrder);
    $posB = array_search($b, $preferredOrder);
    if ($posA !== false && $posB !== false) return $posA - $posB;
    if ($posA !== false) return -1;
    if ($posB !== false) return 1;
    return strcmp($a, $b);
});
ksort($timedMedications);

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
        
        .nav-arrow {
            transition: all 0.2s;
            display: inline-block;
        }
        
        .nav-arrow:hover {
            background: var(--color-bg-light);
            border-radius: 50%;
            transform: scale(1.2);
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
        
        <?php 
        // Check if linked user has any medications
        $linkedUserHasMeds = false;
        if ($linkedUser && $linkedUser['status'] === 'active') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medications WHERE user_id = ? AND (archived = 0 OR archived IS NULL)");
            $stmt->execute([$linkedUser['linked_user_id']]);
            $linkedUserHasMeds = $stmt->fetch()['count'] > 0;
        }
        ?>

        <?php if ($linkedUser && $linkedUser['status'] === 'active' && $linkedUserHasMeds): 
            // Get profile pictures for both users in a single query
            $stmt = $pdo->prepare("SELECT id, profile_picture_path FROM users WHERE id IN (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $linkedUser['linked_user_id']]);
            $userProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Map profiles by user ID
            $myPic = '/assets/images/default-avatar.svg';
            $theirPic = '/assets/images/default-avatar.svg';
            
            foreach ($userProfiles as $profile) {
                if ($profile['id'] === $_SESSION['user_id'] && !empty($profile['profile_picture_path'])) {
                    $myPic = $profile['profile_picture_path'];
                } elseif ($profile['id'] === $linkedUser['linked_user_id'] && !empty($profile['profile_picture_path'])) {
                    $theirPic = $profile['profile_picture_path'];
                }
            }
        ?>
        <!-- Tab Switcher with Profile Pictures -->
        <div style="background: white; border-radius: 10px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; gap: 12px;">
            <a href="/modules/medications/dashboard.php<?= isset($_GET['date']) ? '?date=' . urlencode($_GET['date']) : '' ?>" 
               class="tab-button <?= !$viewingLinkedUser ? 'active' : '' ?>"
               style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= !$viewingLinkedUser ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
                <img src="<?= htmlspecialchars($myPic) ?>" 
                     alt="My profile" 
                     onerror="this.src='/assets/images/default-avatar.svg'"
                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 2px solid <?= !$viewingLinkedUser ? 'white' : 'var(--color-border)' ?>;">
                My Medications
            </a>
            <a href="/modules/medications/dashboard.php?view=linked<?= isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : '' ?>" 
               class="tab-button <?= $viewingLinkedUser ? 'active' : '' ?>"
               style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= $viewingLinkedUser ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
                <img src="<?= htmlspecialchars($theirPic) ?>" 
                     alt="<?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s profile" 
                     onerror="this.src='/assets/images/default-avatar.svg'"
                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $viewingLinkedUser ? 'white' : 'var(--color-border)' ?>;">
                Manage <?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s Meds
            </a>
        </div>
        <?php endif; ?>

        <?php if ($viewingLinkedUser): ?>
        <!-- Linked User Banner -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px; border-radius: 10px; margin-bottom: 20px;">
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                👥 Viewing <?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s Medications
            </div>
            <div style="font-size: 13px; opacity: 0.9;">
                <?php
                $permissions = [];
                if (!empty($myPermissions['can_view_medications'])) $permissions[] = 'View';
                if (!empty($myPermissions['can_mark_taken'])) $permissions[] = 'Mark as taken';
                if (!empty($myPermissions['can_add_medications'])) $permissions[] = 'Add';
                if (!empty($myPermissions['can_edit_medications'])) $permissions[] = 'Edit';
                if (!empty($myPermissions['can_delete_medications'])) $permissions[] = 'Delete';
                echo 'Your permissions: ' . (count($permissions) > 0 ? implode(', ', $permissions) : 'None');
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Scheduled Medications Section -->
        <div class="schedule-section">
            <h3>Scheduled Medications</h3>
            
            <!-- Compact Date Navigation -->
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 8px;">
                    <a href="?date=<?= $prevDate ?><?= $viewingLinkedUser ? '&view=linked' : '' ?>" 
                       style="font-size: 28px; color: var(--color-primary); text-decoration: none; padding: 4px 12px; line-height: 1;" 
                       class="nav-arrow"
                       title="Previous Day"
                       aria-label="Previous Day">
                        ←
                    </a>
                    
                    <div style="font-size: 18px; font-weight: 600; color: var(--color-text); min-width: 280px; text-align: center;">
                        <?= date('l j F Y', strtotime($viewDate)) ?>
                    </div>
                    
                    <a href="?date=<?= $nextDate ?><?= $viewingLinkedUser ? '&view=linked' : '' ?>" 
                       style="font-size: 28px; color: var(--color-primary); text-decoration: none; padding: 4px 12px; line-height: 1;" 
                       class="nav-arrow"
                       title="Next Day"
                       aria-label="Next Day">
                        →
                    </a>
                </div>
                
                <?php if (!$isToday): ?>
                    <a href="?<?= $viewingLinkedUser ? 'view=linked' : '' ?>" 
                       style="display: inline-block; font-size: 13px; color: var(--color-primary); text-decoration: none; padding: 4px 12px; border: 1px solid var(--color-primary); border-radius: 4px; margin-top: 4px;">
                        Return to Today
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($todaysMeds)): ?>
                <div class="no-meds">
                    <p>No medications scheduled for today</p>
                </div>
            <?php else: ?>
                <?php
                // For overdue checking, we need to compare full datetime, not just time
                // Only consider items overdue if viewing today AND the scheduled time has passed
                $currentRealDateTime = new DateTime();
                $viewDateTime = new DateTime($viewDate);
                $isViewingToday = $viewDateTime->format('Y-m-d') === $currentRealDateTime->format('Y-m-d');
                
                // Check if any daily medication group has medications
                $hasDailyMeds = false;
                foreach ($dailyMedications as $group) {
                    if (!empty($group)) {
                        $hasDailyMeds = true;
                        break;
                    }
                }
                ?>
                
                <!-- DAILY MEDICATIONS SECTION (Parent Collapsible) -->
                <?php if ($hasDailyMeds): ?>
                <div class="time-group-collapsible" style="margin-bottom: 20px;">
                    <!-- Daily Medications Header -->
                    <div class="time-header-collapsible" 
                         onclick="toggleTimeGroup('daily-medications-parent')" 
                         style="display: flex; justify-content: space-between; align-items: center; background: #f3f4f6; color: #374151; padding: 8px 12px; border-radius: 6px; cursor: pointer; user-select: none; font-size: 15px; font-weight: 500; border-left: 3px solid #6366f1; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="toggle-icon" id="icon-daily-medications-parent" style="font-size: 14px;">▼</span>
                            <strong>Daily Medications</strong>
                        </div>
                    </div>
                    
                    <!-- Daily Medications Content (expandable) -->
                    <div class="time-group-content" id="daily-medications-parent" style="padding: 12px 0 0 20px;">
                        
                        <!-- DYNAMIC: Loop through all daily medication groups -->
                        <?php foreach ($dailyMedications as $groupName => $meds): ?>
                            <?php if (!empty($meds)): ?>
                                <?php 
                                    // Generate a unique ID for this group
                                    $groupId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $groupName));
                                ?>
                                <div class="time-group-nested" style="margin-bottom: 12px;">
                                    <div class="time-header-nested" 
                                         onclick="toggleTimeGroup('<?= $groupId ?>')" 
                                         style="display: flex; justify-content: space-between; align-items: center; background: #fafafa; color: #4b5563; padding: 6px 10px; border-radius: 4px; cursor: pointer; user-select: none; font-size: 14px; border-left: 2px solid #a78bfa; margin: 4px 0 4px 16px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="toggle-icon" id="icon-<?= $groupId ?>" style="font-size: 12px;">▼</span>
                                            <span><?= htmlspecialchars($groupName) ?></span>
                                            <span style="background: #e5e7eb; color: #6b7280; padding: 1px 6px; border-radius: 8px; font-size: 11px;">
                                                <?= count($meds) ?> med<?= count($meds) !== 1 ? 's' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="time-group-content" id="<?= $groupId ?>" style="padding: 8px 0;">
                                        <?php foreach ($meds as $med): ?>
                                            <?php include __DIR__ . '/../../../app/includes/medication_item.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- TIMED MEDICATIONS (08:00, 12:00, etc.) -->
                <?php foreach ($timedMedications as $time => $timeData): ?>
                <?php
                    $meds = $timeData['meds'];
                    $timeDisplay = $timeData['display'];
                    
                    // Only consider it overdue if:
                    // 1. We're viewing today (not a past or future date)
                    // 2. The scheduled time has passed
                    // 3. There are pending medications
                    $hasPendingMeds = false;
                    foreach ($meds as $med) {
                        if ($med['log_status'] === 'pending') {
                            $hasPendingMeds = true;
                            break;
                        }
                    }
                    
                    // Compare full datetime values instead of just time
                    $isOverdue = false;
                    if ($isViewingToday && $hasPendingMeds) {
                        // For timed medications, check if ANY medication in this time group has a scheduled_date_time in the past
                        foreach ($meds as $med) {
                            if ($med['log_status'] === 'pending' && isset($med['scheduled_date_time'])) {
                                $scheduledDT = new DateTime($med['scheduled_date_time']);
                                if ($scheduledDT < $currentRealDateTime) {
                                    $isOverdue = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    $medCount = count($meds);
                    // Sanitize time for use in HTML ID - ensure it's HH:MM format
                    if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                        $groupId = 'time-' . str_replace(':', '-', $time);
                    } else {
                        // Fallback for unexpected format
                        $groupId = 'time-' . md5($time);
                    }
                ?>
                <div class="time-group-collapsible" style="margin-bottom: 16px;">
                    <!-- Time Group Header -->
                    <div class="time-header-collapsible" 
                         onclick="toggleTimeGroup('<?= $groupId ?>')" 
                         style="display: flex; justify-content: space-between; align-items: center; background: #f3f4f6; color: #374151; padding: 8px 12px; border-radius: 6px; cursor: pointer; user-select: none; font-size: 15px; font-weight: 500; border-left: 3px solid #6366f1; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="toggle-icon" id="icon-<?= $groupId ?>" style="font-size: 14px;">▼</span>
                            <strong><?= htmlspecialchars($timeDisplay) ?></strong>
                            <span style="background: #e5e7eb; color: #6b7280; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                <?= $medCount ?> med<?= $medCount !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <?php if ($isOverdue): ?>
                            <span style="background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                OVERDUE
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Time Group Content -->
                    <div class="time-group-content" id="<?= $groupId ?>" style="padding: 8px 0;">
                        <?php foreach ($meds as $med): ?>
                            <?php include __DIR__ . '/../../../app/includes/medication_item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <?= renderDropdown($pdo, 'skipped_reasons', 'skipped_reason', '', ['id' => 'skipped_reason', 'required' => 'required']) ?>
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
    
    <!-- Late Logging Modal -->
    <div id="lateLoggingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⏰ Late Logging</h3>
            </div>
            <div class="modal-body">
                <p>You are logging this medication for a different date than today.</p>
                <p><strong>Why are you logging this late?</strong></p>
                
                <div class="form-group">
                    <?= renderDropdown($pdo, 'late_logging_reasons', 'lateLoggingReason', '', ['id' => 'lateLoggingReason', 'class' => 'form-control']) ?>
                </div>
                
                <div class="form-group" id="otherReasonGroup" style="display: none;">
                    <label>Please specify:</label>
                    <input type="text" id="otherReasonText" class="form-control" placeholder="Enter reason">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeLateLoggingModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitLateLog()">Submit</button>
            </div>
        </div>
    </div>
    
    <!-- Early Logging Modal -->
    <div id="earlyLoggingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🕐 Early Logging</h3>
            </div>
            <div class="modal-body">
                <p>You are taking this medication early (for a future date).</p>
                <p><strong>Why are you taking this early?</strong></p>
                
                <div class="form-group">
                    <?= renderDropdown($pdo, 'early_logging_reasons', 'earlyLoggingReason', '', ['id' => 'earlyLoggingReason', 'class' => 'form-control']) ?>
                </div>
                
                <div class="form-group" id="earlyOtherReasonGroup" style="display: none;">
                    <label>Please specify:</label>
                    <input type="text" id="earlyOtherReasonText" class="form-control" placeholder="Enter reason">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeEarlyLoggingModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitEarlyLog()">Submit</button>
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
    // Late logging state
    let pendingLateLog = null;
    // Early logging state
    let pendingEarlyLog = null;

    // Toggle function for collapsible time groups
    function toggleTimeGroup(groupId) {
        const content = document.getElementById(groupId);
        const icon = document.getElementById('icon-' + groupId);
        
        if (!content || !icon) return;
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.textContent = '▼';
        } else {
            content.style.display = 'none';
            icon.textContent = '►';
        }
    }

    // Show "Other" text input when selected
    document.getElementById('lateLoggingReason').addEventListener('change', function() {
        const otherGroup = document.getElementById('otherReasonGroup');
        if (this.value === 'Other') {
            otherGroup.style.display = 'block';
        } else {
            otherGroup.style.display = 'none';
        }
    });

    // Show "Other" text input for early logging when selected
    document.getElementById('earlyLoggingReason').addEventListener('change', function() {
        const otherGroup = document.getElementById('earlyOtherReasonGroup');
        if (this.value === 'Other') {
            otherGroup.style.display = 'block';
        } else {
            otherGroup.style.display = 'none';
        }
    });

    function closeLateLoggingModal() {
        document.getElementById('lateLoggingModal').classList.remove('active');
        pendingLateLog = null;
    }

    function closeEarlyLoggingModal() {
        document.getElementById('earlyLoggingModal').classList.remove('active');
        pendingEarlyLog = null;
    }

    function submitLateLog() {
        const reasonSelect = document.getElementById('lateLoggingReason');
        let reason = reasonSelect.value;
        
        if (reason === 'Other') {
            const otherText = document.getElementById('otherReasonText').value.trim();
            if (!otherText) {
                showAlert('Please specify the reason', 'Missing Information');
                return;
            }
            reason = 'Other: ' + otherText;
        }
        
        if (!reason) {
            showAlert('Please select a reason', 'Missing Information');
            return;
        }
        
        // Add reason to pending log and submit
        if (pendingLateLog) {
            pendingLateLog.lateReason = reason;
            submitLogToServer(pendingLateLog);
        }
        
        closeLateLoggingModal();
    }

    function submitEarlyLog() {
        const reasonSelect = document.getElementById('earlyLoggingReason');
        let reason = reasonSelect.value;
        
        if (reason === 'Other') {
            const otherText = document.getElementById('earlyOtherReasonText').value.trim();
            if (!otherText) {
                showAlert('Please specify the reason', 'Missing Information');
                return;
            }
            reason = 'Other: ' + otherText;
        }
        
        if (!reason) {
            showAlert('Please select a reason', 'Missing Information');
            return;
        }
        
        // Add reason to pending log and submit
        if (pendingEarlyLog) {
            pendingEarlyLog.earlyReason = reason;
            submitLogToServer(pendingEarlyLog);
        }
        
        closeEarlyLoggingModal();
    }

    function submitLogToServer(logData) {
        // Build form data
        const formData = new URLSearchParams({
            'medication_id': logData.medId,
            'scheduled_date_time': logData.scheduledDateTime,
            'ajax': '1'
        });
        
        if (logData.lateReason) {
            formData.append('late_logging_reason', logData.lateReason);
        }
        
        if (logData.earlyReason) {
            formData.append('early_logging_reason', logData.earlyReason);
        }
        
        // Add for_user_id if viewing linked user
        <?php if ($viewingLinkedUser): ?>
        formData.append('for_user_id', '<?= $targetUserId ?>');
        <?php endif; ?>
        
        // Submit to take_handler
        fetch('/modules/medications/take_medication_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
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

    function markAsTaken(medId, scheduledDateTime) {
        // Validate scheduledDateTime format
        if (!scheduledDateTime || typeof scheduledDateTime !== 'string') {
            console.error('Invalid scheduledDateTime:', scheduledDateTime);
            return;
        }
        
        // Parse the scheduled date
        const scheduledDate = scheduledDateTime.split(' ')[0]; // Gets YYYY-MM-DD part
        const todayDate = '<?= date("Y-m-d") ?>';
        
        // Show late logging modal ONLY if the scheduled date is in the past
        // Note: String comparison works reliably because dates are in YYYY-MM-DD format
        const isPastDate = scheduledDate < todayDate;
        const isFutureDate = scheduledDate > todayDate;
        
        if (isPastDate) {
            // Show late logging modal
            pendingLateLog = {
                medId: medId,
                scheduledDateTime: scheduledDateTime
            };
            document.getElementById('lateLoggingModal').classList.add('active');
        } else if (isFutureDate) {
            // Show early logging modal
            pendingEarlyLog = {
                medId: medId,
                scheduledDateTime: scheduledDateTime
            };
            document.getElementById('earlyLoggingModal').classList.add('active');
        } else {
            // Direct submission for today's logging
            submitLogToServer({
                medId: medId,
                scheduledDateTime: scheduledDateTime
            });
        }
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
    
    // Nudge functionality for linked users
    async function sendNudge(medicationId, toUserId) {
        const confirmed = await confirmAction(
            'Send a gentle reminder to take this medication?',
            'Send Reminder'
        );
        
        if (!confirmed) {
            return;
        }
        
        const formData = new FormData();
        formData.append('medication_id', medicationId);
        formData.append('to_user_id', toUserId);
        
        fetch('/modules/medications/nudge_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccessModal('Nudge sent! ✓', 2000);
            } else {
                showErrorModal('Error: ' + data.error);
            }
        })
        .catch(error => {
            showErrorModal('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>
