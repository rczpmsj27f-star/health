# iOS Push Notifications - Quick Start Guide

This guide will help you quickly get iOS native push notifications working in the Health Tracker app.

## Overview

The Health Tracker now supports **native iOS push notifications** via:
- Capacitor Push Notifications Plugin
- OneSignal backend service
- Apple Push Notification service (APNs)

## Quick Setup (5 Steps)

### Step 1: Run Database Migration

Visit this URL in your browser (or run via CLI):
```
https://your-domain.com/run_device_tokens_migration.php
```

Or via command line:
```bash
php run_device_tokens_migration.php
```

✅ **Expected output:** "Migration completed successfully!"

After completion, delete `run_device_tokens_migration.php` for security.

### Step 2: Sync Capacitor

From the project root:
```bash
npm run ios:build
# or
npx cap sync ios
```

✅ **Expected output:** "Sync finished" with push-notifications plugin listed

### Step 3: Open in Xcode

```bash
npm run ios:open
```

This opens the iOS project in Xcode.

### Step 4: Configure Code Signing

In Xcode:
1. Select the App target
2. Go to "Signing & Capabilities"
3. Select your Team
4. Ensure "Push Notifications" capability is added
5. Ensure "Background Modes" → "Remote notifications" is checked

### Step 5: Build and Run

Click the Play button in Xcode to build and run on simulator or device.

When the app launches:
1. Navigate to Settings → Notification Preferences
2. You'll see "iOS Native Push Notifications" section
3. Click "Enable iOS Push Notifications"
4. Grant permission when iOS prompts

✅ **Success indicator:** "✅ iOS Push Notifications Enabled" message appears

## Testing Push Notifications

### Method 1: Via OneSignal Dashboard

1. Log into [OneSignal Dashboard](https://onesignal.com)
2. Go to **Messages** → **New Push**
3. Click **Send to Test Users**
4. Enter the Player ID (visible in database or console logs)
5. Set title and message
6. Click **Send Message**

### Method 2: Via Backend API

```bash
curl -X POST https://your-domain.com/api/push-send.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{
    "user_id": 1,
    "title": "Test Notification",
    "message": "This is a test push notification!",
    "data": {
      "type": "test"
    }
  }'
```

### Method 3: Via Medication Reminder

The most realistic test:

1. Add a medication in the app
2. Set a dose time for 2 minutes from now
3. Wait for the scheduled time
4. You should receive a push notification

## Verification Checklist

- [ ] Database migration completed
- [ ] `capacitor-push.js` loaded in notification settings page
- [ ] iOS project synced with Capacitor
- [ ] Push Notifications capability enabled in Xcode
- [ ] App built and running on device/simulator
- [ ] Permission granted in iOS settings
- [ ] "✅ iOS Push Notifications Enabled" appears in app
- [ ] Device token stored in database
- [ ] OneSignal player ID created
- [ ] Test notification received

## Troubleshooting

### "Migration file not found"

**Solution:** Ensure you're running from the project root directory.

### "iOS Push Notifications Not Enabled"

**Causes:**
1. Permission denied by user
2. Device token registration failed
3. OneSignal credentials not configured

**Solutions:**
1. Check iOS Settings → Health Tracker → Notifications
2. Check browser console for JavaScript errors
3. Verify `config.php` has valid OneSignal credentials

### Notifications not received

**Check:**
1. User has granted notification permission in iOS Settings
2. Device token is in database: `SELECT device_token FROM user_notification_settings WHERE user_id = ?`
3. OneSignal player ID exists: `SELECT onesignal_player_id FROM user_notification_settings WHERE user_id = ?`
4. APNs certificate/key is uploaded to OneSignal dashboard
5. App is running production build (for real devices)

### "OneSignal credentials not configured"

**Solution:** Check `/home/runner/work/health/health/config.php`:
```php
define('ONESIGNAL_APP_ID', 'your-actual-app-id');
define('ONESIGNAL_REST_API_KEY', 'your-actual-api-key');
```

Get credentials from [OneSignal Dashboard](https://onesignal.com) → Settings → Keys & IDs

## Database Schema

The migration adds these columns to `user_notification_settings`:

| Column | Type | Description |
|--------|------|-------------|
| `device_token` | VARCHAR(255) | APNs device token |
| `platform` | VARCHAR(50) | Device platform (ios/android/web) |
| `device_id` | VARCHAR(255) | Unique device identifier |
| `last_token_update` | TIMESTAMP | Last token update time |

## API Endpoints

### Register Device Token
```
POST /api/push-devices.php
{
  "action": "register",
  "device_token": "abc123...",
  "platform": "ios",
  "device_id": "unique-id"
}
```

### Check Registration Status
```
POST /api/push-devices.php
{
  "action": "status"
}
```

### Send Push Notification
```
POST /api/push-send.php
{
  "user_id": 123,
  "title": "Notification Title",
  "message": "Notification message",
  "data": {
    "type": "medication_reminder",
    "medication_id": 456
  }
}
```

## Files Modified/Created

### New Files:
- `public/assets/js/capacitor-push.js` - Native push handler
- `public/api/push-devices.php` - Device registration API
- `public/api/push-send.php` - Send notification API
- `database/migrations/migration_add_device_tokens.sql` - Database schema
- `run_device_tokens_migration.php` - Migration runner
- `IOS_PUSH_NOTIFICATIONS.md` - Detailed documentation

### Modified Files:
- `package.json` - Added @capacitor/push-notifications
- `capacitor.config.json` - Added PushNotifications config
- `ios/App/App/Info.plist` - Added push permissions
- `ios/App/App/AppDelegate.swift` - Added push delegates
- `public/modules/settings/notifications.php` - Added iOS push UI
- `app/core/NotificationHelper.php` - Added push sending logic

## Production Deployment

### Before deploying to production:

1. **Upload APNs Certificate to OneSignal**
   - Go to OneSignal Dashboard → Settings → Platforms → iOS
   - Upload your APNs authentication key or certificate
   - Use production certificate for production builds

2. **Build with Production Certificate**
   - In Xcode, select "Any iOS Device (arm64)"
   - Select "Product" → "Archive"
   - Use production provisioning profile

3. **Enable HTTPS**
   - Ensure backend is served over HTTPS
   - Update `capacitor.config.json` server URL to HTTPS

4. **Test on Real Device**
   - Simulators may not reliably receive notifications
   - Test on actual iPhone/iPad

## Support

For detailed documentation, see:
- `IOS_PUSH_NOTIFICATIONS.md` - Comprehensive guide
- [Capacitor Docs](https://capacitorjs.com/docs/apis/push-notifications)
- [OneSignal Docs](https://documentation.onesignal.com/docs/ios-sdk-setup)

## Common Notification Types

| Type | Description | Auto-sent by |
|------|-------------|-------------|
| `medication_reminder` | Medication dose reminder | Scheduler |
| `overdue_alert` | Medication overdue alert | Scheduler |
| `partner_took_med` | Partner took medication | Medication log |
| `partner_overdue` | Partner has overdue meds | Scheduler |
| `nudge_received` | Nudge from partner | Nudge handler |
| `link_request` | Link request received | Link handler |
| `link_accepted` | Link request accepted | Link handler |
| `stock_low` | Stock running low | Stock tracker |

All notification types automatically send via:
- In-app notifications (always)
- Push notifications (if enabled and registered)
- Email (if enabled)

Based on user preferences in Settings → Notification Preferences.
