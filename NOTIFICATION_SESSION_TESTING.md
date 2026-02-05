# Notification Settings Session/Authentication Testing Guide

This guide provides comprehensive testing procedures to verify that notification settings work correctly with proper session handling and authentication.

## Overview

The notification settings feature requires proper session management to save OneSignal Player IDs and user preferences. This guide helps verify that:

1. Session cookies are properly sent with AJAX/fetch requests
2. Authentication failures return proper JSON 401 errors (not 302 redirects)
3. UI provides clear feedback when session expires
4. Player IDs are successfully saved to the database

## Prerequisites

- PHP session configured and working
- User logged in with valid session
- Browser with notification support
- OneSignal App ID configured in config.php

## Test 1: Valid Session - Enable Notifications

**Goal:** Verify that with a valid session, enabling notifications saves the Player ID to the database.

### Steps:

1. **Log in to the application:**
   - Navigate to `/login.php`
   - Enter valid credentials
   - Verify you're redirected to dashboard

2. **Open notification settings:**
   - Navigate to `/modules/settings/notifications.php`
   - Verify page loads successfully

3. **Open browser developer tools:**
   - Press F12 to open DevTools
   - Switch to Console tab
   - Switch to Network tab

4. **Enable notifications:**
   - Click "Enable Notifications" button
   - Grant permission in browser prompt
   - Watch console for success messages

5. **Verify in Network tab:**
   - Find POST request to `save_notifications_handler.php`
   - Check Request Headers section
   - Verify `Cookie` header is present with session ID
   - Check Response section
   - Should see: `{"success":true,"message":"Settings saved successfully"}`
   - Status should be: `200 OK`

6. **Verify in database:**
   ```sql
   SELECT user_id, notifications_enabled, onesignal_player_id 
   FROM user_notification_settings 
   WHERE user_id = [YOUR_USER_ID];
   ```
   
   **Expected:**
   - `notifications_enabled` = 1
   - `onesignal_player_id` = (non-null UUID string)

### Success Criteria:
- ✅ Request includes session cookies
- ✅ Response is 200 OK with JSON success
- ✅ Player ID saved in database
- ✅ UI shows "✅ Notifications enabled!"

---

## Test 2: Missing Session - Enable Notifications

**Goal:** Verify that without a valid session, enabling notifications fails gracefully with proper error messages.

### Steps:

1. **Clear session (simulate session expiry):**
   - Option A: Open browser DevTools → Application → Cookies → Delete session cookie
   - Option B: Log out and navigate directly to `/modules/settings/notifications.php` in a new private/incognito window
   - Option C: Modify PHP session timeout for quick expiry

2. **Try to enable notifications:**
   - Click "Enable Notifications" button
   - Grant permission in browser prompt

3. **Verify error handling:**
   - Should see alert: "⚠️ Your session has expired. Please log in again."
   - Should redirect to `/login.php`

4. **Verify in Network tab:**
   - Find POST request to `save_notifications_handler.php`
   - Check Response section
   - Should see: `{"success":false,"message":"Unauthorized. Please log in again."}`
   - Status should be: `401 Unauthorized`
   - Response should be JSON, NOT HTML redirect (302)

5. **Verify in database:**
   ```sql
   SELECT user_id, notifications_enabled, onesignal_player_id 
   FROM user_notification_settings 
   WHERE user_id = [YOUR_USER_ID];
   ```
   
   **Expected:**
   - No changes to database (or Player ID remains NULL)

### Success Criteria:
- ✅ Request returns 401 status
- ✅ Response is JSON (not HTML redirect)
- ✅ UI shows clear error message
- ✅ User redirected to login page
- ✅ No silent failure - error is visible

---

## Test 3: Session Expiry During Settings Update

**Goal:** Verify that if session expires while user is adjusting settings, the error is handled gracefully.

### Steps:

1. **Log in and enable notifications:**
   - Complete Test 1 to have notifications enabled

2. **Clear session cookie:**
   - Open DevTools → Application → Cookies
   - Delete session cookie
   - Keep the settings page open (don't refresh)

3. **Try to change a setting:**
   - Toggle any notification timing preference (e.g., "10 minutes after")
   - Settings auto-save on change

4. **Verify error handling:**
   - Console should log error
   - Should see alert: "⚠️ Your session has expired. Please log in again."
   - Should redirect to `/login.php`

5. **Verify in Network tab:**
   - POST to `save_notifications_handler.php`
   - Status: `401 Unauthorized`
   - Response: JSON with error message

### Success Criteria:
- ✅ Auto-save detects session expiry
- ✅ Clear error message shown to user
- ✅ Graceful redirect to login page
- ✅ No confusing 404 or 302 errors

---

## Test 4: Session Expiry During Form Submit

**Goal:** Verify manual form submission handles session expiry correctly.

### Steps:

1. **Log in and enable notifications:**
   - Complete Test 1

2. **Make changes to settings:**
   - Change some notification timing preferences
   - DO NOT let them auto-save (make changes quickly)

3. **Clear session cookie:**
   - DevTools → Application → Cookies → Delete session

4. **Submit form manually:**
   - Click "💾 Save Preferences" button

5. **Verify error handling:**
   - Should see alert: "⚠️ Your session has expired. Please log in again."
   - Should redirect to `/login.php`

### Success Criteria:
- ✅ Form submission detects session expiry
- ✅ User informed of session issue
- ✅ Redirect to login page

---

## Test 5: Verify `credentials: 'include'` in Fetch Requests

**Goal:** Verify that all fetch requests include credentials for session propagation.

### Steps:

1. **Review source code:**
   - Open `/modules/settings/notifications.php`
   - Search for all `fetch()` calls
   - Verify each has `credentials: 'include'`

2. **Expected locations:**
   - `saveNotificationStatus()` function (line ~492)
   - Auto-save settings event listener (line ~508)
   - Form submission handler (line ~533)

3. **Verify in Network tab:**
   - Trigger each type of request
   - Check Request Headers
   - Verify `Cookie` header is present

### Success Criteria:
- ✅ All 3 fetch calls include `credentials: 'include'`
- ✅ Cookie headers present in all requests
- ✅ Session cookies transmitted correctly

---

## Test 6: Disable Notifications with Session

**Goal:** Verify disabling notifications works with valid session.

### Steps:

1. **With notifications enabled:**
   - Navigate to notification settings page
   - Verify settings are visible

2. **Disable notifications:**
   - Click "🔕 Disable Notifications" button
   - Confirm the prompt

3. **Verify in Network tab:**
   - POST to `save_notifications_handler.php`
   - Status: `200 OK`
   - Response: `{"success":true,"message":"Settings saved successfully"}`

4. **Verify in database:**
   ```sql
   SELECT notifications_enabled 
   FROM user_notification_settings 
   WHERE user_id = [YOUR_USER_ID];
   ```
   
   **Expected:**
   - `notifications_enabled` = 0

### Success Criteria:
- ✅ Disable request successful
- ✅ Database updated correctly
- ✅ UI updates to show enable button

---

## Test 7: Disable Notifications without Session

**Goal:** Verify disabling notifications fails gracefully without session.

### Steps:

1. **With notifications enabled:**
   - Navigate to settings page
   - Settings should be visible

2. **Clear session cookie:**
   - DevTools → Application → Cookies → Delete session

3. **Try to disable notifications:**
   - Click "🔕 Disable Notifications" button
   - Confirm the prompt

4. **Verify error handling:**
   - Should see alert with session expiry message
   - Should redirect to login page

5. **Verify in Network tab:**
   - Status: `401 Unauthorized`
   - Response: JSON error message

### Success Criteria:
- ✅ Error detected and handled
- ✅ User informed of issue
- ✅ No silent failure

---

## Common Issues & Debugging

### Issue: Session cookie not sent with fetch

**Symptoms:**
- 401 errors even when logged in
- Session cookie visible in Application tab but not in request

**Debug:**
1. Check that `credentials: 'include'` is set on fetch
2. Verify SameSite cookie attribute allows cross-origin
3. Check that page is served over same domain as API

**Fix:**
- Add `credentials: 'include'` to all fetch calls
- Verify PHP session cookie settings

### Issue: Getting 302 redirect instead of 401 JSON

**Symptoms:**
- Network tab shows 302 redirect
- JavaScript doesn't receive JSON response

**Debug:**
1. Check if request has proper headers
2. Verify save_notifications_handler.php returns JSON
3. Check that Content-Type header is set

**Fix:**
- Ensure handler always returns JSON (not HTML)
- Set `Content-Type: application/json` header

### Issue: Player ID not saved despite success message

**Symptoms:**
- Success message shown
- Database shows NULL Player ID

**Debug:**
1. Check browser console for Player ID value
2. Verify OneSignal initialization
3. Check POST data in Network tab

**Fix:**
- Verify `window.OneSignal.User.PushSubscription.id` exists
- Check timing of saveNotificationStatus call

### Issue: Confusing error messages

**Symptoms:**
- Generic "Failed to save" message
- No indication it's a session issue

**Debug:**
1. Check response status code
2. Parse JSON error message
3. Check if 401 is handled differently

**Fix:**
- Add specific handling for 401 responses
- Show session-specific error messages

---

## Automated Testing Script

For automated verification, create a test script:

```php
<?php
// test_session_handling.php
session_start();
require_once "app/config/database.php";

echo "=== Session Handling Tests ===\n\n";

// Test 1: Valid session
echo "Test 1: Valid session\n";
if (!empty($_SESSION['user_id'])) {
    echo "✅ Session exists for user " . $_SESSION['user_id'] . "\n";
} else {
    echo "❌ No session found\n";
}

// Test 2: Database connection
echo "\nTest 2: Database connection\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notification_settings");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "✅ Database connected ($count notification settings found)\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Fetch with session
echo "\nTest 3: Testing fetch with session (requires manual browser test)\n";
echo "Open browser console and run:\n";
echo "```javascript\n";
echo "fetch('/modules/settings/save_notifications_handler.php', {\n";
echo "  method: 'POST',\n";
echo "  body: new FormData(),\n";
echo "  credentials: 'include'\n";
echo "}).then(r => r.json()).then(console.log);\n";
echo "```\n";

echo "\n=== Tests Completed ===\n";
?>
```

---

## Summary Checklist

Before marking this feature as complete, verify:

- [ ] All fetch requests include `credentials: 'include'`
- [ ] save_notifications_handler.php returns JSON 401 (not redirect)
- [ ] save_notifications_handler.php sets Content-Type header
- [ ] UI shows clear error when session expires
- [ ] User redirected to login on session expiry
- [ ] Player ID saved successfully with valid session
- [ ] Player ID NOT saved when session missing
- [ ] Auto-save handles session errors
- [ ] Manual save handles session errors
- [ ] Enable notifications handles session errors
- [ ] Disable notifications handles session errors
- [ ] No silent failures - all errors visible to user

When all items are checked, session/authentication handling is robust!
