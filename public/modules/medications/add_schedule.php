<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Medication Schedule</h2>
            <p>Set up your dosing schedule</p>
        </div>

        <form method="POST" action="/modules/medications/add_schedule_handler.php">
            <input type="hidden" name="med_id" value="<?= htmlspecialchars($medId) ?>">

            <div class="form-group">
                <label>Frequency</label>
                <select name="frequency_type" id="freq" onchange="updateUI()">
                    <option value="per_day">Times per day</option>
                    <option value="per_week">Times per week</option>
                </select>
            </div>

            <div id="daily">
                <div class="form-group">
                    <label>Times per day</label>
                    <input type="number" name="times_per_day" min="1" max="6" value="1">
                </div>
            </div>

            <div id="weekly" style="display:none;">
                <div class="form-group">
                    <label>Times per week</label>
                    <input type="number" name="times_per_week" min="1" max="7" value="1">
                </div>

                <div class="form-group">
                    <label>Days of Week</label>
                    <input type="text" name="days_of_week" placeholder="e.g., Mon, Wed, Fri">
                </div>
            </div>

            <button class="btn btn-accept" type="submit">Continue to Instructions</button>
        </form>
    </div>
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
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
