# Deployment and Server Configuration Guide

## Issue: 404 Error When Accessing the Application

### Problem
When accessing `ht.ianconroy.co.uk/public/index.php`, a 404 error occurs because the web server's document root is pointing to the repository root instead of the `public/` directory.

### Solution
Two `.htaccess` files have been added to properly route requests:

## Files Added

### 1. Root `.htaccess` (Repository Root)
**Location:** `/.htaccess`

**Purpose:** Redirects all incoming requests to the `public/` directory

**How it works:**
- Any request to `ht.ianconroy.co.uk/index.php` → internally routes to `public/index.php`
- Any request to `ht.ianconroy.co.uk/login.php` → internally routes to `public/login.php`
- Requests already containing `/public/` are not double-redirected

### 2. Public `.htaccess` (Public Directory)
**Location:** `/public/.htaccess`

**Purpose:** Handles clean URLs and front controller pattern

**Features:**
- Removes trailing slashes from URLs
- Routes non-existent files to `index.php` (front controller pattern)
- Preserves authorization headers
- Allows direct access to existing files (CSS, JS, images)

## Server Requirements

### Apache Configuration
The server must have the following Apache modules enabled:
- `mod_rewrite` (URL rewriting)
- `mod_env` (environment variables)

To enable these modules on Ubuntu/Debian:
```bash
sudo a2enmod rewrite
sudo a2enmod env
sudo systemctl restart apache2
```

### Document Root Configuration

#### Option 1: Keep Repository Root as Document Root (Recommended with .htaccess)
This is the current setup. The `.htaccess` files handle routing automatically.

**Apache VirtualHost Example:**
```apache
<VirtualHost *:80>
    ServerName ht.ianconroy.co.uk
    DocumentRoot /path/to/health
    
    <Directory /path/to/health>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Option 2: Set Public Directory as Document Root (Alternative)
If you prefer, you can configure the web server to use `public/` as the document root directly.

**Apache VirtualHost Example:**
```apache
<VirtualHost *:80>
    ServerName ht.ianconroy.co.uk
    DocumentRoot /path/to/health/public
    
    <Directory /path/to/health/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Note:** If using Option 2, the root `.htaccess` is not needed, but the `public/.htaccess` is still required.

## URL Access Patterns

With the `.htaccess` files in place, the following URLs all work correctly:

| URL | Routes To | Status |
|-----|-----------|--------|
| `ht.ianconroy.co.uk/` | `public/index.php` | ✅ Works |
| `ht.ianconroy.co.uk/index.php` | `public/index.php` | ✅ Works |
| `ht.ianconroy.co.uk/public/index.php` | `public/index.php` | ✅ Works |
| `ht.ianconroy.co.uk/login.php` | `public/login.php` | ✅ Works |
| `ht.ianconroy.co.uk/dashboard.php` | `public/dashboard.php` | ✅ Works |
| `ht.ianconroy.co.uk/assets/css/app.css` | `public/assets/css/app.css` | ✅ Works |

## Testing the Configuration

### 1. Test Basic Access
```bash
curl -I http://ht.ianconroy.co.uk/
curl -I http://ht.ianconroy.co.uk/index.php
curl -I http://ht.ianconroy.co.uk/login.php
```

All should return `HTTP/1.1 200 OK` or `HTTP/1.1 302 Found` (redirects).

### 2. Test Asset Loading
```bash
curl -I http://ht.ianconroy.co.uk/assets/css/app.css
```

Should return `HTTP/1.1 200 OK` with `Content-Type: text/css`.

### 3. Test 404 Handling
```bash
curl -I http://ht.ianconroy.co.uk/nonexistent.php
```

Should return `HTTP/1.1 404 Not Found` or route to `index.php`.

## Troubleshooting

### Issue: Still Getting 404 Errors
**Solution:** Ensure `AllowOverride All` is set in your Apache configuration. Without this, `.htaccess` files are ignored.

### Issue: Internal Server Error (500)
**Solution:** Check that `mod_rewrite` is enabled:
```bash
apache2ctl -M | grep rewrite
```

### Issue: Assets (CSS/JS) Not Loading
**Solution:** Verify that the paths in your PHP files use absolute paths starting with `/assets/` (e.g., `/assets/css/app.css`).

## File Structure

```
health/
├── .htaccess                  # Routes requests to public/
├── app/                       # Application backend code
├── public/                    # Web-accessible files
│   ├── .htaccess             # Clean URLs & front controller
│   ├── index.php             # Entry point
│   ├── login.php
│   ├── dashboard.php
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── modules/
└── uploads/                   # File uploads
```

## Security Notes

- The `.htaccess` files ensure that files outside the `public/` directory (like database config, helper files) are never directly accessible via web browser
- All user uploads should be in the `uploads/` directory, which is outside the public directory for security
- Database credentials in `app/config/database.php` are protected and cannot be accessed directly
