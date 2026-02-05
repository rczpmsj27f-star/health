# Testing Guide: OneSignal Player ID Implementation

This guide provides step-by-step instructions for manually testing the OneSignal Player ID implementation.

## Prerequisites

Before testing:
1. OneSignal App ID and REST API Key configured in `config.php`
2. Database running with `user_notification_settings` table
3. Web server running (Apache/Nginx)
4. HTTPS enabled (required for push notifications)
5. Modern browser with notification support (Chrome, Firefox, Edge)

## Test 1: Initial Notification Opt-in

**Goal:** Verify that Player ID is captured and stored when user enables notifications.

### Steps:

1. **Clear existing data** (if needed):
   ```sql
   UPDATE user_notification_settings SET notifications_enabled = 0, onesignal_player_id = NULL WHERE user_id = 1;
   ```

2. **Open notification settings page:**
   - Navigate to `/modules/settings/notifications.php`
   - Log in if prompted

3. **Open browser console** (F12):
   - Check for OneSignal initialization messages
   - Should see: "✅ OneSignal initialized"

4. **Enable notifications:**
   - Click "Enable Notifications" button
   - Browser should show permission prompt
   - Click "Allow"

5. **Verify in console:**
   - Look for: "OneSignal User ID: [player-id]"
   - Look for: "✅ Notifications enabled!"
   - UI should update to show notification settings

6. **Verify in database:**
   ```sql
   SELECT user_id, notifications_enabled, onesignal_player_id 
   FROM user_notification_settings 
   WHERE user_id = 1;
   ```
   
   **Expected:**
   - `notifications_enabled` = 1
   - `onesignal_player_id` = (a UUID-like string)

### Success Criteria:
- ✅ Player ID appears in browser console
- ✅ Player ID stored in database
- ✅ UI shows notification settings (not the enable button)

---

## Test 2: Page Refresh Persistence

**Goal:** Verify that notification status persists after page refresh.

### Steps:

1. **After completing Test 1**, refresh the page (F5)

2. **Check browser console:**
   - Look for: "Stored Player ID: [player-id]"
   - Look for: "Notifications enabled in DB: true"

3. **Verify UI:**
   - Should immediately show "✅ Push notifications are enabled on this device"
   - Notification settings should be visible (not hidden)
   - Should NOT show "Enable Notifications" button

4. **Close and reopen browser:**
   - Close browser completely
   - Reopen and navigate to notification settings page
   - Should still show as enabled

### Success Criteria:
- ✅ Notification status persists after refresh
- ✅ No need to re-enable notifications
- ✅ Stored Player ID is logged in console

---

## Test 3: Notification Service

**Goal:** Test the NotificationService can retrieve Player IDs and send notifications.

### Steps:

1. **Run the test script:**
   ```bash
   php app/cron/test_notification_service.php
   ```

2. **Expected output:**
   ```
   === NotificationService Test ===
   
   Test 1: Getting active Player IDs...
   Found X active Player IDs
   Player IDs: abc123..., def456..., ...
   
   Test 2: Checking Player ID for user 1...
   User 1 has Player ID: abc123...
   
   Test 3: Verifying notification settings in database...
   Found X users with Player IDs:
     - User 1: Notifications enabled, Player ID: abc123...
   
   === Tests Completed Successfully ===
   ```

### Success Criteria:
- ✅ Script runs without errors
- ✅ Player IDs are retrieved from database
- ✅ User Player ID lookup works correctly

---

## Test 4: Send Test Notification

**Goal:** Verify that targeted notifications can be sent to specific devices.

### Steps:

1. **Create a test script** (`test_send_notification.php`):
   ```php
   <?php
   require_once 'config.php';
   require_once 'app/config/database.php';
   require_once 'app/services/NotificationService.php';
   
   $notificationService = new NotificationService();
   
   // Send to user 1
   $result = $notificationService->sendToUser(
       1, 
       'Test Notification',
       'This is a test push notification!',
       ['url' => '/dashboard.php']
   );
   
   print_r($result);
   ```

2. **Run the script:**
   ```bash
   php test_send_notification.php
   ```

3. **Check the result:**
   - Should see: `'success' => true`
   - Should see: `'notification_id' => 'some-id'`

4. **Check device:**
   - Should receive push notification
   - Click should open `/dashboard.php`

### Success Criteria:
- ✅ Notification sent successfully
- ✅ Device receives notification
- ✅ Notification is targeted (only to specific user)

---

## Test 5: Medication Reminder Cron

**Goal:** Test the medication reminder cron script.

### Steps:

1. **Set up test data:**
   ```sql
   -- Create a pending medication dose scheduled for current time
   INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status)
   VALUES (1, 1, NOW(), 'pending');
   ```

2. **Run the cron script:**
   ```bash
   php app/cron/send_medication_reminders.php
   ```

3. **Check output:**
   ```
   [2026-02-05 15:00:00] Starting medication reminder check...
   Found 1 pending doses with active notifications
   [2026-02-05 15:00:00] Sent scheduled notification for [Medication] to user 1
   [2026-02-05 15:00:00] Medication reminder check completed
   ```

4. **Check device:**
   - Should receive medication reminder notification

### Success Criteria:
- ✅ Script finds pending doses
- ✅ Notification sent to correct user
- ✅ Device receives reminder

---

## Test 6: Reminder Timing Windows

**Goal:** Verify that reminders are sent at correct times based on user preferences.

### Steps:

1. **Set up test data with specific time:**
   ```sql
   -- Medication scheduled for 10 minutes ago
   INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status)
   VALUES (1, 1, DATE_SUB(NOW(), INTERVAL 10 MINUTE), 'pending');
   ```

2. **Verify user preferences:**
   ```sql
   SELECT notify_at_time, notify_after_10min, notify_after_20min, notify_after_30min, notify_after_60min
   FROM user_notification_settings
   WHERE user_id = 1;
   ```

3. **Run cron script:**
   ```bash
   php app/cron/send_medication_reminders.php
   ```

4. **Expected behavior:**
   - If `notify_after_10min = 1`: Should send "reminder-10" notification
   - If `notify_after_10min = 0`: Should NOT send notification

### Success Criteria:
- ✅ Reminders sent only when user preference is enabled
- ✅ Correct reminder type (scheduled, reminder-10, etc.)
- ✅ Timing tolerance works correctly (±1 minute)

---

## Test 7: Disable Notifications

**Goal:** Verify that disabling notifications works correctly.

### Steps:

1. **On notification settings page:**
   - Click "🔕 Disable Notifications" button
   - Confirm the prompt

2. **Check database:**
   ```sql
   SELECT notifications_enabled, onesignal_player_id 
   FROM user_notification_settings 
   WHERE user_id = 1;
   ```
   
   **Expected:**
   - `notifications_enabled` = 0
   - `onesignal_player_id` = NULL (or unchanged, depends on implementation)

3. **Refresh page:**
   - Should show "Enable Notifications" button again
   - Settings should be hidden

4. **Run cron script:**
   ```bash
   php app/cron/send_medication_reminders.php
   ```
   
   **Expected:**
   - Should NOT send notifications to this user
   - Script should skip users with `notifications_enabled = 0`

### Success Criteria:
- ✅ Notifications disabled in database
- ✅ UI reflects disabled state
- ✅ No notifications sent to disabled users

---

## Test 8: Multiple Devices

**Goal:** Test that multiple devices per user work correctly.

### Steps:

1. **Enable notifications on Device 1:**
   - Complete Test 1 on first device/browser
   - Note Player ID from console

2. **Enable notifications on Device 2:**
   - Open same account in different browser/device
   - Enable notifications
   - Note Player ID from console

3. **Check database:**
   ```sql
   SELECT onesignal_player_id FROM user_notification_settings WHERE user_id = 1;
   ```
   
   **Current behavior:**
   - Should show only the LATEST Player ID (second device)
   - First device's Player ID is overwritten

4. **Send test notification:**
   - Only Device 2 should receive it

### Success Criteria:
- ✅ Latest Player ID is stored
- ✅ Previous device may lose notifications (this is expected with current implementation)

**Note:** For multi-device support, the schema would need to change to store multiple Player IDs per user (one-to-many relationship).

---

## Common Issues & Troubleshooting

### Issue: Player ID is NULL in database
**Possible causes:**
- OneSignal not initialized properly
- Browser blocked notifications
- JavaScript error preventing submission

**Debug:**
- Check browser console for errors
- Verify `window.OneSignal.User.PushSubscription.id` has a value
- Check network tab for POST to `save_notifications_handler.php`

### Issue: Notifications not received
**Possible causes:**
- OneSignal credentials incorrect
- Player ID invalid or expired
- User notifications disabled in settings

**Debug:**
- Check cron output for errors
- Verify OneSignal REST API Key
- Test with manual notification script

### Issue: Notifications sent before scheduled time
**Should not happen** - Fixed in code review
- Check cron script timing logic
- Verify `NOTIFICATION_TOLERANCE_MINUTES` constant

### Issue: Page shows "Enable Notifications" despite Player ID in DB
**Possible causes:**
- Browser permission revoked
- `notifications_enabled` is 0 in database

**Debug:**
- Check `Notification.permission` in console
- Verify database value

---

## Summary Checklist

Use this checklist to verify complete implementation:

- [ ] Test 1: Initial opt-in captures Player ID
- [ ] Test 2: Status persists after page refresh
- [ ] Test 3: NotificationService retrieves Player IDs
- [ ] Test 4: Test notification sent successfully
- [ ] Test 5: Cron script sends reminders
- [ ] Test 6: Timing windows work correctly
- [ ] Test 7: Disabling notifications works
- [ ] Test 8: Multiple devices behavior understood

When all tests pass, the implementation is complete and ready for production!
