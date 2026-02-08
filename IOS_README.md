# Health Tracker - iOS App

A native iOS wrapper for the Health Tracker web application using Capacitor.

## Overview

This iOS app wraps the Health Tracker web application (https://ht.ianconroy.co.uk) as a native iOS app that can be distributed through the Apple App Store.

**App Configuration:**
- **App Name:** Health Tracker
- **Bundle ID:** co.uk.ianconroy.healthtracker
- **Target Platform:** iOS (iPhone and iPad)
- **Web URL:** https://ht.ianconroy.co.uk

## What is Capacitor?

Capacitor is a cross-platform native runtime that makes it easy to build modern web apps that run natively on iOS, Android, and the web. It wraps your web application in a native container, providing:

- Native iOS app experience
- Access to native device features (camera, GPS, etc.)
- App Store distribution
- Native performance optimizations
- Offline capabilities

## Features

✅ **Native iOS App** - Runs as a true native iOS application  
✅ **Web Content** - Loads the full-featured Health Tracker web app  
✅ **App Store Ready** - Configured for Apple App Store deployment  
✅ **Auto-Updates** - Web content updates automatically without app updates  
✅ **Native Plugins** - Includes essential iOS integrations  
✅ **Universal** - Supports both iPhone and iPad  

## Quick Start

### For Development

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Open in Xcode:**
   ```bash
   npm run ios:open
   ```

3. **Run in Simulator:**
   - Select a simulator in Xcode
   - Press `Cmd+R` or click the Play button

### For App Store Deployment

See the comprehensive [iOS Deployment Guide](IOS_DEPLOYMENT.md) for detailed instructions on:
- Setting up Apple Developer account
- Configuring code signing
- Building and archiving
- Submitting to App Store
- Managing updates

## Project Structure

```
health/
├── capacitor.config.json      # Capacitor configuration
├── package.json               # Node dependencies
├── ios/                       # iOS native project (generated)
│   └── App/
│       ├── App.xcworkspace   # Xcode workspace (open this!)
│       ├── App/              # iOS app source
│       └── Pods/             # CocoaPods dependencies
├── public/                    # Web application files
└── IOS_DEPLOYMENT.md         # Detailed deployment guide
```

## Configuration

### App Settings

Edit `capacitor.config.json` to modify:

```json
{
  "appId": "co.uk.ianconroy.healthtracker",
  "appName": "Health Tracker",
  "server": {
    "url": "https://ht.ianconroy.co.uk"
  }
}
```

### Native Plugins

Currently included plugins:

- **@capacitor/status-bar** - Native status bar styling
- **@capacitor/splash-screen** - Launch screen configuration
- **@capacitor/keyboard** - Keyboard behavior optimization
- **@capacitor/network** - Network status monitoring
- **@capacitor/app** - App lifecycle events

To add more plugins:

```bash
npm install @capacitor/[plugin-name]
npm run capacitor:sync
```

## Available Scripts

```bash
# Development
npm run ios:open          # Open Xcode
npm run ios:run           # Run on simulator/device

# Maintenance
npm run capacitor:sync    # Sync web assets and plugins
npm run capacitor:update  # Update Capacitor dependencies
```

## Building for Production

### 1. Prepare the Build

```bash
npm run capacitor:sync
```

### 2. Open in Xcode

```bash
npm run ios:open
```

### 3. Configure Signing

- Select the App target
- Go to "Signing & Capabilities"
- Select your development team
- Ensure Bundle ID is `co.uk.ianconroy.healthtracker`

### 4. Archive

- In Xcode: Product → Archive
- Wait for the archive to complete
- Distribute to App Store Connect

See [IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md) for complete step-by-step instructions.

## How It Works

### App Launch Flow

1. User taps app icon
2. Native iOS app launches
3. Capacitor initializes
4. Splash screen displays (2 seconds)
5. Web view loads https://ht.ianconroy.co.uk
6. Web application renders and runs

### Auto-Updates

Since the app loads remote web content:

✅ **Most updates happen automatically** - Changes to the web application at https://ht.ianconroy.co.uk are immediately reflected in the iOS app

⚠️ **App Store updates only needed for:**
- Native functionality changes
- Plugin updates
- App icon/splash screen changes
- iOS version requirements
- App metadata changes

## Supported iOS Versions

- **Minimum iOS Version:** 13.0
- **Target iOS Version:** Latest
- **Devices:** iPhone, iPad (Universal)

## Customization

### App Icon

Replace icons in: `ios/App/App/Assets.xcassets/AppIcon.appiconset/`

Required sizes: 20x20, 29x29, 40x40, 58x58, 60x60, 76x76, 80x80, 87x87, 120x120, 152x152, 167x167, 180x180, 1024x1024

### Splash Screen

Edit in `capacitor.config.json`:

```json
"SplashScreen": {
  "launchShowDuration": 2000,
  "backgroundColor": "#5b21b6",
  "showSpinner": true,
  "spinnerColor": "#ffffff"
}
```

The purple color (#5b21b6) matches the Health Tracker brand color.

### Status Bar

Edit in `capacitor.config.json`:

```json
"StatusBar": {
  "style": "dark"
}
```

Options: `"light"`, `"dark"`, or `"default"`

## Troubleshooting

### App shows blank screen
- Verify https://ht.ianconroy.co.uk is accessible
- Check internet connection
- Use Safari Web Inspector to debug

### Cannot build/run
- Ensure Xcode is installed
- Run `cd ios/App && pod install`
- Clean build folder in Xcode

### Signing errors
- Configure development team in Xcode
- Ensure Bundle ID matches Apple Developer account

See [IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md) for detailed troubleshooting.

## Requirements

- **macOS** - Required for iOS development
- **Xcode** - Latest version recommended
- **Node.js** - v14 or higher
- **CocoaPods** - iOS dependency manager
- **Apple Developer Account** - For App Store deployment ($99/year)

## Documentation

- **[IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md)** - Complete deployment guide
- **[Capacitor Docs](https://capacitorjs.com/docs)** - Official Capacitor documentation
- **[iOS Developer](https://developer.apple.com/)** - Apple developer resources

## Support

For issues related to:
- **iOS app wrapper:** Check [IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md)
- **Web application:** Contact Health Tracker support
- **Capacitor:** See [Capacitor GitHub](https://github.com/ionic-team/capacitor)

## License

This iOS wrapper follows the same license as the Health Tracker application.
