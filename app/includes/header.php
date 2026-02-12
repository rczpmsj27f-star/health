<?php
// Include cache-buster FIRST (before ANY output)
require_once __DIR__ . '/cache-buster.php';

// Simple scrolling header with profile picture
// Read from session (instant, no DB query)
if (!isset($displayName)) {
    $displayName = $_SESSION['header_display_name'] ?? 'User';
    $avatarUrl = $_SESSION['header_avatar_url'] ?? '/assets/images/default-avatar.svg';
}
?>

<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: max(12px, env(safe-area-inset-top)) 16px 12px 16px; display: flex; align-items: center; gap: 12px;">
    <img src="<?= htmlspecialchars($avatarUrl ?? '/assets/images/default-avatar.svg') ?>" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid white; object-fit: cover;">
    <div>
        <div style="font-size: 14px; font-weight: 600;">Logged in as: <?= htmlspecialchars($displayName ?? 'User') ?></div>
        <div style="font-size: 12px; opacity: 0.9;"><?= date('d F Y') ?></div>
    </div>
</div>
