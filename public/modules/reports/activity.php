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

// Get filter values
$statusFilter = $_GET['status'] ?? '';
$daysFilter = (int)($_GET['days'] ?? 30);
$userFilter = $_GET['user_filter'] ?? '';

// Validate status filter - only allow specific values
if ($statusFilter && !in_array($statusFilter, ['taken', 'skipped'], true)) {
    $statusFilter = '';
}

// Validate user filter - only allow specific values
if ($userFilter && !in_array($userFilter, ['me', 'partner'], true)) {
    $userFilter = '';
}

// Build WHERE conditions safely
// Note: Only hardcoded SQL strings are added to $whereClauses, never user input.
// All dynamic values use prepared statement parameters (?).
$whereClauses = ["m.user_id IN (?, ?)"];
$params = [$_SESSION['user_id'], $linkedUser['linked_user_id']];

if ($statusFilter) {
    $whereClauses[] = "ml.status = ?";
    $params[] = $statusFilter;
}

if ($userFilter === 'me') {
    $whereClauses[] = "m.user_id = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($userFilter === 'partner') {
    $whereClauses[] = "m.user_id = ?";
    $params[] = $linkedUser['linked_user_id'];
}

$whereSQL = implode(' AND ', $whereClauses);

// Get combined activity for both users
$stmt = $pdo->prepare("
    SELECT 
        ml.id,
        ml.scheduled_date_time,
        ml.taken_at,
        ml.status,
        ml.medication_id,
        m.name as medication_name,
        m.user_id as med_owner_id,
        u1.first_name as owner_name,
        ml.created_at
    FROM medication_logs ml
    JOIN medications m ON ml.medication_id = m.id
    JOIN users u1 ON m.user_id = u1.id
    WHERE $whereSQL
    AND ml.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY ml.created_at DESC
    LIMIT 50
");

$params[] = $daysFilter;
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get medication CRUD activity (add, edit, delete)
$stmtCrud = $pdo->prepare("
    SELECT 
        'medication_added' as activity_type,
        m.id as medication_id,
        m.name as medication_name,
        m.user_id as med_owner_id,
        u.first_name as owner_name,
        m.created_at as activity_time
    FROM medications m
    JOIN users u ON m.user_id = u.id
    WHERE m.user_id IN (?, ?)
    AND m.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    
    UNION ALL
    
    SELECT 
        'medication_edited' as activity_type,
        m.id as medication_id,
        m.name as medication_name,
        m.user_id as med_owner_id,
        u.first_name as owner_name,
        m.updated_at as activity_time
    FROM medications m
    JOIN users u ON m.user_id = u.id
    WHERE m.user_id IN (?, ?)
    AND m.updated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    AND m.updated_at != m.created_at
    
    ORDER BY activity_time DESC
    LIMIT 20
");
$stmtCrud->execute([
    $_SESSION['user_id'], $linkedUser['linked_user_id'], $daysFilter,
    $_SESSION['user_id'], $linkedUser['linked_user_id'], $daysFilter
]);
$crudActivities = $stmtCrud->fetchAll();

// Merge medication logs and CRUD activities
$allActivities = array_merge($activities, $crudActivities);

// Sort by time
usort($allActivities, function($a, $b) {
    // For CRUD activities, use activity_time
    // For log activities, use created_at (which should be available)
    $timeA = $a['activity_time'] ?? $a['created_at'] ?? '';
    $timeB = $b['activity_time'] ?? $b['created_at'] ?? '';
    
    // Push empty timestamps to the end
    if (empty($timeA) && empty($timeB)) return 0;
    if (empty($timeA)) return 1;  // A goes after B
    if (empty($timeB)) return -1; // B goes after A
    
    return strtotime($timeB) - strtotime($timeA);
});

$allActivities = array_slice($allActivities, 0, 50);
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
            Feed for your medications
        </p>
        
        <!-- Filters -->
        <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: flex-end;">
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="">All Statuses</option>
                        <option value="taken" <?= ($_GET['status'] ?? '') === 'taken' ? 'selected' : '' ?>>Taken</option>
                        <option value="skipped" <?= ($_GET['status'] ?? '') === 'skipped' ? 'selected' : '' ?>>Skipped</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Days</label>
                    <select name="days" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="7" <?= ($_GET['days'] ?? '30') == '7' ? 'selected' : '' ?>>Last 7 days</option>
                        <option value="30" <?= ($_GET['days'] ?? '30') == '30' ? 'selected' : '' ?>>Last 30 days</option>
                        <option value="60" <?= ($_GET['days'] ?? '30') == '60' ? 'selected' : '' ?>>Last 60 days</option>
                        <option value="90" <?= ($_GET['days'] ?? '30') == '90' ? 'selected' : '' ?>>Last 90 days</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">User</label>
                    <select name="user_filter" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="">Both Users</option>
                        <option value="me" <?= ($_GET['user_filter'] ?? '') === 'me' ? 'selected' : '' ?>>My Activity</option>
                        <option value="partner" <?= ($_GET['user_filter'] ?? '') === 'partner' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s Activity
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                    🔍 Filter
                </button>
            </form>
        </div>
        
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <?php if (empty($allActivities)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📰</div>
                    <div class="empty-state-text">No activity yet</div>
                </div>
            <?php else: ?>
                <?php foreach ($allActivities as $activity): 
                    $isOwn = $activity['med_owner_id'] == $_SESSION['user_id'];
                    
                    // Determine activity type and color
                    if (isset($activity['activity_type'])) {
                        // CRUD activity
                        $borderColor = '#6366f1';
                        $activityText = '';
                        
                        switch ($activity['activity_type']) {
                            case 'medication_added':
                                $activityText = 'added medication';
                                $borderColor = '#10b981';
                                break;
                            case 'medication_edited':
                                $activityText = 'updated medication';
                                $borderColor = '#f59e0b';
                                break;
                            case 'medication_deleted':
                                $activityText = 'deleted medication';
                                $borderColor = '#ef4444';
                                break;
                        }
                        
                        $displayTime = $activity['activity_time'];
                    } else {
                        // Log activity (taken/skipped)
                        $borderColor = $activity['status'] === 'taken' ? '#10b981' : '#ef4444';
                        $activityText = $activity['status'] === 'taken' ? 'took' : 'skipped';
                        // Use taken_at if available, otherwise use created_at, finally fallback to scheduled_date_time
                        $displayTime = $activity['taken_at'] ?? $activity['created_at'] ?? $activity['scheduled_date_time'];
                    }
                ?>
                <div style="padding: 16px; margin-bottom: 12px; border-left: 4px solid <?= $borderColor ?>; background: var(--color-bg-light); border-radius: 0 6px 6px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <strong style="color: var(--color-text);">
                            <?= $isOwn ? 'You' : htmlspecialchars($activity['owner_name']) ?>
                            <span style="color: <?= $borderColor ?>;"><?= $activityText ?></span>
                            <?= htmlspecialchars($activity['medication_name']) ?>
                        </strong>
                        <small style="color: var(--color-text-secondary);">
                            <?= $timeFormatter->formatDateTime($displayTime) ?>
                        </small>
                    </div>
                    
                    <?php if (isset($activity['scheduled_date_time'])): ?>
                    <small style="color: var(--color-text-secondary);">
                        Scheduled for <?= $timeFormatter->formatDateTime($activity['scheduled_date_time']) ?>
                    </small>
                    <?php endif; ?>
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
