# iOS Push Notifications Integration Guide

This guide explains how native iOS push notifications are integrated with OneSignal for the Health Tracker app using Capacitor.

## Overview

The Health Tracker iOS app supports native push notifications for medication reminders and alerts. The system uses:

- **Capacitor Push Notifications Plugin** (`@capacitor/push-notifications`) for native iOS integration
- **OneSignal** as the backend push notification service
- **APNs** (Apple Push Notification service) for iOS device delivery

## Architecture

### Flow Diagram

```
iOS Device → APNs → OneSignal → Backend API → Database
     ↑                                            ↓
     └────── Device Token Registration ──────────┘
```

### Components

1. **Native iOS App** (Swift/Capacitor)
   - Requests notification permissions
   - Registers with APNs
   - Receives device token
   - Handles incoming notifications

2. **JavaScript Handler** (`capacitor-push.js`)
   - Initializes push notifications
   - Registers device token with backend
   - Handles notification events
   - Shows in-app notifications

3. **Backend API** (PHP)
   - `/api/push-devices.php` - Device token registration
   - `/api/push-send.php` - Send notifications via OneSignal
   - Database storage for device tokens

4. **OneSignal Service**
   - Manages device subscriptions
   - Delivers notifications to devices
   - Provides targeting and analytics

## Setup Instructions

### 1. Prerequisites

- Xcode installed on macOS
- Apple Developer Account (for production push notifications)
- OneSignal account with iOS app configured
- APNs authentication key or certificate uploaded to OneSignal

### 2. Install Dependencies

```bash
cd /home/runner/work/health/health
npm install
```

This installs:
- `@capacitor/push-notifications@^5.1.2`
- Other Capacitor plugins

### 3. Database Migration

Run the device token migration:

```bash
php run_migration.php migration_add_device_tokens
```

This adds the following columns to `user_notification_settings`:
- `device_token` - APNs device token
- `platform` - Device platform (ios, android, web)
- `device_id` - Unique device identifier
- `last_token_update` - Timestamp of last token update

### 4. Sync Capacitor

Sync the iOS project with the updated configuration:

```bash
npm run ios:build
# or
npx cap sync ios
```

### 5. Configure OneSignal

Ensure OneSignal credentials are set in `/home/runner/work/health/health/config.php`:

```php
define('ONESIGNAL_APP_ID', 'your-app-id');
define('ONESIGNAL_REST_API_KEY', 'your-rest-api-key');
```

### 6. iOS Project Configuration

The following are already configured:

**Info.plist** (`ios/App/App/Info.plist`):
- `UIBackgroundModes` with `remote-notification`
- `NSUserNotificationsUsageDescription` for permission prompt

**AppDelegate.swift** (`ios/App/App/AppDelegate.swift`):
- `UNUserNotificationCenterDelegate` implementation
- APNs registration callbacks
- Notification presentation handlers

**capacitor.config.json**:
- `PushNotifications` plugin configuration
- Presentation options (badge, sound, alert)

### 7. Build and Run iOS App

Open the iOS project in Xcode:

```bash
npm run ios:open
```

Or build and run:

```bash
npm run ios:run
```

## How It Works

### 1. Initialization

When the app launches on iOS:

1. `capacitor-push.js` detects Capacitor environment
2. Checks notification permissions
3. Requests permissions if needed
4. Registers with APNs
5. Receives device token from iOS

### 2. Device Token Registration

```javascript
// In capacitor-push.js
await PushNotifications.addListener('registration', async (token) => {
    // Register with backend
    await registerDeviceToken(token.value);
});
```

The device token is sent to `/api/push-devices.php` which:
- Validates the token
- Registers device with OneSignal
- Stores token in database
- Returns OneSignal player ID

### 3. Sending Notifications

To send a push notification to a user:

```php
// Example: Send medication reminder
$notification = [
    'user_id' => $user_id,
    'title' => 'Medication Reminder',
    'message' => 'Time to take your medication',
    'data' => [
        'type' => 'medication_reminder',
        'medication_id' => $medication_id
    ]
];

// POST to /api/push-send.php
```

The backend:
1. Looks up user's OneSignal player ID
2. Sends notification via OneSignal API
3. OneSignal delivers to APNs
4. APNs delivers to iOS device

### 4. Receiving Notifications

When a notification is received:

**App in Background:**
- iOS shows notification in notification center
- User taps notification → App opens
- `pushNotificationActionPerformed` event fires
- App navigates to relevant screen

**App in Foreground:**
- `pushNotificationReceived` event fires
- Custom in-app banner displayed
- Notification also shown in notification center

## API Reference

### Device Registration API (`/api/push-devices.php`)

**Register Device**
```javascript
POST /api/push-devices.php
{
    "action": "register",
    "device_token": "abc123...",
    "platform": "ios",
    "device_id": "unique-device-id"
}
```

**Check Status**
```javascript
POST /api/push-devices.php
{
    "action": "status"
}
```

**Unregister Device**
```javascript
POST /api/push-devices.php
{
    "action": "unregister"
}
```

### Send Notification API (`/api/push-send.php`)

```javascript
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

## Notification Types

The system supports different notification types with custom data:

### Medication Reminder
```javascript
{
    "type": "medication_reminder",
    "medication_id": 123,
    "medication_name": "Aspirin"
}
```

### Overdue Alert
```javascript
{
    "type": "overdue_alert",
    "medication_id": 123,
    "hours_overdue": 2
}
```

### Partner Notification
```javascript
{
    "type": "partner_took_med",
    "medication_id": 123,
    "partner_name": "John"
}
```

### Custom URL
```javascript
{
    "url": "/modules/medications/view.php?id=123"
}
```

## Permissions and Privacy

### Notification Permission

iOS requires explicit user permission for notifications. The app:

1. Checks current permission status
2. Requests permission on first launch or when user enables notifications
3. Respects user's choice (grant or deny)
4. Provides clear explanation in permission prompt

### Permission Prompt Text

Configured in `Info.plist`:
```
"Health Tracker needs permission to send you medication reminders and notifications."
```

### Privacy Considerations

- Device tokens are stored securely in database
- Tokens are user-specific and session-independent
- Tokens can be removed by unregistering device
- All API calls require authentication

## Testing

### Test Device Registration

1. Open app on iOS device/simulator
2. Grant notification permission when prompted
3. Check browser console for "Push registration success"
4. Verify database entry in `user_notification_settings`

### Test Notification Delivery

**Option 1: Via OneSignal Dashboard**
1. Log into OneSignal dashboard
2. Go to "Messages" → "New Push"
3. Select specific device by Player ID
4. Send test notification

**Option 2: Via Backend API**
```bash
curl -X POST https://your-domain.com/api/push-send.php \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "title": "Test",
    "message": "Test notification"
  }'
```

**Option 3: Via Medication Reminder**
1. Add medication with dose time in next 2 minutes
2. Wait for scheduled notification
3. Verify notification appears

## Troubleshooting

### "Permission Denied" Error

**Symptom:** Push notification permission is denied

**Solutions:**
1. Check iOS Settings → Health Tracker → Notifications
2. Enable "Allow Notifications"
3. Restart app and check permission status

### Device Token Not Registering

**Symptom:** No device token in database

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify `/api/push-devices.php` is accessible
3. Check PHP error logs for server-side issues
4. Ensure user is logged in (session active)

### Notifications Not Received

**Symptom:** Notifications sent but not delivered

**Solutions:**
1. **Check OneSignal Dashboard:** Verify delivery status
2. **Check Player ID:** Ensure correct player ID in database
3. **Check APNs Certificate:** Verify APNs auth key is configured in OneSignal
4. **Check Device Status:** Ensure device is online and app is installed
5. **Check iOS Settings:** Verify notifications are enabled in iOS Settings

### "OneSignal Not Configured" Error

**Symptom:** API returns OneSignal configuration error

**Solutions:**
1. Check `config.php` has valid `ONESIGNAL_APP_ID` and `ONESIGNAL_REST_API_KEY`
2. Verify credentials in OneSignal dashboard
3. Ensure credentials are not placeholder values

### Notifications Work on Simulator but Not Device

**Symptom:** Push notifications work in simulator but fail on real device

**Solutions:**
1. **Production APNs:** Ensure production APNs certificate/key is uploaded to OneSignal
2. **Build Configuration:** Use production build, not development
3. **Device Provisioning:** Ensure device is properly provisioned

## Advanced Configuration

### Custom Notification Sounds

Add custom notification sounds:

1. Add `.caf` sound file to `ios/App/App/Sounds/`
2. Update notification payload:
```php
$payload['ios_sound'] = 'custom_sound.caf';
```

### Action Buttons

Add action buttons to notifications:

```php
$payload['buttons'] = [
    ['id' => 'mark_taken', 'text' => 'Mark as Taken'],
    ['id' => 'snooze', 'text' => 'Snooze']
];
```

Handle in `capacitor-push.js`:
```javascript
await PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
    const actionId = notification.actionId;
    if (actionId === 'mark_taken') {
        // Handle mark as taken
    }
});
```

### Badge Count

Update app badge count:

```php
$payload['ios_badgeType'] = 'Increase'; // or 'SetTo'
$payload['ios_badgeCount'] = 1;
```

### Notification Grouping

Group related notifications:

```php
$payload['thread_id'] = 'medication-reminders';
$payload['collapse_id'] = 'med-' . $medication_id;
```

## Security Best Practices

1. **Never expose API keys** in client-side code
2. **Validate all inputs** in backend APIs
3. **Use HTTPS** for all API communications
4. **Authenticate all API requests** - check session
5. **Sanitize notification content** to prevent XSS
6. **Rate limit** notification sending to prevent abuse
7. **Store tokens securely** in database with proper encryption if needed

## Monitoring and Analytics

### OneSignal Dashboard

Monitor push notification performance:

1. **Delivery Stats:** See sent, delivered, clicked rates
2. **Device Stats:** View active devices by platform
3. **Error Logs:** Check failed deliveries and errors

### Database Queries

Check device registration:
```sql
SELECT user_id, platform, device_token, 
       onesignal_player_id, last_token_update
FROM user_notification_settings
WHERE device_token IS NOT NULL;
```

Check notification settings:
```sql
SELECT user_id, notifications_enabled, platform
FROM user_notification_settings
WHERE notifications_enabled = 1;
```

## Migration from Web Push

If migrating from web push to native iOS:

1. Users will need to grant permission again (different permission)
2. Web push continues to work for web browsers
3. Native push works for iOS app
4. Backend can detect platform and send to appropriate service
5. No data loss - existing notification preferences preserved

## Production Deployment Checklist

- [ ] OneSignal production credentials configured
- [ ] APNs production certificate/key uploaded to OneSignal
- [ ] Database migration applied to production
- [ ] iOS app built with production certificate
- [ ] HTTPS enabled on backend
- [ ] Error logging configured
- [ ] Rate limiting implemented
- [ ] Monitoring set up in OneSignal dashboard
- [ ] Test notifications sent to real devices
- [ ] User notification settings migrated

## Support and Resources

- **Capacitor Push Notifications:** https://capacitorjs.com/docs/apis/push-notifications
- **OneSignal iOS Setup:** https://documentation.onesignal.com/docs/ios-sdk-setup
- **APNs Documentation:** https://developer.apple.com/documentation/usernotifications
- **Health Tracker Issues:** See main README.md

## Limitations

1. **iOS Simulator:** Push notifications partially work in simulator (registration works, delivery may not)
2. **APNs Requirements:** Requires Apple Developer account for production
3. **OneSignal Free Tier:** Unlimited notifications but basic analytics only
4. **Network Dependency:** Requires internet connection for registration and delivery
5. **User Permission:** Users must grant permission - cannot force

## Future Enhancements

- [ ] Rich notifications with images
- [ ] Notification categories and customization
- [ ] Local notifications for offline scenarios
- [ ] Advanced notification scheduling
- [ ] Notification history and management UI
- [ ] Multi-device support per user
- [ ] Do Not Disturb hours configuration
