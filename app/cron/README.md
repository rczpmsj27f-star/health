# Medication Reminder Cron Setup

This directory contains cron scripts for sending automated medication reminders.

## Scripts

### `send_medication_reminders.php`

Sends medication reminders to users based on their notification preferences and medication schedules.

**Features:**
- Sends notifications at scheduled medication times
- Sends follow-up reminders (10, 20, 30, 60 minutes after) if medication not taken
- Uses OneSignal Player IDs for targeted push notifications
- Only sends to users who have enabled notifications and have a valid Player ID

**How it works:**
1. Queries the database for pending medication doses scheduled for today
2. Checks user notification preferences (notify_at_time, notify_after_10min, etc.)
3. Calculates time difference between scheduled time and current time
4. Sends targeted push notification via OneSignal if time matches a reminder window
5. Uses stored Player IDs to send device-specific notifications

## Cron Setup

### Option 1: Run every minute (recommended)

Add this to your crontab (`crontab -e`):

```bash
* * * * * /usr/bin/php /path/to/health/app/cron/send_medication_reminders.php >> /path/to/health/app/logs/cron.log 2>&1
```

### Option 2: Run every 5 minutes (less frequent)

```bash
*/5 * * * * /usr/bin/php /path/to/health/app/cron/send_medication_reminders.php >> /path/to/health/app/logs/cron.log 2>&1
```

### Setup Instructions

1. **Make the script executable:**
   ```bash
   chmod +x /path/to/health/app/cron/send_medication_reminders.php
   ```

2. **Find your PHP path:**
   ```bash
   which php
   ```

3. **Test the script manually:**
   ```bash
   php /path/to/health/app/cron/send_medication_reminders.php
   ```

4. **Add to crontab:**
   ```bash
   crontab -e
   ```
   Then add one of the cron commands above, replacing `/path/to/health` with your actual path.

5. **Create log directory:**
   ```bash
   mkdir -p /path/to/health/app/logs
   chmod 755 /path/to/health/app/logs
   ```

6. **Monitor the cron log:**
   ```bash
   tail -f /path/to/health/app/logs/cron.log
   ```

## Requirements

- PHP CLI (command line interface)
- Database access with proper credentials in `config.php`
- OneSignal App ID and REST API Key configured in `config.php`
- Users must have:
  - Enabled notifications in their settings
  - A valid OneSignal Player ID stored in the database
  - Pending medication doses scheduled in `medication_logs` table

## Troubleshooting

### Cron not running
- Check cron service is running: `systemctl status cron` or `service cron status`
- Check cron log: `grep CRON /var/log/syslog`
- Verify PHP path: `which php`
- Check file permissions: script should be readable by cron user

### No notifications being sent
- Check the cron log file for errors
- Verify OneSignal credentials are correct
- Ensure users have Player IDs stored in database
- Check that medication_logs table has pending entries
- Verify user notification preferences are enabled

### Testing without cron
Run the script manually to test:
```bash
php /path/to/health/app/cron/send_medication_reminders.php
```

This will output what it's doing and any errors encountered.
