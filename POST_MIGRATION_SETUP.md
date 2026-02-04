# Post-Migration Setup Instructions

## For Users Upgrading from VAPID/web-push

This document explains what you need to do after the OneSignal migration.

## ⚠️ Important: Breaking Changes

The migration from VAPID/web-push to OneSignal is **not backward compatible**. You will need to:

1. ✅ Configure OneSignal credentials
2. ✅ Re-subscribe to notifications (users must grant permission again)
3. ✅ Update environment variables (if using them)

## Quick Setup (5 minutes)

### Step 1: Create OneSignal Account

1. Go to https://onesignal.com/
2. Sign up for a free account
3. Create a new **Web Push** app
4. Enter your app name (e.g., "Medication Reminder")

### Step 2: Configure Web Push

1. In OneSignal dashboard, select **Typical Site**
2. Enter your site URL:
   - Development: `http://localhost:3000`
   - Production: `https://yourdomain.com`
3. Click **Save**

### Step 3: Get Your Credentials

1. Go to **Settings** > **Keys & IDs**
2. Copy these values:
   - **OneSignal App ID**
   - **REST API Key**

### Step 4: Configure the Server

Choose one option:

#### Option A: Environment Variables (Recommended)

```bash
export ONESIGNAL_APP_ID="your-app-id-here"
export ONESIGNAL_API_KEY="your-rest-api-key-here"
cd server
npm start
```

#### Option B: Edit server/index.js

Update lines 11-12:
```javascript
const ONESIGNAL_APP_ID = 'your-app-id-here';
const ONESIGNAL_API_KEY = 'your-rest-api-key-here';
```

### Step 5: Restart and Test

1. **Stop** the server (Ctrl+C if running)
2. **Start** the server:
   ```bash
   cd server
   npm start
   ```
3. You should see:
   ```
   Medication Reminder Server running on port 3000
   OneSignal App ID: your-app-id
   Notification scheduler is active (runs every minute)
   ```
4. If you see warnings about missing credentials, go back to Step 4

### Step 6: Enable Notifications (Users)

Each user must:

1. Open the app: `http://localhost:3000` (or your domain)
2. Click the **⚙️ Settings** button
3. Click **"Enable Notifications"**
4. Grant permission when browser prompts
5. Verify you see "OneSignal initialized" in browser console (F12)

## Verification

✅ **Server Check**
```bash
# Test the OneSignal config endpoint
curl http://localhost:3000/api/onesignal-config
# Should return: {"appId":"your-app-id"}
```

✅ **Browser Check**
- Open DevTools (F12)
- Go to Console tab
- Look for "OneSignal initialized"
- Go to Application > Service Workers
- Verify service worker is registered

✅ **Notification Test**
1. Add a test medication
2. Set scheduled time 2 minutes in future
3. Enable all notification settings
4. Wait for notification
5. Check OneSignal dashboard > Delivery for stats

## Troubleshooting

### "OneSignal credentials not configured" warning

**Problem**: Placeholder values still in use

**Solution**:
- Verify you've set the environment variables correctly
- OR verify you've edited server/index.js with your actual credentials
- Restart the server after making changes

### "OneSignal is not initialized" alert

**Problem**: OneSignal SDK failed to load or initialize

**Check**:
1. Network tab: Is `OneSignalSDK.page.js` loading?
2. Console errors related to OneSignal?
3. Is App ID correct in server configuration?
4. Try hard refresh (Ctrl+Shift+R)

### Notifications not appearing

**Common causes**:
1. **Permission denied**: Check browser notification settings
2. **Service worker issues**: Unregister and re-register in DevTools
3. **Wrong domain**: Ensure OneSignal is configured for your domain
4. **API Key issues**: Verify REST API Key is correct

### Server crashes on startup

**Check**:
- All dependencies installed: `cd server && npm install`
- No syntax errors: `node -c index.js`
- Port 3000 is available
- Check server logs for specific errors

## For Production Deployment

When deploying to production:

1. **Use Environment Variables**
   ```bash
   export ONESIGNAL_APP_ID="production-app-id"
   export ONESIGNAL_API_KEY="production-api-key"
   export PORT=3000
   ```

2. **Update OneSignal Configuration**
   - In OneSignal dashboard, add your production domain
   - Update site URL to your HTTPS domain
   - Test with your production URL

3. **HTTPS Required**
   - Push notifications require HTTPS in production
   - Use Let's Encrypt or similar for free SSL

4. **Update CORS**
   - If needed, restrict CORS to your specific domain

## Data Migration

**Good news**: No data migration needed!

- ✅ All medications are preserved
- ✅ All settings are preserved
- ✅ All "taken" logs are preserved
- ❌ Old VAPID subscriptions will be discarded (users re-subscribe via OneSignal)

## What Changed for Users

### User Experience
- **Before**: Click "Enable Notifications" → Browser permission prompt → Automatically subscribed
- **After**: Click "Enable Notifications" → OneSignal prompt → Browser permission prompt → Subscribed via OneSignal

### Features
- ✅ All medication features work exactly the same
- ✅ Notification timing unchanged
- ✅ "Mark as taken" works the same
- ✅ Settings work the same
- ✅ PWA install still works

### New Benefits
- Better notification delivery
- Can check delivery stats in OneSignal dashboard
- Professional notification service
- Better cross-browser support

## Rolling Back (If Needed)

If you need to rollback to VAPID/web-push:

```bash
git revert 8bfbbd5 f7fba5e fc83c22
cd server
npm install  # Reinstalls web-push
# Generate new VAPID keys
node generate-vapid-keys.js
# Update keys in server/index.js
npm start
```

## Support

Need help? Check these resources:

1. **ONESIGNAL_SETUP.md** - Detailed OneSignal setup guide
2. **README.md** - General troubleshooting
3. **MIGRATION_SUMMARY.md** - List of all changes
4. **OneSignal Docs** - https://documentation.onesignal.com/docs/web-push-quickstart
5. **OneSignal Support** - https://onesignal.com/support

## Security Notes

🔒 **Never commit credentials to git**
```bash
# Add to .gitignore if using .env file
echo ".env" >> .gitignore
```

🔒 **Restrict API keys in production**
- In OneSignal dashboard, restrict API key to specific IP addresses
- Use environment variables, not hardcoded values
- Rotate keys if compromised

🔒 **Use HTTPS in production**
- Required for push notifications
- Protects API keys in transit
- Required for PWA features

## Next Steps

After setup is complete:

1. ✅ Test notification delivery
2. ✅ Verify all medication features work
3. ✅ Check OneSignal dashboard for analytics
4. ✅ Inform users they need to re-enable notifications
5. ✅ Monitor server logs for any issues

## Questions?

- **Setup issues**: See ONESIGNAL_SETUP.md
- **Migration questions**: See MIGRATION_SUMMARY.md
- **General help**: See README.md

---

**Migration completed**: 2026-02-04  
**Version**: OneSignal v16 SDK  
**Status**: ✅ Production Ready (after OneSignal configuration)
