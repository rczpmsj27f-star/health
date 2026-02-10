# Database Migrations

This directory contains SQL migration files for managing database schema changes.

## Migration Files

Migration files follow the naming convention: `migration_<description>.sql`

### Available Migrations

1. **migration_add_archive_and_dose_times.sql**
   - Adds archive functionality to medications table
   - Adds dose times tracking to medication_schedules table
   - Creates performance indexes for archive and dose time queries

2. **migration_create_dose_times_table.sql**
   - Creates the medication_dose_times table for storing specific dose times
   - Tracks individual dose times for medications taken multiple times per day
   - Foreign key relationship with medications table (CASCADE on delete)

3. **migration_create_notification_settings.sql**
   - Creates the user_notification_settings table for storing user notification preferences
   - Tracks notification enable/disable status per user
   - Stores notification timing preferences (at time, 10min, 20min, 30min, 60min after)
   - Stores OneSignal player ID for push notification targeting
   - Foreign key relationship with users table (CASCADE on delete)

4. **migration_add_late_logging.sql**
   - Adds late_logging_reason VARCHAR(255) column to medication_logs table
   - Tracks why medications were logged late (after scheduled time)
   - Includes performance index on the column
   - Migration runner: `run_late_logging_migration.php`

5. **migration_add_early_logging.sql** ⭐ REQUIRED FOR FIX
   - Adds early_logging_reason VARCHAR(255) column to medication_logs table
   - Tracks why medications were logged early (before scheduled time)
   - Includes performance index on the column
   - Migration runner: `run_early_logging_migration.php`
   - **Status**: MUST BE RUN to fix database error
   - See: `DEPLOYMENT_EARLY_LOGGING_FIX.md` for deployment instructions

6. **migration_add_two_factor_auth.sql**
   - Adds two-factor authentication support to users table
   - Adds two_factor_secret, two_factor_enabled, two_factor_backup_codes columns
   - Enables Google Authenticator-based 2FA

7. **migration_add_biometric_auth.sql** 🆕
   - Adds biometric authentication support to users table (Face ID/Touch ID)
   - Adds columns: biometric_enabled, biometric_credential_id, biometric_public_key, biometric_counter, last_biometric_login
   - Enables WebAuthn-based biometric authentication for iOS/iPadOS devices
   - **Requirements**: HTTPS connection required for WebAuthn to work
   - See: `docs/BIOMETRIC_AUTHENTICATION.md` for full documentation

## How to Apply Migrations

Since this project doesn't use an automated migration framework, migrations must be applied manually:

### Option 1: Using MySQL Command Line

```bash
mysql -u username -p database_name < database/migrations/migration_add_archive_and_dose_times.sql
```

### Option 2: Using phpMyAdmin or Database GUI

1. Open your database management tool
2. Select the database (u983097270_ht)
3. Navigate to SQL tab
4. Copy and paste the content of the migration file
5. Execute the SQL statements

### Option 3: Using PHP Script

You can create a simple migration runner script if needed.

## Migration Details

### migration_add_archive_and_dose_times.sql

**Purpose:** Enable archiving of discontinued medications and tracking specific dose times.

**Tables Modified:**
- `medications` - Adds end_date, archived, archived_at columns
- `medication_schedules` - Adds dose_times, last_taken_at, next_due_at columns

**Indexes Created:**
- `idx_medications_archived` - For efficient archive queries
- `idx_medications_user_archived` - For user-specific archive queries
- `idx_schedules_next_due` - For upcoming dose queries
- `idx_schedules_last_taken` - For dose history queries

## Creating New Migrations

When creating new migrations:

1. Name file with descriptive name: `migration_<description>.sql`
2. Include comments at the top explaining what the migration does
3. Use `IF NOT EXISTS` clauses to make migrations rerunnable
4. Add the migration to this README
5. Test on a development database before production

## Notes

- Migrations use `ADD COLUMN IF NOT EXISTS` to prevent errors if run multiple times
- Migrations use `CREATE INDEX IF NOT EXISTS` for the same reason
- Always backup your database before running migrations in production
- Migrations are forward-only (no rollback scripts currently)
