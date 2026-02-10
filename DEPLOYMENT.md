# Deployment and Server Configuration Guide

## Environment Configuration

### Database Setup

Before deploying the application, you must configure database credentials using environment variables.

#### Step 1: Create Environment File

Copy the example environment file to create your local configuration:

```bash
cp .env.example .env
```

#### Step 2: Configure Database and OneSignal Credentials

Edit `.env` and set your actual credentials:

```env
# Database
DB_HOST=localhost
DB_USER=your_database_user
DB_PASS=your_secure_password
DB_NAME=your_database_name

# OneSignal (get from OneSignal Dashboard > Settings > Keys & IDs)
ONESIGNAL_APP_ID=your_onesignal_app_id
ONESIGNAL_REST_API_KEY=your_onesignal_rest_api_key

# Application
APP_ENV=production
ENABLE_DEBUG_LOGGING=false
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

# OneSignal credentials
SetEnv ONESIGNAL_APP_ID "your_onesignal_app_id"
SetEnv ONESIGNAL_REST_API_KEY "your_onesignal_rest_api_key"

# Application settings
SetEnv APP_ENV "production"
SetEnv ENABLE_DEBUG_LOGGING "false"
```

**Nginx (with PHP-FPM):**
```nginx
location ~ \.php$ {
    fastcgi_param DB_HOST "localhost";
    fastcgi_param DB_USER "your_database_user";
    fastcgi_param DB_PASS "your_secure_password";
    fastcgi_param DB_NAME "your_database_name";
    
    # OneSignal credentials
    fastcgi_param ONESIGNAL_APP_ID "your_onesignal_app_id";
    fastcgi_param ONESIGNAL_REST_API_KEY "your_onesignal_rest_api_key";
    
    # Application settings
    fastcgi_param APP_ENV "production";
    fastcgi_param ENABLE_DEBUG_LOGGING "false";
}
```

**Docker:**
```bash
docker run -e DB_HOST=localhost -e DB_USER=user -e DB_PASS=pass -e DB_NAME=dbname ...
```

## 🌐 Hostinger Deployment Guide

This section provides step-by-step instructions specifically for deploying to Hostinger hosting.

### Issue: Database Credentials Deleted on Deployment

**Problem:** Every time code is deployed to Hostinger, database credentials and OneSignal API keys get overwritten, breaking the application.

**Root Cause:** Deployment processes that sync files from Git will overwrite `config.php` and other configuration files with repository versions (which contain placeholder values, not production credentials).

**Solution:** Use environment variables instead of hardcoded credentials in configuration files.

### Hostinger Deployment Steps

#### Option 1: Using .htaccess (Recommended for Hostinger)

Hostinger uses Apache, so you can set environment variables via `.htaccess`:

1. **Copy the example file:**
   ```bash
   cp .htaccess.hostinger.example .htaccess
   ```
   
   Or if you already have a `.htaccess` file, add the environment variables to the top of your existing file.

2. **Edit `.htaccess` with your production credentials:**
   ```apache
   # Database Configuration
   SetEnv DB_HOST "localhost"
   SetEnv DB_NAME "u123456789_health"
   SetEnv DB_USER "u123456789_admin"
   SetEnv DB_PASS "your_secure_production_password"
   
   # OneSignal Configuration
   SetEnv ONESIGNAL_APP_ID "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
   SetEnv ONESIGNAL_REST_API_KEY "yos_v2_app_your_rest_api_key_here"
   
   # Application Settings
   SetEnv APP_ENV "production"
   SetEnv ENABLE_DEBUG_LOGGING "false"
   ```

3. **Add `.htaccess` to `.gitignore`** to prevent committing credentials:
   ```bash
   echo ".htaccess" >> .gitignore
   git add .gitignore
   git commit -m "Ignore .htaccess with production credentials"
   ```

4. **Important:** Keep a backup of your `.htaccess` file outside the Git repository, as it will not be overwritten during deployments.

#### Option 2: Using .env File on Server

If you prefer using a `.env` file on Hostinger:

1. **SSH into your Hostinger account** (if available on your plan)

2. **Create `.env` file in your web root:**
   ```bash
   cd ~/public_html  # or your domain's directory
   cp .env.example .env
   nano .env  # or vi .env
   ```

3. **Configure with production credentials:**
   ```env
   DB_HOST=localhost
   DB_NAME=u123456789_health
   DB_USER=u123456789_admin
   DB_PASS=your_secure_production_password
   
   ONESIGNAL_APP_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   ONESIGNAL_REST_API_KEY=yos_v2_app_your_rest_api_key_here
   
   APP_ENV=production
   ENABLE_DEBUG_LOGGING=false
   ```

4. **Secure the file:**
   ```bash
   chmod 600 .env
   ```

5. **Verify `.env` is in `.gitignore`** (it already is by default)

**Note:** This option requires SSH access. If you don't have SSH access on Hostinger, use Option 1 (.htaccess method).

#### Option 3: Using Hostinger File Manager

If you don't have SSH access:

1. **Log into Hostinger Control Panel (hPanel)**

2. **Navigate to File Manager**

3. **Go to your website's directory** (e.g., `public_html` or domain directory)

4. **Create/Edit `.htaccess`:**
   - Click "New File" → Name it `.htaccess`
   - Or edit existing `.htaccess`
   - Add the `SetEnv` directives as shown in Option 1

5. **Save and close**

### Verifying Environment Variables

After setting up environment variables, verify they're loaded correctly:

1. **Create a test file** `test_env.php` in your web root:
   ```php
   <?php
   // test_env.php - Delete this file after testing!
   echo "<pre>";
   echo "DB_HOST: " . (getenv('DB_HOST') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "DB_NAME: " . (getenv('DB_NAME') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "DB_USER: " . (getenv('DB_USER') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "DB_PASS: " . (getenv('DB_PASS') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "ONESIGNAL_APP_ID: " . (getenv('ONESIGNAL_APP_ID') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "ONESIGNAL_REST_API_KEY: " . (getenv('ONESIGNAL_REST_API_KEY') ? '✅ Set' : '❌ Not Set') . "\n";
   echo "</pre>";
   ?>
   ```

2. **Visit** `https://yourdomain.com/test_env.php`

3. **Verify all variables show ✅ Set**

4. **DELETE the test file immediately** for security:
   ```bash
   rm test_env.php
   ```

### Deployment Workflow for Hostinger

With environment variables properly configured, your deployment workflow becomes:

1. **Make changes locally and commit to Git**
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   ```

2. **Deploy to Hostinger** using one of these methods:

   **Method A: Git Deployment (if available on your plan)**
   - Use Hostinger's Git deployment feature in hPanel
   - It will pull latest code from your repository
   - Your `.htaccess` or `.env` with credentials remains untouched
   
   **Method B: FTP/SFTP Upload**
   - Upload changed files via FileZilla or similar
   - **DO NOT** upload `.htaccess` or `.env` files from local
   - Keep production credentials file on server only
   
   **Method C: File Manager**
   - Upload files through Hostinger File Manager
   - **DO NOT** upload `.htaccess` or `.env` files from local

3. **After deployment, verify:**
   - Application loads correctly
   - Database connection works
   - Push notifications work
   - No credential errors in logs

### Troubleshooting Hostinger Deployments

#### Issue: "Database configuration error: Missing required environment variables"

**Solution:**
1. Check that `.htaccess` exists and has `SetEnv` directives
2. Verify Apache mod_env is enabled (it usually is on Hostinger)
3. Check file permissions on `.htaccess` (should be 644)
4. Check that `.htaccess` is in the correct directory (web root)

#### Issue: "OneSignal credentials not configured"

**Solution:**
1. Verify `ONESIGNAL_APP_ID` and `ONESIGNAL_REST_API_KEY` are set in `.htaccess` or `.env`
2. Check that values are not placeholder strings
3. Visit `/test_env.php` to verify environment variables are loaded

#### Issue: Environment variables work locally but not on Hostinger

**Solution:**
1. Local development uses `.env` file, but Hostinger should use `.htaccess`
2. Ensure `.htaccess` file exists on Hostinger server
3. Verify mod_env is enabled: `php -i | grep -i environment`
4. Contact Hostinger support if mod_env is disabled

#### Issue: Git deployment overwrites .htaccess

**Solution:**
1. Add `.htaccess` to `.gitignore` before committing
2. Keep a backup of `.htaccess` outside Git
3. After Git deployment, restore `.htaccess` from backup if needed
4. Consider using Hostinger's exclude feature if available

### Security Best Practices for Hostinger

✅ **DO:**
- Use `.htaccess` method for environment variables (most reliable on shared hosting)
- Keep different credentials for development and production
- Set file permissions: `.htaccess` = 644, `.env` = 600
- Add `.htaccess` and `.env` to `.gitignore`
- Rotate passwords regularly
- Use strong, unique passwords

❌ **DON'T:**
- Commit `.htaccess` with real credentials to Git
- Use the same database password for dev and production
- Leave test files like `test_env.php` on the server
- Expose environment variables in error messages
- Share credentials in chat/email without encryption

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
