# OneSignal Player ID Implementation Summary

## Overview

This implementation ensures that OneSignal Player IDs are properly stored, retrieved, and used for targeted push notifications. This solves the issue where notification settings were lost on page refresh and reminders couldn't be sent reliably to specific users/devices.

## Changes Made

### 1. Frontend Updates (`public/modules/settings/notifications.php`)

**Changes:**
- Added `storedPlayerId` JavaScript variable that reads from database on page load
- Updated `checkNotificationPermission()` to check both `notificationsEnabled` flag AND stored Player ID
- Now correctly shows notification settings if user has granted permission and has a Player ID in the database

**Key Code:**
```javascript
let storedPlayerId = <?= $settings['onesignal_player_id'] ? '"' . htmlspecialchars($settings['onesignal_player_id'], ENT_QUOTES, 'UTF-8') . '"' : 'null' ?>;

// Show settings if permission granted AND (notifications enabled OR Player ID exists)
if (permission === 'granted' && (notificationsEnabled || storedPlayerId)) {
    showNotificationSettings();
}
```

**Existing Functionality (Already Working):**
- Player ID retrieval: `window.OneSignal.User.PushSubscription.id`
- Sending Player ID to backend via `saveNotificationStatus()` function
- Backend storing Player ID in database

### 2. Backend Service (`app/services/NotificationService.php`)

**New Service Created:**
A comprehensive service for sending targeted OneSignal notifications using Player IDs.

**Key Methods:**
- `getActivePlayerIds()`: Gets all Player IDs for users with notifications enabled
- `getUserPlayerId($userId)`: Gets Player ID for a specific user
- `sendNotification($playerIds, $title, $message, $data)`: Sends push notification to specific Player IDs
- `sendToUser($userId, $title, $message, $data)`: Sends to a single user
- `sendToAll($title, $message, $data)`: Sends to all users with active notifications

**Key Features:**
- Uses `include_player_ids` instead of `included_segments: ['All']` for targeted delivery
- Queries database for Player IDs from `user_notification_settings` table
- Only sends to users with `notifications_enabled = 1` and valid Player ID
- Returns detailed response with success/failure status

### 3. Cron Script (`app/cron/send_medication_reminders.php`)

**New Cron Script:**
PHP CLI script for sending scheduled medication reminders.

**How It Works:**
1. Runs every minute via cron
2. Queries `medication_logs` for pending doses scheduled today
3. Joins with `user_notification_settings` to get Player IDs and preferences
4. Calculates time difference between scheduled time and current time
5. Sends notification if time matches a reminder window (at time, +10min, +20min, +30min, +60min)
6. Uses `NotificationService` to send targeted notifications to specific Player IDs

**Key SQL Query:**
```sql
SELECT ml.*, m.*, uns.* 
FROM medication_logs ml
INNER JOIN medications m ON ml.medication_id = m.id
INNER JOIN user_notification_settings uns ON ml.user_id = uns.user_id
WHERE ml.status = 'pending'
AND uns.notifications_enabled = 1
AND uns.onesignal_player_id IS NOT NULL
```

### 4. Documentation (`app/cron/README.md`)

Complete setup guide for configuring the cron job.

## Database Schema

The `user_notification_settings` table already has the required column:

```sql
CREATE TABLE user_notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notifications_enabled BOOLEAN DEFAULT 0,
    notify_at_time BOOLEAN DEFAULT 1,
    notify_after_10min BOOLEAN DEFAULT 1,
    notify_after_20min BOOLEAN DEFAULT 1,
    notify_after_30min BOOLEAN DEFAULT 1,
    notify_after_60min BOOLEAN DEFAULT 0,
    onesignal_player_id VARCHAR(255) NULL,  -- Stores the Player ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Complete User Flow

### Initial Opt-in
1. User visits `/modules/settings/notifications.php`
2. Clicks "Enable Notifications"
3. Browser prompts for permission
4. User grants permission
5. Frontend subscribes to OneSignal: `OneSignal.User.PushSubscription.optIn()`
6. Frontend retrieves Player ID: `window.OneSignal.User.PushSubscription.id`
7. Frontend sends to backend: `saveNotificationStatus(true, playerId)`
8. Backend stores in database: `onesignal_player_id` column
9. UI updates to show settings

### Page Refresh
1. User returns to notification settings page
2. PHP loads settings from database including `onesignal_player_id`
3. JavaScript receives `storedPlayerId` variable
4. Checks browser permission: `Notification.permission`
5. If permission granted AND (notificationsEnabled OR storedPlayerId):
   - Shows notification settings (enabled state)
6. User sees correct status without re-opting in

### Scheduled Notifications
1. Cron runs every minute: `php app/cron/send_medication_reminders.php`
2. Queries database for pending medication doses
3. Filters users with `notifications_enabled = 1` and valid `onesignal_player_id`
4. Checks user notification preferences (notify_at_time, etc.)
5. Sends targeted notification to specific Player ID via OneSignal REST API
6. Uses `include_player_ids: [playerId]` for device-specific delivery

## Setup Instructions

### 1. Verify OneSignal Configuration

Ensure `config.php` has valid credentials:
```php
define('ONESIGNAL_APP_ID', 'your-app-id');
define('ONESIGNAL_REST_API_KEY', 'your-rest-api-key');
```

### 2. Set Up Cron Job

Add to crontab:
```bash
* * * * * /usr/bin/php /path/to/health/app/cron/send_medication_reminders.php >> /path/to/health/app/logs/cron.log 2>&1
```

### 3. Test the Implementation

1. **Test Frontend:**
   - Visit notification settings page
   - Enable notifications
   - Check browser console for Player ID log
   - Refresh page and verify status persists

2. **Test NotificationService:**
   ```bash
   php app/cron/test_notification_service.php
   ```

3. **Test Cron Script:**
   ```bash
   php app/cron/send_medication_reminders.php
   ```

## Troubleshooting

### Player ID Not Stored
- Check browser console for errors during opt-in
- Verify `saveNotificationStatus()` is called with valid Player ID
- Check `save_notifications_handler.php` receives `onesignal_player_id` POST parameter

### Notifications Not Received
- Verify cron is running: `tail -f /path/to/health/app/logs/cron.log`
- Check OneSignal credentials in `config.php`
- Ensure `medication_logs` has pending entries
- Verify user has valid Player ID in database

### Status Not Persisting After Refresh
- Check database: `SELECT * FROM user_notification_settings WHERE user_id = X`
- Verify `onesignal_player_id` column has a value
- Check JavaScript console for `storedPlayerId` value

## Security Considerations

1. **REST API Key:** Never exposed to frontend, only used in backend PHP
2. **Player ID Validation:** Only active IDs with enabled notifications are used
3. **User Authorization:** All database queries filter by user_id for proper access control
4. **SQL Injection:** All queries use prepared statements with parameter binding

## Testing Checklist

- [ ] User can enable notifications and Player ID is stored
- [ ] After page refresh, notification status is correctly shown
- [ ] Player ID persists in database after browser close/reopen
- [ ] Cron script finds users with active Player IDs
- [ ] Notifications are sent to specific devices (not all users)
- [ ] Multiple devices per user work correctly (if applicable)
- [ ] Disabling notifications removes/clears Player ID appropriately

## Migration Notes

**No database migration required** - the `onesignal_player_id` column already exists in the schema.

**Node.js Server Note:** The existing Node.js server (`server/index.js`) uses file-based storage and sends to all users. The new PHP-based cron script uses the database and sends targeted notifications. Both can coexist, but the PHP cron should be preferred for production as it uses the real database.
