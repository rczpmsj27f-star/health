<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Schedule</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Schedule</h2>

    <form method="POST" action="/modules/medications/add_schedule_handler.php">
        <input type="hidden" name="med_id" value="<?= $medId ?>">

        <label>Frequency</label>
        <select name="frequency_type" id="freq" onchange="updateUI()">
            <option value="per_day">Times per day</option>
            <option value="per_week">Times per week</option>
        </select>

        <div id="daily">
            <label>Times per day</label>
            <input type="number" name="times_per_day" id="times_per_day" min="1" max="6" value="1" onchange="updateTimeInputs()">
            
            <div id="time_inputs_container" style="margin-top:10px;">
                <!-- Time inputs will be dynamically generated here -->
            </div>
        </div>

        <div id="weekly" style="display:none;">
            <label>Times per week</label>
            <input type="number" name="times_per_week" min="1" max="7" value="1">

            <label>Days</label>
            <input type="text" name="days_of_week" placeholder="Mon,Wed,Fri">
        </div>

        <button class="btn btn-accept" type="submit">Continue</button>
    </form>
</div>

<script>
function updateUI() {
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
    
    if (timesPerDay > 1) {
        let html = '<div style="margin-top:10px;"><strong>Dose Times:</strong></div>';
        for (let i = 1; i <= timesPerDay; i++) {
            html += `<label>Time ${i}:</label>`;
            html += `<input type="time" name="dose_time_${i}">`;
        }
        container.innerHTML = html;
    } else {
        container.innerHTML = '';
    }
}
</script>

</body>
</html>
