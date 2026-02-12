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
            // This delay is specified in the requirements to balance two concerns:
            // 1. Prevent black flash during initial render (white background stays visible)
            // 2. Ensure all critical elements are rendered before showing content
            // Trade-off: Slightly longer initial load feel vs. reliable visual consistency
            await new Promise(resolve => setTimeout(resolve, 500));
            try {
                await SplashScreen.hide();
                console.log('Splash screen hidden successfully');
            } catch (e) {
                // Error hiding splash screen - may already be hidden or plugin unavailable
                console.log('SplashScreen hide error:', e?.message || e);
            }
        });
    }
}
