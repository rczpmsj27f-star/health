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
$medicationId = $_GET['medication_id'] ?? null;

if (!$medicationId) {
    $_SESSION['error'] = "No medication specified";
    header("Location: /modules/medications/dashboard.php");
    exit;
}

// Get medication details
$stmt = $pdo->prepare("
    SELECT m.*, md.dose_amount, md.dose_unit, 
           ms.max_doses_per_day, ms.min_hours_between_doses, 
           ms.initial_dose, ms.subsequent_dose, ms.doses_per_administration,
           m.notes, m.instructions
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    WHERE m.id = ? AND m.user_id = ? 
    AND (m.archived = 0 OR m.archived IS NULL)
    AND ms.is_prn = 1
");
$stmt->execute([$medicationId, $userId]);
$medication = $stmt->fetch();

if (!$medication) {
    $_SESSION['error'] = "Medication not found or not a PRN medication";
    header("Location: /modules/medications/dashboard.php");
    exit;
}

// Get dose count in last 24 hours and last dose time
$stmt = $pdo->prepare("
    SELECT COUNT(*) as dose_count, MAX(taken_at) as last_taken, MIN(taken_at) as first_taken
    FROM medication_logs 
    WHERE medication_id = ? 
    AND user_id = ?
    AND taken_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND status = 'taken'
");
$stmt->execute([$medicationId, $userId]);
$logData = $stmt->fetch();

$doseCount = $logData['dose_count'] ?? 0;
$lastTaken = $logData['last_taken'];
$firstTaken = $logData['first_taken'];
$maxDoses = $medication['max_doses_per_day'] ?? 999;
$minHours = $medication['min_hours_between_doses'] ?? 0;

// Determine recommended dose amount (initial vs subsequent)
$recommendedDose = $doseCount == 0 ? 
    ($medication['initial_dose'] ?? 1) : 
    ($medication['subsequent_dose'] ?? 1);

// Calculate if can take now
$canTakeNow = true;
$nextAvailableTime = null;
$timeRemaining = 0;
$reasonNotAvailable = '';

// Check max doses
if ($doseCount >= $maxDoses) {
    $canTakeNow = false;
    $reasonNotAvailable = "You've reached the maximum of $maxDoses doses per day";
    if ($firstTaken) {
        $firstTakenTimestamp = strtotime($firstTaken);
        $nextAvailableTimestamp = $firstTakenTimestamp + (24 * 3600);
        $nextAvailableTime = date('H:i \o\n d M', $nextAvailableTimestamp);
    }
}

// Check minimum time between doses
if ($canTakeNow && $lastTaken && $minHours > 0) {
    $lastTakenTimestamp = strtotime($lastTaken);
    $minGapSeconds = $minHours * 3600;
    $nextAvailableTimestamp = $lastTakenTimestamp + $minGapSeconds;
    $timeRemaining = $nextAvailableTimestamp - time();
    
    if ($timeRemaining > 0) {
        $canTakeNow = false;
        $nextAvailableTime = date('H:i', $nextAvailableTimestamp);
        $hours = floor($minHours);
        $minutes = ($minHours - $hours) * 60;
        $timeDesc = $hours > 0 ? "$hours hour" . ($hours > 1 ? 's' : '') : '';
        if ($minutes > 0) {
            $timeDesc .= ($timeDesc ? ' ' : '') . round($minutes) . ' minutes';
        }
        $reasonNotAvailable = "You must wait at least $timeDesc between doses";
    }
}

$remainingDoses = max(0, $maxDoses - $doseCount);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take PRN - <?= htmlspecialchars($medication['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/medication-icons.js?v=<?= time() ?>"></script>
    <style>
        .calculator-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 80px 16px 40px 16px;
        }
        
        .calculator-card {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 32px 24px;
        }
        
        .med-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .med-icon-display {
            margin-bottom: 16px;
        }
        
        .med-name {
            font-size: 28px;
            font-weight: 600;
            color: var(--color-text);
            margin: 0 0 8px 0;
        }
        
        .med-dose-info {
            color: var(--color-text-secondary);
            font-size: 16px;
        }
        
        .dose-status {
            background: var(--color-bg-gray);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin: 20px 0;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 12px;
        }
        
        .status-item {
            text-align: center;
        }
        
        .status-label {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-bottom: 4px;
        }
        
        .status-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .dose-calculator {
            margin: 24px 0;
        }
        
        .dose-input-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 20px 0;
        }
        
        .dose-btn {
            width: 50px;
            height: 50px;
            border: 2px solid var(--color-primary);
            background: var(--color-bg-white);
            color: var(--color-primary);
            font-size: 28px;
            font-weight: bold;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .dose-btn:hover:not(:disabled) {
            background: var(--color-primary);
            color: var(--color-bg-white);
        }
        
        .dose-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .dose-input {
            width: 80px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-bg-gray);
        }
        
        .alert-box {
            padding: 16px;
            border-radius: var(--radius-sm);
            margin: 16px 0;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .info-box {
            margin: 16px 0;
        }
        
        .info-toggle {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 12px;
            width: 100%;
            text-align: left;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .info-toggle:hover {
            background: #bbdefb;
        }
        
        .toggle-icon {
            float: right;
        }
        
        .info-content {
            padding: 12px;
            border: 1px solid #90caf9;
            border-top: none;
            border-radius: 0 0 6px 6px;
            background: #f5f5f5;
            transition: all 0.3s ease;
        }
        
        .info-content.hidden {
            display: none;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-submit.btn-primary {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-submit.btn-primary:hover {
            background: var(--color-primary-dark);
        }
        
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>
    
    <div class="calculator-container">
        <a href="/modules/medications/dashboard.php" class="btn-back">← Back to Dashboard</a>
        
        <div class="calculator-card">
            <div class="med-header">
                <div class="med-icon-display">
                    <?= renderMedicationIcon(
                        $medication['icon'] ?? 'pill', 
                        $medication['color'] ?? '#5b21b6', 
                        '48px',
                        $medication['secondary_color'] ?? null
                    ) ?>
                </div>
                <h2 class="med-name"><?= htmlspecialchars($medication['name']) ?></h2>
                <div class="med-dose-info">
                    <?= htmlspecialchars(rtrim(rtrim(number_format($medication['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $medication['dose_unit']) ?>
                </div>
            </div>
            
            <!-- Expandable Information Box (Issue #68) -->
            <?php if (!empty($medication['notes']) || !empty($medication['instructions'])): ?>
            <div class="info-box">
                <button type="button" 
                        class="info-toggle" 
                        onclick="toggleInfo()">
                    ℹ️ Additional Information
                    <span class="toggle-icon" id="toggle-icon">▼</span>
                </button>
                <div class="info-content hidden" id="info-content">
                    <?php if (!empty($medication['instructions'])): ?>
                        <p><strong>Instructions:</strong> <?= htmlspecialchars($medication['instructions']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($medication['notes'])): ?>
                        <p style="margin-bottom: 0;"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($medication['notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dose Status -->
            <div class="dose-status">
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-label">Taken Today</div>
                        <div class="status-value"><?= $doseCount ?> / <?= $maxDoses ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Remaining</div>
                        <div class="status-value"><?= $remainingDoses ?></div>
                    </div>
                </div>
                <?php if ($lastTaken): ?>
                <div style="text-align: center; font-size: 12px; color: var(--color-text-secondary); margin-top: 8px;">
                    Last taken: <?= date('H:i \o\n d M', strtotime($lastTaken)) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!$canTakeNow): ?>
                <div class="alert-box alert-danger">
                    <strong>⊘ Not Available</strong><br>
                    <?= htmlspecialchars($reasonNotAvailable) ?>
                    <?php if ($nextAvailableTime): ?>
                        <br>Next dose available at: <strong><?= $nextAvailableTime ?></strong>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Dose Calculator -->
                <form method="POST" action="/modules/medications/log_prn_handler.php" id="doseForm">
                    <input type="hidden" name="medication_id" value="<?= $medication['id'] ?>">
                    
                    <div class="dose-calculator">
                        <div style="text-align: center; margin-bottom: 12px;">
                            <strong>How many to take?</strong>
                            <?php if ($doseCount == 0 && $medication['initial_dose']): ?>
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">
                                    Recommended first dose: <?= $medication['initial_dose'] ?>
                                </div>
                            <?php elseif ($medication['subsequent_dose']): ?>
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">
                                    Recommended dose: <?= $medication['subsequent_dose'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="dose-input-group">
                            <button type="button" class="dose-btn" onclick="decrementDose()" id="btn-minus">−</button>
                            <input type="number" 
                                   name="dose_amount" 
                                   id="dose_amount" 
                                   class="dose-input" 
                                   min="1" 
                                   max="10" 
                                   value="<?= $recommendedDose ?>"
                                   inputmode="numeric"
                                   required>
                            <button type="button" class="dose-btn" onclick="incrementDose()" id="btn-plus">+</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit btn-primary">
                        ✓ Take Dose Now
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($canTakeNow && $remainingDoses <= 2 && $remainingDoses > 0): ?>
                <div class="alert-box alert-warning">
                    ⚠️ Warning: Only <?= $remainingDoses ?> dose<?= $remainingDoses > 1 ? 's' : '' ?> remaining today
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function incrementDose() {
            const input = document.getElementById('dose_amount');
            const current = parseInt(input.value) || 1;
            if (current < 10) {
                input.value = current + 1;
            }
        }
        
        function decrementDose() {
            const input = document.getElementById('dose_amount');
            const current = parseInt(input.value) || 1;
            if (current > 1) {
                input.value = current - 1;
            }
        }
        
        function toggleInfo() {
            const content = document.getElementById('info-content');
            const icon = document.getElementById('toggle-icon');
            
            content.classList.toggle('hidden');
            icon.textContent = content.classList.contains('hidden') ? '▼' : '▲';
        }
    </script>
</body>
</html>
