<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Dose</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Dose</h2>

    <form method="POST" action="/modules/medications/add_dose_handler.php">
        <input type="hidden" name="med_id" value="<?= $medId ?>">

        <label>Dose Amount</label>
        <input type="number" step="0.01" name="dose_amount" required>

        <label>Unit</label>
        <input type="text" name="dose_unit" placeholder="mg, ml, tablet..." required>

        <button class="btn btn-accept" type="submit">Continue</button>
    </form>
</div>

</body>
</html>
