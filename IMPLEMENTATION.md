# Medication Reminder PWA - Implementation Guide

## Architecture Overview

This application consists of two main components:

### 1. Frontend (PWA)
- **Location**: `/pwa` directory
- **Technology**: Vanilla JavaScript, HTML5, CSS3
- **Features**:
  - Progressive Web App with offline support
  - Service Worker for push notifications
  - Responsive design for mobile and desktop
  - LocalStorage with API sync

### 2. Backend (Node.js)
- **Location**: `/server` directory
- **Technology**: Express.js, node-cron, OneSignal
- **Features**:
  - RESTful API for medication management
  - Push notification system with OneSignal
  - File-based storage (upgradeable to database)
  - Cron scheduler for timed reminders

## File Structure

```
/pwa
├── index.html          # Main app HTML
├── app.js              # Application logic
├── styles.css          # Styling
├── sw.js               # Service Worker
├── manifest.json       # PWA manifest
├── OneSignalSDKWorker.js  # OneSignal service worker
└── icons/              # App icons (SVG)
    ├── icon-*.svg      # Various sizes
    └── badge-72x72.svg # Notification badge

/server
├── index.js            # Express server
├── package.json        # Dependencies
└── .gitignore          # Ignored files
```

## API Documentation

### Endpoints

#### 1. Get OneSignal Configuration
```
GET /api/onesignal-config
Response: { "appId": "..." }
```

#### 2. Get All Medications
```
GET /api/medications
Response: Array of medication objects
```

#### 3. Add/Update Medication
```
POST /api/medications
Body: {
  "id": "optional-for-update",
  "name": "Medication name",
  "dose": "100mg",
  "scheduledTimes": ["08:00", "20:00"],
  "instructions": "Take with food"
}
Response: Medication object with ID
```

#### 4. Delete Medication
```
DELETE /api/medications/:id
Response: { "success": true }
```

#### 5. Mark Medication as Taken
```
POST /api/medications/:id/taken
Body: { "scheduleTime": "08:00" }
Response: { "success": true, "medication": {...} }
```

#### 6. Get Settings
```
GET /api/settings
Response: {
  "notifyAtTime": true,
  "notifyAfter10Min": true,
  "notifyAfter20Min": true,
  "notifyAfter30Min": true,
  "notifyAfter60Min": false
}
```

#### 8. Update Settings
```
POST /api/settings
Body: Settings object
Response: { "success": true, "settings": {...} }
```

## Data Models

### Medication
```javascript
{
  id: String,           // Unique identifier
  name: String,         // Medication name
  dose: String,         // Optional dose info
  instructions: String, // Optional instructions
  scheduledTimes: [String],  // Array of "HH:MM" times
  takenLog: [            // Log of taken medications
    {
      scheduleTime: String,  // "HH:MM"
      takenAt: String,       // ISO timestamp
      date: String           // "YYYY-MM-DD"
    }
  ],
  createdAt: String     // ISO timestamp
}
```

### Settings
```javascript
{
  notifyAtTime: Boolean,      // Notify at exact time
  notifyAfter10Min: Boolean,  // Remind after 10 min
  notifyAfter20Min: Boolean,  // Remind after 20 min
  notifyAfter30Min: Boolean,  // Remind after 30 min
  notifyAfter60Min: Boolean   // Remind after 60 min
}
```

### Push Subscription
```javascript
{
  endpoint: String,
  keys: {
    p256dh: String,
    auth: String
  },
  createdAt: String
}
```

## Notification Scheduler Logic

The cron job runs every minute and:

1. Loads all medications from storage
2. Loads all push subscriptions
3. Loads user notification settings
4. For each medication:
   - For each scheduled time:
     - Check if already taken today
     - Calculate minutes difference from current time
     - If difference matches enabled interval (0, 10, 20, 30, or 60 min):
       - Send push notification to all subscriptions

### Example Flow

Medication: Aspirin at 08:00
- 08:00 - Send notification (if notifyAtTime enabled)
- 08:10 - Send reminder (if notifyAfter10Min enabled and not taken)
- 08:20 - Send reminder (if notifyAfter20Min enabled and not taken)
- 08:30 - Send reminder (if notifyAfter30Min enabled and not taken)
- 09:00 - Send reminder (if notifyAfter60Min enabled and not taken)

## Service Worker Features

### Caching Strategy
- **Cache-first** for static assets (HTML, CSS, JS, icons)
- **Network-first** for API calls
- Offline fallback for when network is unavailable

### Push Notification Handling
1. Receives push event from server
2. Displays notification with:
   - Title
   - Body text
   - Icon
   - Badge
   - Action buttons (Mark as Taken, Snooze)
3. Handles notification clicks:
   - Opens/focuses app
   - Can mark medication as taken from notification

## Security Considerations

### Current Implementation
- OneSignal API keys for push notification authentication
- CORS enabled for development
- No user authentication (single-user mode)

### Production Recommendations
1. **Add Authentication**
   - Implement user login/registration
   - JWT tokens for API authentication
   - User-specific data isolation

2. **HTTPS Required**
   - Service Workers require HTTPS
   - Push notifications require HTTPS
   - Use Let's Encrypt for free SSL certificates

3. **Database Security**
   - Replace file-based storage with proper database
   - Encrypt sensitive medication data
   - Regular backups

4. **API Security**
   - Rate limiting
   - Input validation
   - SQL injection prevention (if using SQL)
   - XSS protection

5. **Environment Variables**
   - Store OneSignal credentials in environment variables
   - Never commit secrets to git
   - Use .env files for local development

## Performance Optimization

### Current
- Service Worker caching reduces network requests
- LocalStorage provides instant data access
- Minimal JavaScript dependencies

### Future Improvements
1. Code splitting for faster initial load
2. Image optimization (convert SVG to PNG where needed)
3. Database indexing for faster queries
4. CDN for static assets
5. Lazy loading for large lists

## Testing Checklist

- [ ] Add medication with multiple scheduled times
- [ ] Mark medication as taken
- [ ] Verify taken status persists across page refresh
- [ ] Enable push notifications
- [ ] Receive notification at scheduled time
- [ ] Receive reminder notifications if not taken
- [ ] Toggle notification settings
- [ ] Install PWA on mobile device
- [ ] Test offline functionality
- [ ] Test notification action buttons

## Known Limitations

1. **File-based storage**: Not suitable for production with multiple users
2. **No user authentication**: Single-user mode only
3. **No data backup**: Data can be lost if files are deleted
4. **Limited notification scheduling**: Only checks every minute
5. **No timezone support**: Uses local time only
6. **No recurring schedules**: Daily times only, no weekly/monthly

## Future Enhancements

See README.md for complete list of planned features.

## Troubleshooting

### Notifications not working
1. Check browser notification permissions
2. Verify OneSignal App ID and API Key are correct
3. Check service worker is registered
4. Check OneSignal initialization in browser console
5. Ensure HTTPS in production

### PWA not installing
1. Verify manifest.json is valid
2. Check all icons are accessible
3. Ensure service worker is registered
4. Use HTTPS (required for production)

### Data not syncing
1. Check server is running
2. View network tab for failed requests
3. Check CORS settings
4. Verify API endpoints are correct

## License

MIT - See LICENSE file for details
