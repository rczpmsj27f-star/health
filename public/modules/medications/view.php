<?php
session_start();
require_once "../../../app/config/database.php";

$medId = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM medication_doses WHERE medication_id = ?");
$stmt->execute([$medId]);
$dose = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM medication_schedules WHERE medication_id = ?");
$stmt->execute([$medId]);
$schedule = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM medication_dose_times WHERE medication_id = ? ORDER BY dose_number");
$stmt->execute([$medId]);
$doseTimes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM medication_instructions WHERE medication_id = ?");
$stmt->execute([$medId]);
$instructions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM medication_alerts WHERE medication_id = ?");
$stmt->execute([$medId]);
$alerts = $stmt->fetchAll();
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
    <?php if ($schedule['frequency_type'] === 'per_day' && $schedule['times_per_day']): ?>
        - <?= $schedule['times_per_day'] ?> time<?= $schedule['times_per_day'] > 1 ? 's' : '' ?> per day
        <?php if (!empty($doseTimes)): ?>
            <ul>
                <?php foreach ($doseTimes as $dt): ?>
                    <li>Dose <?= $dt['dose_number'] ?>: <?= date('g:i A', strtotime($dt['dose_time'])) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php elseif ($schedule['frequency_type'] === 'per_week' && $schedule['times_per_week']): ?>
        - <?= $schedule['times_per_week'] ?> time<?= $schedule['times_per_week'] > 1 ? 's' : '' ?> per week
        <?php if ($schedule['days_of_week']): ?>
            (<?= htmlspecialchars($schedule['days_of_week']) ?>)
        <?php endif; ?>
    <?php endif; ?>

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

    <div style="margin-top:30px; display:grid; gap:10px;">
        <a class="btn btn-accept" href="/modules/medications/edit.php?id=<?= $medId ?>">Edit Medication</a>
        
        <?php if (!empty($med['archived']) && $med['archived'] == 1): ?>
            <form method="POST" action="/modules/medications/archive_handler.php" style="margin:0;">
                <input type="hidden" name="med_id" value="<?= $medId ?>">
                <input type="hidden" name="action" value="unarchive">
                <button class="btn btn-info" type="submit">Unarchive Medication</button>
            </form>
        <?php else: ?>
            <form method="POST" action="/modules/medications/archive_handler.php" style="margin:0;">
                <input type="hidden" name="med_id" value="<?= $medId ?>">
                <input type="hidden" name="action" value="archive">
                <button class="btn btn-info" type="submit">Archive Medication</button>
            </form>
        <?php endif; ?>
        
        <form method="POST" action="/modules/medications/delete_handler.php" style="margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete this medication? This cannot be undone.');">
            <input type="hidden" name="med_id" value="<?= $medId ?>">
            <button class="btn btn-danger" type="submit">Delete Medication</button>
        </form>
        
        <a class="btn btn-info" href="/modules/medications/list.php">Back to List</a>
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
