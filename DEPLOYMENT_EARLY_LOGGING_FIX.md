# Early Logging Feature - Deployment Guide

## Issue
Database error when logging medications:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'early_logging_reason' in 'SET'
```

## Root Cause
The code is ready to handle early logging (tracking why medications are taken early), but the database migration to add the `early_logging_reason` column has not been executed in production.

## Solution
Run the early logging migration to add the missing database column.

---

## Deployment Steps

## Prerequisites
- Database access (MySQL 8.0.19+ or MariaDB 10.4.0+)
- Web server access with PHP
- Backup of database (recommended)

> **Note**: The migration uses `IF NOT EXISTS` syntax which requires MySQL 8.0.19+ or MariaDB 10.4.0+. If you're using an older database version, see "Manual SQL Execution for Older Databases" below.

### Option 1: Run Migration via Browser (Recommended)

1. **Access the migration runner**:
   ```
   https://your-domain.com/run_early_logging_migration.php
   ```

2. **Verify success**:
   - Look for "✅ Migration completed successfully!"
   - Verify "✓ Column 'early_logging_reason' exists" appears

3. **Delete the migration runner** (security best practice):
   ```bash
   rm run_early_logging_migration.php
   ```

### Option 2: Run Migration via Command Line

```bash
cd /path/to/health
php run_early_logging_migration.php
```

Then delete the runner:
```bash
rm run_early_logging_migration.php
```

### Option 3: Manual SQL Execution

If you prefer to run the SQL directly (MySQL 8.0.19+ or MariaDB 10.4.0+):

```sql
-- Add early logging reason column
ALTER TABLE medication_logs 
ADD COLUMN IF NOT EXISTS early_logging_reason VARCHAR(255) NULL 
AFTER late_logging_reason;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_medication_logs_early_reason 
ON medication_logs(early_logging_reason);
```

### Option 4: Manual SQL for Older Database Versions

If you're using MySQL < 8.0.19 or MariaDB < 10.4.0:

```sql
-- Check if column exists first, then add only if missing
ALTER TABLE medication_logs 
ADD COLUMN early_logging_reason VARCHAR(255) NULL 
AFTER late_logging_reason;

-- Add index (ignore error if exists)
CREATE INDEX idx_medication_logs_early_reason 
ON medication_logs(early_logging_reason);
```

> **Note**: On older database versions, these commands will fail if the column/index already exists. This is expected behavior - if you get "column already exists" or "duplicate key name" errors, the migration has already been applied.

---

## Verification

After running the migration, verify it worked:

### 1. Check Database Schema
```sql
DESCRIBE medication_logs;
```

You should see `early_logging_reason` column with:
- Type: `VARCHAR(255)`
- Null: `YES`
- Default: `NULL`

### 2. Test Early Logging Feature

1. **Log in to the application**
2. **Go to Dashboard**
3. **Click "Take" on a medication scheduled for a future date**
4. **You should see an "Early Logging" modal asking for a reason**
5. **Select a reason and submit**
6. **Verify no database errors occur**

### 3. Verify Data Storage

Check that the reason is stored:
```sql
SELECT id, medication_id, scheduled_date_time, status, early_logging_reason, late_logging_reason
FROM medication_logs
WHERE early_logging_reason IS NOT NULL
LIMIT 5;
```

---

## What This Migration Does

### Database Changes
- **Adds column**: `early_logging_reason VARCHAR(255) NULL` to `medication_logs` table
- **Adds index**: `idx_medication_logs_early_reason` for query performance
- **Position**: After `late_logging_reason` column (for consistency)

### Feature Enabled
Once deployed, users can:
1. Take medications early (before scheduled time)
2. Provide a reason from a dropdown:
   - "Instructed by doctor"
   - "Going on vacation"
   - "Adjusting schedule"
   - "Accidentally took early"
   - "Other" (with free text)
3. Track early logging in their medication history

### Affected Files (Already Deployed)
- ✅ `public/modules/medications/dashboard.php` - UI for early logging modal
- ✅ `public/modules/medications/take_medication_handler.php` - Backend handling
- ✅ `database/migrations/migration_add_early_logging.sql` - Migration SQL
- ✅ `database/migrations/migration_create_dropdown_options.sql` - Dropdown options

---

## Rollback Plan

If issues occur after migration, the column can be safely removed:

```sql
-- Remove index
DROP INDEX IF EXISTS idx_medication_logs_early_reason ON medication_logs;

-- Remove column
ALTER TABLE medication_logs DROP COLUMN early_logging_reason;
```

**Note**: This will delete any early logging reasons already recorded.

---

## Related Migrations

This migration is similar to:
- `migration_add_late_logging.sql` - Adds tracking for late medication logging
- Both follow the same pattern and can be deployed together if needed

---

## Support

### Common Issues

**Issue**: "Migration file not found"
- **Solution**: Verify you're running from the correct directory (application root)

**Issue**: "Access denied for user"
- **Solution**: Check database credentials in `.env` file

**Issue**: "Column already exists"
- **Solution**: Migration is idempotent (safe to run multiple times). This message means it's already been applied.

### Testing

After deployment, test these scenarios:
1. ✅ Taking medication on time (no reason required)
2. ✅ Taking medication late (late_logging_reason field)
3. ✅ Taking medication early (early_logging_reason field - NEW)
4. ✅ No database errors in logs

---

## Deployment Checklist

- [ ] Database backup completed
- [ ] Migration runner accessible
- [ ] Migration executed successfully
- [ ] Column verified in database schema
- [ ] Early logging feature tested
- [ ] Migration runner deleted (`run_early_logging_migration.php`)
- [ ] No errors in application logs
- [ ] User can take medications early with reason

**Deployed By**: _______________  
**Deployment Date**: _______________  
**Verified By**: _______________  
