/**
 * Splash Screen Management for iOS Capacitor App
 * Prevents black flash during page navigation by hiding splash screen after page loads
 */

// Configuration
const SPLASH_HIDE_DELAY_MS = 100; // Delay to ensure page is fully rendered before hiding splash

// Check if running in Capacitor environment
if (window.Capacitor) {
    const { SplashScreen } = window.Capacitor.Plugins;
    
    // Hide splash screen after page is fully loaded
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Wait for page to fully render to prevent black flash
            await new Promise(resolve => setTimeout(resolve, SPLASH_HIDE_DELAY_MS));
            
            // Hide splash screen smoothly
            if (SplashScreen && SplashScreen.hide) {
                await SplashScreen.hide();
                console.log('Splash screen hidden successfully');
            }
        } catch (e) {
            // Splash screen may already be hidden or not available
            console.log('SplashScreen hide skipped:', e.message || 'Already hidden');
        }
    });
}
