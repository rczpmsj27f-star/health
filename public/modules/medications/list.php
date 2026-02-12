<?php
require_once "../../../app/includes/cache-buster.php";
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/medication_icon.php";

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

// Check permissions if viewing linked user
if ($viewingLinkedUser) {
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    if (!$myPermissions || !$myPermissions['can_view_medications']) {
        $_SESSION['error_msg'] = "You don't have permission to view their medications";
        header("Location: /modules/medications/list.php");
        exit;
    }
}

// Get active medications (not archived)
$stmt = $pdo->prepare("
    SELECT m.*, ms.frequency_type, ms.times_per_day, ms.times_per_week, ms.days_of_week, ms.is_prn 
    FROM medications m 
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id 
    WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL) 
    ORDER BY m.created_at DESC
");
$stmt->execute([$targetUserId]);
$allActiveMeds = $stmt->fetchAll();

// Separate into scheduled and PRN medications
$scheduledMeds = [];
$prnMeds = [];
foreach ($allActiveMeds as $med) {
    if (!empty($med['is_prn'])) {
        $prnMeds[] = $med;
    } else {
        $scheduledMeds[] = $med;
    }
}

// Get archived medications
$stmt = $pdo->prepare("
    SELECT m.*, ms.frequency_type, ms.times_per_day, ms.times_per_week, ms.days_of_week, ms.is_prn 
    FROM medications m 
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id 
    WHERE m.user_id = ? AND m.archived = 1 
    ORDER BY m.archived_at DESC
");
$stmt->execute([$targetUserId]);
$archivedMeds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Management</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/modal.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
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
    </style>
    <script>
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.toggle('expanded');
        }
        
        // Check for success messages from session
        window.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['success'])): ?>
                showSuccessModal('<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES) ?>');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div class="page-content">
        <div class="page-title">
            <h2>💊 Medication Management</h2>
            <p>Track and manage your medications</p>
        </div>

        <?php include __DIR__ . '/../../../app/includes/user_switcher.php'; ?>

        <?php if (empty($scheduledMeds) && empty($prnMeds) && empty($archivedMeds)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: var(--color-text-secondary); margin: 0;">No medications added yet. Click "Add Medication" to get started.</p>
            </div>
        <?php else: ?>
            <!-- Current Medications Section -->
            <h3 style="margin: 24px 0 16px 0; padding-left: 16px; color: #333; font-size: 20px; font-weight: 600;">Current Medications</h3>
            
            <?php if (!empty($scheduledMeds)): ?>
                <div class="expandable-section expanded" id="scheduledSection">
                    <div class="section-header-toggle" onclick="toggleSection('scheduledSection')">
                        <span class="toggle-icon">▶</span>
                        <span>Scheduled Medications (<?= count($scheduledMeds) ?>)</span>
                    </div>
                    <div class="section-content">
                        <div style="padding: 0 16px;">
                            <?php foreach ($scheduledMeds as $m): ?>
                                <a class="medication-tile-fullwidth" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                                    <div class="medication-tile-line1">
                                        <?= renderMedicationIcon($m['icon'] ?? 'pill', $m['color'] ?? '#5b21b6', '20px', $m['secondary_color'] ?? null) ?>
                                        <span><?= htmlspecialchars($m['name']) ?></span>
                                    </div>
                                    <div class="medication-tile-line2">
                                        <strong>Schedule:</strong> 
                                        <?php if ($m['frequency_type']): ?>
                                            <?php if ($m['frequency_type'] === 'per_day'): ?>
                                                <?= htmlspecialchars($m['times_per_day']) ?> time<?= $m['times_per_day'] > 1 ? 's' : '' ?> per day
                                            <?php else: ?>
                                                <?= htmlspecialchars($m['times_per_week']) ?> time<?= $m['times_per_week'] > 1 ? 's' : '' ?> per week
                                                <?php if ($m['days_of_week']): ?>
                                                    on <?= htmlspecialchars($m['days_of_week']) ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Not scheduled
                                        <?php endif; ?>
                                    </div>
                                    <div class="medication-tile-line3">
                                        <strong>Added:</strong> <?= date('M d, Y', strtotime($m['created_at'])) ?>
                                        <?php if ($m['end_date']): ?>
                                            | <strong>Expiry:</strong> <?= date('M d, Y', strtotime($m['end_date'])) ?>
                                        <?php endif; ?>
                                        <?php if ($m['current_stock']): ?>
                                            | <strong>Stock:</strong> <?= htmlspecialchars($m['current_stock']) ?>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($prnMeds)): ?>
                <div class="expandable-section expanded" id="prnSection">
                    <div class="section-header-toggle" onclick="toggleSection('prnSection')">
                        <span class="toggle-icon">▶</span>
                        <span>PRN Medications (<?= count($prnMeds) ?>)</span>
                    </div>
                    <div class="section-content">
                        <div style="padding: 0 16px;">
                            <?php foreach ($prnMeds as $m): ?>
                                <a class="medication-tile-fullwidth" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                                    <div class="medication-tile-line1">
                                        <?= renderMedicationIcon($m['icon'] ?? 'pill', $m['color'] ?? '#5b21b6', '20px', $m['secondary_color'] ?? null) ?>
                                        <span><?= htmlspecialchars($m['name']) ?></span>
                                    </div>
                                    <div class="medication-tile-line2">
                                        <strong>Schedule:</strong> As and when needed (PRN)
                                    </div>
                                    <div class="medication-tile-line3">
                                        <strong>Added:</strong> <?= date('M d, Y', strtotime($m['created_at'])) ?>
                                        <?php if ($m['end_date']): ?>
                                            | <strong>Expiry:</strong> <?= date('M d, Y', strtotime($m['end_date'])) ?>
                                        <?php endif; ?>
                                        <?php if ($m['current_stock']): ?>
                                            | <strong>Stock:</strong> <?= htmlspecialchars($m['current_stock']) ?>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($archivedMeds)): ?>
                <div class="expandable-section collapsed" id="archivedSection">
                    <div class="section-header-toggle" onclick="toggleSection('archivedSection')">
                        <span class="toggle-icon">▶</span>
                        <span>Archived Medications (<?= count($archivedMeds) ?>)</span>
                    </div>
                    <div class="section-content">
                        <div style="padding: 16px;">
                            <?php foreach ($archivedMeds as $m): ?>
                                <a class="medication-tile-fullwidth" href="/modules/medications/view.php?id=<?= $m['id'] ?>" style="background: #ffebee; border-left-color: #e57373;">
                                    <div class="medication-tile-line1">
                                        <?= renderMedicationIcon($m['icon'] ?? 'pill', $m['color'] ?? '#5b21b6', '20px', $m['secondary_color'] ?? null) ?>
                                        <span><?= htmlspecialchars($m['name']) ?></span>
                                    </div>
                                    <div class="medication-tile-line2">
                                        <strong>Schedule:</strong> 
                                        <?php if (!empty($m['is_prn'])): ?>
                                            As and when needed (PRN)
                                        <?php elseif ($m['frequency_type']): ?>
                                            <?php if ($m['frequency_type'] === 'per_day'): ?>
                                                <?= htmlspecialchars($m['times_per_day']) ?> time<?= $m['times_per_day'] > 1 ? 's' : '' ?> per day
                                            <?php else: ?>
                                                <?= htmlspecialchars($m['times_per_week']) ?> time<?= $m['times_per_week'] > 1 ? 's' : '' ?> per week
                                                <?php if ($m['days_of_week']): ?>
                                                    on <?= htmlspecialchars($m['days_of_week']) ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Not scheduled
                                        <?php endif; ?>
                                    </div>
                                    <div class="medication-tile-line3">
                                        <strong>Added:</strong> <?= date('M d, Y', strtotime($m['created_at'])) ?>
                                        <?php if ($m['end_date']): ?>
                                            | <strong>Expiry:</strong> <?= date('M d, Y', strtotime($m['end_date'])) ?>
                                        <?php endif; ?>
                                        <?php if ($m['current_stock']): ?>
                                            | <strong>Stock:</strong> <?= htmlspecialchars($m['current_stock']) ?>
                                        <?php endif; ?>
                                        <?php if ($m['archived_at']): ?>
                                            | <strong>Archived:</strong> <?= date('M d, Y', strtotime($m['archived_at'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Manage Stock Section -->
            <h3 style="margin: 32px 0 16px 0; padding-left: 16px; color: #333; font-size: 20px; font-weight: 600;">Manage Stock</h3>
            <div style="padding: 0 16px;">
                <a class="btn btn-secondary" href="/modules/medications/stock.php" style="display: inline-block; margin-bottom: 16px;">📦 View Stock Levels</a>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons" style="margin-top: 32px;">
            <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add Medication</a>
        </div>
    </div>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
