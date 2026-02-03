<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = $_GET['id'];
$isAdmin = Auth::isAdmin();

$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

$dose = $pdo->query("SELECT * FROM medication_doses WHERE medication_id = $medId")->fetch();
$schedule = $pdo->query("SELECT * FROM medication_schedules WHERE medication_id = $medId")->fetch();
$instructions = $pdo->query("SELECT * FROM medication_instructions WHERE medication_id = $medId")->fetchAll();
$alerts = $pdo->query("SELECT * FROM medication_alerts WHERE medication_id = $medId")->fetchAll();

// Days of week for visualizer
$daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$activeDays = [];
if ($schedule && $schedule['days_of_week']) {
    $activeDays = array_map('trim', explode(',', $schedule['days_of_week']));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($med['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/menu.js" defer></script>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        <a href="/modules/medications/list.php">💊 Medications</a>
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div style="padding: 80px 16px 40px 16px; max-width: 800px; margin: 0 auto;">
        <div class="page-card">
            <div class="page-header">
                <h2>💊 <?= htmlspecialchars($med['name']) ?></h2>
                <p>Medication Details</p>
            </div>

            <div class="section-header">Dosage Information</div>
            <div class="info-item">
                <div class="info-label">Dose Amount</div>
                <div class="info-value"><?= number_format($dose['dose_amount'], 2) ?> <?= htmlspecialchars($dose['dose_unit']) ?></div>
            </div>

            <div class="section-header">Schedule</div>
            <div class="schedule-grid">
                <?php if ($schedule['frequency_type'] === 'per_day'): ?>
                    <div class="schedule-time">
                        <div class="schedule-time-label">⏰ Daily Schedule</div>
                        <div class="schedule-time-value">
                            <?= htmlspecialchars($schedule['times_per_day']) ?> time(s) per day
                        </div>
                    </div>
                    
                    <!-- Daily visualizer -->
                    <div class="day-visualizer">
                        <?php foreach ($daysOfWeek as $day): ?>
                            <div class="day-badge active"><?= $day ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="schedule-time">
                        <div class="schedule-time-label">📅 Weekly Schedule</div>
                        <div class="schedule-time-value">
                            <?= htmlspecialchars($schedule['times_per_week']) ?> time(s) per week
                        </div>
                    </div>
                    
                    <!-- Weekly visualizer -->
                    <div class="day-visualizer">
                        <?php foreach ($daysOfWeek as $day): ?>
                            <div class="day-badge <?= in_array($day, $activeDays) ? 'active' : '' ?>">
                                <?= $day ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($instructions)): ?>
            <div class="section-header">Special Instructions</div>
            <div class="schedule-grid">
                <?php foreach ($instructions as $i): ?>
                    <div class="schedule-time">
                        <div class="schedule-time-value">
                            📋 <?= htmlspecialchars($i['instruction_text']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($alerts)): ?>
            <div class="section-header">NHS System Alerts</div>
            <?php foreach ($alerts as $a): ?>
                <div class="nhs-alert">
                    <div class="nhs-alert-title"><?= htmlspecialchars($a['alert_title']) ?></div>
                    <div class="nhs-alert-body"><?= nl2br(htmlspecialchars($a['alert_body'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="action-buttons three-col" style="margin-top: 32px;">
                <a class="btn btn-primary" href="/modules/medications/edit.php?id=<?= $medId ?>">✏️ Edit</a>
                <a class="btn btn-secondary" href="/modules/medications/archive_handler.php?id=<?= $medId ?>&action=archive">📦 Archive</a>
                <a class="btn btn-danger" href="/modules/medications/delete_handler.php?id=<?= $medId ?>" onclick="return confirm('Are you sure you want to delete this medication? This action cannot be undone.');">🗑️ Delete</a>
            </div>
            
            <div style="margin-top: 16px;">
                <a class="btn btn-info" href="/modules/medications/list.php">⬅️ Back to Medications</a>
            </div>
        </div>
    </div>
</body>
</html>
