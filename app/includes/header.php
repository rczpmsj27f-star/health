<?php
// Simple scrolling header - no fixed positioning
if (!isset($displayName)) {
    if (isset($pdo) && !empty($_SESSION['user_id'])) {
        $userStmt = $pdo->prepare("SELECT first_name, surname, email FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        if (empty($displayName)) {
            $displayName = explode('@', $user['email'] ?? 'User')[0];
        }
    }
}
?>

<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px; text-align: center;">
    <div style="font-size: 16px; font-weight: 600;">Logged in as: <?= htmlspecialchars($displayName ?? 'User') ?></div>
    <div style="font-size: 13px; opacity: 0.9; margin-top: 4px;"><?= date('d F Y') ?></div>
</div>
