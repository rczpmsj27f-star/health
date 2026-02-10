# health

## Medication Reminder PWA

A Progressive Web App (PWA) for medication reminders with push notifications. Never miss a dose!

---

## 🔴 Database Error Fix Required

**Are you seeing this error?**
```
Column 'early_logging_reason' not found
```

**Quick Fix**: See **[URGENT_FIX_DATABASE_ERROR.md](URGENT_FIX_DATABASE_ERROR.md)** for immediate resolution.

---

### 📱 iOS Native App Available

This application is also available as a native iOS app for the Apple App Store. The iOS app wraps the web application using Capacitor, providing a native app experience.

**iOS App Documentation:**
- **[IOS_QUICKSTART.md](IOS_QUICKSTART.md)** - Quick setup guide for iOS development
- **[IOS_README.md](IOS_README.md)** - iOS app overview and features
- **[IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md)** - Complete App Store deployment guide

## Security Setup (REQUIRED)

**⚠️ IMPORTANT**: Before running the application, you must configure database and OneSignal credentials securely.

### Quick Setup

1. **Copy the environment template:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` with your credentials:**
   ```env
   # Database credentials
   DB_HOST=localhost
   DB_USER=your_database_user
   DB_PASS=your_secure_password
   DB_NAME=your_database_name
   
   # OneSignal credentials (get from OneSignal Dashboard)
   ONESIGNAL_APP_ID=your_onesignal_app_id
   ONESIGNAL_REST_API_KEY=your_onesignal_rest_api_key
   
   # Application settings
   APP_ENV=development
   ENABLE_DEBUG_LOGGING=false
   ```

3. **Secure the file (Unix/Linux):**
   ```bash
   chmod 600 .env
   ```

**Security Notes:**
- Never commit `.env` to version control (already in `.gitignore`)
- Use strong, unique passwords
- Different credentials for development and production
- OneSignal App ID is safe for client-side but keep REST API Key secret
- See [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment on Hostinger and other hosts

### Features

- 💊 **Medication Management**: Add medications with scheduled times
- 🔔 **Smart Reminders**: Configurable push notifications at scheduled times and intervals
- ✅ **Track Adherence**: Mark medications as taken
- 📱 **Installable**: Add to home screen on iOS, Android, and Desktop
- 🔒 **Privacy-First**: Data stored locally with optional server sync
- 🌐 **Offline Support**: Works without internet connection
- 🔐 **Biometric Authentication**: Face ID / Touch ID support for quick and secure login (iOS/iPadOS 14+)

### Security Features

- **Two-Factor Authentication (2FA)**: Google Authenticator support
- **Biometric Authentication**: Face ID and Touch ID for iPhone/iPad users (see [BIOMETRIC_AUTHENTICATION.md](docs/BIOMETRIC_AUTHENTICATION.md))
- **Secure Sessions**: HttpOnly cookies with SameSite protection
- **Password Hashing**: Industry-standard bcrypt password hashing
- **Permission System**: Granular permissions for linked users

### Quick Start

#### 1. Install Dependencies

```bash
cd server
npm install
```

#### 2. Configure OneSignal

OneSignal credentials are configured in the `.env` file you created in the Security Setup section.

If you haven't already configured OneSignal:

1. Create a free account at [OneSignal.com](https://onesignal.com/)
2. Create a new Web Push app
3. Get your credentials from Settings > Keys & IDs
4. Add them to your `.env` file:
   ```env
   ONESIGNAL_APP_ID=your-onesignal-app-id
   ONESIGNAL_REST_API_KEY=your-onesignal-rest-api-key
   ```

**Note:** The `.env` file is used by both the PHP application and the Node.js server for consistent configuration.

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

Push notifications require OneSignal configuration. This should already be configured in your `.env` file (see Security Setup section above).

If you need to set up OneSignal:

1. Create a free account at [OneSignal.com](https://onesignal.com/)
2. Create a new Web Push app
3. Get your App ID and REST API Key from Settings > Keys & IDs
4. Add them to your `.env` file:

```env
ONESIGNAL_APP_ID=your-app-id
ONESIGNAL_REST_API_KEY=your-rest-api-key
```

The application will automatically load these credentials from the `.env` file.

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

### Troubleshooting

Having issues with notification settings or session handling? See our comprehensive guides:

#### Notification Settings Issues

Common issues and solutions:
- **"Unauthorized" errors when logged in**: Session cookies not being sent with AJAX requests
- **Player ID not saved**: Timing or initialization issues with OneSignal
- **Session expires too quickly**: Session timeout or cookie configuration issues
- **Getting HTML redirects instead of JSON**: AJAX request detection not working

**Quick Diagnostics:**
1. Check browser console for JavaScript errors
2. Check Network tab for failed requests (look for 401, 500 status codes)
3. Verify session cookie exists (DevTools → Application → Cookies)
4. Enable debug logging (see below)

**Detailed troubleshooting:** See [`NOTIFICATION_TROUBLESHOOTING.md`](NOTIFICATION_TROUBLESHOOTING.md)

**Testing guide:** See [`NOTIFICATION_SESSION_TESTING.md`](NOTIFICATION_SESSION_TESTING.md)

#### Enable Debug Logging

For detailed diagnostics, enable debug logging:

```php
// In config.php
define('ENABLE_DEBUG_LOGGING', true);
```

Or via environment variable:
```bash
export DEBUG_MODE=true
```

This will log:
- Session state information
- POST request payloads (with sensitive data redacted)
- Request headers and metadata
- Database operations

**View logs:**
```bash
# Find log location
php -i | grep error_log

# Watch logs in real-time
tail -f /var/log/apache2/error.log | grep save_notifications_handler
```

**⚠️ Important:** Disable debug logging in production to avoid performance impact and log bloat.

#### Session Cookie Configuration

Session cookies must be properly configured for AJAX requests to work. Check `config.php`:

```php
session_set_cookie_params([
    'lifetime' => 0,        // Session cookie (expires when browser closes)
    'path' => '/',          // Available to entire site
    'domain' => '',         // Current domain only (no subdomains)
    'secure' => false,      // Set to true in production with HTTPS
    'httponly' => true,     // Prevent JavaScript access (security)
    'samesite' => 'Lax'     // Allow same-site requests, block cross-site
]);
```

**For cross-subdomain support:**
- Set `domain` to `'.example.com'` (note the leading dot)
- Both subdomains must use HTTPS if `secure` is `true`

#### AJAX Request Issues

All AJAX/fetch requests must include `credentials: 'include'` to send session cookies:

```javascript
fetch('/modules/settings/save_notifications_handler.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'  // ← CRITICAL for session cookies
})
```

**Verify in browser:**
- DevTools → Network → Select request
- Check "Request Headers" section
- Should see `Cookie:` header with session ID

### Deployment

For production deployment, see the comprehensive [DEPLOYMENT.md](DEPLOYMENT.md) guide which includes:

- **Environment variable configuration** for different hosting providers
- **Hostinger-specific deployment guide** with step-by-step instructions
- **Database setup and migrations**
- **PHP dependencies (Composer)**
- **Apache/Nginx configuration**

#### Quick Deployment Checklist

1. **Use HTTPS**: Required for PWA and push notifications
2. **Configure environment variables**: 
   - **Hostinger**: Use `.htaccess` with `SetEnv` directives (see [DEPLOYMENT.md](DEPLOYMENT.md))
   - **Other hosts**: Use `.env` file or server-specific environment variable configuration
3. **Never commit credentials**: Keep `.env` and `.htaccess` (with real credentials) in `.gitignore`
4. **Set file permissions**: `.env` should be 600, `.htaccess` should be 644
5. **Install dependencies**: Run `composer install` for PHP dependencies
6. **Apply database migrations**: Check `database/migrations/` for required schema updates
7. **Configure CORS**: Restrict to specific domains in production
8. **Monitor**: Set up logging and error tracking
9. **Backup**: Regular backups of database and user data
10. **OneSignal setup**: Complete OneSignal web push configuration for your production domain

### License

MIT

### Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
