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
$stmt = $pdo->prepare("SELECT * FROM medications WHERE user_id = ? AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY created_at DESC");
$stmt->execute([$userId]);
$activeMeds = $stmt->fetchAll();

// Get archived medications (end_date in past)
$stmt = $pdo->prepare("SELECT * FROM medications WHERE user_id = ? AND end_date IS NOT NULL AND end_date < CURDATE() ORDER BY end_date DESC");
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

        <div class="action-buttons">
            <a class="btn btn-primary" href="/modules/medications/add.php">➕ Add Medication</a>
            <a class="btn btn-secondary" href="/dashboard.php">🏠 Back to Dashboard</a>
        </div>

        <?php if (empty($activeMeds) && empty($archivedMeds)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: var(--color-text-secondary); margin: 0;">No medications added yet. Click "Add Medication" to get started.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($activeMeds)): ?>
                <div class="section-header">Your Current Medications</div>
                <div class="dashboard-grid">
                    <?php foreach ($activeMeds as $m): ?>
                        <a class="tile tile-green" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                            <div>
                                <span class="tile-icon">💊</span>
                                <div class="tile-title"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="tile-desc">View details</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($archivedMeds)): ?>
                <div class="section-header" style="margin-top: 32px;">Archived Medications</div>
                <div class="dashboard-grid">
                    <?php foreach ($archivedMeds as $m): ?>
                        <a class="tile tile-red" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                            <div>
                                <span class="tile-icon">📦</span>
                                <div class="tile-title"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="tile-desc">Archived</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
