<?php
// Persistent header component for UI redesign
// Purple bar with avatar circle (left) and user/date info (right)

// Fetch user details if not already loaded
if (!isset($user) || !isset($displayName)) {
    if (isset($pdo) && !empty($_SESSION['user_id'])) {
        $userStmt = $pdo->prepare("SELECT first_name, surname, email, profile_picture_path FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        if (empty($displayName)) {
            // Fallback to email if no name is set
            $displayName = explode('@', $user['email'] ?? 'User')[0];
        }
        
        // Default avatar if none set
        $avatarUrl = !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '/assets/images/default-avatar.svg';
    } else {
        $displayName = 'User';
        $avatarUrl = '/assets/images/default-avatar.svg';
    }
}
?>

<div class="app-header">
    <div class="header-content">
        <div class="header-left">
            <img src="<?= htmlspecialchars($avatarUrl ?? '/assets/images/default-avatar.svg') ?>" 
                 alt="Profile" 
                 onerror="this.src='/assets/images/default-avatar.svg'"
                 class="header-avatar">
        </div>
        <div class="header-right">
            <div class="header-user">Logged in as: <?= htmlspecialchars($displayName) ?></div>
            <div class="header-date"><?= date('d F Y') ?></div>
        </div>
    </div>
</div>

<style>
.app-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    z-index: 10001;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    /* iOS Safari fixes for fixed positioning */
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    /* Extend background into safe area (iOS notch/status bar) */
    padding-top: env(safe-area-inset-top);
}

.header-content {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    max-width: 1200px;
    margin: 0 auto;
    /* Add padding for safe areas on left/right */
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
}

.header-left {
    margin-right: 16px;
}

.header-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 3px solid white;
    object-fit: cover;
    background: white;
}

.header-right {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.header-user {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 2px;
}

.header-date {
    font-size: 14px;
    opacity: 0.9;
}

/* Add padding to body to account for fixed header */
body {
    /* Base padding (90px) + safe area inset for iOS notch */
    padding-top: calc(90px + env(safe-area-inset-top)) !important;
    padding-bottom: calc(70px + env(safe-area-inset-bottom)) !important; /* Footer + safe area */
}

/* iOS-specific fixes for header positioning */
@supports (-webkit-touch-callout: none) {
    /* This targets iOS Safari specifically */
    body {
        padding-top: calc(90px + env(safe-area-inset-top)) !important;
    }
    
    .app-header {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
    }
}

@media (max-width: 576px) {
    .header-user {
        font-size: 14px;
    }
    
    .header-date {
        font-size: 12px;
    }
    
    .header-avatar {
        width: 45px;
        height: 45px;
        border-width: 2px;
    }
    
    body {
        padding-top: 70px !important;
    }
    
    /* iOS-specific mobile fixes */
    @supports (-webkit-touch-callout: none) {
        body {
            padding-top: 70px !important;
        }
    }
}
</style>
