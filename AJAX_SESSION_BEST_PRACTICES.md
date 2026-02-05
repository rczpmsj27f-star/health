# AJAX and Session Handling Best Practices

This document outlines best practices for implementing AJAX/fetch requests with proper session handling in this application. Following these patterns will prevent common issues like 302 redirects, session cookie propagation failures, and poor error handling.

## Table of Contents

1. [Quick Reference](#quick-reference)
2. [Frontend: JavaScript/Fetch Best Practices](#frontend-javascriptfetch-best-practices)
3. [Backend: PHP Handler Best Practices](#backend-php-handler-best-practices)
4. [Session Configuration](#session-configuration)
5. [Common Pitfalls](#common-pitfalls)
6. [Testing Checklist](#testing-checklist)

---

## Quick Reference

### ✅ Correct Pattern

**Frontend (JavaScript):**
```javascript
try {
    const response = await fetch('/api/endpoint.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'  // ← CRITICAL!
    });
    
    if (response.status === 401) {
        // Handle session expiry
        alert('Your session has expired. Please log in again.');
        window.location.href = '/login.php';
        return;
    }
    
    if (!response.ok) {
        // Try to get error message from JSON
        const errorData = await response.json();
        throw new Error(errorData.message || 'Request failed');
    }
    
    const data = await response.json();
    console.log('Success:', data);
    
} catch (error) {
    console.error('Error:', error);
    alert('An error occurred: ' + error.message);
}
```

**Backend (PHP):**
```php
<?php
session_start();
require_once "../../config.php";
require_once "../../app/helpers/ajax_helpers.php";
require_once "../../app/helpers/debug_helpers.php";

debug_snapshot('handler_name');

$isAjax = is_ajax_request();

if ($isAjax) {
    header('Content-Type: application/json');
}

// Check authentication
if (empty($_SESSION['user_id'])) {
    debug_log('handler_name', 'Authentication failed');
    
    if ($isAjax) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in again.'
        ]);
        exit;
    } else {
        header("Location: /login.php");
        exit;
    }
}

// Process request...
try {
    // Your business logic here
    
    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => 'Operation successful'
        ]);
    } else {
        $_SESSION['success'] = 'Operation successful';
        header("Location: /success-page.php");
    }
    exit;
    
} catch (Exception $e) {
    debug_log('handler_name', 'Error occurred', ['error' => $e->getMessage()]);
    
    http_response_code(500);
    
    if ($isAjax) {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    } else {
        $_SESSION['error'] = 'An error occurred';
        header("Location: /error-page.php");
    }
    exit;
}
```

---

## Frontend: JavaScript/Fetch Best Practices

### 1. Always Include Credentials

**Why:** Session cookies are not sent with fetch requests by default. Without `credentials: 'include'`, the server won't receive the session cookie and will treat the request as unauthenticated.

```javascript
fetch('/api/endpoint.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'  // ← Essential for session cookies
})
```

### 2. Handle All Response Statuses

Don't just check for `response.ok`. Handle specific status codes:

```javascript
if (response.status === 401) {
    // Session expired - redirect to login
    alert('Your session has expired. Please log in again.');
    window.location.href = '/login.php';
    return;
}

if (response.status === 403) {
    // Forbidden - user doesn't have permission
    alert('You do not have permission to perform this action.');
    return;
}

if (response.status === 500) {
    // Server error
    alert('A server error occurred. Please try again later.');
    return;
}

if (!response.ok) {
    // Other errors
    const errorData = await response.json();
    throw new Error(errorData.message || 'Request failed');
}
```

### 3. Parse Error Messages from Server

Always try to get the error message from the server's JSON response:

```javascript
try {
    const errorData = await response.json();
    const errorMessage = errorData.message || 'An error occurred';
    alert(errorMessage);
} catch (e) {
    // If response isn't JSON, use a generic message
    alert('An error occurred. Please try again.');
}
```

### 4. Handle Network Errors

Distinguish between server errors and network errors:

```javascript
try {
    const response = await fetch(...);
    // Process response
} catch (error) {
    if (error.message && error.message.includes('Failed to fetch')) {
        alert('Network error. Please check your internet connection.');
    } else {
        alert('An error occurred: ' + error.message);
    }
}
```

### 5. Use Helper Functions

Create reusable helper functions to reduce code duplication:

```javascript
function isSessionError(error) {
    if (error && error.message) {
        return error.message.includes('Session expired') || 
               error.message.includes('log in') ||
               error.message.includes('Unauthorized');
    }
    return false;
}

function handleSessionExpiry() {
    alert('⚠️ Your session has expired. Please log in again.');
    window.location.href = '/login.php';
}

// Usage in catch blocks:
catch (error) {
    if (isSessionError(error)) {
        handleSessionExpiry();
    } else {
        alert('Error: ' + error.message);
    }
}
```

### 6. Content-Type Considerations

**For FormData (recommended for PHP):**
```javascript
const formData = new FormData();
formData.append('key', 'value');

fetch('/api/endpoint.php', {
    method: 'POST',
    body: formData,  // Browser sets Content-Type automatically
    credentials: 'include'
})
```

**For JSON (requires server-side JSON decode):**
```javascript
fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({key: 'value'}),
    credentials: 'include'
})

// PHP side needs:
// $data = json_decode(file_get_contents('php://input'), true);
```

---

## Backend: PHP Handler Best Practices

### 1. Use AJAX Detection Helper

**Import the helper:**
```php
require_once "../../app/helpers/ajax_helpers.php";
```

**Detect AJAX requests:**
```php
$isAjax = is_ajax_request();
```

The helper detects AJAX via:
- POST parameter `ajax=1`
- Accept header contains `application/json`
- Content-Type header contains `application/json`
- X-Requested-With header equals `XMLHttpRequest`

### 2. Set Content-Type for AJAX Responses

```php
if ($isAjax) {
    header('Content-Type: application/json');
}
```

This ensures the browser correctly parses the JSON response.

### 3. Return Appropriate Responses by Request Type

```php
if (empty($_SESSION['user_id'])) {
    if ($isAjax) {
        // Return JSON for AJAX requests
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in again.'
        ]);
        exit;
    } else {
        // Redirect for normal page requests
        header("Location: /login.php");
        exit;
    }
}
```

### 4. Use Debug Logging

**Import the helper:**
```php
require_once "../../app/helpers/debug_helpers.php";
```

**Create a debug snapshot at the start of the handler:**
```php
debug_snapshot('handler_name');
```

This logs (when enabled):
- Session state (session_id, user_id, status)
- POST data (with sensitive fields redacted)
- Request headers (method, content-type, etc.)

**Log specific events:**
```php
debug_log('handler_name', 'Processing request', ['user_id' => $user_id]);
debug_log('handler_name', 'Settings updated', ['fields' => $updateFields]);
```

### 5. Provide Detailed Error Messages

```php
try {
    // Business logic
} catch (Exception $e) {
    debug_log('handler_name', 'Error occurred', [
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    
    http_response_code(500);
    
    $errorResponse = [
        'success' => false,
        'message' => 'Failed to save settings. Please try again.'
    ];
    
    // Include detailed error in debug mode
    if (is_debug_enabled()) {
        $errorResponse['debug_info'] = $e->getMessage();
    }
    
    if ($isAjax) {
        echo json_encode($errorResponse);
    } else {
        $_SESSION['error'] = $errorResponse['message'];
        header("Location: /settings.php");
    }
    exit;
}
```

### 6. Use Consistent JSON Response Format

**Success response:**
```php
echo json_encode([
    'success' => true,
    'message' => 'Operation completed successfully',
    'data' => $additionalData  // optional
]);
```

**Error response:**
```php
echo json_encode([
    'success' => false,
    'message' => 'User-friendly error message',
    'error_code' => 'ERROR_CODE',  // optional
    'debug_info' => 'Details...'   // optional, only in debug mode
]);
```

---

## Session Configuration

### Cookie Parameters

Configure in `config.php`:

```php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires on browser close)
        'path' => '/',             // Available to entire site
        'domain' => '',            // Current domain only
        'secure' => false,         // Set to true for HTTPS
        'httponly' => true,        // Prevent JavaScript access
        'samesite' => 'Lax'        // Allow same-site requests
    ]);
}
```

### Parameter Explanations

| Parameter | Value | Explanation |
|-----------|-------|-------------|
| `lifetime` | `0` | Session cookie (expires when browser closes). Use a specific time (in seconds) for persistent cookies. |
| `path` | `/` | Cookie is sent for all paths on the site. Use a specific path (e.g., `/api/`) to restrict. |
| `domain` | `''` (empty) | Cookie only for current domain. Use `.example.com` to include subdomains. |
| `secure` | `false` | Cookie sent over HTTP and HTTPS. Set to `true` for HTTPS-only (required in production). |
| `httponly` | `true` | Cookie cannot be accessed via JavaScript (security). Keep `true` to prevent XSS attacks. |
| `samesite` | `Lax` | Cookie sent for same-site requests. Use `None` (with `secure=true`) for cross-site. |

### Cross-Subdomain Configuration

To share sessions across subdomains (e.g., `www.example.com` and `api.example.com`):

```php
session_set_cookie_params([
    'domain' => '.example.com',  // Note the leading dot
    'secure' => true,            // Required for cross-subdomain
    'samesite' => 'None'         // Required for cross-site
]);
```

**Requirements:**
- Both subdomains must use HTTPS
- Leading dot in domain is important
- `samesite` must be `None` with `secure=true`

---

## Common Pitfalls

### ❌ Pitfall 1: Forgetting `credentials: 'include'`

**Problem:**
```javascript
fetch('/api/endpoint.php', {
    method: 'POST',
    body: formData
    // Missing credentials!
})
```

**Symptom:** 401 errors even when logged in

**Fix:** Always include `credentials: 'include'`

### ❌ Pitfall 2: Not Handling AJAX Detection

**Problem:**
```php
// Always redirects, even for AJAX
if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
```

**Symptom:** Getting HTML redirects instead of JSON in Network tab

**Fix:** Use `is_ajax_request()` and return JSON for AJAX

### ❌ Pitfall 3: Not Setting Content-Type Header

**Problem:**
```php
// No Content-Type header
echo json_encode(['success' => true]);
```

**Symptom:** Browser may misinterpret response

**Fix:** Set `header('Content-Type: application/json');`

### ❌ Pitfall 4: Only Checking `response.ok`

**Problem:**
```javascript
if (response.ok) {
    // Success
} else {
    // Generic error
    alert('Error!');
}
```

**Symptom:** User doesn't know why request failed

**Fix:** Check specific status codes and parse error messages

### ❌ Pitfall 5: Logging Sensitive Data

**Problem:**
```php
error_log('POST data: ' . json_encode($_POST));  // May contain passwords!
```

**Symptom:** Sensitive data in logs

**Fix:** Use `debug_log()` which automatically redacts sensitive fields

### ❌ Pitfall 6: Inconsistent Error Responses

**Problem:**
```php
// Sometimes returns string, sometimes JSON, sometimes HTML
echo "Error occurred";
// or
echo json_encode(['error' => 'message']);
// or
echo "<html>Error</html>";
```

**Symptom:** Frontend can't reliably parse errors

**Fix:** Always use consistent JSON format for AJAX requests

---

## Testing Checklist

Before deploying any new AJAX handler, verify:

### Frontend Tests
- [ ] All fetch calls include `credentials: 'include'`
- [ ] 401 status is handled (redirects to login)
- [ ] Error messages are parsed from JSON response
- [ ] Network errors are caught and handled
- [ ] Success messages are displayed to user
- [ ] Loading states are shown during request
- [ ] No JavaScript errors in console

### Backend Tests
- [ ] Uses `is_ajax_request()` for detection
- [ ] Sets `Content-Type: application/json` for AJAX responses
- [ ] Returns JSON for AJAX, redirects for normal requests
- [ ] Returns 401 for unauthorized (not 302 redirect for AJAX)
- [ ] Includes `debug_snapshot()` at start
- [ ] Uses `debug_log()` for important events
- [ ] Error messages are user-friendly
- [ ] Sensitive data is not logged
- [ ] Consistent JSON response format

### Session Tests
- [ ] Session cookie is sent with request (check Network tab)
- [ ] Session cookie has correct Path, Domain, SameSite
- [ ] Works across page refreshes
- [ ] Works after session timeout (shows error, redirects to login)
- [ ] Multiple requests in sequence work correctly

### Integration Tests
- [ ] Test with valid session: Success
- [ ] Test with missing session: Clear error + redirect
- [ ] Test with expired session: Clear error + redirect
- [ ] Test with network offline: Network error message
- [ ] Test with server error (500): Clear error message
- [ ] Test rapid requests (no race conditions)

---

## Example: Complete Implementation

See `public/modules/settings/save_notifications_handler.php` and `public/modules/settings/notifications.php` for a complete reference implementation that follows all these best practices.

**Key files:**
- `app/helpers/ajax_helpers.php` - AJAX detection and response helpers
- `app/helpers/debug_helpers.php` - Debug logging helpers
- `config.php` - Session configuration
- `NOTIFICATION_TROUBLESHOOTING.md` - Troubleshooting guide

---

## Summary

**Golden Rules:**
1. **Always** include `credentials: 'include'` in fetch
2. **Always** use `is_ajax_request()` in PHP handlers
3. **Always** set `Content-Type: application/json` for AJAX responses
4. **Always** return JSON for AJAX, redirects for normal requests
5. **Always** handle 401 status (session expired)
6. **Always** provide user-friendly error messages
7. **Always** use debug logging for troubleshooting
8. **Never** log sensitive data
9. **Never** return HTML for AJAX requests
10. **Never** forget to test with expired session

Following these patterns will result in robust, maintainable, and user-friendly AJAX implementations!
