# Quick Reference: New Features

## Feature 1: iOS Face ID / Touch ID Authentication

### For Developers

**Check if biometric is available:**
```javascript
const availability = await BiometricAuth.isPlatformAuthenticatorAvailable();
// Returns: { available: true, isNative: true, biometryType: 'FaceID' }
```

**Verify user identity:**
```javascript
try {
    const result = await BiometricAuth.verifyNative('Unlock your session');
    // User successfully authenticated
    console.log('Authentication successful');
} catch (error) {
    // Authentication failed or user cancelled
    console.error('Authentication failed:', error);
}
```

### For Users

**On iOS App:**
1. When prompted for authentication, Face ID or Touch ID will appear
2. Follow the on-screen prompts to authenticate
3. If biometric fails, you can use your device passcode as fallback

**On Web:**
- WebAuthn API is used as fallback
- Browser will prompt for platform authenticator if available

---

## Feature 2: PRN Medication Exclusion from Compliance

### What Changed

**Before:**
- Compliance included all medications (scheduled + PRN)
- PRN usage could skew adherence percentages

**After:**
- Compliance only counts scheduled medications
- PRN medications tracked separately in their own views
- Clear labels: "Scheduled Compliance" instead of "Overall Compliance"

### For Users

**Viewing Compliance Reports:**
1. Go to Activity & Compliance → Compliance Report
2. All statistics show only scheduled medications
3. Note: "PRN (as needed) medications are not included in compliance calculations"
4. View PRN usage separately in Medications → PRN view

**Understanding the Metrics:**
- **Scheduled Compliance %** = (Scheduled Doses Taken / Total Scheduled Doses) × 100
- PRN doses do NOT affect this percentage
- PRN medications still tracked in separate PRN views (daily/weekly/monthly)

---

## Feature 3: PDF Sharing

### For Users

**Sharing a PDF Report (iOS App):**
1. Go to Medication Exports & Reports
2. Choose the report you want to share
3. Tap "📤 Share PDF" button (instead of Download)
4. iOS Share Sheet appears with options:
   - **AirDrop** - Share with nearby Apple devices
   - **Messages** - Share via iMessage
   - **Mail** - Email the PDF
   - **WhatsApp** - Share via WhatsApp (if installed)
   - **Save to Files** - Save to iCloud or local storage
   - Other apps on your device
5. Select your preferred sharing method

**Sharing on Web Browser:**
1. Go to Medication Exports & Reports
2. Choose the report you want to share
3. Click "📤 Share PDF" button
4. If browser supports Web Share API:
   - Browser's native share dialog appears
   - Choose sharing method (varies by OS/browser)
5. If not supported:
   - PDF downloads automatically as fallback

**Available Reports:**
- Current Medications List
- Weekly Schedule
- Monthly Schedule
- Manual Medication Chart (with date range)
- PRN Usage Report (with date range)

### For Developers

**Using the PDF Share API:**
```javascript
// Simple share
await PdfShare.sharePdf(
    'medication_report.pdf',
    base64Data,
    'Health Tracker Report'
);

// The function automatically:
// 1. Detects if Capacitor Share is available (native app)
// 2. Falls back to Web Share API (modern browsers)
// 3. Falls back to download (older browsers)
```

**Getting PDF as base64 for sharing:**
```javascript
const response = await fetch('export_pdf.php?type=current_medications&share=1');
const data = await response.json();
// data.base64 contains the PDF data
// data.filename contains suggested filename
```

---

## Platform Support Matrix

| Feature | iOS App | Android App | Modern Browsers | Older Browsers |
|---------|---------|-------------|-----------------|----------------|
| **Biometric Auth** | ✅ Face ID/Touch ID | ✅ Fingerprint/Face | ✅ WebAuthn | ⚠️ Limited |
| **PRN Exclusion** | ✅ | ✅ | ✅ | ✅ |
| **PDF Share** | ✅ Share Sheet | ✅ Share Dialog | ✅ Web Share API | ⚠️ Download only |

**Legend:**
- ✅ Full support
- ⚠️ Limited/fallback support

---

## Troubleshooting

### Biometric Authentication

**Issue:** "Biometric authentication not available"
- **Solution:** Ensure Face ID/Touch ID is set up on your device
- **iOS:** Settings → Face ID & Passcode (or Touch ID & Passcode)

**Issue:** Biometric prompt doesn't appear
- **Solution 1:** Check that app has permission to use Face ID
- **Solution 2:** Restart the app
- **Solution 3:** Use passcode fallback

### PDF Sharing

**Issue:** Share button shows "Download" instead of "Share"
- **Cause:** Browser doesn't support Web Share API
- **Solution:** This is normal - PDF will download instead

**Issue:** Share Sheet doesn't show WhatsApp
- **Cause:** WhatsApp not installed or not set up for sharing
- **Solution:** Install WhatsApp or choose another sharing method

**Issue:** Share fails on iOS
- **Solution 1:** Check device storage (PDF needs temporary space)
- **Solution 2:** Try downloading PDF instead
- **Solution 3:** Restart app and try again

### Compliance Reports

**Issue:** PRN medication shows in compliance report
- **Cause:** Medication may not be properly marked as PRN
- **Solution:** Edit medication and ensure "PRN (as needed)" is checked

**Issue:** Scheduled medication doesn't show in compliance
- **Cause:** Medication may not have a schedule set
- **Solution:** Add a schedule to the medication

---

## Technical Details

### Capacitor Plugins Used

1. **@capgo/capacitor-native-biometric** (v5.1.1)
   - Native biometric authentication
   - iOS: Face ID, Touch ID
   - Android: Fingerprint, Face, Iris

2. **@capacitor/share** (v5.0.8)
   - Native share functionality
   - iOS Share Sheet
   - Android Share Dialog

3. **@capacitor/filesystem** (v5.2.2)
   - File system access
   - Cache directory for temporary PDF storage

### Browser Compatibility

**Web Share API (Level 2):**
- Chrome/Edge 89+
- Safari 15.4+
- Firefox: Not supported (downloads as fallback)

**WebAuthn:**
- Chrome 67+
- Safari 14+
- Firefox 60+
- Edge 18+

---

## API Reference

### BiometricAuth

```javascript
// Check availability
BiometricAuth.isNativeBiometricAvailable()
// Returns: Promise<{available: boolean, biometryType: string, isNative: boolean}>

BiometricAuth.isPlatformAuthenticatorAvailable()
// Returns: Promise<{available: boolean, isNative: boolean}>

// Authenticate
BiometricAuth.verifyNative(reason)
// Params: reason (string) - Message shown to user
// Returns: Promise<{success: boolean, verified: boolean}>
```

### PdfShare

```javascript
// Share PDF
PdfShare.sharePdf(filename, base64Data, title)
// Params:
//   - filename: string (e.g., 'report.pdf')
//   - base64Data: string (base64-encoded PDF)
//   - title: string (share dialog title)
// Returns: Promise<{success: boolean, method: string}>
//   method: 'capacitor' | 'webapi' | 'download'

// Check availability
PdfShare.isCapacitorShareAvailable()
// Returns: boolean

PdfShare.isWebShareAvailable()
// Returns: boolean
```

---

## Testing Checklist

### Biometric Authentication
- [ ] Test Face ID on iOS device with Face ID
- [ ] Test Touch ID on iOS device with Touch ID
- [ ] Test fallback to passcode
- [ ] Test WebAuthn on web browser
- [ ] Test "not available" message on unsupported devices

### PRN Exclusion
- [ ] Create scheduled medication and verify in compliance
- [ ] Create PRN medication and verify NOT in compliance
- [ ] Verify PRN appears in PRN views (daily/weekly/monthly)
- [ ] Check compliance percentages exclude PRN doses
- [ ] Verify labels say "Scheduled Compliance"

### PDF Sharing
- [ ] Test Share Sheet on iOS device
- [ ] Share via AirDrop
- [ ] Share via iMessage
- [ ] Share via Mail
- [ ] Share via WhatsApp
- [ ] Test Web Share API on Chrome
- [ ] Test download fallback on Firefox
- [ ] Test all report types (5 total)

---

## Support

For issues or questions:
1. Check IMPLEMENTATION_USER_FEATURES.md for detailed documentation
2. Review this Quick Reference for common solutions
3. Contact support with specific error messages
