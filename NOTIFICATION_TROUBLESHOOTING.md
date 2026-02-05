# Notification Settings Troubleshooting Guide

This guide helps developers and administrators troubleshoot issues with the notification settings feature, particularly those related to session handling, OneSignal Player ID saving, and AJAX request handling.

## Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Common Issues](#common-issues)
3. [Debug Logging](#debug-logging)
4. [Session Issues](#session-issues)
5. [AJAX/Fetch Issues](#ajaxfetch-issues)
6. [OneSignal Issues](#onesignal-issues)
7. [Database Issues](#database-issues)
8. [Browser Console Debugging](#browser-console-debugging)

---

## Quick Diagnostics

### Check if Notification Settings are Working

1. **Log in** to the application
2. **Navigate** to `/modules/settings/notifications.php`
3. **Click** "Enable Notifications" button
4. **Grant** browser permission when prompted
5. **Check** browser DevTools Console for errors
6. **Check** Network tab for failed requests
7. **Verify** in database:
   ```sql
   SELECT user_id, notifications_enabled, onesignal_player_id, created_at, updated_at
   FROM user_notification_settings 
   WHERE user_id = [YOUR_USER_ID];
   ```

**Expected Results:**
- ✅ No JavaScript errors in console
- ✅ POST to `save_notifications_handler.php` returns `200 OK`
- ✅ Response contains `{"success":true,"message":"Settings saved successfully"}`
- ✅ Database shows `notifications_enabled = 1` and non-null `onesignal_player_id`

---

## Common Issues

### Issue 1: "Unauthorized" Error Even When Logged In

**Symptoms:**
- User is logged in but gets "Unauthorized. Please log in again." message
- Network tab shows `401 Unauthorized` response
- Session cookie exists in browser but not sent with request

**Root Causes:**
1. Missing `credentials: 'include'` in fetch call
2. Session cookie has restrictive SameSite or domain settings
3. CORS or cross-origin request blocking cookie

**Diagnosis:**
```javascript
// In browser console, check if fetch includes credentials
fetch('/modules/settings/save_notifications_handler.php', {
    method: 'POST',
    body: new FormData(),
    credentials: 'include'  // ← Must be present!
}).then(r => r.json()).then(console.log);
```

**Fix:**
1. Verify all fetch calls in `notifications.php` include `credentials: 'include'`
2. Check `config.php` session cookie configuration:
   ```php
   session_set_cookie_params([
       'samesite' => 'Lax',  // or 'None' with Secure=true
       'secure' => false,    // true for HTTPS
       'httponly' => true,
   ]);
   ```
3. Ensure the page and API endpoint are on the same domain

### Issue 2: Getting HTML Redirect Instead of JSON Response

**Symptoms:**
- Network tab shows `302 Found` redirect
- JavaScript doesn't receive JSON response
- Console shows parsing error: "Unexpected token < in JSON"

**Root Cause:**
Handler doesn't detect AJAX request and returns HTML redirect instead of JSON error

**Diagnosis:**
```bash
# Check if handler detects AJAX requests
curl -X POST http://localhost/modules/settings/save_notifications_handler.php \
  -H "Accept: application/json" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

**Fix:**
Verify `save_notifications_handler.php` uses the AJAX detection helper:
```php
require_once "../../../app/helpers/ajax_helpers.php";
$isAjax = is_ajax_request();

if ($isAjax) {
    header('Content-Type: application/json');
}

if (empty($_SESSION['user_id'])) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    } else {
        header("Location: /login.php");
        exit;
    }
}
```

### Issue 3: Player ID Not Saved to Database

**Symptoms:**
- Notification permission granted successfully
- UI shows "Notifications enabled"
- Database shows `onesignal_player_id` is NULL
- Console logs show Player ID value

**Root Cause:**
- Player ID not included in POST request
- Timing issue (Player ID not available yet when saved)
- OneSignal not fully initialized

**Diagnosis:**
```javascript
// In browser console, after enabling notifications
console.log('Player ID:', window.OneSignal?.User?.PushSubscription?.id);
```

**Fix:**
1. Ensure Player ID is retrieved AFTER permission granted:
   ```javascript
   await window.OneSignal.User.PushSubscription.optIn();
   const playerId = window.OneSignal.User.PushSubscription.id;
   await saveNotificationStatus(true, playerId);
   ```
2. Check Network tab to verify Player ID is in POST data
3. Verify database column allows sufficient length (UUID is 36 chars)

### Issue 4: Session Expires Too Quickly

**Symptoms:**
- User gets logged out frequently
- "Session expired" errors during normal usage
- Sessions don't persist across page refreshes

**Root Cause:**
- Short session timeout
- Session garbage collection too aggressive
- Cookie lifetime too short

**Diagnosis:**
```php
// Add to a test page to check session settings
<?php
session_start();
phpinfo();  // Look for session.* settings
?>
```

**Fix:**
Adjust session settings in `config.php` or `php.ini`:
```php
ini_set('session.gc_maxlifetime', 86400);  // 24 hours
ini_set('session.cookie_lifetime', 0);     // Session cookie (expires on browser close)

session_set_cookie_params([
    'lifetime' => 0,  // Session cookie
    'path' => '/',
    'secure' => false,  // true for HTTPS
    'httponly' => true,
]);
```

---

## Debug Logging

### Enable Debug Logging

Debug logging helps diagnose session and request issues by logging:
- Session state (user_id, session_id, session status)
- POST data (with sensitive fields redacted)
- Request headers (Content-Type, Accept, User-Agent)

**To Enable:**

**Option 1: Via config.php**
```php
// In config.php
define('ENABLE_DEBUG_LOGGING', true);
```

**Option 2: Via Environment Variable**
```bash
export DEBUG_MODE=true
php -S localhost:8000 -t public
```

### View Debug Logs

**Location:** PHP error log (configured in `php.ini`)

**Find error log location:**
```bash
php -i | grep error_log
```

**Common locations:**
- Ubuntu/Debian: `/var/log/apache2/error.log` or `/var/log/php-fpm/www-error.log`
- macOS: `/usr/local/var/log/php-fpm.log`
- Docker: `docker logs <container_name>`

**Watch logs in real-time:**
```bash
tail -f /var/log/apache2/error.log | grep save_notifications_handler
```

### Example Debug Output

```
[2024-01-15 10:30:45] [save_notifications_handler] === DEBUG SNAPSHOT START ===
[2024-01-15 10:30:45] [save_notifications_handler] Session state | Data: {"session_id":"abc123","has_user_id":true,"user_id":42,"session_status":2}
[2024-01-15 10:30:45] [save_notifications_handler] POST data | Data: {"notifications_enabled":"1","onesignal_player_id":"550e8400-e29b-41d4-a716-446655440000"}
[2024-01-15 10:30:45] [save_notifications_handler] Request headers | Data: {"method":"POST","content_type":"application/x-www-form-urlencoded","accept":"application/json"}
[2024-01-15 10:30:45] [save_notifications_handler] === DEBUG SNAPSHOT END ===
[2024-01-15 10:30:45] [save_notifications_handler] Processing request for user | Data: {"user_id":42}
[2024-01-15 10:30:45] [save_notifications_handler] Parsed settings | Data: {"notifications_enabled":1,"has_player_id":true,"player_id_length":36}
[2024-01-15 10:30:45] [save_notifications_handler] Updated existing settings | Data: {"fields_updated":2}
[2024-01-15 10:30:45] [save_notifications_handler] Request completed successfully
```

### Disable Debug Logging in Production

**IMPORTANT:** Debug logging should be disabled in production to:
- Avoid performance impact
- Prevent log bloat
- Protect sensitive information

```php
// In config.php for production
define('ENABLE_DEBUG_LOGGING', false);
```

---

## Session Issues

### Test Session Propagation

**Browser Console Test:**
```javascript
// Test if session cookie is sent with fetch
fetch('/modules/settings/save_notifications_handler.php', {
    method: 'POST',
    body: new FormData(),
    credentials: 'include'
})
.then(r => r.json())
.then(data => {
    if (data.success === false && data.message.includes('Unauthorized')) {
        console.error('❌ Session not sent or invalid');
    } else {
        console.log('✅ Session valid');
    }
});
```

### Inspect Session Cookie

**DevTools → Application → Cookies:**
- Look for cookie with name matching `session_name()` (default: `PHPSESSID`)
- Check these attributes:
  - **Path:** Should be `/` to be available site-wide
  - **Domain:** Empty or matches current domain
  - **SameSite:** `Lax` or `None` (None requires Secure)
  - **Secure:** Should match HTTPS status
  - **HttpOnly:** Should be `true` for security

### Common Session Cookie Issues

| Issue | Symptom | Fix |
|-------|---------|-----|
| Path mismatch | Cookie not sent with request | Set `path` to `/` |
| Domain mismatch | Cookie not sent to subdomain | Set `domain` to `.example.com` |
| SameSite too strict | Cookie blocked on cross-origin | Use `Lax` or `None` with Secure |
| Secure flag on HTTP | Cookie not sent over HTTP | Set `secure` to `false` for HTTP |

---

## AJAX/Fetch Issues

### Verify AJAX Request Detection

The handler uses multiple methods to detect AJAX/JSON requests:

1. **POST parameter:** `$_POST['ajax'] == '1'`
2. **Accept header:** Contains `application/json`
3. **Content-Type header:** Contains `application/json`
4. **X-Requested-With:** `XMLHttpRequest`

**Test each method:**
```bash
# Test with Accept header
curl -X POST http://localhost/modules/settings/save_notifications_handler.php \
  -H "Accept: application/json"

# Test with Content-Type
curl -X POST http://localhost/modules/settings/save_notifications_handler.php \
  -H "Content-Type: application/json"

# Test with X-Requested-With
curl -X POST http://localhost/modules/settings/save_notifications_handler.php \
  -H "X-Requested-With: XMLHttpRequest"
```

### Check Fetch Configuration

All fetch calls should include:
```javascript
fetch('save_notifications_handler.php', {
    method: 'POST',
    body: formData,  // FormData or JSON.stringify(data)
    credentials: 'include',  // ← CRITICAL for session cookies
    // For JSON body, also add:
    // headers: {'Content-Type': 'application/json'}
})
```

---

## OneSignal Issues

### Verify OneSignal Initialization

**Browser Console:**
```javascript
// Check if OneSignal is loaded
console.log('OneSignal loaded:', typeof window.OneSignal !== 'undefined');

// Check if initialized
console.log('OneSignal API:', window.OneSignal?.User?.PushSubscription);

// Check Player ID
console.log('Player ID:', window.OneSignal?.User?.PushSubscription?.id);
```

### Common OneSignal Errors

| Error | Cause | Fix |
|-------|-------|-----|
| "OneSignal is not defined" | SDK not loaded | Check SDK script tag, network errors |
| "PushSubscription undefined" | Not initialized | Wait for init completion |
| "Player ID is null" | Not subscribed | Call `optIn()` first, check permission |
| "Push not supported" | Insecure context | Use HTTPS or localhost |

### Test OneSignal Configuration

```javascript
// In browser console
window.OneSignalDeferred.push(async function(OneSignal) {
    console.log('✅ OneSignal callback triggered');
    console.log('App ID:', OneSignal.context?.appId);
    console.log('Permission:', await OneSignal.Notifications.permission);
});
```

---

## Database Issues

### Verify Table Schema

```sql
DESCRIBE user_notification_settings;
```

**Required columns:**
- `user_id` - INT (foreign key to users table)
- `notifications_enabled` - TINYINT or BOOLEAN
- `onesignal_player_id` - VARCHAR(255) or TEXT
- `notify_at_time`, `notify_after_10min`, etc. - TINYINT

### Test Database Connectivity

```php
<?php
require_once "app/config/database.php";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connected\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
```

### Common Database Errors

| Error | Cause | Fix |
|-------|-------|-----|
| "Table doesn't exist" | Migration not run | Run database migrations |
| "Column too short" | Player ID truncated | Increase column size to VARCHAR(255) |
| "Duplicate entry" | Unique constraint | Check if user already has settings |
| "Foreign key constraint" | Invalid user_id | Verify user exists in users table |

---

## Browser Console Debugging

### Enable Verbose Logging

Add this to `notifications.php` for more detailed console output:

```javascript
// At the top of the script section
const DEBUG = true;

function log(...args) {
    if (DEBUG) console.log('[NotificationSettings]', ...args);
}

// Use throughout the code
log('Initializing OneSignal...');
log('Player ID:', playerId);
log('Saving notification status...');
```

### Monitor Network Requests

**DevTools → Network → Filter by "save_notifications"**

Check these details:
1. **Request URL:** Should be correct endpoint
2. **Request Method:** Should be `POST`
3. **Status Code:** `200` for success, `401` for auth, `500` for error
4. **Request Headers:**
   - `Cookie:` Should contain session cookie
   - `Content-Type:` Should be `application/x-www-form-urlencoded` or `multipart/form-data`
5. **Request Payload:** Should contain expected data
6. **Response:** Should be valid JSON

### Common Console Errors

| Error | Meaning | Fix |
|-------|---------|-----|
| `Failed to fetch` | Network error, CORS, or server down | Check server, network, CORS headers |
| `Unexpected token < in JSON` | HTML response instead of JSON | Check handler returns JSON for AJAX |
| `401 Unauthorized` | Session missing or invalid | Check session cookie, login status |
| `TypeError: Cannot read property 'id'` | OneSignal not initialized | Wait for init, check initialization |

---

## Step-by-Step Troubleshooting Workflow

When facing issues, follow this workflow:

### 1. Verify User is Logged In
```javascript
// Browser console
fetch('/dashboard.php').then(r => {
    if (r.redirected && r.url.includes('login')) {
        console.error('❌ Not logged in');
    } else {
        console.log('✅ Logged in');
    }
});
```

### 2. Check Session Cookie
- DevTools → Application → Cookies
- Verify PHPSESSID (or your session name) exists
- Check Path, Domain, SameSite, Secure attributes

### 3. Test AJAX Endpoint
```javascript
// Browser console
fetch('/modules/settings/save_notifications_handler.php', {
    method: 'POST',
    body: new FormData(),
    credentials: 'include'
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

### 4. Enable Debug Logging
```php
// config.php
define('ENABLE_DEBUG_LOGGING', true);
```
Then watch logs:
```bash
tail -f /var/log/apache2/error.log
```

### 5. Check OneSignal
```javascript
// Browser console
window.OneSignalDeferred.push(async function(OneSignal) {
    console.log('Initialized:', await OneSignal.Notifications.permission);
    console.log('Player ID:', OneSignal.User.PushSubscription.id);
});
```

### 6. Verify Database
```sql
SELECT * FROM user_notification_settings WHERE user_id = [YOUR_ID];
```

---

## Getting Help

If you've followed this guide and still have issues:

1. **Gather Information:**
   - Browser console errors (screenshot)
   - Network tab screenshots showing failed requests
   - Relevant server logs
   - Database query results
   - Session cookie details

2. **Enable Debug Logging** and collect logs

3. **Create a Test Case:**
   - Exact steps to reproduce
   - Expected vs actual behavior
   - Environment details (browser, PHP version, server)

4. **Check Existing Documentation:**
   - `NOTIFICATION_SESSION_TESTING.md` - Testing procedures
   - `SESSION_FIX_SUMMARY.md` - Session handling implementation
   - `ONESIGNAL_SETUP.md` - OneSignal configuration

---

## Quick Reference: Key Files

| File | Purpose |
|------|---------|
| `public/modules/settings/notifications.php` | Frontend UI and JavaScript |
| `public/modules/settings/save_notifications_handler.php` | Backend API handler |
| `app/helpers/ajax_helpers.php` | AJAX detection utilities |
| `app/helpers/debug_helpers.php` | Debug logging utilities |
| `config.php` | Session and debug configuration |
| `app/config/database.php` | Database connection |

---

## Prevention: Best Practices

To avoid notification settings issues in the future:

1. **Always use `credentials: 'include'`** in fetch requests
2. **Always check response status** before parsing JSON
3. **Always provide user feedback** for errors
4. **Test with session expiry** (delete cookie and retry)
5. **Enable debug logging** in development
6. **Monitor server logs** for errors
7. **Document any configuration changes**
8. **Test in multiple browsers** (Chrome, Firefox, Safari)

---

## Summary Checklist

Before declaring the feature "working", verify:

- [ ] User can enable notifications successfully
- [ ] Player ID is saved to database
- [ ] Session expiry shows clear error message
- [ ] Session expiry redirects to login page
- [ ] All fetch requests include `credentials: 'include'`
- [ ] Handler returns JSON for AJAX, redirects for normal requests
- [ ] Error messages are specific and actionable
- [ ] Debug logging can be enabled for troubleshooting
- [ ] No 302 redirects for AJAX requests
- [ ] No silent failures - all errors visible to user
- [ ] Works across browser refreshes
- [ ] Works after session cookie deleted and restored

When all items are checked, the notification settings are robust and production-ready!
