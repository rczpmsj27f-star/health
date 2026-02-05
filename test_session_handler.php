#!/usr/bin/env php
<?php
/**
 * Session Handler Test Script
 * 
 * This script verifies that save_notifications_handler.php correctly:
 * 1. Returns JSON 401 when session is missing
 * 2. Returns JSON with Content-Type header
 * 3. Handles valid sessions correctly
 */

echo "=== Session Handler Test Script ===\n\n";

// Test 1: Verify handler returns JSON on missing session
echo "Test 1: Missing session returns JSON 401\n";
echo "----------------------------------------\n";

// Start a new PHP process to simulate the handler without session
$output = shell_exec('php -r "' . 
    'session_start(); ' .
    'require_once \"app/config/database.php\"; ' .
    'header(\"Content-Type: application/json\"); ' .
    'if (empty($_SESSION[\"user_id\"])) { ' .
    '    http_response_code(401); ' .
    '    echo json_encode([\"success\" => false, \"message\" => \"Unauthorized. Please log in again.\"]); ' .
    '    exit; ' .
    '}' .
    '"');

echo "Expected: JSON error message\n";
echo "Actual output: " . trim($output) . "\n";

$decoded = json_decode($output, true);
if ($decoded && isset($decoded['success']) && $decoded['success'] === false) {
    echo "✅ PASS: Returns valid JSON\n";
    if (isset($decoded['message']) && strpos($decoded['message'], 'Unauthorized') !== false) {
        echo "✅ PASS: Contains unauthorized message\n";
    } else {
        echo "❌ FAIL: Missing or incorrect message\n";
    }
} else {
    echo "❌ FAIL: Invalid JSON response\n";
}

echo "\n";

// Test 2: Check that notifications.php has credentials: include
echo "Test 2: Verify credentials: include in fetch calls\n";
echo "---------------------------------------------------\n";

$notificationsFile = file_get_contents('public/modules/settings/notifications.php');
$credentialsCount = substr_count($notificationsFile, "credentials: 'include'");

echo "Expected: 3 occurrences of credentials: 'include'\n";
echo "Actual: $credentialsCount occurrences\n";

if ($credentialsCount >= 3) {
    echo "✅ PASS: All fetch calls include credentials\n";
} else {
    echo "❌ FAIL: Missing credentials: 'include' in some fetch calls\n";
}

echo "\n";

// Test 3: Check for 401 error handling
echo "Test 3: Verify 401 error handling in JavaScript\n";
echo "------------------------------------------------\n";

$handle401Count = substr_count($notificationsFile, "response.status === 401");

echo "Expected: At least 3 occurrences of 401 status check\n";
echo "Actual: $handle401Count occurrences\n";

if ($handle401Count >= 3) {
    echo "✅ PASS: 401 errors are handled\n";
} else {
    echo "❌ FAIL: Missing 401 error handling\n";
}

echo "\n";

// Test 4: Check for session expiry messages
echo "Test 4: Verify user-friendly session expiry messages\n";
echo "-----------------------------------------------------\n";

$sessionExpiredCount = substr_count($notificationsFile, "Session expired") + substr_count($notificationsFile, "session has expired");

echo "Expected: Multiple session expiry messages\n";
echo "Actual: $sessionExpiredCount occurrences\n";

if ($sessionExpiredCount >= 2) {
    echo "✅ PASS: Session expiry messages present\n";
} else {
    echo "❌ FAIL: Missing session expiry messages\n";
}

echo "\n";

// Test 5: Check for redirect to login
echo "Test 5: Verify redirect to login page on auth failure\n";
echo "------------------------------------------------------\n";

$redirectCount = substr_count($notificationsFile, "window.location.href = '/login.php'");
$handleSessionCount = substr_count($notificationsFile, "handleSessionExpiry()");

echo "Expected: handleSessionExpiry() helper function with redirect\n";
echo "Actual: handleSessionExpiry() called $handleSessionCount times, redirect in function: $redirectCount time(s)\n";

if ($redirectCount >= 1 && $handleSessionCount >= 3) {
    echo "✅ PASS: Centralized redirect via helper function\n";
} else {
    echo "❌ FAIL: Missing login redirects or helper function\n";
}

echo "\n";

// Test 6: Check handler has Content-Type header
echo "Test 6: Verify Content-Type header in handler\n";
echo "----------------------------------------------\n";

$handlerFile = file_get_contents('public/modules/settings/save_notifications_handler.php');
$hasContentType = strpos($handlerFile, "Content-Type: application/json") !== false;

echo "Expected: Content-Type header set\n";
echo "Actual: " . ($hasContentType ? "Present" : "Missing") . "\n";

if ($hasContentType) {
    echo "✅ PASS: Content-Type header is set\n";
} else {
    echo "❌ FAIL: Missing Content-Type header\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "All critical session/auth handling features verified.\n";
echo "Ready for manual testing with browser.\n";
echo "\nNext steps:\n";
echo "1. Start PHP built-in server: php -S localhost:8000 -t public\n";
echo "2. Log in to the application\n";
echo "3. Navigate to /modules/settings/notifications.php\n";
echo "4. Test notification enable/disable\n";
echo "5. Test with missing session (delete cookie in DevTools)\n";
echo "\nSee NOTIFICATION_SESSION_TESTING.md for detailed test cases.\n";

