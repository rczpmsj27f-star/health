# Quick Start Guide - Medication Reminder PWA

## Prerequisites

- Node.js 14+ installed
- Modern web browser (Chrome, Edge, Safari, Firefox)
- Port 3000 available

## Installation (5 minutes)

### Step 1: Install Dependencies
```bash
cd server
npm install
```

### Step 2: Generate VAPID Keys
```bash
cd server
node generate-vapid-keys.js
```

Copy the output and update `server/index.js` lines 18-21 with your keys:
```javascript
const vapidKeys = {
  publicKey: 'YOUR_PUBLIC_KEY',
  privateKey: 'YOUR_PRIVATE_KEY'
};
```

### Step 3: Start the Server
```bash
cd server
npm start
```

You should see:
```
Medication Reminder Server running on port 3000
VAPID Public Key: ...
Notification scheduler is active (runs every minute)
```

### Step 4: Open the App
Open your browser and go to:
```
http://localhost:3000
```

## First Use (2 minutes)

### Add Your First Medication

1. Click **"+ Add Medication"**
2. Enter medication details:
   - Name: "Aspirin"
   - Dose: "100mg" (optional)
   - Instructions: "Take with food" (optional)
3. Click **"+ Add Time"** and select "08:00"
4. Add more times if needed (e.g., "20:00")
5. Click **"Save Medication"**

### Enable Notifications

1. Click the **⚙️ Settings** button
2. Click **"Enable Notifications"**
3. Allow notifications when prompted
4. Your notification settings are already configured:
   - ✓ At scheduled time
   - ✓ 10 minutes after (if not taken)
   - ✓ 20 minutes after (if not taken)
   - ✓ 30 minutes after (if not taken)
   - ☐ 60 minutes after (if not taken)

### Mark Medication as Taken

1. Return to home screen (click **← Back**)
2. Find your medication in today's list
3. Click **"✓ Mark Taken"** when you take it
4. The status will change to "✓ Taken"

## Testing Notifications (5 minutes)

To test push notifications:

1. Add a medication with a time 2-3 minutes from now
2. Enable all notification settings
3. Wait for the scheduled time
4. You should receive a notification on your device
5. If you don't mark it as taken, you'll receive reminders

## Install as PWA

### On Desktop (Chrome/Edge)
1. Look for the install icon (⊕) in the address bar
2. Click it and confirm
3. App opens in its own window

### On iPhone (Safari)
1. Tap the Share button (□↑)
2. Scroll and tap "Add to Home Screen"
3. Tap "Add"
4. App icon appears on home screen

### On Android (Chrome)
1. Tap menu (⋮)
2. Select "Add to Home Screen" or "Install App"
3. Confirm installation
4. App icon appears on home screen

## Stopping the Server

Press `Ctrl+C` in the terminal where the server is running.

## Next Steps

- Read the full [README.md](README.md) for detailed documentation
- See [IMPLEMENTATION.md](IMPLEMENTATION.md) for architecture details
- Run the test suite: `./test-pwa.sh`

## Common Issues

### "Cannot GET /"
- Make sure you're in the project root directory
- Verify server is running on port 3000

### Notifications not working
- Check browser notification permissions
- Verify VAPID keys are set correctly
- Service Worker must be registered (check browser DevTools)

### Port 3000 already in use
- Stop other processes using port 3000
- Or change the port in `server/index.js`: `const PORT = 8080;`

## Support

For issues or questions:
1. Check the README.md troubleshooting section
2. Review browser console for errors
3. Check server logs for error messages

## What's Next?

This is a demo/development version. For production use:
- Replace file-based storage with a database
- Add user authentication
- Deploy to a server with HTTPS
- Add more features (see README.md)

Happy medication tracking! 💊
