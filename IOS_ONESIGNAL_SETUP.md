# iOS OneSignal SDK Setup

## Overview
This document describes the native OneSignal SDK integration added to the iOS app to enable push notifications.

## Changes Made

### 1. Podfile
Added OneSignal SDK dependency:
```ruby
pod 'OneSignalXCFramework', '~> 5.0'
```

### 2. AppDelegate.swift
- Imported OneSignalFramework
- Added OneSignal initialization in `didFinishLaunchingWithOptions`
- Configured silent initialization (no extra prompts)
- Set debug logging to verbose for troubleshooting

## Installation Steps

After pulling these changes, you need to install the CocoaPods dependencies:

```bash
cd ios/App
pod install --repo-update
```

## Configuration

The OneSignal App ID is configured in the code:
- **App ID**: `27f8d4d3-3a69-4a4d-8f7b-113d16763c4b`

This matches the app ID defined in `config.php`. The App ID is a public identifier (not a secret) and is safe to include in version control.

**Note**: Debug logging is only enabled in DEBUG builds and disabled in production builds for security and performance.

## How It Works

1. **Silent Initialization**: The SDK initializes without showing notification permission prompts automatically
2. **Custom Capacitor Plugin**: A custom `PushPermissionPlugin` bridges JavaScript to native iOS to request permissions when the user taps the button in Settings
3. **Permission Flow**: `onesignal-capacitor.js` → `PushPermissionPlugin` → `OneSignal.Notifications.requestPermission()`
4. **Debug Logging**: Verbose logging is enabled only in DEBUG builds for troubleshooting (disabled in production for security and performance)
5. **Launch Options**: Passing launch options allows OneSignal to handle notifications that launched the app

## Testing

After installing pods and building the app:

1. Launch the app on a physical iOS device (push notifications don't work on simulators)
2. Check the Xcode console for OneSignal initialization logs
3. Navigate to the app's notification settings to enable push notifications
4. Send a test notification from the OneSignal dashboard

## Integration with Cordova Plugin

The native SDK works alongside the `onesignal-cordova-plugin` (v5.3.0):
- Native SDK handles iOS-specific initialization and notification delivery
- Cordova plugin provides JavaScript API for web-based interactions
- Both use the same App ID for seamless integration

## Troubleshooting

If push notifications are not working:

1. **Check pod installation**: Run `pod install` in `ios/App` directory
2. **Verify certificate**: Ensure APNs certificate is configured in OneSignal dashboard
3. **Check logs**: Look for OneSignal logs in Xcode console
4. **Test device**: Verify you're using a physical device (not simulator)
5. **Permissions**: Ensure notification permissions are granted in device settings

## References

- [OneSignal iOS SDK Documentation](https://documentation.onesignal.com/docs/ios-sdk-setup)
- [OneSignal Cordova Plugin](https://github.com/OneSignal/onesignal-cordova-plugin)
