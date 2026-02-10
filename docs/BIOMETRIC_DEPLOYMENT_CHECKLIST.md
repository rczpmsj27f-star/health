# Biometric Authentication - Deployment Checklist

## Pre-Deployment Requirements

### Server Requirements
- [ ] **HTTPS enabled** - WebAuthn requires secure connections
- [ ] PHP 7.4 or later
- [ ] MySQL/MariaDB database
- [ ] Session handling configured properly

### Database Migration
- [ ] Run migration: `database/migrations/migration_add_biometric_auth.sql`
  ```bash
  mysql -u username -p database_name < database/migrations/migration_add_biometric_auth.sql
  ```
- [ ] Verify new columns exist in users table:
  - biometric_enabled
  - biometric_credential_id
  - biometric_public_key
  - biometric_counter
  - last_biometric_login

### File Deployment
Ensure all new files are deployed:

**Backend Files:**
- [ ] `app/core/BiometricAuth.php`
- [ ] `public/api/biometric/status.php`
- [ ] `public/api/biometric/register.php`
- [ ] `public/api/biometric/authenticate.php`
- [ ] `public/api/biometric/disable.php`
- [ ] `public/api/biometric/challenge.php`

**Frontend Files:**
- [ ] `public/assets/js/biometric-auth.js`
- [ ] `public/modules/settings/biometric.php`
- [ ] Updated `public/login.php`
- [ ] Updated `app/includes/menu.php`

**Documentation:**
- [ ] `docs/BIOMETRIC_AUTHENTICATION.md`
- [ ] `docs/BIOMETRIC_TESTING.md`
- [ ] Updated `database/migrations/README.md`
- [ ] Updated `README.md`

## Deployment Steps

### 1. Backup
- [ ] Backup database before running migration
- [ ] Backup current application files

### 2. Deploy Code
```bash
# Pull latest code
git pull origin main

# Or manually upload files via FTP/SFTP
```

### 3. Run Database Migration
```bash
# Via MySQL CLI
mysql -u your_user -p your_database < database/migrations/migration_add_biometric_auth.sql

# Or via phpMyAdmin - copy and paste SQL from migration file
```

### 4. Verify Deployment
- [ ] Check that biometric settings page loads: `/modules/settings/biometric.php`
- [ ] Verify "Biometric Auth" appears in Settings menu
- [ ] Check browser console for JavaScript errors
- [ ] Test on iOS device with Safari

### 5. Test Core Functionality
- [ ] Enable biometric authentication with test account
- [ ] Verify Face ID/Touch ID prompt appears
- [ ] Test biometric login works
- [ ] Test disable biometric authentication
- [ ] Verify password login still works

## Post-Deployment Verification

### Security Checks
- [ ] HTTPS is enforced (HTTP redirects to HTTPS)
- [ ] Challenge endpoint returns new challenge each time
- [ ] Old authentication attempts cannot be replayed
- [ ] Password required to enable biometric
- [ ] Session created only on successful authentication

### User Experience
- [ ] Biometric option appears only on supported devices/browsers
- [ ] Clear error messages for failures
- [ ] Fallback to password works correctly
- [ ] Loading states shown during async operations

### Browser Testing
- [ ] Safari on iOS 14+ (Face ID device)
- [ ] Safari on iOS 14+ (Touch ID device)
- [ ] Desktop Safari (if Touch ID Mac available)
- [ ] Graceful degradation on unsupported browsers

## Rollback Plan

If issues arise:

1. **Disable feature temporarily:**
   - Remove biometric link from menu (edit `app/includes/menu.php`)
   - Or add a feature flag in config

2. **Revert database if needed:**
   ```sql
   ALTER TABLE users 
   DROP COLUMN biometric_enabled,
   DROP COLUMN biometric_credential_id,
   DROP COLUMN biometric_public_key,
   DROP COLUMN biometric_counter,
   DROP COLUMN last_biometric_login;
   ```

3. **Revert code:**
   ```bash
   git revert <commit-hash>
   ```

## User Communication

### Announcement Template

**Subject:** New Feature: Face ID / Touch ID Login

We've added biometric authentication to make logging in faster and more secure!

**What's New:**
- Use Face ID or Touch ID to log in on iPhone/iPad
- Quick, secure access to your medications
- Your biometric data never leaves your device

**How to Enable:**
1. Sign in with your password
2. Go to Settings → Biometric Auth
3. Enter your password to verify
4. Complete the Face ID/Touch ID setup

**Requirements:**
- iPhone/iPad with iOS 14 or later
- Safari browser
- Face ID or Touch ID enabled on your device

**Note:** Your password still works - biometric is just a faster option!

Questions? See our guide: [Link to BIOMETRIC_AUTHENTICATION.md]

## Monitoring

After deployment, monitor for:
- [ ] JavaScript errors in browser console
- [ ] PHP errors in server logs
- [ ] Failed authentication attempts
- [ ] User feedback about the feature
- [ ] Database performance (new columns are indexed)

## Support Resources

- **User Guide:** `docs/BIOMETRIC_AUTHENTICATION.md`
- **Testing Guide:** `docs/BIOMETRIC_TESTING.md`
- **Technical Docs:** See README.md and code comments
- **Troubleshooting:** See BIOMETRIC_AUTHENTICATION.md#troubleshooting

## Success Criteria

Deployment is successful when:
- ✅ Migration applied without errors
- ✅ No JavaScript console errors
- ✅ No PHP server errors
- ✅ Users can enable biometric authentication
- ✅ Users can log in with biometric
- ✅ Users can disable biometric authentication
- ✅ Password login still works as fallback
- ✅ Feature gracefully hidden on unsupported devices

## Known Limitations

Document these for users:
1. Each device requires separate biometric enrollment
2. HTTPS required (feature won't work on HTTP)
3. Safari on iOS/iPadOS 14+ only
4. Simplified WebAuthn verification (see security notes in docs)

## Next Steps (Future Enhancements)

Consider for future releases:
- Full WebAuthn signature verification library
- Support for multiple enrolled devices per user
- Biometric re-auth for sensitive actions
- Analytics/logging for biometric usage
- Periodic password re-validation
