<?php

/**
 * Security Helper Functions
 * Provides security-related utility functions for the application
 */

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $data The input data to sanitize
 * @return string The sanitized data
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure CSRF token
 * 
 * @return string The CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hash a password securely
 * 
 * @param string $password The password to hash
 * @return string The hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash
 * 
 * @param string $password The password to verify
 * @param string $hash The hash to verify against
 * @return bool True if password matches, false otherwise
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
