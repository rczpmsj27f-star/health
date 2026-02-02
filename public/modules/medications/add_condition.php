<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Condition</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Condition Being Treated</h2>

    <form method="POST" action="/modules/medications/add_condition_handler.php">
        <input type="hidden" name="med_id" value="<?= $medId ?>">

        <label>Condition Name</label>
        <input type="text" name="condition_name" required>

        <button class="btn btn-accept" type="submit">Finish</button>
    </form>
</div>

</body>
</html>
