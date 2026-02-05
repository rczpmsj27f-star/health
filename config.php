<?php
// ... (other configuration and PHP opening tag)

// =================== OneSignal Configuration =======================

// OneSignal App ID (Safe for client-side JS - required for OneSignal SDK)
define('ONESIGNAL_APP_ID', '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b');

// OneSignal REST API Key (SERVER-SIDE ONLY! Never expose this to client-side JS)
define('ONESIGNAL_REST_API_KEY', 'yos_v2_app_e74njuz2nffe3d33ce6rm5r4jobuamioediusjfadwrwiwi53chrv6zoomac3yfthlsb5ws6e4tjhpytgvqzvv5gir44qxfiznor6pi');

/**
 * Helper: Check if OneSignal credentials are configured
 * @return bool
 */
function onesignal_is_configured() {
    return ONESIGNAL_APP_ID !== '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b'
        && ONESIGNAL_REST_API_KEY !== 'os_v2_app_e74njuz2nffe3d33ce6rm5r4jobuamioediusjfadwrwiwi53chrv6zoomac3yfthlsb5ws6e4tjhpytgvqzvv5gir44qxfiznor6pi';
}

/**
 * Helper: Validate OneSignal configuration
 * @param bool $throw_on_error
 * @return bool
 * @throws Exception
 */
function onesignal_validate_config($throw_on_error = false) {
    if (!onesignal_is_configured()) {
        if ($throw_on_error) {
            throw new Exception(
                'OneSignal credentials not configured. ' .
                'Please update config.php with your actual OneSignal App ID and REST API Key. '
            );
        }
        return false;
    }
    return true;
}

// ================= End OneSignal Configuration =====================

// =================== Debug/Logging Configuration ===================

/**
 * Enable debug logging for troubleshooting
 * 
 * When enabled, debug information is logged to PHP error log including:
 * - Session state information
 * - POST request payloads (with sensitive data redacted)
 * - Request headers and metadata
 * 
 * SECURITY NOTE: Only enable in development/staging environments.
 * Disable in production to avoid performance impact and log bloat.
 * 
 * To enable:
 * 1. Set this constant to true, OR
 * 2. Set environment variable: DEBUG_MODE=true
 */
define('ENABLE_DEBUG_LOGGING', false);

// ================= End Debug/Logging Configuration =================

// =================== Session Configuration ==========================

/**
 * Session Cookie Configuration
 * 
 * These settings control how PHP session cookies are handled.
 * Critical for AJAX requests and cross-subdomain functionality.
 * 
 * Current configuration:
 * - SameSite: Lax (allows cookies on same-site AJAX, blocks cross-site)
 * - Secure: false (set to true in production with HTTPS)
 * - HttpOnly: true (prevents JavaScript access for security)
 * - Path: / (cookie available to entire site)
 * - Domain: empty (restricts to exact domain, no subdomains)
 * 
 * For cross-subdomain support (e.g., api.example.com and www.example.com):
 * - Set domain to '.example.com' (note the leading dot)
 * - Both subdomains must use HTTPS if Secure flag is true
 * 
 * For local development:
 * - Secure should be false (unless using HTTPS locally)
 * - SameSite 'Lax' or 'None' (None requires Secure=true)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,  // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',   // Current domain only (no subdomains)
        'secure' => false,  // Set to true in production with HTTPS
        'httponly' => true,  // Prevent JavaScript access
        'samesite' => 'Lax'  // Allow same-site requests, block cross-site
    ]);
}

// ================= End Session Configuration ========================

// ... (other configuration, functions, or classes)
