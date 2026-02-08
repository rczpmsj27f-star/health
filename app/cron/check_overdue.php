<?php
require_once __DIR__ . "/../../app/config/database.php";
require_once __DIR__ . "/../../app/core/LinkedUserHelper.php";
require_once __DIR__ . "/../../app/core/NotificationHelper.php";

$linkedHelper = new LinkedUserHelper($pdo);
$notificationHelper = new NotificationHelper($pdo);

// Get all active links
$stmt = $pdo->query("SELECT * FROM user_links WHERE status = 'active'");
$links = $stmt->fetchAll();

foreach ($links as $link) {
    // Check permissions for both directions
    $aPermissions = $linkedHelper->getPermissions($link['id'], $link['user_a_id']);
    $bPermissions = $linkedHelper->getPermissions($link['id'], $link['user_b_id']);
    
    // Notify user A about user B's overdue meds
    if ($aPermissions && $aPermissions['notify_on_overdue']) {
        checkAndNotifyOverdue($link['user_b_id'], $link['user_a_id'], $pdo, $notificationHelper);
    }
    
    // Notify user B about user A's overdue meds
    if ($bPermissions && $bPermissions['notify_on_overdue']) {
        checkAndNotifyOverdue($link['user_a_id'], $link['user_b_id'], $pdo, $notificationHelper);
    }
}

function checkAndNotifyOverdue($medicationOwnerId, $notifyUserId, $pdo, $notificationHelper) {
    // Get overdue medications
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, mdt.dose_time
        FROM medications m
        JOIN medication_dose_times mdt ON m.id = mdt.medication_id
        WHERE m.user_id = ?
        AND m.is_prn = 0
        AND CONCAT(CURDATE(), ' ', mdt.dose_time) < NOW()
        AND NOT EXISTS (
            SELECT 1 FROM medication_logs ml
            WHERE ml.medication_id = m.id
            AND ml.scheduled_date_time = CONCAT(CURDATE(), ' ', TIME(mdt.dose_time))
            AND ml.status = 'taken'
        )
    ");
    $stmt->execute([$medicationOwnerId]);
    $overdueMeds = $stmt->fetchAll();
    
    if (count($overdueMeds) > 0) {
        // Get owner name
        $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
        $stmt->execute([$medicationOwnerId]);
        $ownerNameRow = $stmt->fetch();
        
        if (!$ownerNameRow) {
            return; // Skip if user not found
        }
        
        $ownerName = $ownerNameRow['first_name'];
        
        // Check if we already notified in the last hour
        $stmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? 
            AND type = 'partner_overdue' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$notifyUserId]);
        
        if (!$stmt->fetch()) {
            $notificationHelper->create(
                $notifyUserId,
                'partner_overdue',
                $ownerName . ' has overdue medications',
                $ownerName . ' has ' . count($overdueMeds) . ' overdue medication' . (count($overdueMeds) > 1 ? 's' : ''),
                $medicationOwnerId,
                null
            );
        }
    }
}

echo "Overdue check complete\n";
