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
            await new Promise(resolve => setTimeout(resolve, 500));
            try {
                await SplashScreen.hide();
            } catch (e) {
                console.log('SplashScreen already hidden or error:', e.message);
            }
        });
    }
}
