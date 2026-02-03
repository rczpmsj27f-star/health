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

// Get active medications (no end_date or end_date in future)
$stmt = $pdo->prepare("
    SELECT m.*, ms.frequency_type, ms.times_per_day, ms.times_per_week, ms.days_of_week 
    FROM medications m 
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id 
    WHERE m.user_id = ? AND (m.end_date IS NULL OR m.end_date >= CURDATE()) 
    ORDER BY m.created_at DESC
");
$stmt->execute([$userId]);
$activeMeds = $stmt->fetchAll();

// Get archived medications (end_date in past)
$stmt = $pdo->prepare("
    SELECT m.*, ms.frequency_type, ms.times_per_day, ms.times_per_week, ms.days_of_week 
    FROM medications m 
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id 
    WHERE m.user_id = ? AND m.end_date IS NOT NULL AND m.end_date < CURDATE() 
    ORDER BY m.end_date DESC
");
$stmt->execute([$userId]);
$archivedMeds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Management</title>
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
        
        /* Compact tiles for medication list */
        .medications-list .tile {
            min-height: 80px !important;
            padding: 16px !important;
        }
        
        .medications-list .tile .tile-icon {
            font-size: 30px !important;
            margin-bottom: 8px !important;
        }
        
        .medications-list .tile .tile-title {
            font-size: 16px !important;
            margin-bottom: 4px !important;
        }
        
        .medications-list .tile .tile-desc {
            font-size: 12px !important;
        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        <a href="/modules/medications/list.php">💊 Medications</a>
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="page-content">
        <div class="page-title">
            <h2>💊 Medication Management</h2>
            <p>Track and manage your medications</p>
        </div>

        <?php if (empty($activeMeds) && empty($archivedMeds)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: var(--color-text-secondary); margin: 0;">No medications added yet. Click "Add Medication" to get started.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($activeMeds)): ?>
                <div class="section-header">Your Current Medications</div>
                <div class="dashboard-grid medications-list">
                    <?php foreach ($activeMeds as $m): ?>
                        <a class="tile tile-green" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                            <div>
                                <span class="tile-icon">💊</span>
                                <div class="tile-title"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="tile-desc">View details</div>
                                <?php if ($m['frequency_type']): ?>
                                    <div class="tile-schedule">
                                        <?php if ($m['frequency_type'] === 'per_day'): ?>
                                            <?= htmlspecialchars($m['times_per_day']) ?>x daily
                                        <?php else: ?>
                                            <?= htmlspecialchars($m['times_per_week']) ?>x weekly
                                            <?php if ($m['days_of_week']): ?>
                                                (<?= htmlspecialchars($m['days_of_week']) ?>)
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($archivedMeds)): ?>
                <div class="section-header" style="margin-top: 32px;">Archived Medications</div>
                <div class="dashboard-grid medications-list">
                    <?php foreach ($archivedMeds as $m): ?>
                        <a class="tile tile-red" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                            <div>
                                <span class="tile-icon">📦</span>
                                <div class="tile-title"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="tile-desc">Archived</div>
                                <?php if ($m['frequency_type']): ?>
                                    <div class="tile-schedule">
                                        <?php if ($m['frequency_type'] === 'per_day'): ?>
                                            <?= htmlspecialchars($m['times_per_day']) ?>x daily
                                        <?php else: ?>
                                            <?= htmlspecialchars($m['times_per_week']) ?>x weekly
                                            <?php if ($m['days_of_week']): ?>
                                                (<?= htmlspecialchars($m['days_of_week']) ?>)
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="action-buttons" style="margin-top: 32px;">
            <a class="btn btn-primary" href="/modules/medications/add.php">➕ Add Medication</a>
            <a class="btn btn-secondary" href="/dashboard.php">🏠 Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
