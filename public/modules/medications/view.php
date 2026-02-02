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
    <title><?= htmlspecialchars($med['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2><?= htmlspecialchars($med['name']) ?></h2>

    <h3>Dose</h3>
    <?= $dose['dose_amount'] . " " . $dose['dose_unit'] ?>

    <h3>Schedule</h3>
    <?= $schedule['frequency_type'] ?>

    <h3>Instructions</h3>
    <ul>
        <?php foreach ($instructions as $i): ?>
            <li><?= htmlspecialchars($i['instruction_text']) ?></li>
        <?php endforeach; ?>
    </ul>

    <h3 onclick="toggleAlerts()" style="cursor:pointer;">NHS Alerts ▼</h3>
    <div id="alerts" style="display:none;">
        <?php foreach ($alerts as $a): ?>
            <div style="padding:10px; border:1px solid #ccc; margin-bottom:10px;">
                <strong><?= htmlspecialchars($a['alert_title']) ?></strong><br>
                <?= nl2br(htmlspecialchars($a['alert_body'])) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleAlerts() {
    let a = document.getElementById("alerts");
    a.style.display = a.style.display === "none" ? "block" : "none";
}
</script>

</body>
</html>
