<?php
// =================== Environment Configuration ======================

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    
    if ($env === false) {
        error_log('Configuration warning: Failed to parse .env file. Check for syntax errors such as missing quotes, invalid characters, or incorrectly formatted lines.');
    } else {
        // Load all environment variables from .env
        foreach ($env as $key => $value) {
            // Only set if not already set in the environment
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}

// ================ End Environment Configuration =====================

// =================== OneSignal Configuration =======================

// OneSignal App ID (Safe for client-side JS - required for OneSignal SDK)
// Read from environment variable or use placeholder
$onesignalAppId = $_ENV['ONESIGNAL_APP_ID'] ?? getenv('ONESIGNAL_APP_ID');
define('ONESIGNAL_APP_ID', $onesignalAppId ?: 'YOUR_ONESIGNAL_APP_ID');

// OneSignal REST API Key (SERVER-SIDE ONLY! Never expose this to client-side JS)
// Read from environment variable or use placeholder
$onesignalApiKey = $_ENV['ONESIGNAL_REST_API_KEY'] ?? getenv('ONESIGNAL_REST_API_KEY');
define('ONESIGNAL_REST_API_KEY', $onesignalApiKey ?: 'YOUR_ONESIGNAL_REST_API_KEY');

/**
 * Helper: Check if OneSignal credentials are configured
 * @return bool
 */
function onesignal_is_configured() {
    // Check that credentials are set and not placeholder values
    $appIdSet = defined('ONESIGNAL_APP_ID') 
        && ONESIGNAL_APP_ID !== '' 
        && ONESIGNAL_APP_ID !== 'YOUR_ONESIGNAL_APP_ID';
    
    $apiKeySet = defined('ONESIGNAL_REST_API_KEY') 
        && ONESIGNAL_REST_API_KEY !== '' 
        && ONESIGNAL_REST_API_KEY !== 'YOUR_ONESIGNAL_REST_API_KEY';
    
    return $appIdSet && $apiKeySet;
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
                'Please set ONESIGNAL_APP_ID and ONESIGNAL_REST_API_KEY in your .env file or environment variables. ' .
                'See .env.example for the required format.'
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
 * 1. Set environment variable: ENABLE_DEBUG_LOGGING=true in .env, OR
 * 2. Set environment variable: DEBUG_MODE=true in .env
 */
$debugEnabled = filter_var(
    $_ENV['ENABLE_DEBUG_LOGGING'] ?? getenv('ENABLE_DEBUG_LOGGING') ?? 
    $_ENV['DEBUG_MODE'] ?? getenv('DEBUG_MODE') ?? 'false',
    FILTER_VALIDATE_BOOLEAN
);
define('ENABLE_DEBUG_LOGGING', $debugEnabled);

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
        'secure' => true,  // HTTPS requires secure cookies
        'httponly' => true,  // Prevent JavaScript access
        'samesite' => 'Lax'  // Allow same-site requests, block cross-site
    ]);
}

// ================= End Session Configuration ========================

// ... (other configuration, functions, or classes)
