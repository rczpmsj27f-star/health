# health

## Medication Reminder PWA

A Progressive Web App (PWA) for medication reminders with push notifications. Never miss a dose!

### Features

- 💊 **Medication Management**: Add medications with scheduled times
- 🔔 **Smart Reminders**: Configurable push notifications at scheduled times and intervals
- ✅ **Track Adherence**: Mark medications as taken
- 📱 **Installable**: Add to home screen on iOS, Android, and Desktop
- 🔒 **Privacy-First**: Data stored locally with optional server sync
- 🌐 **Offline Support**: Works without internet connection

### Quick Start

#### 1. Install Dependencies

```bash
cd server
npm install
```

#### 2. Configure OneSignal

Create a free account at [OneSignal.com](https://onesignal.com/) and set up a new web app. Then configure your OneSignal credentials:

**Option A: Using Environment Variables (Recommended)**
```bash
export ONESIGNAL_APP_ID="your-onesignal-app-id"
export ONESIGNAL_API_KEY="your-onesignal-rest-api-key"
```

**Option B: Directly in server/index.js**
Update lines 11-12 in `server/index.js`:
```javascript
const ONESIGNAL_APP_ID = 'your-onesignal-app-id';
const ONESIGNAL_API_KEY = 'your-onesignal-rest-api-key';
```

#### 3. Start the Server

```bash
cd server
npm start
```

The server will run on `http://localhost:3000`

#### 4. Open the PWA

Open your browser and navigate to:
```
http://localhost:3000
```

The PWA will be served from the `/pwa` directory.

### Using the App

#### Adding a Medication

1. Click the **"+ Add Medication"** button
2. Enter medication name (required)
3. Optionally add dose (e.g., "100mg") and instructions
4. Add one or more scheduled times
5. Click **"Save Medication"**

#### Enabling Push Notifications

1. Click the **⚙️ Settings** button
2. Click **"Enable Notifications"**
3. Grant notification permission when prompted
4. Configure reminder intervals:
   - At scheduled time
   - 10 minutes after (if not taken)
   - 20 minutes after (if not taken)
   - 30 minutes after (if not taken)
   - 60 minutes after (if not taken)

#### Marking Medications as Taken

1. On the home screen, you'll see today's medications
2. Click **"✓ Mark Taken"** for each dose
3. Reminders will be automatically skipped for taken medications

### Installing as PWA

#### On Desktop (Chrome/Edge)

1. Open the app in Chrome or Edge
2. Look for the install icon (⊕) in the address bar
3. Click it and confirm installation
4. The app will open in its own window

#### On iOS (iPhone/iPad)

1. Open the app in Safari
2. Tap the **Share** button (□↑)
3. Scroll down and tap **"Add to Home Screen"**
4. Tap **"Add"**
5. The app icon will appear on your home screen

#### On Android

1. Open the app in Chrome
2. Tap the menu (⋮)
3. Select **"Add to Home Screen"** or **"Install App"**
4. Confirm installation
5. The app icon will appear on your home screen

### How Push Notifications Work

1. **OneSignal Integration**: The PWA uses OneSignal SDK for push notification delivery
2. **Service Worker Registration**: The PWA registers a service worker for offline functionality
3. **Push Subscription**: When you enable notifications, OneSignal handles the browser subscription
4. **Scheduled Checks**: The server runs a cron job every minute to check if any medications are due
5. **Smart Notifications**: Based on your settings, the server sends push notifications via OneSignal API:
   - At the exact scheduled time
   - 10, 20, 30, or 60 minutes after (if not marked as taken)
6. **Client Handling**: OneSignal delivers the push and displays a notification
7. **Action Buttons**: Click the notification to open the app or use quick actions

### Testing Push Notifications

1. Add a medication with a scheduled time in the near future (e.g., 2 minutes from now)
2. Enable notifications in settings
3. Ensure all reminder intervals are enabled
4. Wait for the scheduled time
5. You should receive a push notification
6. If you don't mark it as taken, you'll receive reminder notifications at the configured intervals

### Data Storage

The app uses a hybrid storage approach:

- **Client-Side**: LocalStorage for immediate data access and offline support
- **Server-Side**: File-based JSON storage (can be replaced with a database)

All data is stored locally first, then synced with the server when available.

### Architecture

```
/pwa                    # Progressive Web App front-end
  ├── index.html        # Main HTML file
  ├── styles.css        # Styling
  ├── app.js            # Application logic
  ├── sw.js             # Service Worker
  ├── manifest.json     # PWA manifest
  ├── OneSignalSDKWorker.js  # OneSignal service worker
  └── icons/            # App icons

/server                 # Node.js backend
  ├── index.js          # Express server with push notification logic
  └── package.json      # Dependencies
```

### API Endpoints

- `GET /api/onesignal-config` - Get OneSignal App ID for client initialization
- `GET /api/medications` - Get all medications
- `POST /api/medications` - Create/update medication
- `DELETE /api/medications/:id` - Delete medication
- `POST /api/medications/:id/taken` - Mark medication as taken
- `GET /api/settings` - Get notification settings
- `POST /api/settings` - Update notification settings

### Configuration

#### Server Port

Change the port in `server/index.js`:

```javascript
const PORT = process.env.PORT || 3000;
```

Or set the `PORT` environment variable:

```bash
PORT=8080 npm start
```

#### OneSignal Configuration

Push notifications require OneSignal configuration:

1. Create a free account at [OneSignal.com](https://onesignal.com/)
2. Create a new Web Push app
3. Get your App ID from Settings > Keys & IDs
4. Get your REST API Key from Settings > Keys & IDs

Set them as environment variables or update `server/index.js`:

```bash
export ONESIGNAL_APP_ID="your-app-id"
export ONESIGNAL_API_KEY="your-rest-api-key"
npm start
```

### Troubleshooting

#### Notifications Not Working

1. **Check permission**: Ensure notification permission is granted in browser settings
2. **HTTPS requirement**: Push notifications require HTTPS in production (localhost works for development)
3. **Service worker**: Check browser DevTools > Application > Service Workers to ensure it's registered
4. **OneSignal configuration**: Ensure valid OneSignal App ID and API Key are configured in the server
5. **OneSignal initialization**: Check browser console for OneSignal initialization errors

#### PWA Not Installing

1. **HTTPS required**: PWAs require HTTPS in production (localhost works for development)
2. **Manifest.json**: Ensure manifest.json is being served correctly
3. **Service worker**: Must be registered successfully
4. **Icons**: Ensure all required icons are present

#### Data Not Syncing

1. **Server running**: Ensure the Node.js server is running
2. **CORS**: Check browser console for CORS errors
3. **Network**: Check DevTools > Network tab for failed requests
4. **Fallback**: App uses localStorage as fallback when server is unavailable

### Browser Support

- ✅ Chrome 67+ (Desktop & Android)
- ✅ Edge 79+
- ✅ Safari 11.1+ (iOS 11.3+)
- ✅ Firefox 63+
- ✅ Samsung Internet 8.0+

### Future Enhancements

- [ ] User authentication and multi-user support
- [ ] Database integration (PostgreSQL, MongoDB)
- [ ] Medication interaction checking
- [ ] Refill reminders based on stock levels
- [ ] Export medication history to PDF
- [ ] Integration with pharmacy APIs
- [ ] Family/caregiver access
- [ ] Photo documentation
- [ ] Advanced analytics and trends
- [ ] Multiple notification channels (SMS, Email)

### Security Considerations

- OneSignal handles push subscription security
- OneSignal API keys should be kept secret in production (server-side only)
- HTTPS is required for production deployment
- Implement authentication for multi-user scenarios
- Consider encrypting sensitive medication data

### Deployment

For production deployment:

1. **Use HTTPS**: Required for PWA and push notifications
2. **Set environment variables**: OneSignal credentials, database credentials
3. **Use a real database**: Replace file-based storage with PostgreSQL/MongoDB
4. **Add authentication**: Implement user login/registration
5. **Configure CORS**: Restrict to specific domains
6. **Monitor**: Set up logging and error tracking
7. **Backup**: Regular backups of user data
8. **OneSignal setup**: Complete OneSignal web push configuration for your production domain

### License

MIT

### Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
