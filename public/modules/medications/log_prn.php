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

// Get all active PRN medications for the user
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.current_stock, md.dose_amount, md.dose_unit, 
           ms.max_doses_per_day, ms.min_hours_between_doses
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

// For each PRN medication, get dose count in last 24 hours and last dose time
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
    $maxDoses = $med['max_doses_per_day'] ?? 999;
    $minHours = $med['min_hours_between_doses'] ?? 0;
    
    // Calculate if can take now
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
        'max_doses' => $maxDoses,
        'last_taken' => $lastTaken,
        'can_take_now' => $canTakeNow,
        'next_available_time' => $nextAvailableTime,
        'time_remaining' => $timeRemaining
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log PRN Medication</title>
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
        
        .prn-card {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 20px;
        }
        
        .prn-header {
            margin-bottom: 16px;
        }
        
        .prn-header h3 {
            margin: 0 0 4px 0;
            font-size: 20px;
            color: var(--color-text);
        }
        
        .prn-header p {
            margin: 0;
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        .dose-info {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .dose-count {
            flex: 1;
        }
        
        .dose-count-label {
            font-size: 14px;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
        }
        
        .dose-count-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .progress-bar-container {
            flex: 2;
        }
        
        .progress-bar {
            width: 100%;
            height: 24px;
            background: #e0e0e0;
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: var(--color-success);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }
        
        .progress-bar-fill.warning {
            background: var(--color-warning);
        }
        
        .progress-bar-fill.danger {
            background: var(--color-danger);
        }
        
        .status-message {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 14px;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-message.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-take-dose {
            background: var(--color-primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
            width: 100%;
        }
        
        .btn-take-dose:hover:not(:disabled) {
            opacity: 0.9;
        }
        
        .btn-take-dose:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .no-meds {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-secondary);
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }
        
        .countdown {
            font-weight: 600;
            color: var(--color-primary);
        }
        
        .alert {
            padding: 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            <h2>💊 Log PRN Medication</h2>
            <p>Track and log your as-needed (PRN) medications</p>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (empty($prnMedications)): ?>
            <div class="no-meds">
                <p>You don't have any PRN medications yet.</p>
                <p>PRN medications are taken as and when needed, not on a regular schedule.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add PRN Medication</a>
            </div>
        <?php else: ?>
            <?php foreach ($prnData as $data): ?>
                <?php 
                $med = $data['medication'];
                $doseCount = $data['dose_count'];
                $maxDoses = $data['max_doses'];
                $canTake = $data['can_take_now'];
                $nextTime = $data['next_available_time'];
                $timeRemaining = $data['time_remaining'];
                
                $remainingDoses = max(0, $maxDoses - $doseCount);
                $progressPercent = $maxDoses > 0 ? ($doseCount / $maxDoses) * 100 : 0;
                
                $progressClass = '';
                if ($progressPercent >= 100) {
                    $progressClass = 'danger';
                } elseif ($progressPercent >= 75) {
                    $progressClass = 'warning';
                }
                ?>
                <div class="prn-card">
                    <div class="prn-header">
                        <h3>💊 <?= htmlspecialchars($med['name']) ?></h3>
                        <?php if ($med['dose_amount'] && $med['dose_unit']): ?>
                            <p><?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dose-info">
                        <div class="dose-count">
                            <div class="dose-count-label">Doses taken (24h)</div>
                            <div class="dose-count-value"><?= $doseCount ?> / <?= $maxDoses ?></div>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-bar-fill <?= $progressClass ?>" style="width: <?= min(100, $progressPercent) ?>%">
                                    <?php if ($progressPercent > 20): ?>
                                        <?= $doseCount ?> of <?= $maxDoses ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$canTake && $doseCount >= $maxDoses): ?>
                        <div class="status-message danger">
                            ⚠️ <strong>Maximum daily dose reached.</strong> Do not take more until 24 hours have passed since your first dose today.
                        </div>
                    <?php elseif (!$canTake && $nextTime): ?>
                        <div class="status-message warning">
                            ⏱️ <strong>Next dose available at <span class="countdown" data-target="<?= strtotime($data['last_taken']) + ($data['medication']['min_hours_between_doses'] * 3600) ?>"><?= $nextTime ?></span></strong>
                            <br>
                            <small>Minimum time between doses: <?= $med['min_hours_between_doses'] ?> hours</small>
                        </div>
                    <?php else: ?>
                        <div class="status-message success">
                            ✓ You can take <?= $remainingDoses ?> more dose<?= $remainingDoses !== 1 ? 's' : '' ?> today
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/modules/medications/log_prn_handler.php" style="margin: 0;">
                        <input type="hidden" name="medication_id" value="<?= $med['id'] ?>">
                        <button type="submit" class="btn-take-dose" <?= !$canTake ? 'disabled' : '' ?>>
                            <?= $canTake ? '✅ Take Dose Now' : '🚫 Cannot Take Dose' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
    // Update countdown timers
    function updateCountdowns() {
        const countdowns = document.querySelectorAll('.countdown[data-target]');
        const now = Math.floor(Date.now() / 1000);
        
        countdowns.forEach(countdown => {
            const target = parseInt(countdown.getAttribute('data-target'));
            const remaining = target - now;
            
            if (remaining <= 0) {
                countdown.textContent = 'Now';
                // Reload page to update availability
                setTimeout(() => location.reload(), 1000);
            } else {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                
                if (hours > 0) {
                    countdown.textContent = `${hours}h ${minutes}m`;
                } else if (minutes > 0) {
                    countdown.textContent = `${minutes}m ${seconds}s`;
                } else {
                    countdown.textContent = `${seconds}s`;
                }
            }
        });
    }
    
    // Update every second
    if (document.querySelectorAll('.countdown[data-target]').length > 0) {
        setInterval(updateCountdowns, 1000);
        updateCountdowns();
    }
    </script>
</body>
</html>
