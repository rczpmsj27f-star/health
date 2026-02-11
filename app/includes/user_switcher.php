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
?>
<div style="background: white; border-radius: 10px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; gap: 12px;">
    <a href="<?= htmlspecialchars($myUrl) ?>" 
       style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= !$isViewingLinked ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
        <img src="<?= htmlspecialchars($myPic) ?>" 
             alt="My profile" 
             onerror="this.src='/assets/images/default-avatar.svg'"
             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid <?= !$isViewingLinked ? 'white' : 'var(--color-border)' ?>;">
        My Medications
    </a>
    <a href="<?= htmlspecialchars($linkedUrl) ?>" 
       style="flex: 1; text-align: center; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; <?= $isViewingLinked ? 'background: var(--color-primary); color: white;' : 'background: var(--color-bg-light); color: var(--color-text);' ?>">
        <img src="<?= htmlspecialchars($theirPic) ?>" 
             alt="<?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s profile" 
             onerror="this.src='/assets/images/default-avatar.svg'"
             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $isViewingLinked ? 'white' : 'var(--color-border)' ?>;">
        Manage <?= htmlspecialchars($linkedUser['linked_user_name']) ?>'s Meds
    </a>
</div>
<?php endif; ?>
