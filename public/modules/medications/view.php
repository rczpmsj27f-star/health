<?php
session_start();
require_once "../../../app/config/database.php";

$medId = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

$dose = $pdo->query("SELECT * FROM medication_doses WHERE medication_id = $medId")->fetch();
$schedule = $pdo->query("SELECT * FROM medication_schedules WHERE medication_id = $medId")->fetch();
$instructions = $pdo->query("SELECT * FROM medication_instructions WHERE medication_id = $medId")->fetchAll();
$alerts = $pdo->query("SELECT * FROM medication_alerts WHERE medication_id = $medId")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($med['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .alerts-header {
            cursor: pointer;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 12px;
            transition: background-color 0.2s;
        }
        
        .alerts-header:hover {
            background: #e9ecef;
        }
        
        .alert-item {
            padding: 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 12px;
            background: #fff;
        }
        
        .alert-item strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
    </style>
</head>
<body class="centered-page">
    <div class="page-card">
        <div class="page-header">
            <h2><?= htmlspecialchars($med['name']) ?></h2>
            <p>Medication Details</p>
        </div>

        <div class="info-item">
            <div class="info-label">Dose</div>
            <div class="info-value"><?= htmlspecialchars($dose['dose_amount']) ?> <?= htmlspecialchars($dose['dose_unit']) ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Schedule</div>
            <div class="info-value">
                <?php if ($schedule['frequency_type'] === 'per_day'): ?>
                    <?= htmlspecialchars($schedule['times_per_day']) ?> time(s) per day
                <?php else: ?>
                    <?= htmlspecialchars($schedule['times_per_week']) ?> time(s) per week
                    <?php if ($schedule['days_of_week']): ?>
                        on <?= htmlspecialchars($schedule['days_of_week']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($instructions)): ?>
        <div class="info-item">
            <div class="info-label">Instructions</div>
            <div class="info-value">
                <ul style="margin: 8px 0; padding-left: 20px;">
                    <?php foreach ($instructions as $i): ?>
                        <li><?= htmlspecialchars($i['instruction_text']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($alerts)): ?>
        <div class="alerts-header" onclick="toggleAlerts()">
            <strong>NHS Alerts (<?= count($alerts) ?>)</strong>
            <span id="arrow">▼</span>
        </div>
        <div id="alerts" style="display:none;">
            <?php foreach ($alerts as $a): ?>
                <div class="alert-item">
                    <strong><?= htmlspecialchars($a['alert_title']) ?></strong>
                    <?= nl2br(htmlspecialchars($a['alert_body'])) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 24px;">
            <a class="btn btn-info" href="/modules/medications/list.php">Back to Medications</a>
        </div>
    </div>

    <script>
    function toggleAlerts() {
        let alertsDiv = document.getElementById("alerts");
        let arrow = document.getElementById("arrow");
        if (alertsDiv.style.display === "none") {
            alertsDiv.style.display = "block";
            arrow.textContent = "▲";
        } else {
            alertsDiv.style.display = "none";
            arrow.textContent = "▼";
        }
    }
    </script>
</body>
</html>
