<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condition</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <div id="main-content">

    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Condition Being Treated</h2>
            <p>What condition is this medication treating?</p>
        </div>

        <form method="POST" action="/modules/medications/add_condition_handler.php">
            <input type="hidden" name="med_id" value="<?= htmlspecialchars($medId) ?>">

            <div class="form-group">
                <label>Condition Name</label>
                <input type="text" name="condition_name" placeholder="e.g., High blood pressure" required>
            </div>

            <button class="btn btn-accept" type="submit">Finish Adding Medication</button>
        </form>
    </div>
    </div>

</div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
