<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instructions</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Special Instructions</h2>

    <form method="POST" action="/modules/medications/add_instructions_handler.php">
        <input type="hidden" name="med_id" value="<?= $medId ?>">

        <label><input type="checkbox" name="instructions[]" value="Take with water"> Take with water</label><br>
        <label><input type="checkbox" name="instructions[]" value="Take on empty stomach"> Take on empty stomach</label><br>

        <label>Other</label>
        <input type="text" name="other_instruction">

        <button class="btn btn-accept" type="submit">Continue</button>
    </form>
</div>

</body>
</html>
