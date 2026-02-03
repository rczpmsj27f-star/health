<?php

/**
 * Validation Helper Functions
 * Provides validation utilities for user input
 */

/**
 * Validate email address
 * 
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * 
 * @param string $password The password to validate
 * @param int $min_length Minimum password length
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_password($password, $min_length = 8) {
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least {$min_length} characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate username
 * 
 * @param string $username The username to validate
 * @return bool True if valid, false otherwise
 */
function validate_username($username) {
    // Username must be 3-20 characters, alphanumeric with underscores
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

/**
 * Validate phone number
 * 
 * @param string $phone The phone number to validate
 * @return bool True if valid, false otherwise
 */
function validate_phone($phone) {
    // Basic phone validation - can be enhanced based on requirements
    return preg_match('/^[0-9\s\-\+\(\)]{10,20}$/', $phone) === 1;
}

/**
 * Validate date format
 * 
 * @param string $date The date to validate
 * @param string $format The expected format (default: Y-m-d)
 * @return bool True if valid, false otherwise
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
