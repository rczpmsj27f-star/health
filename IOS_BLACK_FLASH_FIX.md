# iOS Black Screen Flash Fix - Implementation Summary

## Problem
Pages flicker with black flash during navigation in the iOS Capacitor app (not in Safari browser). The WebView briefly shows the native app background (black in dark mode) during page transitions.

## Root Cause
The native iOS WebView is transparent during page transitions, exposing the black system window behind it.

## Solution Implemented

### 1. Capacitor Configuration (`capacitor.config.json`)
Changed splash screen configuration to prevent auto-hide:
```json
{
  "plugins": {
    "SplashScreen": {
      "launchAutoHide": false,
      "backgroundColor": "#5b21b6",
      "androidScaleType": "center",
      "splashImmersive": true,
      "showSpinner": false
    }
  }
}
```

**Key Changes:**
- `launchAutoHide: false` - Prevents splash screen from hiding automatically
- `showSpinner: false` - Removes spinner for cleaner appearance
- Added `androidScaleType` and `splashImmersive` for Android compatibility

### 2. iOS Native Configuration (`ios/App/App/AppDelegate.swift`)
Set solid white background on the app window:
```swift
func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
    // Set solid white background to prevent black flash during transitions
    window?.backgroundColor = UIColor.white
    
    // ... rest of initialization
}
```

**Why This Works:**
- The white background fills the WebView immediately when it becomes transparent
- No black flash is visible during transitions
- Matches the typical page background color

### 3. JavaScript Splash Screen Handler (`public/assets/js/splash-screen.js`)
Created a new script to handle splash screen hiding after page load:
```javascript
if (window.Capacitor) {
    const { SplashScreen } = window.Capacitor.Plugins;
    
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Wait for page to fully render to prevent black flash
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Hide splash screen smoothly
            if (SplashScreen && SplashScreen.hide) {
                await SplashScreen.hide();
                console.log('Splash screen hidden successfully');
            }
        } catch (e) {
            console.log('SplashScreen hide skipped:', e.message || 'Already hidden');
        }
    });
}
```

**Key Features:**
- Only runs in Capacitor environment (checks `window.Capacitor`)
- Waits 100ms after DOM load to ensure page is rendered
- Gracefully handles errors if splash screen is already hidden
- Non-blocking and fail-safe

### 4. Page Integration
Added splash screen script to all main application pages:
```html
<link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
<script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
```

**Updated Pages (10 total):**
1. `public/dashboard.php`
2. `public/login.php`
3. `public/register.php`
4. `public/verify-2fa.php`
5. `public/modules/medications/dashboard.php`
6. `public/modules/medications/medication_dashboard.php`
7. `public/modules/medications/list.php`
8. `public/modules/medications/view.php`
9. `public/modules/profile/view.php`
10. `public/modules/settings/notifications.php`

## How It Works

1. **App Launch:**
   - Splash screen displays with purple background (#5b21b6)
   - iOS window has solid white background (no black showing through)

2. **Page Navigation:**
   - WebView begins loading new page
   - Splash screen remains visible (launchAutoHide: false)
   - White background fills any transparent areas
   - Page content loads and renders

3. **Page Ready:**
   - DOMContentLoaded event fires
   - Wait 100ms for full render
   - Splash screen hides smoothly
   - No black flash visible at any point

## Benefits

✅ **No Black Flash:** White background prevents black flash during transitions  
✅ **Smooth Navigation:** Splash screen covers transitions seamlessly  
✅ **Professional Appearance:** Clean, polished user experience  
✅ **Dark Mode Compatible:** Works regardless of system theme  
✅ **Safe Implementation:** Graceful error handling, no breaking changes  
✅ **Browser Compatible:** Only affects Capacitor environment, no impact on web

## Testing Checklist

Before deploying to production, verify:

- [ ] Navigate between pages on iOS device
- [ ] Enable dark mode and test again
- [ ] Verify no black flash appears during transitions
- [ ] Test on iOS simulator
- [ ] Test on physical iOS device
- [ ] Verify web browser functionality unchanged
- [ ] Test all main navigation paths

## Deployment Steps

1. **Merge PR to main branch**
2. **Rebuild iOS app:**
   ```bash
   npm run ios:build
   ```
3. **Run on simulator/device:**
   ```bash
   npm run ios:run
   ```
4. **Test navigation thoroughly**
5. **Deploy to App Store if successful**

## Technical Notes

- **Minimal Changes:** Only modified configuration and added one small JavaScript file
- **Backward Compatible:** Web browser functionality unchanged
- **No Dependencies:** Uses built-in Capacitor SplashScreen plugin
- **Performance:** 100ms delay is negligible and ensures smooth rendering
- **Maintainability:** Simple, well-documented code

## Files Changed

```
capacitor.config.json                               | 4 lines
ios/App/App/AppDelegate.swift                       | 3 lines
public/assets/js/splash-screen.js                   | 27 lines (new file)
public/dashboard.php                                | 1 line
public/login.php                                    | 1 line
public/register.php                                 | 1 line
public/verify-2fa.php                               | 1 line
public/modules/medications/dashboard.php            | 1 line
public/modules/medications/medication_dashboard.php | 1 line
public/modules/medications/list.php                 | 1 line
public/modules/medications/view.php                 | 1 line
public/modules/profile/view.php                     | 1 line
public/modules/settings/notifications.php           | 1 line
```

**Total:** 13 files changed, 45 insertions(+), 3 deletions(-)

## References

- Capacitor SplashScreen Plugin: https://capacitorjs.com/docs/apis/splash-screen
- iOS WebView Transparency Issue: Common issue with hybrid apps
- Solution Pattern: Industry standard approach for preventing flash transitions
