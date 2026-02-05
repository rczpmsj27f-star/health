<?php
/**
 * AJAX/JSON Request Detection and Response Helpers
 * 
 * Provides utilities for detecting AJAX/fetch/JSON requests and
 * returning appropriate JSON or HTML responses based on request type.
 */

/**
 * Detect if the current request is an AJAX/JSON request
 * 
 * Detection methods (in order of precedence):
 * 1. POST parameter 'ajax' set to '1' (explicit flag)
 * 2. Accept header contains 'application/json'
 * 3. Content-Type header contains 'application/json'
 * 4. X-Requested-With header equals 'XMLHttpRequest' (traditional AJAX)
 * 
 * @return bool True if AJAX/JSON request, false if normal page request
 */
function is_ajax_request() {
    // Method 1: Explicit AJAX flag in POST data
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        return true;
    }
    
    // Method 2: Check Accept header for JSON
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($acceptHeader, 'application/json') !== false) {
        return true;
    }
    
    // Method 3: Check Content-Type for JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        return true;
    }
    
    // Method 4: Traditional XMLHttpRequest detection
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    
    return false;
}

/**
 * Return a JSON response with appropriate headers and HTTP status code
 * 
 * @param bool $success Whether the operation was successful
 * @param string $message User-friendly message
 * @param array $data Optional additional data to include in response
 * @param int $httpCode HTTP status code (default: 200 for success, 400 for failure)
 */
function json_response($success, $message, $data = [], $httpCode = null) {
    // Set Content-Type header for JSON
    header('Content-Type: application/json');
    
    // Determine HTTP status code if not provided
    if ($httpCode === null) {
        $httpCode = $success ? 200 : 400;
    }
    
    http_response_code($httpCode);
    
    // Build response array
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    // Add any additional data
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Return an authentication error response
 * Returns JSON for AJAX requests, redirects for normal page requests
 * 
 * @param string $loginUrl URL to redirect to for normal requests (default: /login.php)
 */
function auth_required_response($loginUrl = '/login.php') {
    if (is_ajax_request()) {
        json_response(false, 'Unauthorized. Please log in again.', [], 401);
    } else {
        header("Location: $loginUrl");
        exit;
    }
}
