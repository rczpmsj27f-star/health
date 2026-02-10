# Deployment and Server Configuration Guide

## Environment Configuration

### Database Setup

Before deploying the application, you must configure database credentials using environment variables.

#### Step 1: Create Environment File

Copy the example environment file to create your local configuration:

```bash
cp .env.example .env
```

#### Step 2: Configure Database Credentials

Edit `.env` and set your actual database credentials:

```env
DB_HOST=localhost
DB_USER=your_database_user
DB_PASS=your_secure_password
DB_NAME=your_database_name
```

#### Step 3: Secure the Environment File

Ensure `.env` has restricted permissions (Unix/Linux):

```bash
chmod 600 .env
```

**Security Notes:**
- Never commit `.env` to version control (already in `.gitignore`)
- Use different credentials for development and production
- Rotate passwords regularly
- Use strong, unique passwords for production databases
- The exposed password `Bananas9082!` in git history must be changed immediately

### Environment Variables (Alternative)

Instead of using a `.env` file, you can set environment variables directly in your web server configuration:

**Apache (.htaccess or VirtualHost):**
```apache
SetEnv DB_HOST "localhost"
SetEnv DB_USER "your_database_user"
SetEnv DB_PASS "your_secure_password"
SetEnv DB_NAME "your_database_name"
```

**Nginx (with PHP-FPM):**
```nginx
location ~ \.php$ {
    fastcgi_param DB_HOST "localhost";
    fastcgi_param DB_USER "your_database_user";
    fastcgi_param DB_PASS "your_secure_password";
    fastcgi_param DB_NAME "your_database_name";
}
```

**Docker:**
```bash
docker run -e DB_HOST=localhost -e DB_USER=user -e DB_PASS=pass -e DB_NAME=dbname ...
```

## PHP Dependencies (Composer)

This project uses Composer to manage PHP dependencies, primarily PHPMailer for email functionality.

### Installing Dependencies

Before deploying or after pulling new changes, you must install PHP dependencies:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# For development (includes dev dependencies)
composer install
```

**Important**: The `vendor/` directory is not tracked in git. You must run `composer install` on every deployment or environment setup.

### Required Dependencies

- **PHPMailer** (phpmailer/phpmailer ^6.9) - Used for sending registration verification emails and password reset emails

### Troubleshooting

If you encounter errors like:
```
Warning: require(vendor/autoload.php): Failed to open stream: No such file or directory
```

This means Composer dependencies are not installed. Run `composer install` to fix.

## Database Migrations

This project includes SQL migration files in the `database/migrations/` directory for managing database schema changes.

### Applying Migrations

Before deploying or after pulling new changes, check if there are new migration files to apply:

```bash
# List available migrations
ls -la database/migrations/

# Apply a specific migration
mysql -u username -p database_name < database/migrations/migration_add_archive_and_dose_times.sql
```

For detailed migration instructions, see [database/migrations/README.md](database/migrations/README.md).

### Available Migrations

- **migration_add_archive_and_dose_times.sql** - Adds archive functionality and dose time tracking
- **migration_add_late_logging.sql** - Adds late_logging_reason column for tracking late medication logs
- **migration_add_early_logging.sql** ⚠️ **CRITICAL** - Adds early_logging_reason column (REQUIRED to fix database error)

### Critical Migration Required

⚠️ **Action Required**: If you're experiencing database errors when logging medications, you must run the early logging migration:

```bash
# Via browser (recommended)
https://your-domain.com/run_early_logging_migration.php

# OR via command line
php run_early_logging_migration.php

# OR manually via MySQL
mysql -u username -p database_name < database/migrations/migration_add_early_logging.sql
```

**See detailed instructions**: [DEPLOYMENT_EARLY_LOGGING_FIX.md](DEPLOYMENT_EARLY_LOGGING_FIX.md)

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
