# iOS Native Plugin Implementation Guide

## Overview
This guide explains the implementation of a custom Capacitor plugin (`PushPermissionPlugin`) to handle iOS notification permissions.

## What Was Changed

### 1. AppDelegate.swift
- **Removed**: Auto-prompt for OneSignal permissions on app launch (lines 28-32)
- **Result**: OneSignal initializes silently without requesting permissions immediately

### 2. Created PushPermissionPlugin.swift
- **Location**: `ios/App/App/PushPermissionPlugin.swift`
- **Purpose**: Custom Capacitor plugin that exposes `requestPermission()` and `checkPermission()` methods
- **Methods**:
  - `requestPermission()`: Triggers iOS native permission prompt via OneSignal
  - `checkPermission()`: Returns current permission status

### 3. Created PushPermissionPlugin.m
- **Location**: `ios/App/App/PushPermissionPlugin.m`
- **Purpose**: Objective-C bridge file to register the plugin with Capacitor
- **Exports**: `PushPermission` plugin to JavaScript

### 4. Updated onesignal-capacitor.js
- **Changes**: 
  - Now uses `window.Capacitor.Plugins.PushPermission` instead of OneSignal Web SDK
  - `requestPermission()` calls native plugin method
  - `checkPermission()` checks native permission status

### 5. Updated notifications.php
- **Changes**:
  - Removed dependency on OneSignal Web SDK availability check
  - Directly calls `window.OneSignalCapacitor.requestPermission()`

## IMPORTANT: Manual Step Required

### Adding Plugin Files to Xcode Project

The new Swift and Objective-C files (`PushPermissionPlugin.swift` and `PushPermissionPlugin.m`) have been created but need to be added to the Xcode project:

1. Open `ios/App/App.xcworkspace` in Xcode
2. In the Project Navigator, right-click on the "App" folder (under "App" group)
3. Select "Add Files to 'App'..."
4. Navigate to the `App` folder and select:
   - `PushPermissionPlugin.swift`
   - `PushPermissionPlugin.m`
5. Ensure these options are set correctly:
   - ❌ "Copy items if needed" (should be UNCHECKED since files are already in correct location)
   - ✅ "Create groups" (should be CHECKED)
   - ✅ "Add to targets: App" (should be CHECKED)
6. Click "Add"
7. Build the project (⌘+B) to verify there are no errors

### Verifying the Setup

After adding the files to Xcode:

1. The files should appear in the Project Navigator under the "App" group
2. Build should succeed without errors
3. When running the app, the permission prompt should NOT appear on launch
4. Permission prompt should appear only when tapping "Enable Push Notifications" in Settings

## Expected User Flow

1. **App Launch**: No notification prompt ✅
2. **User Logs In**: No notification prompt ✅
3. **User Goes to Settings → Notifications**: Sees "Enable Push Notifications" button
4. **User Taps Button**: iOS native permission prompt appears
5. **User Taps "Allow"**: Device registers with OneSignal
6. **Toggle Updates**: Shows "Push Notifications Enabled"

## Troubleshooting

### Plugin Not Available Error
If you see "PushPermission plugin not available" in console:
- Verify the plugin files were added to the Xcode project
- Check that the build succeeded without errors
- Ensure the app was rebuilt after adding the files

### Permission Not Working
If the permission prompt doesn't appear:
- Check iOS Simulator/Device settings → App → Notifications
- Reset notification permissions in iOS Settings if needed
- Check Xcode console for NSLog messages from the plugin

### Build Errors
If you get build errors:
- Verify OneSignalFramework is properly installed via CocoaPods
- Run `pod install` in the `ios/App` directory
- Clean build folder (⌘+Shift+K) and rebuild

## Technical Details

### Plugin Architecture
```
JavaScript (onesignal-capacitor.js)
    ↓
window.Capacitor.Plugins.PushPermission
    ↓
PushPermissionPlugin.m (Objective-C bridge)
    ↓
PushPermissionPlugin.swift (Swift implementation)
    ↓
OneSignal.Notifications.requestPermission()
```

### Return Values
- `requestPermission()`: Returns `{ accepted: boolean }`
- `checkPermission()`: Returns `{ permission: boolean }`

## Files Modified/Created

### Created:
- `ios/App/App/PushPermissionPlugin.swift`
- `ios/App/App/PushPermissionPlugin.m`

### Modified:
- `ios/App/App/AppDelegate.swift`
- `public/assets/js/onesignal-capacitor.js`
- `public/modules/settings/notifications.php`
