# OneSignal Setup Guide

This guide explains how to configure OneSignal for push notifications in the Medication Reminder PWA.

## Overview

This application uses OneSignal for web push notifications instead of custom VAPID/web-push implementation. OneSignal provides:

- Easy setup and configuration
- Better deliverability across browsers
- Built-in analytics and debugging
- Support for multiple platforms
- Free tier for small projects

## Step-by-Step Setup

### 1. Create a OneSignal Account

1. Go to [OneSignal.com](https://onesignal.com/)
2. Click "Get Started Free"
3. Sign up with your email or social account
4. Verify your email address

### 2. Create a New App

1. After logging in, click "New App/Website"
2. Enter a name for your app (e.g., "Medication Reminder")
3. Select "Web Push" as the platform
4. Click "Create App"

### 3. Configure Web Push

1. You'll be taken to the Web Push configuration
2. Choose "Typical Site" (not WordPress or custom code)
3. Enter your Site URL:
   - For **development**: `http://localhost:3000`
   - For **production**: Your actual domain (e.g., `https://yourdomain.com`)
4. Upload an icon (optional, the app has built-in icons)
5. Click "Save"

### 4. Get Your Credentials

1. Go to **Settings** > **Keys & IDs**
2. Copy these two values:
   - **OneSignal App ID** (looks like: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`)
   - **REST API Key** (long alphanumeric string)

### 5. Configure the Server

You have two options to set your credentials:

#### Option A: Environment Variables (Recommended for Production)

```bash
export ONESIGNAL_APP_ID="your-app-id-here"
export ONESIGNAL_API_KEY="your-rest-api-key-here"
```

Then start the server:
```bash
cd server
npm start
```

#### Option B: Direct Configuration (Good for Development)

Edit `server/index.js` and update lines 11-12:

```javascript
const ONESIGNAL_APP_ID = 'your-app-id-here';
const ONESIGNAL_API_KEY = 'your-rest-api-key-here';
```

### 6. Test Your Setup

1. Start the server: `cd server && npm start`
2. Open the app in your browser: `http://localhost:3000`
3. Click Settings ⚙️
4. Click "Enable Notifications"
5. Grant permission when prompted
6. Add a test medication with a time in the next 2-3 minutes
7. Wait for the notification

## Verification Checklist

- [ ] OneSignal App ID is configured
- [ ] OneSignal REST API Key is configured
- [ ] Server starts without errors
- [ ] Browser console shows "OneSignal initialized"
- [ ] Notification permission prompt appears
- [ ] Test notifications are received

## Troubleshooting

### "OneSignal App ID not configured" warning

**Problem**: Server is using placeholder credentials

**Solution**: Set your actual OneSignal App ID and API Key

### Notifications not appearing

**Possible causes**:

1. **Permission not granted**: Check browser notification settings
2. **Service worker not registered**: Check DevTools > Application > Service Workers
3. **Wrong URL configured**: Ensure OneSignal is configured for your domain
4. **Browser compatibility**: Use Chrome, Firefox, Edge, or Safari (latest versions)

### OneSignal initialization fails

**Check these**:

1. OneSignal SDK script is loaded: Check Network tab for `OneSignalSDK.page.js`
2. App ID is valid: Verify in OneSignal dashboard
3. Browser console for error messages

### Server errors when sending notifications

**Common issues**:

1. **Invalid API Key**: Verify the REST API Key in OneSignal dashboard
2. **Network issues**: Check server can reach `https://onesignal.com/api/v1/notifications`
3. **Invalid payload**: Check server logs for OneSignal API errors

## Advanced Configuration

### Targeting Specific Users

By default, notifications are sent to all subscribed users (`included_segments: ['All']`).

To target specific users, you can use OneSignal's player IDs or external user IDs:

```javascript
// In server/index.js, modify the notification payload
const notificationPayload = {
  app_id: ONESIGNAL_APP_ID,
  include_player_ids: ['player-id-1', 'player-id-2'], // Specific users
  // OR use external user IDs (requires setting them when user subscribes)
  include_external_user_ids: ['user-123', 'user-456'],
  headings: { en: payload.title },
  contents: { en: payload.body },
  // ... rest of payload
};
```

### Notification Actions

OneSignal supports action buttons in notifications. To enable:

```javascript
// In server/index.js notification payload
const notificationPayload = {
  // ... other fields
  buttons: [
    {
      id: 'mark-taken',
      text: '✓ Mark as Taken'
    },
    {
      id: 'snooze',
      text: '⏰ Snooze'
    }
  ]
};
```

Then handle the actions in your service worker (`sw.js`).

### Notification Scheduling

OneSignal also supports delayed notifications:

```javascript
const notificationPayload = {
  // ... other fields
  send_after: new Date(Date.now() + 10 * 60 * 1000).toISOString() // Send in 10 minutes
};
```

## Security Best Practices

1. **Never commit credentials**: Use environment variables for API keys
2. **Restrict API key**: In OneSignal dashboard, you can restrict API key to specific IPs
3. **Use HTTPS in production**: Required for web push notifications
4. **Validate data**: Always validate user input before sending notifications

## Monitoring and Analytics

### OneSignal Dashboard

1. Go to **Delivery** to see notification delivery stats
2. Go to **Audience** to see total subscribed users
3. Go to **Messages** to see all sent notifications

### Debugging

Enable verbose logging in OneSignal initialization:

```javascript
// In pwa/app.js
OneSignal.init({
  appId: appId,
  allowLocalhostAsSecureOrigin: true,
  notifyButton: { enable: false },
  // Enable debugging
  serviceWorkerParam: { scope: '/' },
  serviceWorkerPath: 'OneSignalSDKWorker.js'
});
```

Check browser console for detailed logs.

## Migration Notes

This app was migrated from custom VAPID/web-push to OneSignal. Key changes:

1. Removed `web-push` npm package
2. Removed VAPID key generation and management
3. Removed custom subscription endpoint (`/api/subscriptions`)
4. Added OneSignal SDK and configuration
5. Updated notification delivery to use OneSignal REST API

All core functionality remains the same - medication tracking, scheduling, and reminders work identically.

## Support and Resources

- **OneSignal Documentation**: https://documentation.onesignal.com/docs/web-push-quickstart
- **OneSignal Support**: https://onesignal.com/support
- **App Issues**: Check the main README.md troubleshooting section

## Free Tier Limits

OneSignal free tier includes:

- Unlimited subscribers
- Unlimited push notifications
- Basic analytics
- Email support

This is more than enough for personal use or small deployments.
