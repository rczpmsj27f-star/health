<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$medId) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Get medication details
$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medId, $_SESSION['user_id']]);
$med = $stmt->fetch();

if (!$med) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Get dose information
$stmt = $pdo->prepare("SELECT * FROM medication_doses WHERE medication_id = ?");
$stmt->execute([$medId]);
$dose = $stmt->fetch();

// Get schedule information
$stmt = $pdo->prepare("SELECT * FROM medication_schedules WHERE medication_id = ?");
$stmt->execute([$medId]);
$schedule = $stmt->fetch();

// Get instructions
$stmt = $pdo->prepare("SELECT * FROM medication_instructions WHERE medication_id = ?");
$stmt->execute([$medId]);
$instructions = $stmt->fetchAll();

// Get existing dose times
$stmt = $pdo->prepare("SELECT * FROM medication_dose_times WHERE medication_id = ? ORDER BY dose_number");
$stmt->execute([$medId]);
$existingDoseTimes = $stmt->fetchAll();
$doseTimesArray = [];
foreach ($existingDoseTimes as $dt) {
    $doseTimesArray[$dt['dose_number']] = $dt['dose_time'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Medication - <?= htmlspecialchars($med['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Edit Medication</h2>

    <form method="POST" action="/modules/medications/edit_handler.php">
        <input type="hidden" name="med_id" value="<?= $medId ?>">

        <h3>Medication Name</h3>
        <input type="text" name="med_name" value="<?= htmlspecialchars($med['name']) ?>" required>

        <h3>Dose</h3>
        <label>Dose Amount</label>
        <input type="number" step="0.01" name="dose_amount" value="<?= $dose['dose_amount'] ?>" required>
        
        <label>Unit</label>
        <input type="text" name="dose_unit" value="<?= htmlspecialchars($dose['dose_unit']) ?>" placeholder="mg, ml, tablet..." required>

        <h3>Schedule</h3>
        <label>Frequency</label>
        <select name="frequency_type" id="freq" onchange="updateScheduleUI()">
            <option value="per_day" <?= $schedule['frequency_type'] === 'per_day' ? 'selected' : '' ?>>Times per day</option>
            <option value="per_week" <?= $schedule['frequency_type'] === 'per_week' ? 'selected' : '' ?>>Times per week</option>
        </select>

        <div id="daily" style="<?= $schedule['frequency_type'] === 'per_day' ? '' : 'display:none;' ?>">
            <label>Times per day</label>
            <input type="number" name="times_per_day" id="times_per_day" min="1" max="24" value="<?= $schedule['times_per_day'] ?: 1 ?>" onchange="updateTimeInputs()">
            
            <div id="time_inputs_container">
                <!-- Time inputs will be dynamically generated here -->
            </div>
        </div>

        <div id="weekly" style="<?= $schedule['frequency_type'] === 'per_week' ? '' : 'display:none;' ?>">
            <label>Times per week</label>
            <input type="number" name="times_per_week" min="1" max="7" value="<?= $schedule['times_per_week'] ?: 1 ?>">

            <label>Days</label>
            <input type="text" name="days_of_week" value="<?= htmlspecialchars($schedule['days_of_week']) ?>" placeholder="Mon,Wed,Fri">
        </div>

        <h3>Special Instructions</h3>
        <?php
        $hasWater = false;
        $hasEmptyStomach = false;
        $otherInstructions = [];
        
        foreach ($instructions as $i) {
            if ($i['instruction_text'] === 'Take with water') {
                $hasWater = true;
            } elseif ($i['instruction_text'] === 'Take on empty stomach') {
                $hasEmptyStomach = true;
            } else {
                $otherInstructions[] = $i['instruction_text'];
            }
        }
        ?>
        <label><input type="checkbox" name="instructions[]" value="Take with water" <?= $hasWater ? 'checked' : '' ?>> Take with water</label><br>
        <label><input type="checkbox" name="instructions[]" value="Take on empty stomach" <?= $hasEmptyStomach ? 'checked' : '' ?>> Take on empty stomach</label><br>

        <label>Other Instructions (one per line)</label>
        <textarea name="other_instructions" rows="3"><?= htmlspecialchars(implode("\n", $otherInstructions)) ?></textarea>

        <button class="btn btn-accept" type="submit">Save Changes</button>
        <a class="btn btn-deny" href="/modules/medications/view.php?id=<?= $medId ?>" style="margin-top:10px;">Cancel</a>
    </form>
</div>

<script>
function updateScheduleUI() {
    let f = document.getElementById("freq").value;
    document.getElementById("daily").style.display = f === "per_day" ? "block" : "none";
    document.getElementById("weekly").style.display = f === "per_week" ? "block" : "none";
    
    if (f === "per_day") {
        updateTimeInputs();
    }
}

function updateTimeInputs() {
    let timesPerDay = parseInt(document.getElementById("times_per_day").value) || 1;
    let container = document.getElementById("time_inputs_container");
    
    // Get existing dose times from PHP
    let existingTimes = <?= json_encode($doseTimesArray) ?>;
    
    if (timesPerDay > 1) {
        let html = '<div style="margin-top:10px;"><strong>Dose Times:</strong></div>';
        for (let i = 1; i <= timesPerDay; i++) {
            html += `<label>Time ${i}:</label>`;
            let timeValue = existingTimes[i] || '';
            html += `<input type="time" name="dose_time_${i}" value="${timeValue}">`;
        }
        container.innerHTML = html;
    } else {
        container.innerHTML = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTimeInputs();
});
</script>

</body>
</html>
