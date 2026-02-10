<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Dose</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Dose Information</h2>
            <p>Enter the dosage details</p>
        </div>

        <form method="POST" action="/modules/medications/add_dose_handler.php">
            <input type="hidden" name="med_id" value="<?= htmlspecialchars($medId) ?>">

            <div class="form-group">
                <label>Dose Amount</label>
                <input type="number" step="0.01" name="dose_amount" placeholder="e.g., 500" required>
            </div>

            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="dose_unit" placeholder="e.g., mg, ml, tablet" required>
            </div>

            <button class="btn btn-accept" type="submit">Continue to Schedule</button>
        </form>
    </div>
    </div>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
