<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/TimeFormatter.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medicationId = $_GET['medication_id'] ?? 0;
$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);

// Initialize TimeFormatter with current user's preferences
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

// Get medication details
$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ?");
$stmt->execute([$medicationId]);
$medication = $stmt->fetch();

if (!$medication) {
    $_SESSION['error_msg'] = "Medication not found";
    header("Location: /modules/medications/dashboard.php");
    exit;
}

// Check permissions if viewing linked user's medication
if ($medication['user_id'] != $_SESSION['user_id']) {
    if (!$linkedUser || $linkedUser['linked_user_id'] != $medication['user_id']) {
        $_SESSION['error_msg'] = "No permission to view this medication";
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
    
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    if (!$myPermissions || !$myPermissions['can_view_schedule']) {
        $_SESSION['error_msg'] = "No permission to view medication history";
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
}

// Get history (last 90 days)
$stmt = $pdo->prepare("
    SELECT ml.*
    FROM medication_logs ml
    WHERE ml.medication_id = ?
    AND ml.scheduled_date_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    ORDER BY ml.scheduled_date_time DESC
    LIMIT 100
");
$stmt->execute([$medicationId]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication History</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

<div id="main-content">
    <div style="max-width: 800px; margin: 0 auto; padding: 16px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 8px;">
            📜 <?= htmlspecialchars($medication['name']) ?> History
        </h2>
        <p style="color: var(--color-text-secondary); margin-bottom: 24px;">
            Last 90 days of medication logs
        </p>
        
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📜</div>
                    <div class="empty-state-text">No history yet</div>
                </div>
            <?php else: ?>
                <?php $currentTime = time(); // Cache current time for performance ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--color-bg-light);">
                                <th style="text-align: left; padding: 12px;">Date/Time</th>
                                <th style="text-align: left; padding: 12px;">Status</th>
                                <th style="text-align: left; padding: 12px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $log): ?>
                            <tr style="border-bottom: 1px solid var(--color-bg-light);">
                                <td style="padding: 12px;">
                                    <?= $timeFormatter->formatDateTime($log['scheduled_date_time']) ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if ($log['status'] === 'taken'): ?>
                                        <span style="color: #10b981; font-weight: 600;" aria-label="Taken">✓ Taken</span>
                                        <?php if ($log['taken_at']): ?>
                                            <br><small style="color: var(--color-text-secondary);">
                                                at <?= $timeFormatter->formatTime($log['taken_at']) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ($log['status'] === 'skipped'): ?>
                                        <span style="color: #ef4444; font-weight: 600;" aria-label="Skipped">✗ Skipped</span>
                                    <?php else: ?>
                                        <?php // Status is null/pending - check if future or past ?>
                                        <?php 
                                        $isToday = date('Y-m-d', strtotime($log['scheduled_date_time'])) === date('Y-m-d');
                                        if (strtotime($log['scheduled_date_time']) > $currentTime): ?>
                                            <span style="color: #6b7280; font-weight: 600;" aria-label="Pending">⊙ Pending</span>
                                        <?php elseif ($isToday): ?>
                                            <span style="color: #f59e0b; font-weight: 600;" aria-label="Overdue">⏰ Overdue</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444; font-weight: 600;" aria-label="Missed">✗ Missed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if ($log['status'] === 'skipped' && $log['skipped_reason']): ?>
                                        <small style="color: var(--color-text-secondary);">
                                            <?= htmlspecialchars($log['skipped_reason']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/modules/medications/dashboard.php" style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
</div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
