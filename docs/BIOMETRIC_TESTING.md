# Biometric Authentication Testing Guide

## Prerequisites

### Device Requirements
- iPhone X or newer (Face ID) OR iPhone 5s-8/SE (Touch ID)
- iOS/iPadOS 14 or later
- Safari browser
- Face ID or Touch ID enabled in device Settings

### Server Requirements
- HTTPS enabled (required for WebAuthn)
- Database migration applied (migration_add_biometric_auth.sql)
- PHP 7.4 or later
- MySQL/MariaDB database

## Test Cases

### 1. Test Setup and Initial State

**Steps:**
1. Sign in to the Health Tracker with username and password
2. Navigate to Settings → Biometric Auth
3. Verify the page loads without errors

**Expected Results:**
- ✅ Page displays "Not Enabled" status badge
- ✅ "Enable Face ID / Touch ID" section is visible
- ✅ Password input field is shown
- ✅ "Enable Face ID / Touch ID" button is visible

### 2. Test Biometric Unavailability Detection

**Test on non-biometric device or browser:**
1. Open the biometric settings page on Chrome, Firefox, or older Safari
2. Observe the page behavior

**Expected Results:**
- ✅ Warning message displayed: "Biometric authentication is not supported on this device or browser"
- ✅ Enable/disable sections are hidden
- ✅ User guidance provided for compatible browsers/devices

### 3. Test Enable Biometric Authentication

**Steps:**
1. Navigate to Settings → Biometric Auth
2. Enter your password in the password field
3. Click "Enable Face ID / Touch ID"
4. Complete the biometric prompt (Face ID or Touch ID)

**Expected Results:**
- ✅ Face ID/Touch ID prompt appears
- ✅ After successful biometric verification, success message shown
- ✅ Page reloads and shows "✓ Enabled" status
- ✅ "Disable Face ID / Touch ID" button now visible
- ✅ Enable section is hidden

**Error Cases:**
- Wrong password: Shows "Invalid password" error
- Biometric cancelled: Shows error message
- Biometric failed: User can retry

### 4. Test Biometric Login

**Steps:**
1. Sign out from the application
2. Navigate to the login page
3. Verify biometric login option appears
4. Click "Sign in with Face ID / Touch ID"
5. Complete biometric verification

**Expected Results:**
- ✅ "Sign in with Face ID / Touch ID" button is visible on login page
- ✅ Button shows biometric icon (🔐)
- ✅ On click, Face ID/Touch ID prompt appears
- ✅ After successful verification, user is redirected to dashboard
- ✅ Session is created (user is logged in)

**Error Cases:**
- Biometric fails: Error message shown, password login still available
- Cancelled biometric: User can try again or use password
- Invalid credential: Error shown with fallback to password

### 5. Test Password Fallback

**Steps:**
1. At login page with biometric option visible
2. Ignore the biometric button
3. Enter username and password
4. Click regular "Login" button

**Expected Results:**
- ✅ Password login works normally
- ✅ User is logged in successfully
- ✅ No interference from biometric feature

### 6. Test Disable Biometric Authentication

**Steps:**
1. Sign in to the application
2. Navigate to Settings → Biometric Auth
3. Verify "✓ Enabled" status is shown
4. Click "Disable Face ID / Touch ID"
5. Confirm the action in the dialog

**Expected Results:**
- ✅ Confirmation dialog appears
- ✅ After confirming, biometric is disabled
- ✅ Status changes to "Not Enabled"
- ✅ Enable section becomes visible again
- ✅ At login page, biometric button no longer appears

### 7. Test Session and Challenge Security

**Steps:**
1. Enable biometric authentication
2. Open browser dev tools → Network tab
3. Attempt to authenticate with biometric
4. Observe network requests

**Expected Results:**
- ✅ Challenge is fetched from `/api/biometric/challenge.php`
- ✅ Challenge is different on each request
- ✅ Authentication request includes assertion data
- ✅ Replay of old authentication attempt fails

### 8. Test Multiple Devices

**Steps:**
1. Enable biometric on Device A
2. Sign out and try to use biometric on Device B (different device)

**Expected Results:**
- ✅ Each device requires its own biometric enrollment
- ✅ Credential from Device A doesn't work on Device B
- ✅ User can enable biometric separately on Device B

### 9. Test HTTPS Requirement

**Steps:**
1. Try to access biometric features over HTTP (if possible)

**Expected Results:**
- ✅ WebAuthn features should not work over HTTP
- ✅ Appropriate error message shown
- ✅ Graceful degradation (password login still works)

### 10. Test Browser Compatibility

**Test on different browsers:**
- Safari on iOS 14+ ✅ Should work
- Safari on iOS 13 ❌ Should show not supported
- Chrome on iOS ✅ Should work (uses Safari WebView)
- Firefox on iOS ✅ Should work (uses Safari WebView)
- Desktop Safari ✅ Should work (with Touch ID on Mac)
- Desktop Chrome ❌ Should show not supported (no platform authenticator)

## Manual Testing Checklist

### Setup Phase
- [ ] Database migration applied successfully
- [ ] Server is running on HTTPS
- [ ] Test account created and can log in with password
- [ ] Biometric settings page loads

### Core Functionality
- [ ] Can enable biometric authentication with password verification
- [ ] Biometric prompt appears and works correctly
- [ ] Can log in using biometric on login page
- [ ] Can disable biometric authentication
- [ ] Password login still works when biometric is enabled

### Security & Edge Cases
- [ ] Wrong password prevents enabling biometric
- [ ] Challenge is validated server-side
- [ ] Cannot replay old authentication attempts
- [ ] Biometric fails gracefully and allows password fallback
- [ ] Credential from one device doesn't work on another
- [ ] HTTPS requirement enforced

### User Experience
- [ ] Clear error messages for all failure cases
- [ ] Loading states shown during async operations
- [ ] Success messages displayed appropriately
- [ ] Navigation flows make sense
- [ ] UI is responsive on mobile devices

### Browser/Device Compatibility
- [ ] Works on iPhone with Face ID
- [ ] Works on iPhone with Touch ID
- [ ] Shows "not supported" on incompatible devices
- [ ] Graceful degradation on older browsers

## Automated API Testing

### Using curl

**1. Get Challenge:**
```bash
curl -X GET https://your-domain.com/api/biometric/challenge.php \
  -H "Cookie: PHPSESSID=your_session_id" \
  -v
```

**2. Check Status:**
```bash
curl -X GET https://your-domain.com/api/biometric/status.php \
  -H "Cookie: PHPSESSID=your_session_id" \
  -v
```

**3. Disable Biometric:**
```bash
curl -X POST https://your-domain.com/api/biometric/disable.php \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -v
```

## Known Limitations

1. **WebAuthn Verification**: Current implementation uses simplified verification. For production, implement full WebAuthn signature verification.
2. **Single Device**: Each device requires separate biometric enrollment
3. **Browser Support**: Limited to Safari and WebView-based browsers on iOS
4. **HTTPS Required**: Feature only works over secure connections

## Reporting Issues

When reporting issues, include:
- Device model and iOS version
- Browser name and version
- Steps to reproduce
- Expected vs actual behavior
- Browser console errors (if any)
- Network request logs (if applicable)

## Success Criteria

All test cases should pass with:
- ✅ No JavaScript errors in console
- ✅ No PHP errors in server logs
- ✅ No database errors
- ✅ Smooth user experience
- ✅ Proper security measures in place
