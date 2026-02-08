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

// Enhanced nudge with better UX
function sendNudge(medicationId, toUserId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    if (!confirm('Send a gentle reminder to take this medication?')) {
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
            alert('Error: ' + data.error);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
