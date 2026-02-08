# iOS App Quick Start Guide

This is a quick reference for getting started with the Health Tracker iOS app development.

## Prerequisites Checklist

- [ ] macOS computer
- [ ] Xcode installed (from Mac App Store)
- [ ] Node.js v14+ installed
- [ ] CocoaPods installed

## Installation

### 1. Install CocoaPods (if not already installed)
```bash
sudo gem install cocoapods
```

### 2. Install Node Dependencies
```bash
npm install
```

### 3. Install iOS Dependencies
```bash
cd ios/App
pod install
cd ../..
```

## Running the App

### Option 1: Using Xcode (Recommended)
```bash
npm run ios:open
```
Then press `Cmd+R` in Xcode to run on a simulator or connected device.

### Option 2: Using Command Line
```bash
npm run ios:run
```

## After Making Changes

### If you modified capacitor.config.json
```bash
npm run capacitor:sync
```

### If you added/removed Capacitor plugins
```bash
npm install
npm run capacitor:sync
```

### If CocoaPods dependencies are out of sync
```bash
cd ios/App
pod install
cd ../..
npm run capacitor:sync
```

## Common Commands

```bash
# Open Xcode
npm run ios:open

# Sync Capacitor (after config changes)
npm run capacitor:sync

# Update all Capacitor packages
npm run capacitor:update

# Run on simulator/device
npm run ios:run
```

## Project URLs

- **Web App URL:** https://ht.ianconroy.co.uk
- **Bundle ID:** co.uk.ianconroy.healthtracker
- **App Name:** Health Tracker

## Important Files

- `capacitor.config.json` - Capacitor configuration
- `package.json` - Node dependencies and scripts
- `ios/App/App.xcworkspace` - Xcode workspace (always open this, not .xcodeproj)
- `ios/App/App/Info.plist` - iOS app configuration

## Need More Help?

- **Full deployment guide:** See [IOS_DEPLOYMENT.md](IOS_DEPLOYMENT.md)
- **iOS app overview:** See [IOS_README.md](IOS_README.md)
- **Capacitor docs:** https://capacitorjs.com/docs

## Troubleshooting Quick Fixes

**Problem: Build fails with Pod errors**
```bash
cd ios/App
rm -rf Pods Podfile.lock
pod install
cd ../..
```

**Problem: Xcode shows old content**
```bash
npm run capacitor:sync
```
Then clean build in Xcode: `Product → Clean Build Folder`

**Problem: App shows blank screen**
- Check that https://ht.ianconroy.co.uk is accessible
- Verify internet connection on device/simulator
- Check `server.url` in `capacitor.config.json`

**Problem: Can't sign the app**
- In Xcode, select the App target
- Go to "Signing & Capabilities"
- Enable "Automatically manage signing"
- Select your Team from the dropdown
