<?php
/**
 * User Switcher Component
 * 
 * Displays a tab-based switcher for viewing own medications vs linked user's medications
 * Uses profile pictures for a polished UI
 * 
 * Required variables:
 * - $pdo: Database connection
 * - $_SESSION['user_id']: Current user ID
 * - $linkedUser: Array from LinkedUserHelper (or null)
 * 
 * Optional variables:
 * - $currentPage: Current page identifier (for preserving query params)
 */

// Only display if there's an active linked user
if ($linkedUser && $linkedUser['status'] === 'active'):
    // Get profile pictures for both users in a single query
    $stmt = $pdo->prepare("SELECT id, profile_picture_path FROM users WHERE id IN (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $linkedUser['linked_user_id']]);
    $userProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map profiles by user ID
    $myPic = '/assets/images/default-avatar.svg';
    $theirPic = '/assets/images/default-avatar.svg';
    
    foreach ($userProfiles as $profile) {
        if ($profile['id'] === $_SESSION['user_id'] && !empty($profile['profile_picture_path'])) {
            $myPic = $profile['profile_picture_path'];
        } elseif ($profile['id'] === $linkedUser['linked_user_id'] && !empty($profile['profile_picture_path'])) {
            $theirPic = $profile['profile_picture_path'];
        }
    }
    
    // Determine current view
    $isViewingLinked = isset($_GET['view']) && $_GET['view'] === 'linked';
    
    // Build URLs preserving other query params
    $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;
    unset($queryParams['view']);
    $myUrl = $baseUrl . (empty($queryParams) ? '' : '?' . http_build_query($queryParams));
    $queryParams['view'] = 'linked';
    $linkedUrl = $baseUrl . '?' . http_build_query($queryParams);

    // Compute overdue counts for badge display
    $overdueCurrentDow = date('D');
    $overdueCurrentDate = date('Y-m-d');
    $overdueCountQuery = "
        SELECT COUNT(DISTINCT CONCAT(m.id, '_', mdt.dose_time)) as cnt
        FROM medications m
        LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
        LEFT JOIN medication_dose_times mdt ON m.id = mdt.medication_id
        WHERE m.user_id = :user_id
        AND (m.archived = 0 OR m.archived IS NULL)
        AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
        AND (
            ms.frequency_type = 'per_day'
            OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE :day_of_week)
        )
        AND mdt.dose_time IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM medication_logs ml2
            WHERE ml2.medication_id = m.id
            AND DATE(ml2.scheduled_date_time) = :today_date
            AND TIME(ml2.scheduled_date_time) = mdt.dose_time
            AND ml2.status IN ('taken', 'skipped')
        )
        AND (
            (ms.special_timing = 'on_waking' AND CONCAT(:today_date, ' 09:00:00') < NOW())
            OR (ms.special_timing = 'before_bed' AND CONCAT(:today_date, ' 22:00:00') < NOW())
            OR ((ms.special_timing IS NULL OR ms.special_timing NOT IN ('on_waking', 'before_bed')) AND CONCAT(:today_date, ' ', mdt.dose_time) < NOW())
        )
        AND (
            (ms.special_timing = 'on_waking' AND CONCAT(:today_date, ' 09:00:00') >= m.created_at)
            OR (ms.special_timing = 'before_bed' AND CONCAT(:today_date, ' 22:00:00') >= m.created_at)
            OR ((ms.special_timing IS NULL OR ms.special_timing NOT IN ('on_waking', 'before_bed')) AND CONCAT(:today_date, ' ', mdt.dose_time) >= m.created_at)
        )
    ";

    $swStmt = $pdo->prepare($overdueCountQuery);
    $swStmt->execute(['user_id' => $_SESSION['user_id'], 'day_of_week' => "%$overdueCurrentDow%", 'today_date' => $overdueCurrentDate]);
    $myOverdueCount = (int)$swStmt->fetchColumn();

    // Only show linked user's overdue count if current user has can_view_schedule permission
    $theirOverdueCount = 0;
    $swPermStmt = $pdo->prepare("SELECT can_view_schedule FROM user_link_permissions WHERE link_id = ? AND user_id = ?");
    $swPermStmt->execute([$linkedUser['id'], $_SESSION['user_id']]);
    $swPerm = $swPermStmt->fetch();
    if ($swPerm && !empty($swPerm['can_view_schedule'])) {
        $swStmt2 = $pdo->prepare($overdueCountQuery);
        $swStmt2->execute(['user_id' => $linkedUser['linked_user_id'], 'day_of_week' => "%$overdueCurrentDow%", 'today_date' => $overdueCurrentDate]);
        $theirOverdueCount = (int)$swStmt2->fetchColumn();
    }
?>
<div style="background: white; border-radius: 10px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; gap: 12px;">
    <a href="<?= htmlspecialchars($myUrl) ?>" 
       style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= !$isViewingLinked ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
        <img src="<?= htmlspecialchars($myPic) ?>" 
             alt="My profile" 
             onerror="this.src='/assets/images/default-avatar.svg'"
             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid <?= !$isViewingLinked ? 'white' : 'var(--color-border)' ?>;">
        My Medications
        <?php if ($myOverdueCount > 0): ?>
        <span style="background: #dc2626; color: white; border-radius: 999px; padding: 1px 7px; font-size: 11px; font-weight: 700; margin-left: 4px;">
            <?= $myOverdueCount ?>
        </span>
        <?php endif; ?>
    </a>
    <a href="<?= htmlspecialchars($linkedUrl) ?>" 
       style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= $isViewingLinked ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
        <img src="<?= htmlspecialchars($theirPic) ?>" 
             alt="<?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s profile" 
             onerror="this.src='/assets/images/default-avatar.svg'"
             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $isViewingLinked ? 'white' : 'var(--color-border)' ?>;">
        Manage <?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s Meds
        <?php if ($theirOverdueCount > 0): ?>
        <span style="background: #dc2626; color: white; border-radius: 999px; padding: 1px 7px; font-size: 11px; font-weight: 700; margin-left: 4px;">
            <?= $theirOverdueCount ?>
        </span>
        <?php endif; ?>
    </a>
</div>
<?php endif; ?>
