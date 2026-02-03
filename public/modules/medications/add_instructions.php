<?php
session_start();
$medId = $_GET['med'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructions</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="centered-page">
        <div class="page-card">
        <div class="page-header">
            <h2>Special Instructions</h2>
            <p>Add any special instructions for this medication</p>
        </div>

        <form method="POST" action="/modules/medications/add_instructions_handler.php">
            <input type="hidden" name="med_id" value="<?= htmlspecialchars($medId) ?>">

            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="instructions[]" value="Take with water">
                    Take with water
                </label>
                <label>
                    <input type="checkbox" name="instructions[]" value="Take on empty stomach">
                    Take on empty stomach
                </label>
                <label>
                    <input type="checkbox" name="instructions[]" value="Take with food">
                    Take with food
                </label>
            </div>

            <div class="form-group">
                <label>Other Instructions (optional)</label>
                <textarea name="other_instruction" rows="3" placeholder="Enter any additional instructions..."></textarea>
            </div>

            <button class="btn btn-accept" type="submit">Continue to Condition</button>
        </form>
    </div>
    </div>
</body>
</html>
