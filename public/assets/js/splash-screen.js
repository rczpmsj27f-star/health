/**
 * Splash Screen Management for iOS Capacitor App
 * Prevents black flash during page navigation by hiding splash screen after page loads
 */

// Only initialize splash screen once per app session
if (!window.SPLASH_SCREEN_INITIALIZED) {
    window.SPLASH_SCREEN_INITIALIZED = true;
    
    // Check if running in Capacitor environment
    if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.SplashScreen) {
        const { SplashScreen } = window.Capacitor.Plugins;
        
        document.addEventListener('DOMContentLoaded', async () => {
            // Wait 500ms to ensure page is fully rendered before hiding splash
            // Increased from 100ms to be more reliable across different devices and page complexities
            await new Promise(resolve => setTimeout(resolve, 500));
            try {
                await SplashScreen.hide();
                console.log('Splash screen hidden successfully');
            } catch (e) {
                // Splash screen may already be hidden or error occurred
                console.log('SplashScreen hide skipped:', e?.message || 'Already hidden or unavailable');
            }
        });
    }
}
