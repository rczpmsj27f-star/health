# Enhancement Features - Quick Start Guide

This guide helps you quickly deploy and test the new enhancement features.

## 🚀 Quick Deployment (5 minutes)

### Step 1: Apply Database Migrations

```bash
# Option A: Use the migration runner (recommended)
cd /path/to/health
php run_enhancement_migrations.php

# Option B: Apply manually
mysql -u username -p database_name < database/migrations/migration_create_user_preferences.sql
mysql -u username -p database_name < database/migrations/migration_add_medication_appearance.sql
mysql -u username -p database_name < database/migrations/migration_create_stock_notification_log.sql
```

### Step 2: Schedule Cron Job

```bash
# Open crontab
crontab -e

# Add this line (daily at 9:00 AM)
0 9 * * * /usr/bin/php /path/to/health/app/cron/check_low_stock.php >> /var/log/health-stock.log 2>&1

# Save and exit
```

### Step 3: Test Features

1. **Navigate to:** Settings > Preferences
2. **Test dark mode:** Toggle the dark mode switch
3. **Test time format:** Change between 12h/24h
4. **Configure stock notifications:** Set threshold and enable
5. **Add/edit medication:** Try the icon and color picker

## ✨ Feature Overview

### 1. Dark Mode 🌙
- **Access:** Settings > Preferences
- **Features:** Full site-wide dark theme
- **Defaults to:** System preference (prefers-color-scheme)

### 2. Time Format ⏰
- **Access:** Settings > Preferences
- **Options:** 12-hour (AM/PM) or 24-hour
- **Default:** 12-hour format

### 3. Password Confirmation ✓
- **Where:** Registration page
- **Features:** Real-time match indicator
- **Validation:** Client & server-side

### 4. Custom Medication Icons 💊
- **Access:** Add/Edit Medication forms
- **Icons:** 11 types (pill, capsule, liquid, etc.)
- **Colors:** 10 presets + custom picker

### 5. Low Stock Notifications 📧
- **Access:** Settings > Preferences
- **Features:** Email when stock below threshold
- **Cooldown:** 7 days between notifications

### 6. Form Protection 🔒
- **Where:** All forms site-wide
- **Features:** Prevents double-submission
- **Visual:** Loading spinner + disabled state

## 🧪 Testing Checklist

### Basic Tests (5 minutes)

- [ ] Login to the application
- [ ] Navigate to Settings > Preferences
- [ ] Toggle dark mode - verify theme changes
- [ ] Change time format - verify toggle works
- [ ] Add a new medication with custom icon and color
- [ ] View medication list - verify custom icon displays
- [ ] Edit existing medication - change icon/color
- [ ] Set stock notification threshold
- [ ] Try double-clicking a form submit button - verify protection

### Advanced Tests (10 minutes)

- [ ] Register new user - test password confirmation
- [ ] Add medication with low stock
- [ ] Manually run stock check cron: `php app/cron/check_low_stock.php`
- [ ] Check email for low stock notification
- [ ] Verify notification logged in database
- [ ] Test dark mode on different pages
- [ ] Test form protection on different forms

### Database Verification

```sql
-- Check user preferences table
SELECT * FROM user_preferences LIMIT 5;

-- Check medications have icons
SELECT id, name, icon, color FROM medications LIMIT 5;

-- Check stock notification log
SELECT * FROM stock_notification_log ORDER BY notification_sent_at DESC LIMIT 5;
```

## 🎨 Customization Examples

### PHP: Custom Medication Icon

```php
require_once 'app/helpers/medication_icon.php';

// Render a blue pill icon
echo renderMedicationIcon('pill', '#2563eb', '32px');

// Render a green liquid icon
echo renderMedicationIcon('liquid', '#16a34a', '24px');
```

### JavaScript: Manual Form Protection

```javascript
// Protect a specific form
FormProtection.protectForm(document.getElementById('myForm'));

// Add loading to a button
const button = document.getElementById('myButton');
FormProtection.setButtonLoading(button);

// Remove loading (after API call completes)
FormProtection.removeButtonLoading(button);
```

### PHP: Format Time for User

```php
require_once 'app/helpers/time_format.php';

$time = '14:30:00';
$formattedTime = formatTimeForUser($pdo, $_SESSION['user_id'], $time);
// Returns: "2:30 PM" or "14:30" based on user preference
```

## 🐛 Troubleshooting

### Dark Mode Not Working
- Clear browser cache
- Check that preferences were saved (check database)
- Verify CSS file is loaded without cache

### Icons Not Showing
- Check medication has icon/color in database
- Verify medication_icon.php helper is included
- Check browser console for JavaScript errors

### Stock Notifications Not Sent
```bash
# Test cron job manually
php app/cron/check_low_stock.php

# Check PHP error log
tail -f /var/log/php-error.log

# Verify email configuration
cat app/config/mailer.php
```

### Form Protection Too Aggressive
```javascript
// Disable for specific form
document.getElementById('myForm').dataset.skipProtection = 'true';
```

## 📊 Monitoring

### Check Notification Logs
```sql
SELECT 
    u.username,
    m.name as medication,
    snl.stock_level,
    snl.threshold,
    snl.notification_sent_at
FROM stock_notification_log snl
JOIN users u ON snl.user_id = u.id
JOIN medications m ON snl.medication_id = m.id
ORDER BY snl.notification_sent_at DESC
LIMIT 10;
```

### Check User Preferences
```sql
SELECT 
    u.username,
    up.dark_mode,
    up.time_format,
    up.stock_notification_enabled,
    up.stock_notification_threshold
FROM user_preferences up
JOIN users u ON up.user_id = u.id;
```

## 📝 User Documentation

### For End Users

**Dark Mode:**
1. Click Settings in menu
2. Click Preferences
3. Toggle "Enable Dark Mode"
4. Click "Save Preferences"

**Time Format:**
1. Click Settings > Preferences
2. Select "12-hour (AM/PM)" or "24-hour"
3. Click "Save Preferences"

**Custom Medication Icons:**
1. Add or edit a medication
2. Scroll to "Medication Icon" section
3. Click on an icon to select
4. Click on a color to select
5. Preview shows your selection
6. Click "Save" or "Add Medication"

**Stock Notifications:**
1. Click Settings > Preferences
2. Enable "Low Stock Notifications"
3. Set threshold (e.g., 7 days)
4. Optionally enable "Notify Linked Users"
5. Click "Save Preferences"

## 🔗 Related Documentation

- Full Implementation Guide: `ENHANCEMENT_FEATURES_IMPLEMENTATION.md`
- Cron Setup: `app/cron/README.md`
- Database Migrations: `database/migrations/README.md`

## 💡 Tips

1. **Dark Mode:** Best experience when system also uses dark mode
2. **Icons:** Choose icons that visually represent medication type
3. **Colors:** Use colors to group similar medications
4. **Stock:** Set threshold based on prescription refill time
5. **Forms:** Protection automatically applies, no configuration needed

## 🆘 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review error logs: `/var/log/php-error.log`
3. Check database for missing data
4. Verify migrations were applied
5. Test in different browser

## ✅ Success Indicators

You've successfully deployed when:
- ✅ Dark mode toggle works
- ✅ Time format changes throughout app
- ✅ Can add medication with custom icon
- ✅ Icons display on medication list
- ✅ Form buttons show loading spinner
- ✅ Preferences are saved and persist
- ✅ Stock notifications can be configured

---

**Deployment Time:** ~5 minutes  
**Testing Time:** ~15 minutes  
**Total Time:** ~20 minutes

Happy tracking! 🎉
