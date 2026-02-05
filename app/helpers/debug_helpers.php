<?php
/**
 * Debug and Logging Helpers
 * 
 * Provides utilities for debugging and logging that respect
 * environment settings and avoid logging sensitive data.
 */

// Maximum length for logged values before truncation
define('MAX_LOG_VALUE_LENGTH', 200);

/**
 * Check if debug mode is enabled
 * 
 * @return bool True if debugging is enabled
 */
function is_debug_enabled() {
    // Check environment variable
    if (getenv('DEBUG_MODE') === 'true') {
        return true;
    }
    
    // Check PHP constant
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        return true;
    }
    
    // Check config file setting
    if (defined('ENABLE_DEBUG_LOGGING') && ENABLE_DEBUG_LOGGING === true) {
        return true;
    }
    
    return false;
}

/**
 * Log debug information to error log
 * Only logs if debug mode is enabled
 * 
 * @param string $context Context/location of the log (e.g., 'save_notifications_handler')
 * @param string $message Log message
 * @param array $data Optional data to include (will be sanitized)
 */
function debug_log($context, $message, $data = []) {
    if (!is_debug_enabled()) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$context] $message";
    
    // Add data if provided (sanitize sensitive fields)
    if (!empty($data)) {
        $sanitizedData = sanitize_log_data($data);
        $logMessage .= " | Data: " . json_encode($sanitizedData);
    }
    
    error_log($logMessage);
}

/**
 * Log session information for debugging
 * 
 * @param string $context Context/location of the log
 */
function debug_log_session($context) {
    if (!is_debug_enabled()) {
        return;
    }
    
    $sessionData = [
        'session_id' => session_id(),
        'has_user_id' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? 'NOT_SET',
        'session_status' => session_status(),
    ];
    
    debug_log($context, 'Session state', $sessionData);
}

/**
 * Log POST request data for debugging
 * 
 * @param string $context Context/location of the log
 */
function debug_log_post($context) {
    if (!is_debug_enabled()) {
        return;
    }
    
    $postData = $_POST;
    
    // Sanitize before logging
    $sanitizedPost = sanitize_log_data($postData);
    
    debug_log($context, 'POST data', $sanitizedPost);
}

/**
 * Log request headers for debugging
 * 
 * @param string $context Context/location of the log
 */
function debug_log_headers($context) {
    if (!is_debug_enabled()) {
        return;
    }
    
    $headers = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET',
        'accept' => $_SERVER['HTTP_ACCEPT'] ?? 'NOT_SET',
        'x_requested_with' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NOT_SET',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'NOT_SET',
    ];
    
    debug_log($context, 'Request headers', $headers);
}

/**
 * Sanitize data before logging to avoid exposing sensitive information
 * 
 * @param array $data Data to sanitize
 * @return array Sanitized data
 */
function sanitize_log_data($data) {
    $sensitiveKeys = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'auth',
        'authorization',
        'session_id',
        'csrf_token',
    ];
    
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        // Check if key contains sensitive information
        $isSensitive = false;
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (stripos($key, $sensitiveKey) !== false) {
                $isSensitive = true;
                break;
            }
        }
        
        if ($isSensitive) {
            $sanitized[$key] = '***REDACTED***';
        } else {
            // If value is array, recursively sanitize
            if (is_array($value)) {
                $sanitized[$key] = sanitize_log_data($value);
            } else {
                // Truncate very long values
                $sanitized[$key] = is_string($value) && strlen($value) > MAX_LOG_VALUE_LENGTH 
                    ? substr($value, 0, MAX_LOG_VALUE_LENGTH) . '...[truncated]' 
                    : $value;
            }
        }
    }
    
    return $sanitized;
}

/**
 * Create a comprehensive debug snapshot for troubleshooting
 * 
 * @param string $context Context/location of the snapshot
 */
function debug_snapshot($context) {
    if (!is_debug_enabled()) {
        return;
    }
    
    debug_log($context, '=== DEBUG SNAPSHOT START ===');
    debug_log_session($context);
    debug_log_post($context);
    debug_log_headers($context);
    debug_log($context, '=== DEBUG SNAPSHOT END ===');
}
