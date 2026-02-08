<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);

// Determine which user to view
$viewingLinkedUser = isset($_GET['view']) && $_GET['view'] === 'linked' && $linkedUser;
$targetUserId = $viewingLinkedUser ? $linkedUser['linked_user_id'] : $_SESSION['user_id'];

if ($viewingLinkedUser) {
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    if (!$myPermissions || !$myPermissions['can_view_schedule']) {
        $_SESSION['error_msg'] = "You don't have permission to view their reports";
        header("Location: /modules/medications/dashboard.php");
        exit;
    }
}

// Date range (default: last 30 days)
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));

// Get compliance data
$stmt = $pdo->prepare("
    SELECT 
        m.id,
        m.name,
        COUNT(DISTINCT DATE(ml.scheduled_date_time)) as days_logged,
        SUM(CASE WHEN ml.status = 'taken' THEN 1 ELSE 0 END) as taken_count,
        SUM(CASE WHEN ml.status = 'skipped' THEN 1 ELSE 0 END) as skipped_count,
        COUNT(*) as total_doses,
        ROUND(SUM(CASE WHEN ml.status = 'taken' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as compliance_rate
    FROM medications m
    LEFT JOIN medication_logs ml ON m.id = ml.medication_id 
        AND ml.scheduled_date_time BETWEEN ? AND ?
    WHERE m.user_id = ?
    AND m.is_prn = 0
    GROUP BY m.id, m.name
    ORDER BY compliance_rate DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $targetUserId]);
$complianceData = $stmt->fetchAll();

// Overall compliance
$overallTaken = array_sum(array_column($complianceData, 'taken_count'));
$overallTotal = array_sum(array_column($complianceData, 'total_doses'));
$overallCompliance = $overallTotal > 0 ? round(($overallTaken / $overallTotal) * 100, 1) : 0;

// Get user name
$stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();
$targetUserName = $targetUser ? $targetUser['first_name'] : 'User';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Report</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .compliance-bar {
            height: 24px;
            background: #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .compliance-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
        }
        
        .compliance-fill.medium {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }
        
        .compliance-fill.low {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: var(--color-primary);
        }
        
        .stat-label {
            color: var(--color-text-secondary);
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div style="max-width: 1000px; margin: 0 auto; padding: 80px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 8px;">
            📊 <?= $viewingLinkedUser ? htmlspecialchars($targetUserName) . "'s " : 'My ' ?>Compliance Report
        </h2>
        <p style="color: var(--color-text-secondary); margin-bottom: 24px;">
            Medication adherence from <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?>
        </p>
        
        <!-- Date Range Filter -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                <?php if ($viewingLinkedUser): ?>
                    <input type="hidden" name="view" value="linked">
                <?php endif; ?>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Start Date</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">End Date</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <button type="submit" class="btn btn-primary">Update Report</button>
            </form>
        </div>
        
        <!-- Overall Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-number"><?= $overallCompliance ?>%</div>
                <div class="stat-label">Overall Compliance</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #10b981;"><?= $overallTaken ?></div>
                <div class="stat-label">Doses Taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;">
                    <?= array_sum(array_column($complianceData, 'skipped_count')) ?>
                </div>
                <div class="stat-label">Doses Skipped</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #6366f1;"><?= $overallTotal ?></div>
                <div class="stat-label">Total Doses</div>
            </div>
        </div>
        
        <!-- Per-Medication Compliance -->
        <div style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: var(--color-primary);">Compliance by Medication</h3>
            
            <?php if (empty($complianceData)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <div class="empty-state-text">No data for this period</div>
                </div>
            <?php else: ?>
                <?php foreach ($complianceData as $med): 
                    $rate = $med['compliance_rate'];
                    $fillClass = $rate >= 80 ? '' : ($rate >= 60 ? 'medium' : 'low');
                ?>
                <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <strong><?= htmlspecialchars($med['name']) ?></strong>
                        <span style="font-weight: 600; color: var(--color-primary);"><?= $rate ?>%</span>
                    </div>
                    
                    <div class="compliance-bar">
                        <div class="compliance-fill <?= $fillClass ?>" style="width: <?= $rate ?>%;">
                            <span style="color: white; font-size: 12px; font-weight: 600;">
                                <?php if ($rate > 15): ?><?= $rate ?>%<?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 16px; margin-top: 8px; font-size: 13px; color: var(--color-text-secondary);">
                        <span>✓ Taken: <?= $med['taken_count'] ?></span>
                        <span>✗ Skipped: <?= $med['skipped_count'] ?></span>
                        <span>📅 Days: <?= $med['days_logged'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/modules/medications/dashboard.php<?= $viewingLinkedUser ? '?view=linked' : '' ?>" 
               style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
