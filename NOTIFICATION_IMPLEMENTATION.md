# Notification Settings Implementation

## Overview
This implementation adds Push Notification settings to the PHP dashboard, allowing users to configure medication reminder notifications similar to the PWA implementation.

## Changes Made

### 1. Database Schema
**File:** `database/migrations/migration_create_notification_settings.sql`

Created a new table `user_notification_settings` to store user notification preferences:
- `notifications_enabled` - Whether push notifications are enabled for the user
- `notify_at_time` - Send reminder at scheduled medication time
- `notify_after_10min` - Send reminder 10 minutes after if not taken
- `notify_after_20min` - Send reminder 20 minutes after if not taken
- `notify_after_30min` - Send reminder 30 minutes after if not taken
- `notify_after_60min` - Send reminder 60 minutes after if not taken
- `onesignal_player_id` - OneSignal device identifier for push notifications

### 2. Settings Module
**Directory:** `public/modules/settings/`

Created new settings module with:

#### notifications.php
- Full notification settings UI
- OneSignal SDK integration
- Toggle switches for notification timing preferences
- Enable/Disable notification functionality
- Auto-save feature for preference changes
- Device-specific notification management

#### save_notifications_handler.php
- Backend handler to save notification preferences to database
- Handles both creation and updates of user settings
- Validates user authentication
- Returns JSON responses for AJAX requests

### 3. Menu Integration
**File:** `app/includes/menu.php`

Added "🔔 Notifications" link under the Settings section in the main navigation menu, making it accessible to all users.

### 4. Helper Files
**File:** `run_migration.php` (temporary)

Created a PHP-based migration runner for environments where MySQL CLI isn't available. This file can be deleted after migration is complete.

## Deployment Instructions

### Step 1: Run Database Migration
Choose one of the following methods:

**Option A: Using MySQL Command Line**
```bash
mysql -h hostname -u u983097270_ht -p u983097270_ht < database/migrations/migration_create_notification_settings.sql
```

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select database `u983097270_ht`
3. Go to SQL tab
4. Copy and paste content from `database/migrations/migration_create_notification_settings.sql`
5. Click "Go" to execute

**Option C: Using the PHP Migration Runner**
1. Access `https://yourdomain.com/run_migration.php` in your browser
2. Verify the migration completed successfully
3. **Important:** Delete `run_migration.php` after completion for security

### Step 2: Verify OneSignal Configuration
Ensure `config.php` has valid OneSignal credentials:
- `ONESIGNAL_APP_ID` - Your OneSignal App ID
- `ONESIGNAL_REST_API_KEY` - Your OneSignal REST API Key

See `ONESIGNAL_CONFIG_GUIDE.md` for detailed setup instructions.

### Step 3: Test the Implementation
1. Log in as a standard user
2. Click the hamburger menu
3. Expand "⚙️ Settings"
4. Click "🔔 Notifications"
5. Click "Enable Notifications" and grant permission
6. Verify the notification settings UI appears
7. Toggle notification timing preferences
8. Verify preferences are saved (check browser console for "Settings auto-saved")

## Features

### User Interface
- Clean, modern toggle switches for each notification timing option
- Clear visual feedback when notifications are enabled/disabled
- Device-specific notification management (users can have different settings per device)
- Auto-save functionality - no need to click a save button after toggling options
- Informative status messages and help text

### Notification Timing Options
Users can customize when they receive reminders:
- **At scheduled time** - Get notified when medication is due
- **10 minutes after** - If not taken, remind again
- **20 minutes after** - If still not taken, remind again
- **30 minutes after** - If still not taken, remind again
- **60 minutes after** - Optional additional reminder

### OneSignal Integration
- Seamless OneSignal SDK integration
- Automatic player ID tracking for targeted notifications
- Graceful fallback if OneSignal is not configured
- Browser permission management

## Technical Details

### Security Considerations
- All database queries use prepared statements (PDO)
- User authentication required for all notification endpoints
- XSS protection with `htmlspecialchars()` for output
- CSRF protection through session validation
- OneSignal REST API key kept server-side only

### Browser Compatibility
- Works on all modern browsers that support:
  - Web Push API
  - JavaScript ES6+
  - CSS Grid and Flexbox
- Graceful degradation for browsers without notification support

### Database Performance
- Indexed `user_id` column for fast lookups
- Unique constraint prevents duplicate settings per user
- Automatic timestamps for tracking changes
- Cascading delete maintains referential integrity

## Troubleshooting

### Issue: "OneSignal not configured" message
**Solution:** Update `config.php` with valid OneSignal credentials

### Issue: Notification permission denied
**Solution:** User must manually enable in browser settings, then revisit the page

### Issue: Settings not saving
**Solution:** Check browser console for errors, verify database connection, ensure migration was run successfully

### Issue: Migration fails
**Solution:** Verify database credentials, check if table already exists, review error messages in migration output

## Future Enhancements

Potential improvements for future iterations:
- Server-side notification scheduling logic
- Notification history/log
- Bulk notification management (disable all, enable all)
- Email notifications as fallback
- SMS notifications integration
- Notification sound customization
- Quiet hours/Do Not Disturb scheduling

## Files Modified/Created

### New Files
- `database/migrations/migration_create_notification_settings.sql`
- `public/modules/settings/notifications.php`
- `public/modules/settings/save_notifications_handler.php`
- `run_migration.php` (temporary)
- `NOTIFICATION_IMPLEMENTATION.md` (this file)

### Modified Files
- `app/includes/menu.php` - Added Notifications link
- `database/migrations/README.md` - Added migration documentation

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Review server logs for PHP errors
3. Verify database migration was successful
4. Confirm OneSignal configuration is correct
5. Test in multiple browsers/devices
