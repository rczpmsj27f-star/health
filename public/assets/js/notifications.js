// Loading spinner component
function showLoadingSpinner(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div style="display: flex; justify-content: center; align-items: center; padding: 40px;">
                <div class="spinner"></div>
            </div>
        `;
    }
}

// Enhanced notification loading with error handling
function loadNotifications() {
    showLoadingSpinner('notificationList');
    
    fetch('/api/notifications.php?action=get_recent')
        .then(r => {
            if (!r.ok) throw new Error('Network error');
            return r.json();
        })
        .then(data => {
            const list = document.getElementById('notificationList');
            
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
                     style="display: flex; align-items: center; transition: all 0.2s;">
                    <div onclick="markAsRead(${n.id})" style="flex: 1; cursor: pointer;">
                        <div style="font-weight: 600; margin-bottom: 4px;">${escapeHtml(n.title)}</div>
                        <div style="font-size: 13px; color: var(--color-text-secondary);">${escapeHtml(n.message)}</div>
                        <div style="font-size: 11px; color: var(--color-text-secondary); margin-top: 4px;">
                            ${formatTime(n.created_at)}
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); deleteNotification(${n.id})"
                            style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; margin-left: 8px; cursor: pointer; font-size: 12px; flex-shrink: 0;"
                            title="Delete notification">
                        🗑️
                    </button>
                </div>
            `).join('');
        })
        .catch(error => {
            document.getElementById('notificationList').innerHTML = `
                <div style="padding: 40px; text-align: center; color: #ef4444;">
                    <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                    <div>Failed to load notifications</div>
                    <button onclick="loadNotifications()" style="margin-top: 12px; padding: 8px 16px; background: var(--color-primary); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            `;
        });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Format time for display
function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}

// Mark notification as read
function markAsRead(notificationId) {
    fetch('/api/notifications.php', {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_read', notification_id: notificationId})
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadNotifications();
            // Force badge update with a small delay to ensure DOM is ready
            setTimeout(() => {
                if (typeof updateBadge === 'function') {
                    updateBadge();
                }
            }, 100);
        } else {
            console.error('Failed to mark as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        alert('Failed to mark notification as read. Please try again.');
    });
}

// Mark all notifications as read
function markAllRead() {
    fetch('/api/notifications.php', {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_all_read'})
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadNotifications();
            // Force badge update with a small delay to ensure DOM is ready
            setTimeout(() => {
                if (typeof updateBadge === 'function') {
                    updateBadge();
                }
            }, 100);
        } else {
            console.error('Failed to mark all as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        alert('Failed to mark all as read. Please try again.');
    });
}

/**
 * Delete a specific notification
 */
function deleteNotification(notificationId) {
    fetch('/api/notifications.php', {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete', notification_id: notificationId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            if (typeof updateBadge === 'function') {
                updateBadge();
            }
        } else {
            console.error('Failed to delete notification:', data.error);
            alert('Failed to delete notification. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
        alert('Failed to delete notification. Please try again.');
    });
}

/**
 * Update the notification badge count
 */
function updateBadge() {
    fetch('/api/notifications.php?action=get_count', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            const count = data.count || 0;

            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                
                // Force reflow to ensure update is visible
                badge.offsetHeight;
                
                console.log('✅ Badge updated to:', count);
            } else {
                console.warn('⚠️ Notification badge element not found in DOM');
            }
        }
    })
    .catch(error => {
        console.error('Error updating badge:', error);
    });
}

// Update badge on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateBadge);
} else {
    updateBadge();
}

// Enhanced nudge with better UX
async function sendNudge(medicationId, toUserId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    const confirmed = await confirmAction(
        'Send a gentle reminder to take this medication?',
        'Send Reminder'
    );
    
    if (!confirmed) {
        return;
    }
    
    button.disabled = true;
    button.innerHTML = '⏳ Sending...';
    
    const formData = new FormData();
    formData.append('medication_id', medicationId);
    formData.append('to_user_id', toUserId);
    
    fetch('/modules/medications/nudge_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '✓ Sent!';
            button.style.background = '#10b981';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                button.style.background = '';
            }, 3000);
        } else {
            showAlert('Error: ' + data.error, 'Error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        showAlert('Network error. Please try again.', 'Error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
