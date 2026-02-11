<?php
// Simple scrolling header with profile picture
if (!isset($displayName)) {
    if (isset($pdo) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $userStmt = $pdo->prepare("SELECT first_name, surname, email, profile_picture_path FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        if (empty($displayName)) {
            $displayName = explode('@', $user['email'] ?? 'User')[0];
        }
        $avatarUrl = isset($user['profile_picture_path']) && !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '/assets/images/default-avatar.svg';
    } else {
        $displayName = 'User';
        $avatarUrl = '/assets/images/default-avatar.svg';
    }
}
?>

<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
    <img src="<?= htmlspecialchars($avatarUrl ?? '/assets/images/default-avatar.svg') ?>" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid white; object-fit: cover;">
    <div>
        <div style="font-size: 14px; font-weight: 600;">Logged in as: <?= htmlspecialchars($displayName ?? 'User') ?></div>
        <div style="font-size: 12px; opacity: 0.9;"><?= date('d F Y') ?></div>
    </div>
</div>
