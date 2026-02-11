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
$notifications = $notificationHelper->getRecent($_SESSION['user_id'], 50);
$unreadCount = $notificationHelper->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div style="max-width: 900px; margin: 0 auto; padding: 20px 16px 100px 16px;">
        <div style="margin-bottom: 24px;">
            <h2 style="color: var(--color-primary); font-size: 28px; margin: 0;">🔔 Notifications</h2>
        </div>
        
        <div id="notificationList" style="background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 80px;">
            <?php if (empty($notifications)): ?>
                <div style="padding: 60px 20px; text-align: center; color: var(--color-text-secondary);">
                    <div style="font-size: 64px; margin-bottom: 16px;">🔔</div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No notifications yet</div>
                    <div style="font-size: 14px;">You'll see your medication reminders and alerts here</div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                         onclick="markAsRead(<?= $notification['id'] ?>)"
                         style="cursor: pointer; padding: 16px; border-bottom: 1px solid #e5e7eb; transition: all 0.2s;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div style="font-weight: 600; font-size: 16px; color: #1f2937;">
                                <?= htmlspecialchars($notification['title']) ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; white-space: nowrap; margin-left: 12px;">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 14px; color: #4b5563; margin-bottom: 8px; line-height: 1.5;">
                            <?= htmlspecialchars($notification['message']) ?>
                        </div>
                        <div style="font-size: 12px; color: #9ca3af;">
                            <?php
                            $time = strtotime($notification['created_at']);
                            $now = time();
                            $diff = $now - $time;
                            
                            if ($diff < 60) {
                                echo 'Just now';
                            } elseif ($diff < 3600) {
                                $mins = floor($diff / 60);
                                echo $mins . ($mins == 1 ? ' min' : ' mins') . ' ago';
                            } elseif ($diff < 86400) {
                                $hours = floor($diff / 3600);
                                echo $hours . ($hours == 1 ? ' hour' : ' hours') . ' ago';
                            } elseif ($diff < 604800) {
                                $days = floor($diff / 86400);
                                echo $days . ($days == 1 ? ' day' : ' days') . ' ago';
                            } else {
                                echo date('M j, Y', $time);
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Fixed action bar above footer -->
        <?php if ($unreadCount > 0): ?>
        <div style="position: fixed; bottom: 80px; left: 0; right: 0; background: white; border-top: 1px solid #e5e7eb; padding: 12px 16px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 999;">
            <div style="max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--color-text-secondary); font-size: 14px;"><?= $unreadCount ?> unread notification<?= $unreadCount !== 1 ? 's' : '' ?></span>
                <button onclick="markAllRead()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                    Mark All as Read
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/dashboard.php" style="color: var(--color-text-secondary); text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>

    <?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
    
    <style>
    .notification-item:hover {
        background: #f9fafb;
    }
    
    .notification-item.unread {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding-left: 12px;
    }
    
    .notification-item.unread:hover {
        background: #dbeafe;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    </style>
    
    <script>
    function markAsRead(notificationId) {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'mark_read', notification_id: notificationId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update UI
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
    
    function markAllRead() {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'mark_all_read'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update UI
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    }
    </script>
</body>
</html>
