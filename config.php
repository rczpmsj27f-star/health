<?php
/**
 * OneSignal Configuration
 * 
 * SECURITY NOTICE:
 * - ONESIGNAL_APP_ID: Safe to expose to client-side JavaScript (required for OneSignal SDK initialization)
 * - ONESIGNAL_REST_API_KEY: MUST remain server-side only (used for API calls from PHP)
 * 
 * DEPLOYMENT INSTRUCTIONS:
 * 1. Replace 'YOUR_ONESIGNAL_APP_ID_HERE' with your actual OneSignal App ID
 *    (Found in OneSignal Dashboard -> Settings -> Keys & IDs)
 * 2. Replace 'YOUR_ONESIGNAL_REST_API_KEY_HERE' with your actual REST API Key
 *    (Found in OneSignal Dashboard -> Settings -> Keys & IDs)
 * 3. For production: Consider adding config.php to .gitignore to prevent committing actual credentials
 * 
 * EXAMPLE VALUES:
 * - App ID format: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' (UUID format)
 * - REST API Key format: Long alphanumeric string (e.g., 'ZmFkZjg4...')
 */

// OneSignal App ID - Will be exposed to JavaScript for client-side SDK initialization
define('ONESIGNAL_APP_ID', 'YOUR_ONESIGNAL_APP_ID_HERE');

// OneSignal REST API Key - Server-side only, never expose to client
define('ONESIGNAL_REST_API_KEY', 'YOUR_ONESIGNAL_REST_API_KEY_HERE');

/**
 * Helper function to check if OneSignal credentials are properly configured
 * 
 * @return bool True if credentials are configured, false if still using placeholders
 */
function onesignal_is_configured() {
    return ONESIGNAL_APP_ID !== 'YOUR_ONESIGNAL_APP_ID_HERE' 
        && ONESIGNAL_REST_API_KEY !== 'YOUR_ONESIGNAL_REST_API_KEY_HERE';
}

/**
 * Helper function to validate OneSignal configuration before use
 * Useful for preventing accidental production deployments with placeholder values
 * 
 * @param bool $throw_on_error If true, throws an exception on invalid config. If false, returns validation result.
 * @return bool True if configuration is valid
 * @throws Exception If $throw_on_error is true and configuration is invalid
 */
function onesignal_validate_config($throw_on_error = false) {
    if (!onesignal_is_configured()) {
        if ($throw_on_error) {
            throw new Exception(
                'OneSignal credentials not configured. ' .
                'Please update config.php with your actual OneSignal App ID and REST API Key. ' .
                'See ONESIGNAL_CONFIG_GUIDE.md for instructions.'
            );
        }
        return false;
    }
    return true;
}
