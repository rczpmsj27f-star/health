# App Store Assets Guide

This guide helps you prepare the required assets for App Store submission.

## Required Assets Checklist

### App Icons
- [ ] 1024x1024 - App Store icon (PNG, no transparency, no alpha channel)
- [ ] 180x180 - iPhone @3x
- [ ] 167x167 - iPad Pro @2x
- [ ] 152x152 - iPad @2x
- [ ] 120x120 - iPhone @2x
- [ ] 87x87 - iPhone @3x
- [ ] 80x80 - iPad @2x
- [ ] 76x76 - iPad @1x
- [ ] 58x58 - Settings @2x
- [ ] 40x40 - Spotlight @2x
- [ ] 29x29 - Settings @1x
- [ ] 20x20 - Notifications @1x

### Screenshots
- [ ] iPhone 6.7" (1290 x 2796) - Required
- [ ] iPhone 6.5" (1284 x 2778) - Required
- [ ] iPhone 5.5" (1242 x 2208) - Required
- [ ] iPad Pro 12.9" (2048 x 2732) - Required for iPad support
- [ ] iPad Pro 11" (1668 x 2388) - Required for iPad support

### App Store Listing
- [ ] App name (30 characters max)
- [ ] Subtitle (30 characters max)
- [ ] Description (4000 characters max)
- [ ] Keywords (100 characters max, comma-separated)
- [ ] Privacy Policy URL
- [ ] Support URL
- [ ] Marketing URL (optional)
- [ ] Promotional text (170 characters max, optional)

## App Icon Guidelines

### Design Requirements

**Must Have:**
- Square shape with no rounded corners (iOS applies them automatically)
- No transparency or alpha channels
- RGB color space (not CMYK)
- High resolution (1024x1024 for App Store)
- Clear, recognizable design

**Avoid:**
- Text that's too small to read
- Photos of people (unless it's a social app)
- Generic clip art
- Designs that look like iOS system icons

### Creating App Icons

#### Option 1: Using Design Tools
1. Create a 1024x1024 icon in Photoshop, Figma, or Sketch
2. Use [AppIconMaker](https://appicon.co/) to generate all sizes
3. Download and replace files in `ios/App/App/Assets.xcassets/AppIcon.appiconset/`

#### Option 2: Using Xcode
1. Create a single 1024x1024 PNG
2. Open Xcode project
3. Select Assets.xcassets → AppIcon
4. Drag your 1024x1024 icon to the "App Store iOS 1024pt" slot
5. Right-click → Generate All Sizes (requires Xcode 14+)

### Health Tracker Branding

Consider using:
- **Primary Color:** #5b21b6 (purple from the app)
- **Icon Style:** Medical/health-related imagery
- **Suggestions:** 
  - Pill/capsule icon
  - Medical cross
  - Heart rate symbol
  - Calendar with medical icon
  - Simple "H" or "HT" monogram

## Screenshots

### Taking Screenshots

1. **Run app in iOS Simulator:**
   ```bash
   npm run ios:open
   ```

2. **Select required device sizes:**
   - iPhone 15 Pro Max (6.7")
   - iPhone 14 Plus (6.5")
   - iPhone 8 Plus (5.5")
   - iPad Pro 12.9"
   - iPad Pro 11"

3. **Navigate to key screens:**
   - Login/Home screen
   - Medication list
   - Add medication form
   - Medication details
   - Settings/notifications

4. **Capture screenshots:**
   - Press `Cmd+S` in simulator
   - Screenshots save to Desktop

### Screenshot Requirements

**Format:**
- PNG or JPEG
- RGB color space
- 72 DPI minimum

**Content:**
- No status bar showing sensitive info (time, battery, etc.)
- High quality, clear text
- Representative of actual app functionality
- Up to 10 screenshots per device size

### Screenshot Best Practices

1. **Show Key Features:**
   - Medication tracking
   - Reminder notifications
   - Easy medication management
   - Calendar view
   - Settings options

2. **Clean Status Bar:**
   - Show full battery
   - Show good signal strength
   - Set time to 9:41 AM (Apple's marketing time)
   - Use Xcode's "Clean Status Bar" feature

3. **Add Text Overlays (Optional):**
   - Brief feature descriptions
   - Value propositions
   - Call-to-action

4. **Consistent Design:**
   - Same orientation for all screenshots
   - Similar composition
   - Professional appearance

## Splash Screen (Launch Screen)

### Current Configuration

The splash screen is configured in `capacitor.config.json`:

```json
"SplashScreen": {
  "launchShowDuration": 2000,
  "backgroundColor": "#5b21b6",
  "showSpinner": true,
  "spinnerColor": "#ffffff"
}
```

### Customizing Splash Screen

#### Colors Only (Simple)
Edit `capacitor.config.json`:
```json
"backgroundColor": "#YOUR_COLOR",
"spinnerColor": "#YOUR_COLOR"
```

Then sync:
```bash
npm run capacitor:sync
```

#### Custom Image (Advanced)
1. Create splash images for all sizes:
   - 2732x2732 - iPad Pro 12.9"
   - 2048x2732 - iPad Pro 10.5"
   - 1668x2388 - iPad Pro 11"
   - 1668x2224 - iPad 10.2"
   - 1536x2048 - iPad
   - 1242x2688 - iPhone 11 Pro Max
   - 1125x2436 - iPhone X/XS
   - 828x1792 - iPhone XR
   - 750x1334 - iPhone 8
   - 640x1136 - iPhone SE

2. Add to: `ios/App/App/Assets.xcassets/Splash.imageset/`

3. Update Contents.json in that directory

4. Sync Capacitor:
   ```bash
   npm run capacitor:sync
   ```

## App Store Description

### Suggested Template

**Short Description (Subtitle):**
```
Medication reminders & tracking
```

**Full Description:**
```
Health Tracker helps you manage medications and never miss a dose.

KEY FEATURES:
• Easy medication tracking
• Smart reminders at scheduled times
• Track medication adherence
• Simple, intuitive interface
• Secure and private

MEDICATION MANAGEMENT:
Add your medications with name, dose, and schedule. Health Tracker will remind you when it's time to take them.

SMART REMINDERS:
Receive notifications at your scheduled times. Configurable reminder intervals ensure you never forget.

TRACK YOUR HEALTH:
Mark medications as taken and track your adherence over time. View your medication history easily.

PRIVACY FIRST:
Your health data stays secure. We respect your privacy and protect your information.

Perfect for:
- Managing daily medications
- Tracking prescription schedules
- Remembering vitamins and supplements
- Coordinating care for family members
```

**Keywords:**
```
medication,reminder,health,tracker,pills,prescription,medicine,dose,schedule,adherence
```

### Privacy Policy Requirements

You must provide a privacy policy URL. Key points to cover:

1. **What data is collected:**
   - Medication names and schedules
   - User account information
   - Usage statistics (if applicable)

2. **How data is used:**
   - To provide medication reminders
   - To track adherence
   - To improve the app

3. **How data is stored:**
   - Securely on servers
   - Encrypted in transit
   - Not shared with third parties

4. **User rights:**
   - Access their data
   - Delete their data
   - Export their data

## App Store Connect Configuration

### App Information

**Category:** Medical  
**Secondary Category:** Health & Fitness (optional)

**Age Rating:**
- Medical/Treatment Information: Yes
- Unrestricted Web Access: No (since it's a wrapped app)

### Pricing and Availability

- **Price:** Free (or set your price)
- **Availability:** All countries or select specific ones

### App Privacy

You'll need to complete the App Privacy section with:

1. **Data Types Collected:**
   - Health data (medications)
   - Contact info (email, if used for account)
   - User content (medication notes)

2. **Purpose:**
   - App functionality
   - Product personalization

3. **Data Linking:**
   - Whether data is linked to user identity

4. **Data Tracking:**
   - Whether data is used to track users across apps/websites

## Review Preparation

### Demo Account

If your app requires login:
1. Create a test account
2. Pre-populate with sample medications
3. Provide credentials in App Review Information
4. Ensure account remains active during review

### Review Notes

Provide helpful notes to reviewers:

```
Health Tracker is a medication reminder and tracking application.

Test Account:
Username: reviewer@example.com
Password: [provided separately]

The app loads the Health Tracker web application from https://ht.ianconroy.co.uk
and provides a native iOS wrapper with push notifications and native UI.

To test:
1. Log in with the provided credentials
2. View existing medications
3. Add a new medication with a scheduled time
4. Mark a medication as taken
5. Check notification settings

The app requires an internet connection to load the web application.
All core features are accessible through the provided test account.
```

## Asset Checklist Before Submission

- [ ] All app icons are correct sizes and format
- [ ] App Store icon (1024x1024) is perfect quality
- [ ] Screenshots for all required device sizes
- [ ] Screenshots show actual app features
- [ ] Privacy policy is published and URL works
- [ ] Support URL is accessible
- [ ] App description is accurate and compelling
- [ ] Keywords are relevant and optimized
- [ ] Demo account works (if applicable)
- [ ] Review notes are clear and helpful
- [ ] App version number is correct
- [ ] Copyright information is accurate

## Tools and Resources

### Design Tools
- [Figma](https://figma.com) - Free design tool
- [Canva](https://canva.com) - Easy graphic design
- [AppIconMaker](https://appicon.co/) - Generate all icon sizes
- [MakeAppIcon](https://makeappicon.com/) - Another icon generator

### Screenshot Tools
- [Screenshot Designer](https://www.screenshot.app/) - Add frames and text
- [AppMockUp](https://app-mockup.com/) - Device mockups
- [PlaceIt](https://placeit.net/) - Professional mockups

### Testing Tools
- [TestFlight](https://developer.apple.com/testflight/) - Beta testing
- [App Store Connect](https://appstoreconnect.apple.com/) - Manage submissions

### Privacy Policy Generators
- [TermsFeed](https://www.termsfeed.com/privacy-policy-generator/)
- [FreePrivacyPolicy](https://www.freeprivacypolicy.com/)
- [PrivacyPolicies.com](https://www.privacypolicies.com/)

## Next Steps

1. Create app icons (start with 1024x1024)
2. Take screenshots on all required devices
3. Write app description and keywords
4. Create or update privacy policy
5. Prepare demo account (if needed)
6. Upload to App Store Connect
7. Fill in all metadata
8. Submit for review

For detailed submission steps, see [IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md).
