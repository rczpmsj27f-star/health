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

$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);

// Initialize TimeFormatter with current user's preferences
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

if (!$linkedUser || $linkedUser['status'] !== 'active') {
    $_SESSION['error_msg'] = "No active linked user";
    header("Location: /modules/medications/dashboard.php");
    exit;
}

// Get combined activity for both users (last 30 days)
$stmt = $pdo->prepare("
    SELECT 
        ml.id,
        ml.scheduled_date_time,
        ml.taken_at,
        ml.status,
        ml.medication_id,
        m.name as medication_name,
        m.user_id as med_owner_id,
        u1.first_name as owner_name
    FROM medication_logs ml
    JOIN medications m ON ml.medication_id = m.id
    JOIN users u1 ON m.user_id = u1.id
    WHERE m.user_id IN (?, ?)
    AND ml.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY ml.created_at DESC
    LIMIT 50
");
$stmt->execute([$_SESSION['user_id'], $linkedUser['linked_user_id']]);
$activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div style="max-width: 800px; margin: 0 auto; padding: 80px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 8px;">
            📰 Activity Feed
        </h2>
        <p style="color: var(--color-text-secondary); margin-bottom: 24px;">
            Combined medication activity for you and <?= htmlspecialchars($linkedUser['linked_user_name']) ?>
        </p>
        
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <?php if (empty($activities)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📰</div>
                    <div class="empty-state-text">No activity yet</div>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): 
                    $isOwn = $activity['med_owner_id'] == $_SESSION['user_id'];
                ?>
                <div style="padding: 16px; margin-bottom: 12px; border-left: 4px solid <?= $activity['status'] === 'taken' ? '#10b981' : '#ef4444' ?>; background: var(--color-bg-light); border-radius: 0 6px 6px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <strong style="color: var(--color-text);">
                            <?= $isOwn ? 'You' : htmlspecialchars($activity['owner_name']) ?>
                            <?php if ($activity['status'] === 'taken'): ?>
                                <span style="color: #10b981;">took</span>
                            <?php else: ?>
                                <span style="color: #ef4444;">skipped</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($activity['medication_name']) ?>
                        </strong>
                        <small style="color: var(--color-text-secondary);">
                            <?= $timeFormatter->formatDateTime($activity['taken_at'] ?? $activity['scheduled_date_time']) ?>
                        </small>
                    </div>
                    <small style="color: var(--color-text-secondary);">
                        Scheduled for <?= $timeFormatter->formatDateTime($activity['scheduled_date_time']) ?>
                    </small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/modules/medications/dashboard.php" style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
