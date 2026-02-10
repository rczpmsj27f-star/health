# Biometric Authentication (Face ID / Touch ID)

## Overview

This feature enables users to authenticate using biometric methods like Face ID or Touch ID on their iPhone or iPad devices. It uses the Web Authentication API (WebAuthn) which is a W3C standard for secure authentication.

## Features

- **Face ID** support for iPhone X and newer models
- **Touch ID** support for iPhone 5s through iPhone 8/SE models
- Secure authentication using device biometrics
- Fallback to password authentication if biometrics fail or are unavailable
- User control to enable/disable biometric authentication
- Password verification required before enabling biometric auth

## Browser & Device Compatibility

### Supported Platforms
- **iOS Safari 14+** (Face ID/Touch ID)
- **iPadOS 14+** (Face ID/Touch ID)
- **macOS Safari 14+** (Touch ID on compatible Macs)

### Requirements
- HTTPS connection (required by WebAuthn spec)
- Device with biometric hardware (Face ID or Touch ID)
- User must have biometrics enabled in device settings
- Modern browser with WebAuthn support

## User Guide

### Enabling Biometric Authentication

1. **Sign in** to your account using your username and password
2. Navigate to **Settings → Biometric Auth** from the menu
3. Click **"Enable Face ID / Touch ID"**
4. Enter your **password** to verify your identity
5. Follow the biometric prompt (Face ID or Touch ID)
6. Once successful, biometric authentication is enabled

### Using Biometric Authentication to Sign In

1. Open the Health Tracker app or website
2. On the login page, if biometric auth is enabled, you'll see:
   - **"Sign in with Face ID / Touch ID"** button
3. Click the button
4. Complete the biometric verification
5. You'll be automatically signed in

### Disabling Biometric Authentication

1. Navigate to **Settings → Biometric Auth**
2. Click **"Disable Face ID / Touch ID"**
3. Confirm the action
4. Biometric authentication is now disabled

### Fallback to Password

You can **always** use your password to sign in, even when biometric authentication is enabled. If biometric authentication fails or is unavailable:

1. Simply use the regular username/password login form
2. Your account security is maintained

## Security Considerations

### Data Privacy
- **Your biometric data never leaves your device** - Face ID and Touch ID data is stored securely in the device's Secure Enclave
- Only cryptographic keys are stored on the server
- Authentication is verified using public-key cryptography

### Security Features
- Password required to enable biometric authentication
- Biometrics supplement, not replace, password authentication
- Each device requires separate biometric enrollment
- Server-side validation of all authentication attempts
- Secure session management after successful authentication

### Best Practices
- Keep your device OS updated for latest security patches
- Use a strong password as your primary authentication method
- Disable biometric auth if you lose access to your device
- Re-enable biometric auth if you get a new device

## Technical Implementation

### Database Schema

The following columns are added to the `users` table:

```sql
biometric_enabled         TINYINT(1)   DEFAULT 0
biometric_credential_id   VARCHAR(255) NULL
biometric_public_key      TEXT         NULL
biometric_counter         INT          DEFAULT 0
last_biometric_login      DATETIME     NULL
```

### API Endpoints

- **GET** `/api/biometric/status.php` - Check biometric status
- **POST** `/api/biometric/register.php` - Register biometric credential
- **POST** `/api/biometric/authenticate.php` - Authenticate with biometric
- **POST** `/api/biometric/disable.php` - Disable biometric authentication

## Troubleshooting

### Biometric button doesn't appear on login
- Ensure you're using Safari on iOS/iPadOS 14+
- Verify you've enabled biometric auth in Settings
- Check that you're on an HTTPS connection
- Confirm your device has Face ID or Touch ID

### "Biometric authentication is not available"
- Ensure Face ID or Touch ID is set up in device Settings
- Check that biometrics are not disabled for Safari
- Try restarting your device
- Update to latest iOS version

### Authentication fails repeatedly
- Try disabling and re-enabling biometric auth
- Clear browser cache and data
- Ensure your face/finger is being recognized by the device
- Use password login as fallback

## References

- [Web Authentication API (WebAuthn)](https://www.w3.org/TR/webauthn/)
- [Apple Face ID / Touch ID Documentation](https://developer.apple.com/documentation/localauthentication)
- [Can I use WebAuthn](https://caniuse.com/webauthn)
