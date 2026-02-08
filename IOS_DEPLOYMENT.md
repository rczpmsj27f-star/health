# iOS App Deployment Guide

This guide covers building and deploying the Health Tracker iOS app to the Apple App Store.

## Overview

The Health Tracker iOS app is built using Capacitor, which wraps the existing web application (https://ht.ianconroy.co.uk) as a native iOS app.

**App Details:**
- **App Name:** Health Tracker
- **Bundle ID:** co.uk.ianconroy.healthtracker
- **Web URL:** https://ht.ianconroy.co.uk
- **Platform:** iOS (iPhone and iPad)

## Prerequisites

Before you begin, ensure you have:

1. **macOS Computer** - Required for iOS development
2. **Xcode** - Download from the Mac App Store (latest version recommended)
3. **Node.js and npm** - Version 14 or higher
4. **CocoaPods** - iOS dependency manager
5. **Apple Developer Account** - Required for App Store deployment ($99/year)

### Installing Prerequisites

#### Install Xcode
```bash
# Download from Mac App Store or
xcode-select --install
```

#### Install CocoaPods
```bash
sudo gem install cocoapods
```

#### Verify Node.js
```bash
node --version  # Should be v14 or higher
npm --version
```

## Setup

### 1. Install Dependencies

```bash
# From the project root directory
npm install
```

### 2. Install iOS Dependencies

```bash
cd ios/App
pod install
cd ../..
```

### 3. Sync Capacitor

```bash
npm run capacitor:sync
```

This command:
- Copies web assets to the iOS project
- Updates native dependencies
- Syncs plugin configurations

## Development Workflow

### Open the iOS Project in Xcode

```bash
npm run ios:open
```

Or manually:
```bash
open ios/App/App.xcworkspace
```

**⚠️ Important:** Always open the `.xcworkspace` file, not the `.xcodeproj` file.

### Running on Simulator

1. Open the project in Xcode
2. Select a simulator from the device dropdown (e.g., iPhone 15)
3. Click the Play button (▶️) or press `Cmd+R`

Or use the command line:
```bash
npm run ios:run
```

### Running on Physical Device

1. Connect your iPhone/iPad via USB
2. Select your device from the device dropdown in Xcode
3. Click the Play button (▶️) or press `Cmd+R`
4. Trust the developer certificate on your device when prompted

**Note:** You'll need to configure signing (see below) before running on a physical device.

## Configuring App Signing

### For Development Testing

1. Open the project in Xcode
2. Select the "App" target in the project navigator
3. Go to "Signing & Capabilities" tab
4. Enable "Automatically manage signing"
5. Select your Team (your Apple Developer account)
6. Xcode will automatically generate development certificates

### For App Store Distribution

1. Log into [Apple Developer Portal](https://developer.apple.com)
2. Create an App ID:
   - Go to "Certificates, Identifiers & Profiles"
   - Select "Identifiers" → "+"
   - Select "App IDs" → "Continue"
   - Enter Bundle ID: `co.uk.ianconroy.healthtracker`
   - Enter Description: "Health Tracker"
   - Enable required capabilities (Push Notifications, if needed)
   - Click "Register"

3. Create a Distribution Certificate:
   - Go to "Certificates" → "+"
   - Select "iOS Distribution" → "Continue"
   - Generate a Certificate Signing Request (CSR) using Keychain Access
   - Upload CSR and download the certificate
   - Double-click to install in Keychain

4. Create a Provisioning Profile:
   - Go to "Profiles" → "+"
   - Select "App Store" → "Continue"
   - Select your App ID
   - Select your Distribution Certificate
   - Name it (e.g., "Health Tracker App Store")
   - Download and double-click to install

## Building for App Store

### 1. Update Version and Build Number

In Xcode:
1. Select the "App" target
2. Go to "General" tab
3. Update "Version" (e.g., 1.0.0)
4. Update "Build" number (increment for each submission)

### 2. Create App Store Connect Record

1. Log into [App Store Connect](https://appstoreconnect.apple.com)
2. Click "My Apps" → "+"
3. Select "New App"
4. Fill in details:
   - **Platform:** iOS
   - **Name:** Health Tracker
   - **Primary Language:** English (UK)
   - **Bundle ID:** co.uk.ianconroy.healthtracker
   - **SKU:** healthtracker-ios (or any unique identifier)
5. Click "Create"

### 3. Archive the App

1. In Xcode, select "Any iOS Device (arm64)" as the destination
2. Go to "Product" → "Archive"
3. Wait for the archive to complete
4. The Organizer window will open automatically

### 4. Upload to App Store Connect

1. In the Organizer, select your archive
2. Click "Distribute App"
3. Select "App Store Connect" → "Next"
4. Select "Upload" → "Next"
5. Choose signing options:
   - Select "Automatically manage signing" (recommended)
   - Or manually select your provisioning profile
6. Review the archive information → "Upload"
7. Wait for the upload to complete

### 5. Submit for Review

1. In App Store Connect, select your app
2. Go to the version you want to submit
3. Fill in required information:
   - **App Information:**
     - Name: Health Tracker
     - Subtitle: Medication reminders and tracking
     - Category: Medical
     - Privacy Policy URL
     - Support URL
   
   - **Version Information:**
     - Screenshots (required for all device sizes)
     - Description
     - Keywords
     - What's New (for updates)
   
   - **Build:**
     - Select the uploaded build
   
   - **App Review Information:**
     - Contact information
     - Demo account (if login required)
     - Notes for reviewer

4. Click "Submit for Review"

## App Assets

### App Icons

Required sizes (in pixels):
- **1024x1024** - App Store
- **180x180** - iPhone (60pt @3x)
- **167x167** - iPad Pro (83.5pt @2x)
- **152x152** - iPad (76pt @2x)
- **120x120** - iPhone (60pt @2x, 40pt @3x)
- **87x87** - iPhone (29pt @3x)
- **80x80** - iPad (40pt @2x)
- **76x76** - iPad (76pt @1x)
- **58x58** - iPad (29pt @2x)
- **40x40** - iPad (20pt @2x)
- **29x29** - iPhone, iPad (29pt @1x)
- **20x20** - iPad (20pt @1x)

Icons are located in: `ios/App/App/Assets.xcassets/AppIcon.appiconset/`

### Launch Screen (Splash Screen)

The splash screen is configured in `capacitor.config.json`:
```json
"SplashScreen": {
  "launchShowDuration": 2000,
  "backgroundColor": "#5b21b6",
  "showSpinner": true,
  "spinnerColor": "#ffffff"
}
```

To customize:
1. Edit colors in `capacitor.config.json`
2. Run `npm run capacitor:sync` to update iOS project
3. For custom splash images, edit `ios/App/App/Assets.xcassets/Splash.imageset/`

### Screenshots

Required screenshot sizes for App Store:
- **iPhone 6.7"** (1290 x 2796 pixels) - iPhone 15 Pro Max
- **iPhone 6.5"** (1284 x 2778 pixels) - iPhone 14 Plus
- **iPhone 5.5"** (1242 x 2208 pixels) - iPhone 8 Plus
- **iPad Pro 12.9"** (2048 x 2732 pixels)
- **iPad Pro 11"** (1668 x 2388 pixels)

Take screenshots using the iOS Simulator:
1. Run the app in various simulators
2. Press `Cmd+S` to save screenshot
3. Screenshots saved to Desktop

## Updating the App

### When the Web App Changes

Since this is a wrapper app that loads the remote web URL, most updates to the web application at https://ht.ianconroy.co.uk will be automatically reflected in the iOS app without requiring an App Store update.

**You only need to submit an App Store update when:**
- Changing native functionality (plugins, permissions)
- Updating app icons or splash screen
- Changing app metadata (name, description)
- Major version changes
- Apple requires it (policy changes)

### Syncing Changes

After modifying `capacitor.config.json` or adding/removing plugins:

```bash
npm run capacitor:sync
```

## Troubleshooting

### Build Errors

**"No signing certificate found"**
- Solution: Configure signing in Xcode (see "Configuring App Signing" above)

**"Command PhaseScriptExecution failed"**
- Solution: Clean build folder (`Product` → `Clean Build Folder`) and rebuild

**"Unable to install pod"**
- Solution: Update CocoaPods
  ```bash
  sudo gem install cocoapods
  cd ios/App
  pod repo update
  pod install
  ```

### Network Issues in App

**App shows blank screen:**
1. Check that https://ht.ianconroy.co.uk is accessible
2. Verify `server.url` in `capacitor.config.json`
3. Check iOS app's network permissions
4. Use Safari Web Inspector to debug (see below)

**"Could not connect to server":**
- Ensure device/simulator has internet connection
- Check that the web URL is correct and accessible
- Verify SSL certificate is valid

### Debugging

**Enable Web Inspector:**
1. On your iOS device: Settings → Safari → Advanced → Web Inspector (ON)
2. Connect device to Mac
3. Run the app
4. Open Safari → Develop → [Your Device] → [App Name]
5. Use Safari DevTools to inspect and debug

**View Console Logs:**
In Xcode:
1. Run the app
2. Open the Debug Console (View → Debug Area → Activate Console)
3. View native and JavaScript logs

## App Store Review Guidelines

Key points to ensure approval:

1. **Functionality:**
   - App must be fully functional
   - No broken links or features
   - All features must work as described

2. **Design:**
   - App must use standard iOS UI patterns
   - Proper navigation and gestures
   - Respects system settings (Dark Mode, text size)

3. **Privacy:**
   - Include privacy policy
   - Properly request permissions
   - Explain data collection in App Privacy section

4. **Performance:**
   - Fast loading times
   - Responsive to user input
   - No crashes or freezing

5. **Content:**
   - No inappropriate content
   - Accurate metadata
   - Proper categorization

See [App Store Review Guidelines](https://developer.apple.com/app-store/review/guidelines/) for complete details.

## Maintenance

### Regular Updates

- **Monitor Web App:** Ensure the web application remains accessible and functional
- **Test Periodically:** Run the iOS app regularly to catch any issues
- **Keep Dependencies Updated:**
  ```bash
  npm update
  npm run capacitor:update
  ```
- **Test on New iOS Versions:** When Apple releases new iOS versions

### Analytics and Monitoring

Consider adding:
- **Crashlytics** - Monitor app crashes
- **Analytics** - Track user engagement
- **Error Tracking** - Capture JavaScript errors

Can be added via Capacitor plugins.

## Support

### Resources

- [Capacitor Documentation](https://capacitorjs.com/docs)
- [iOS Developer Documentation](https://developer.apple.com/documentation/)
- [App Store Connect Help](https://help.apple.com/app-store-connect/)

### Getting Help

- **Capacitor Community:** [GitHub Discussions](https://github.com/ionic-team/capacitor/discussions)
- **Stack Overflow:** Tag with `capacitor` and `ios`
- **Apple Developer Forums:** [developer.apple.com/forums](https://developer.apple.com/forums)

## Quick Reference

```bash
# Install dependencies
npm install

# Sync Capacitor (after config changes)
npm run capacitor:sync

# Open in Xcode
npm run ios:open

# Run on simulator/device
npm run ios:run

# Update Capacitor
npm run capacitor:update

# Clean and rebuild
rm -rf ios/App/Pods
cd ios/App && pod install && cd ../..
npm run capacitor:sync
```

## License

This iOS wrapper follows the same license as the Health Tracker application.
