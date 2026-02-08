<?php
// Ensure $isAdmin is set - auto-detect if not provided
if (!isset($isAdmin)) {
    require_once __DIR__ . '/../core/auth.php';
    $isAdmin = Auth::isAdmin();
}

// Get unread notification count - only if $pdo is available
$unreadCount = 0;
if (isset($pdo) && !empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../core/NotificationHelper.php';
    try {
        $notificationHelper = new NotificationHelper($pdo);
        $unreadCount = $notificationHelper->getUnreadCount($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Menu notification error: " . $e->getMessage());
        $unreadCount = 0;
    }
}
?>
<div class="hamburger" onclick="toggleMenu()">
    <div></div><div></div><div></div>
</div>

<!-- Notification Bell -->
<div class="header-notifications">
    <button class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
        🔔
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </button>
    
    <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
        <div class="notification-header">
            <strong>Notifications</strong>
            <button onclick="markAllRead()" style="background: none; border: none; color: var(--color-primary); cursor: pointer; font-size: 12px;">
                Mark all read
            </button>
        </div>
        
        <div class="notification-list" id="notificationList">
            <div style="padding: 20px; text-align: center; color: var(--color-text-secondary);">
                Loading...
            </div>
        </div>
        
        <div class="notification-footer">
            <a href="/modules/settings/notifications.php">View all notifications →</a>
        </div>
    </div>
</div>

<style>
.header-notifications {
    position: relative;
    display: inline-block;
}

.notification-bell {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    position: relative;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 600;
}

.notification-dropdown {
    position: fixed;
    top: 60px;
    left: 50%;
    transform: translateX(-50%);
    width: min(400px, calc(100vw - 32px));
    max-height: calc(100vh - 80px);
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 10000;
    overflow: hidden;
}

@media (max-width: 768px) {
    .notification-dropdown {
        left: 16px !important;
        right: 16px !important;
        transform: none !important;
        width: auto !important;
        margin: 0 auto;
    }
}

.notification-header {
    padding: 16px;
    border-bottom: 1px solid var(--color-bg-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-bg-light);
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: var(--color-bg-light);
}

.notification-item.unread {
    background: #eff6ff;
}

.notification-footer {
    padding: 12px 16px;
    text-align: center;
    border-top: 1px solid var(--color-bg-light);
}

.notification-footer a {
    color: var(--color-primary);
    text-decoration: none;
    font-size: 14px;
}
</style>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isVisible = dropdown.style.display === 'block';
    
    if (isVisible) {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
        loadNotifications();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadNotifications() {
    const list = document.getElementById('notificationList');
    list.innerHTML = '<div style="padding: 20px; text-align: center;"><div class="spinner"></div></div>';
    
    fetch('/api/notifications.php?action=get_recent', {
            credentials: 'include'
        })
        .then(r => {
            console.log('Notification response status:', r.status);
            if (!r.ok) {
                return r.json().then(err => {
                    throw new Error(err.error || 'Network error');
                });
            }
            return r.json();
        })
        .then(data => {
            console.log('Notification data:', data);
            
            if (!data.success || !data.notifications) {
                throw new Error('Invalid response format');
            }
            
            if (data.notifications.length === 0) {
                list.innerHTML = `
                    <div style="padding: 40px; text-align: center; color: var(--color-text-secondary);">
                        <div style="font-size: 48px; margin-bottom: 12px;">🔔</div>
                        <div>No notifications yet</div>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = data.notifications.map(n => `
                <div class="notification-item ${n.is_read ? '' : 'unread'}" 
                     onclick="markAsRead(${n.id})"
                     style="cursor: pointer; transition: all 0.2s;">
                    <div style="font-weight: 600; margin-bottom: 4px;">${escapeHtml(n.title)}</div>
                    <div style="font-size: 13px; color: var(--color-text-secondary);">${escapeHtml(n.message)}</div>
                    <div style="font-size: 11px; color: var(--color-text-secondary); margin-top: 4px;">
                        ${formatTime(n.created_at)}
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Notification error:', error);
            list.innerHTML = `
                <div style="padding: 40px; text-align: center; color: #ef4444;">
                    <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                    <div>Failed to load notifications</div>
                    <div style="font-size: 12px; margin-top: 8px; color: var(--color-text-secondary);">
                        Please try again later
                    </div>
                    <button onclick="loadNotifications()" style="margin-top: 12px; padding: 8px 16px; background: var(--color-primary); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            `;
        });
}

function markAsRead(notificationId) {
    fetch('/api/notifications.php', {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_read', notification_id: notificationId})
    }).then(() => {
        loadNotifications();
        updateBadge();
    });
}

function markAllRead() {
    fetch('/api/notifications.php', {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_all_read'})
    }).then(() => {
        loadNotifications();
        updateBadge();
    });
}

function updateBadge() {
    fetch('/api/notifications.php?action=get_count', {
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            const bell = document.getElementById('notificationBell');
            const existingBadge = bell.querySelector('.notification-badge');
            
            if (data.count > 0) {
                if (existingBadge) {
                    existingBadge.textContent = data.count > 9 ? '9+' : data.count;
                } else {
                    bell.innerHTML += `<span class="notification-badge">${data.count > 9 ? '9+' : data.count}</span>`;
                }
            } else if (existingBadge) {
                existingBadge.remove();
            }
        });
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}

// Close dropdown when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (bell && dropdown && !bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>

<div class="menu" id="menu">
    <h3>Menu</h3>
    <a href="/dashboard">🏠 Dashboard</a>
    
    <div class="menu-section">
        <div class="menu-section-header" onclick="toggleSubmenu('medications-menu')">
            <span><a href="/modules/medications/dashboard.php" style="color: inherit; text-decoration: none;">💊 Medications</a></span>
            <span class="menu-toggle-icon" id="medications-menu-icon">▶</span>
        </div>
        <div class="menu-section-children" id="medications-menu">
            <!-- Activity & Compliance nested submenu -->
            <div class="menu-section nested">
                <div class="menu-section-header nested-header" onclick="toggleSubmenu('activity-menu'); event.stopPropagation();">
                    <span>📊 Activity & Compliance</span>
                    <span class="menu-toggle-icon" id="activity-menu-icon">▶</span>
                </div>
                <div class="menu-section-children" id="activity-menu">
                    <a href="/modules/reports/activity.php">Activity Feed</a>
                    <a href="/modules/medications/compliance.php">Compliance Calendar</a>
                    <a href="/modules/reports/compliance.php">Compliance Explorer</a>
                    <a href="/modules/reports/exports.php">📊 Exports & Reports</a>
                </div>
            </div>
            <a href="/modules/medications/log_prn.php">Log PRN</a>
            <a href="/medications/stock">Medication Stock</a>
            <a href="/medications">My Medications</a>
        </div>
    </div>
    
    <a href="/profile">👤 My Profile</a>
    
    <div class="menu-section">
        <div class="menu-section-header" onclick="toggleSubmenu('settings-menu')">
            <span>⚙️ Settings</span>
            <span class="menu-toggle-icon" id="settings-menu-icon">▶</span>
        </div>
        <div class="menu-section-children" id="settings-menu">
            <?php if ($isAdmin): ?>
            <div class="menu-section nested">
                <div class="menu-section-header nested-header" onclick="toggleSubmenu('admin-menu'); event.stopPropagation();">
                    <span>🔐 Admin Panel</span>
                    <span class="menu-toggle-icon" id="admin-menu-icon">▶</span>
                </div>
                <div class="menu-section-children" id="admin-menu">
                    <a href="/modules/admin/users.php">User Management</a>
                    <a href="/modules/admin/dropdown_maintenance.php">🛠️ Dropdown Maintenance</a>
                </div>
            </div>
            <?php endif; ?>
            <a href="/modules/settings/linked_users.php">👥 Linked Users</a>
            <a href="/settings/notifications">🔔 Notifications</a>
            <a href="/modules/settings/preferences.php">⚡ Preferences</a>
            <a href="/modules/settings/two_factor.php">🔒 Two-Factor Auth</a>
        </div>
    </div>
    
    <a href="/logout">🚪 Logout</a>
</div>

<!-- Phase 5: UI Polish & Mobile Optimization -->
<link rel="stylesheet" href="/assets/css/mobile.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/accessibility.css?v=<?= time() ?>">
<script src="/assets/js/confirm-modal.js?v=<?= time() ?>"></script>
<script src="/assets/js/notifications.js?v=<?= time() ?>" defer></script>
<script src="/assets/js/error-handler.js?v=<?= time() ?>" defer></script>
<script src="/assets/js/performance.js?v=<?= time() ?>" defer></script>
