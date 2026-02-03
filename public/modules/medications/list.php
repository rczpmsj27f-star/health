<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM medications WHERE user_id = ? AND (archived IS NULL OR archived = 0)");
$stmt->execute([$userId]);
$meds = $stmt->fetchAll();

$stmtArchived = $pdo->prepare("SELECT * FROM medications WHERE user_id = ? AND archived = 1");
$stmtArchived->execute([$userId]);
$archivedMeds = $stmtArchived->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Medication Management</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<h2 style="padding:16px;">Your Medications</h2>

<div style="padding:16px;">
    <a class="btn btn-accept" href="/modules/medications/add.php">Add Medication</a>
    <a class="btn btn-info" href="/dashboard.php">Back to Dashboard</a>
</div>

<div class="dashboard-grid">
<?php foreach ($meds as $m): ?>
    <a class="tile" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
        <?= htmlspecialchars($m['name']) ?>
    </a>
<?php endforeach; ?>
</div>

<?php if (!empty($archivedMeds)): ?>
<h2 style="padding:16px; margin-top:20px;">Archived Medications</h2>
<div class="dashboard-grid">
<?php foreach ($archivedMeds as $m): ?>
    <a class="tile archived" href="/modules/medications/view.php?id=<?= $m['id'] ?>">
        <?= htmlspecialchars($m['name']) ?>
    </a>
<?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
