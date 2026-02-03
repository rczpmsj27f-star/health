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
            <input type="number" name="times_per_day" min="1" max="6" value="1">
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
    
    // Clear hidden field values to prevent undefined submissions
    if (f === "per_day") {
        document.querySelector('[name="times_per_week"]').value = "";
        document.querySelector('[name="days_of_week"]').value = "";
        document.getElementById("daily").style.display = "block";
        document.getElementById("weekly").style.display = "none";
    } else {
        document.querySelector('[name="times_per_day"]').value = "";
        document.getElementById("daily").style.display = "none";
        document.getElementById("weekly").style.display = "block";
    }
}
</script>

</body>
</html>
