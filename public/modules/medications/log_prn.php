<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Date format constant for next dose time display
define('NEXT_DOSE_DATE_FORMAT', 'H:i \o\n d M');  // e.g., "14:30 on 06 Feb"

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Get all active PRN medications for the user
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.current_stock, md.dose_amount, md.dose_unit, 
           ms.max_doses_per_day, ms.min_hours_between_doses, ms.initial_dose, ms.subsequent_dose
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
        SELECT COALESCE(SUM(quantity_taken), 0) as dose_count, MAX(taken_at) as last_taken, MIN(taken_at) as first_taken
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
    $firstTaken = $logData['first_taken'];
    $maxDoses = $med['max_doses_per_day'] ?? 999;
    $minHours = $med['min_hours_between_doses'] ?? 0;
    
    // Calculate if can take now
    $canTakeNow = true;
    $nextAvailableTime = null;
    $nextAvailableTimeForMaxDose = null;
    $timeRemaining = 0;
    
    // Check max doses
    if ($doseCount >= $maxDoses) {
        $canTakeNow = false;
        // Calculate when next dose will be available (24 hours after first dose)
        if ($firstTaken) {
            $firstTakenTimestamp = strtotime($firstTaken);
            $nextAvailableTimestamp = $firstTakenTimestamp + (24 * 3600);
            
            // Format time with date if it's on a different day than today
            $todayEnd = strtotime('tomorrow') - 1;
            if ($nextAvailableTimestamp > $todayEnd) {
                $nextAvailableTimeForMaxDose = date('H:i, j M', $nextAvailableTimestamp);
            } else {
                $nextAvailableTimeForMaxDose = date('H:i', $nextAvailableTimestamp);
            }
        }
    }
    
    // Check minimum time between doses
    if ($lastTaken && $minHours > 0) {
        $lastTakenTimestamp = strtotime($lastTaken);
        $minGapSeconds = $minHours * 3600;
        $nextAvailableTimestamp = $lastTakenTimestamp + $minGapSeconds;
        $timeRemaining = $nextAvailableTimestamp - time();
        
        if ($timeRemaining > 0) {
            $canTakeNow = false;
            // Show date if next dose is on a different day
            $todayEnd = strtotime('tomorrow') - 1;
            if ($nextAvailableTimestamp > $todayEnd) {
                $nextAvailableTime = date(NEXT_DOSE_DATE_FORMAT, $nextAvailableTimestamp);
            } else {
                $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
            }
        }
    }
    
    $prnData[] = [
        'medication' => $med,
        'dose_count' => $doseCount,
        'max_doses' => $maxDoses,
        'last_taken' => $lastTaken,
        'first_taken' => $firstTaken,
        'can_take_now' => $canTakeNow,
        'next_available_time' => $nextAvailableTime,
        'next_available_time_for_max_dose' => $nextAvailableTimeForMaxDose,
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
    
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

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
                        <?php if (!$canTake && $nextTime): ?>
                            <div class="next-dose-info" style="margin-top: 8px; font-size: 14px; color: var(--color-text-secondary);">
                                ⏱️ Next dose available at <span class="countdown" data-target="<?= strtotime($data['last_taken']) + ($data['medication']['min_hours_between_doses'] * 3600) ?>"><?= $nextTime ?></span>
                            </div>
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
                            ⚠️ <strong>Maximum daily dose limit reached.</strong> 
                            <?php if (!empty($data['next_available_time_for_max_dose'])): ?>
                                Next dose available at <?= htmlspecialchars($data['next_available_time_for_max_dose']) ?>.
                            <?php else: ?>
                                Do not take more until 24 hours have passed since your first dose today.
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$canTake && $nextTime): ?>
                        <div class="status-message warning">
                            <small>Minimum time between doses: <?= $med['min_hours_between_doses'] ?> hours</small>
                        </div>
                    <?php else: ?>
                        <div class="status-message success">
                            ✓ You can take <?= $remainingDoses ?> more dose<?= $remainingDoses !== 1 ? 's' : '' ?> today
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn-take-dose" <?= !$canTake ? 'disabled' : '' ?> 
                            onclick="<?= $canTake ? 'showQuantityModal(' . $med['id'] . ', \'' . htmlspecialchars($med['name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($med['dose_amount'] . ' ' . $med['dose_unit'], ENT_QUOTES) . '\', ' . (int)($med['initial_dose'] ?? 1) . ', ' . (int)($med['subsequent_dose'] ?? 1) . ', ' . $doseCount . ')' : '' ?>">
                        <?= $canTake ? '✅ Take Dose Now' : '🚫 Cannot Take Dose' ?>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Quantity Selection Modal -->
    <div id="quantityModal" class="modal">
        <div class="modal-content">
            <h3 id="quantityModalTitle" style="margin: 0 0 16px 0; color: var(--color-primary);">💊 Take Medication</h3>
            <p id="quantityModalDose" style="margin: 0 0 24px 0; color: var(--color-text-secondary);"></p>
            
            <div style="text-align: center; margin-bottom: 24px;">
                <p style="margin: 0 0 8px 0; font-weight: 600;">How many doses to take?</p>
                <p id="quantityDoseInfo" style="margin: 0 0 16px 0; font-size: 14px; color: var(--color-text-secondary);"></p>
                <div class="number-stepper" style="max-width: 200px; margin: 0 auto;">
                    <button type="button" class="stepper-btn" onclick="decrementQuantity()">−</button>
                    <input type="number" id="quantityInput" value="1" min="1" max="10" style="flex: 1; text-align: center; background: var(--color-bg-gray);">
                    <button type="button" class="stepper-btn" onclick="incrementQuantity()">+</button>
                </div>
            </div>
            
            <form id="quantityForm" method="POST" action="/modules/medications/log_prn_handler.php">
                <input type="hidden" name="medication_id" id="quantityMedicationId">
                <input type="hidden" name="quantity_taken" id="quantityTaken" value="1">
                <div class="modal-buttons" style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeQuantityModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Confirm</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
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
        background: none;
    }
    
    .modal.active {
        display: flex;
        background: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
        background: var(--color-bg-white);
        padding: 32px;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        max-width: 400px;
        width: 90%;
    }
    
    .modal-buttons {
        margin-top: 24px;
    }
    
    .next-dose-info {
        font-style: italic;
    }
    </style>
    
    <script>
    let currentMedicationId = null;
    
    function showQuantityModal(medId, medName, doseInfo, initialDose, subsequentDose, doseCount) {
        currentMedicationId = medId;
        document.getElementById('quantityModalTitle').textContent = '💊 Take ' + medName;
        document.getElementById('quantityModalDose').textContent = doseInfo;
        
        // Determine if this is the first dose in the 24-hour period
        const isFirstDose = (doseCount === 0);
        const tabletsPerDose = isFirstDose ? initialDose : subsequentDose;
        const doseType = isFirstDose ? 'initial' : 'subsequent';
        
        // Show dose information
        const doseInfoText = tabletsPerDose > 1 
            ? '(Each dose contains ' + tabletsPerDose + ' tablets)' 
            : '';
        document.getElementById('quantityDoseInfo').textContent = doseInfoText;
        
        document.getElementById('quantityMedicationId').value = medId;
        document.getElementById('quantityInput').value = 1;
        document.getElementById('quantityTaken').value = 1;
        document.getElementById('quantityModal').classList.add('active');
    }
    
    function closeQuantityModal() {
        document.getElementById('quantityModal').classList.remove('active');
        currentMedicationId = null;
    }
    
    function incrementQuantity() {
        const input = document.getElementById('quantityInput');
        const currentValue = parseInt(input.value) || 1;
        if (currentValue < 10) {
            input.value = currentValue + 1;
            document.getElementById('quantityTaken').value = currentValue + 1;
        }
    }
    
    function decrementQuantity() {
        const input = document.getElementById('quantityInput');
        const currentValue = parseInt(input.value) || 1;
        if (currentValue > 1) {
            input.value = currentValue - 1;
            document.getElementById('quantityTaken').value = currentValue - 1;
        }
    }
    
    // Sync input field with hidden field
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.getElementById('quantityInput');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                const value = parseInt(this.value) || 1;
                const clampedValue = Math.max(1, Math.min(10, value));
                this.value = clampedValue;
                document.getElementById('quantityTaken').value = clampedValue;
            });
        }
        
        // Ensure quantity is synced before form submission
        const quantityForm = document.getElementById('quantityForm');
        if (quantityForm) {
            quantityForm.addEventListener('submit', function(e) {
                const input = document.getElementById('quantityInput');
                const hidden = document.getElementById('quantityTaken');
                const value = parseInt(input.value) || 1;
                const clampedValue = Math.max(1, Math.min(10, value));
                hidden.value = clampedValue;
            });
        }
        
        // Close modal on outside click
        document.getElementById('quantityModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuantityModal();
            }
        });
    });
    
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
