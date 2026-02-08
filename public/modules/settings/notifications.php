<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$notificationHelper = new NotificationHelper($pdo);
$preferences = $notificationHelper->getPreferences($_SESSION['user_id']);

// Default notification types
$notificationTypes = [
    'medication_reminder' => 'Medication Reminders',
    'overdue_alert' => 'Overdue Alerts',
    'partner_took_med' => 'Partner Took Medication',
    'partner_overdue' => 'Partner Has Overdue Meds',
    'nudge_received' => 'Nudge Received',
    'link_request' => 'Link Requests',
    'link_accepted' => 'Link Accepted',
    'stock_low' => 'Stock Low Warning'
];

$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div style="max-width: 900px; margin: 0 auto; padding: 80px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 24px;">🔔 Notification Preferences</h2>
        
        <?php if ($successMsg): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                ✓ <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/modules/settings/notifications_handler.php">
            <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-bg-light);">
                            <th style="text-align: left; padding: 12px;">Notification Type</th>
                            <th style="text-align: center; padding: 12px; width: 100px;">In-App</th>
                            <th style="text-align: center; padding: 12px; width: 100px;">Push</th>
                            <th style="text-align: center; padding: 12px; width: 100px;">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificationTypes as $type => $label): 
                            $pref = $preferences[$type] ?? ['in_app' => 1, 'push' => 1, 'email' => 0];
                        ?>
                        <tr style="border-bottom: 1px solid var(--color-bg-light);">
                            <td style="padding: 12px;"><?= htmlspecialchars($label) ?></td>
                            <td style="text-align: center; padding: 12px;">
                                <input type="checkbox" name="<?= $type ?>[in_app]" value="1" 
                                       <?= $pref['in_app'] ? 'checked' : '' ?>>
                            </td>
                            <td style="text-align: center; padding: 12px;">
                                <input type="checkbox" name="<?= $type ?>[push]" value="1" 
                                       <?= $pref['push'] ? 'checked' : '' ?>>
                            </td>
                            <td style="text-align: center; padding: 12px;">
                                <input type="checkbox" name="<?= $type ?>[email]" value="1" 
                                       <?= $pref['email'] ? 'checked' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%; padding: 16px;">
                💾 Save Preferences
            </button>
        </form>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/dashboard.php" style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
