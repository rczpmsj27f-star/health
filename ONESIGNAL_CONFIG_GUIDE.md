# OneSignal Configuration Guide

## Overview

This guide explains the new PHP-based configuration system for OneSignal credentials in the Health Tracker application.

## Files Modified

1. **`config.php`** (NEW) - Central configuration file for OneSignal credentials
2. **`public/dashboard.php`** - Updated to include config.php and expose App ID to JavaScript
3. **`.gitignore`** - Updated with optional config.php ignore example

## Configuration Structure

### config.php

Located at the repository root (`/config.php`), this file defines two constants:

```php
define('ONESIGNAL_APP_ID', 'YOUR_ONESIGNAL_APP_ID_HERE');
define('ONESIGNAL_REST_API_KEY', 'YOUR_ONESIGNAL_REST_API_KEY_HERE');
```

**Security Model:**
- `ONESIGNAL_APP_ID`: Safe to expose to client-side JavaScript (required for SDK initialization)
- `ONESIGNAL_REST_API_KEY`: Server-side only, never exposed to the browser

## How It Works

### Server-Side Integration

The `config.php` file is included only in pages that need OneSignal functionality:

```php
<?php
// Include OneSignal configuration
require_once __DIR__ . '/../config.php';
```

**Note:** Only include config.php in files where you actually use the OneSignal credentials to avoid unnecessary file loading.

Currently included in:
- `public/dashboard.php` - To expose App ID to JavaScript for client-side OneSignal initialization

This makes both constants available throughout the PHP application where included.

### Client-Side Integration

In `dashboard.php`, the App ID is exposed to JavaScript via a script tag:

```html
<script>
    window.ONESIGNAL_APP_ID = '<?php echo htmlspecialchars(ONESIGNAL_APP_ID, ENT_QUOTES, 'UTF-8'); ?>';
</script>
```

This allows JavaScript code to access the App ID while keeping the REST API Key secure on the server.

## Deployment Instructions

### Step 1: Get Your OneSignal Credentials

1. Log in to your OneSignal dashboard at https://onesignal.com/
2. Navigate to **Settings** → **Keys & IDs**
3. Copy the following values:
   - **OneSignal App ID** (UUID format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`)
   - **REST API Key** (long alphanumeric string)

### Step 2: Update config.php

Replace the placeholder values in `/config.php`:

```php
define('ONESIGNAL_APP_ID', 'your-actual-app-id-here');
define('ONESIGNAL_REST_API_KEY', 'your-actual-rest-api-key-here');
```

### Step 3: Secure the Configuration (Production)

For production deployments, consider one of these approaches:

#### Option A: Git Ignore (Recommended)

1. Edit `.gitignore` and uncomment the line:
   ```
   config.php
   ```

2. Commit this change to your repository

3. Ensure config.php with actual credentials is deployed separately (via secure deployment pipeline, manual upload, or environment-specific deployment scripts)

#### Option B: Environment-Specific Files

1. Keep `config.php` in the repository with placeholder values
2. Create `config.production.php` with actual credentials
3. Add `config.production.php` to `.gitignore`
4. Modify your deployment process to copy `config.production.php` to `config.php` on the server

## Using OneSignal Credentials

### Configuration Validation

The config.php file includes helper functions to check if credentials are properly configured:

```php
// Check if credentials are configured (returns boolean)
if (onesignal_is_configured()) {
    // Credentials are set
} else {
    // Still using placeholder values
    error_log('Warning: OneSignal credentials not configured');
}

// Validate and throw exception if not configured (useful for critical features)
try {
    onesignal_validate_config(true);
    // Proceed with OneSignal functionality
} catch (Exception $e) {
    // Handle configuration error
    error_log($e->getMessage());
}
```

### In PHP

Access the credentials using the defined constants:

```php
// Server-side API call example
$app_id = ONESIGNAL_APP_ID;
$api_key = ONESIGNAL_REST_API_KEY;

// Validate configuration before using
if (onesignal_is_configured()) {
    // Use these for OneSignal REST API calls
}
```

### In JavaScript

Access the App ID using the global variable:

```javascript
// Client-side OneSignal initialization
if (window.ONESIGNAL_APP_ID) {
    OneSignal.init({
        appId: window.ONESIGNAL_APP_ID,
        // ... other options
    });
}
```

**Note:** The REST API Key is intentionally NOT available in JavaScript for security reasons.

## Security Best Practices

1. **Never commit actual credentials** to version control
2. **Use HTTPS** in production for all API communications
3. **Validate and sanitize** all user input before sending to OneSignal API
4. **Rotate credentials** periodically and after any suspected security breach
5. **Restrict access** to config.php file permissions on the server (chmod 600)
6. **Monitor usage** in the OneSignal dashboard for suspicious activity

## Verification

### Check Server-Side Setup

Run this PHP command to verify constants are defined:

```bash
php -r "require 'config.php'; echo ONESIGNAL_APP_ID . PHP_EOL;"
```

Expected output: Your App ID or `YOUR_ONESIGNAL_APP_ID_HERE` if not yet configured

### Check Client-Side Setup

1. Open the dashboard page in a browser
2. Open browser Developer Tools (F12)
3. Go to Console tab
4. Type: `window.ONESIGNAL_APP_ID`
5. Press Enter

Expected output: Your App ID or `YOUR_ONESIGNAL_APP_ID_HERE` if not yet configured

### Verify REST API Key is NOT Exposed

1. Open the dashboard page in a browser
2. View page source (Ctrl+U or Cmd+U)
3. Search for "REST_API_KEY" or your actual REST API Key value

Expected result: Should NOT appear anywhere in the HTML source

## Troubleshooting

### "Constant already defined" error

**Cause:** config.php is being included multiple times

**Solution:** Use `require_once` instead of `require` when including config.php

### Credentials not working

**Checklist:**
1. Verify credentials are copied correctly from OneSignal dashboard
2. Check for extra spaces or quotes around the values
3. Ensure you're using the correct App ID (not User Auth Key or other ID)
4. Verify REST API Key is the full key, not truncated

### config.php not found

**Cause:** Incorrect path in require statement

**Solution:** Ensure the path is relative to the file including it:
- From `/public/*.php`: `require_once __DIR__ . '/../config.php';`
- From `/app/*/*.php`: `require_once __DIR__ . '/../../config.php';`

## Integration with Existing OneSignal Setup

This PHP configuration complements the existing Node.js server configuration:

- **Node.js server** (`server/index.js`): Uses environment variables for server-side push notifications
- **PHP application** (this config): Provides credentials for PHP-based integrations
- **PWA** (`pwa/app.js`): Fetches App ID from Node.js API endpoint

Both systems can coexist. The PHP config is useful if you want to send push notifications directly from PHP code without going through the Node.js server.

## Next Steps

After configuring credentials:

1. Test push notification functionality
2. Review OneSignal dashboard for delivery statistics
3. Consider implementing PHP-based notification triggers
4. Set up monitoring for notification delivery rates

## Support

- OneSignal Documentation: https://documentation.onesignal.com/
- Application Issues: See main README.md
- Security Concerns: Contact repository maintainers
