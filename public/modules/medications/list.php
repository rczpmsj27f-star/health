<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM medications WHERE user_id = ?");
$stmt->execute([$userId]);
$meds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Management</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: #333;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        @media (min-width: 576px) {
            .action-buttons {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="page-title">
            <h2>Your Medications</h2>
            <p>Manage your medication schedule</p>
        </div>

        <div class="action-buttons">
            <a class="btn btn-accept" href="/modules/medications/add.php">Add Medication</a>
            <a class="btn btn-info" href="/dashboard.php">Back to Dashboard</a>
        </div>

        <?php if (empty($meds)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: #666; margin: 0;">No medications added yet. Click "Add Medication" to get started.</p>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($meds as $m): ?>
                    <a class="tile" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 4px;"><?= htmlspecialchars($m['name']) ?></div>
                            <div style="font-size: 13px; color: #666;">View details</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
