# Cache-Buster Implementation Guidelines

## Overview
This document explains the correct usage of `cache-buster.php` in the Health Tracker application.

## Rules

### ✅ DO Include cache-buster.php in:
- **Page files** (files that display HTML content via GET requests)
- Examples:
  - `login.php`
  - `dashboard.php` 
  - `verify-2fa.php`
  - All pages in `modules/` directories

### ❌ DO NOT Include cache-buster.php in:
- **Handler files** (files that process POST requests)
- Files ending in `_handler.php`
- Admin action files: `delete_user.php`, `toggle_*.php`, `force_reset.php`
- Any file that primarily redirects after processing form data

## Why This Pattern?

### Cache-buster.php Behavior:
- Only applies cache-busting headers to **GET requests**
- Allows POST requests to pass through normally
- Always applies security headers (X-Frame-Options, etc.)

### Handler Files Don't Need It Because:
1. **No caching needed**: Handlers process POST data and redirect immediately
2. **Cleaner code**: Removing unnecessary includes improves clarity
3. **Performance**: One less file to load per request
4. **Maintenance**: Prevents confusion about why cache-buster is in a POST handler

## Correct Implementation Pattern

### Page File (GET request - displays content):
```php
<?php
require_once __DIR__ . '/../app/includes/cache-buster.php';
require_once __DIR__ . '/../app/config/database.php';

// Display page content...
?>
<!DOCTYPE html>
...
```

### Handler File (POST request - processes and redirects):
```php
<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

// Process POST data
// ...

// Redirect
header("Location: /some-page.php");
exit;
```

## Common Mistakes to Avoid

### ❌ WRONG: Including cache-buster in handler
```php
<?php
require_once __DIR__ . '/../app/includes/cache-buster.php';  // DON'T DO THIS
session_start();
// ... handler code
```

### ✅ CORRECT: Handler without cache-buster
```php
<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
// ... handler code
```

## Deployment Requirements

**IMPORTANT**: The application requires composer dependencies to be installed:

```bash
composer install --no-dev --optimize-autoloader
```

Required packages:
- `phpmailer/phpmailer` - Email functionality
- `pragmarx/google2fa` - Two-factor authentication
- `bacon/bacon-qr-code` - QR code generation for 2FA
- `tecnickcom/tcpdf` - PDF generation

**Without these dependencies, the login flow will fail when 2FA is enabled!**

## Troubleshooting

### Issue: "Login hangs at 2FA page"
**Cause**: Missing vendor dependencies  
**Solution**: Run `composer install` on the server

### Issue: "Headers already sent" error in handler
**Cause**: Output before header() call or cache-buster incorrectly included  
**Solution**: Remove cache-buster from handler files, check for whitespace/BOM before `<?php`

### Issue: "Page content not refreshing"
**Cause**: Page file missing cache-buster include  
**Solution**: Add `require_once __DIR__ . '/../app/includes/cache-buster.php';` as first line after `<?php`

## Verification Script

Run this to verify correct cache-buster usage:

```bash
# Find any handler files with cache-buster (should return nothing)
find public -type f \( -name "*_handler.php" -o -name "delete_user.php" -o -name "toggle_*.php" -o -name "force_reset.php" \) -exec grep -l "cache-buster" {} \;

# Count page files with cache-buster (should be 30+)
find public -type f -name "*.php" -exec grep -l "cache-buster" {} \; | wc -l
```

## Summary

- **Page files (GET)**: INCLUDE cache-buster
- **Handler files (POST)**: EXCLUDE cache-buster  
- **Always run**: `composer install` on deployment
- **Test**: Login flow with 2FA enabled user

Following these guidelines ensures:
✅ Pages always show fresh content  
✅ Forms submit and redirect correctly  
✅ Login and 2FA work properly  
✅ No "headers already sent" errors
