# V1 Sync Setup Guide - V2 Site (api.fadsms.com)

## Overview

This V2 site (api.fadsms.com) has been configured to sync with V1 (faddedsms.com) for:
- User authentication (users can log in with V1 credentials)
- Balance synchronization (V1 is the source of truth)
- Seamless migration from V1 to V2

## Implementation Status

✅ **Completed**:
1. Database migration added (`v1_user_id` field to users table)
2. V1SyncService created (`app/Services/V1SyncService.php`)
3. AuthController updated to support V1 authentication
4. Service registered in AppServiceProvider
5. .env file configured (needs API key from V1)
6. Test script created

⏳ **Pending**:
1. Get V2_SYNC_API_KEY from faddedsms.com (V1)
2. Update V1_SYNC_API_KEY in .env
3. Test the integration
4. Deploy to production

---

## How It Works

### Login Flow:
1. User enters email/password on V2 (api.fadsms.com)
2. V2 calls V1 API to authenticate and fetch user data
3. If successful, V2 creates/updates local user record
4. User is logged into V2 with synced balance from V1

### Transaction Flow:
1. User makes a purchase on V2
2. V2 calls V1 API to deduct balance
3. If successful, transaction completes
4. Local balance is updated to match V1

---

## Setup Instructions

### Step 1: Get API Key from V1 (faddedsms.com)

On **faddedsms.com** (V1 site), find the `V2_SYNC_API_KEY`:

```bash
# SSH to faddedsms.com server
ssh user@faddedsms.com

# Check .env file
grep V2_SYNC_API_KEY /path/to/faddedsms.com/.env

# Or check config file
cat /path/to/faddedsms.com/v2-sync-config.txt
```

### Step 2: Update V2 .env File

On **api.fadsms.com** (this V2 site), update `.env`:

```bash
# Edit .env file
nano /var/www/api.fadsms.com/.env

# Find and update this line:
V1_SYNC_API_KEY=YOUR_ACTUAL_API_KEY_FROM_V1_HERE
```

Replace `PLEASE_GET_THIS_FROM_FADDEDSMS_COM` with the actual API key.

### Step 3: Clear Cache

```bash
cd /var/www/api.fadsms.com
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test Connection

```bash
php /var/www/api.fadsms.com/test-v1-connection.php
```

Expected output:
```
✅ CONNECTION SUCCESSFUL
✅ V1 Sync integration is working!
```

### Step 5: Test Login

Try logging in with a V1 user's credentials:

```bash
curl -X POST https://api.fadsms.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

If successful, you should see:
- User data from V1
- Balance synced from V1
- Authentication token for V2

---

## Files Created/Modified

### New Files:
- `app/Services/V1SyncService.php` - Service to communicate with V1 API
- `database/migrations/2025_10_09_094329_add_v1_sync_fields_to_users_table.php` - Migration
- `test-v1-connection.php` - Test script
- `V1_SYNC_SETUP_GUIDE.md` - This file

### Modified Files:
- `app/Http/Controllers/AuthController.php` - Added V1 authentication support
- `app/Models/User.php` - Added `v1_user_id` to fillable fields
- `app/Providers/AppServiceProvider.php` - Registered V1SyncService
- `.env` - Added V1 sync configuration

---

## Configuration

### V2 Site (.env) - THIS SITE (api.fadsms.com)
```env
# Connect TO V1 site (faddedsms.com)
V1_API_URL=https://faddedsms.com/api/v2-sync
V1_SYNC_API_KEY=YOUR_API_KEY_HERE
V1_SYNC_DEBUG=true
```

### V1 Site (.env) - OLD SITE (faddedsms.com)
```env
# API key that V2 will use to connect
V2_SYNC_API_KEY=same_as_v2_v1_sync_api_key
```

**Important**: `V1_SYNC_API_KEY` on V2 must match `V2_SYNC_API_KEY` on V1!

---

## API Endpoints Used

V2 (this site) will call these endpoints on V1 (faddedsms.com):

- `POST /api/v2-sync/verify-user` - Check if user exists
- `POST /api/v2-sync/get-user` - Get user data by email
- `POST /api/v2-sync/update-balance` - Update balance on V1
- `POST /api/v2-sync/batch-users` - Batch fetch users (optional)
- `POST /api/v2-sync/create-user` - Create user on V1 (optional)

---

## Troubleshooting

### Issue: "Invalid sync API key"

**Solution:**
1. Verify API key matches between V1 and V2
2. Clear config cache: `php artisan config:clear`
3. Check for extra spaces or quotes in .env

### Issue: "Connection timeout"

**Solution:**
1. Check if faddedsms.com is accessible from api.fadsms.com server
2. Verify firewall rules allow outbound HTTPS
3. Test manually: `curl https://faddedsms.com/api/v2-sync/verify-user`

### Issue: "User not found"

**Solution:**
1. Verify user exists on V1 (faddedsms.com)
2. Try logging in directly on V1 first
3. Check email is correct (case-sensitive)

### Issue: "Balance not syncing"

**Solution:**
1. Check logs: `tail -f storage/logs/laravel.log`
2. Enable debug mode: `V1_SYNC_DEBUG=true`
3. Test V1 API: `php test-v1-connection.php`

---

## Monitoring & Logging

All V1 sync operations are logged to `storage/logs/laravel.log`:

```bash
# Watch logs in real-time
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep "V1 Sync"
```

Log entries include:
- User authentication attempts
- Balance updates
- API errors
- Response times

---

## Security Best Practices

1. **API Key Security**
   - Never commit .env to git
   - Use different keys for dev/staging/production
   - Rotate keys periodically

2. **HTTPS Only**
   - Always use HTTPS for V1 API calls
   - Never send API key over HTTP

3. **Error Handling**
   - Don't expose V1 errors to end users
   - Log all errors for debugging
   - Return generic error messages

4. **Rate Limiting**
   - Monitor V1 API usage
   - Implement retry logic with exponential backoff
   - Cache user data locally when possible

---

## Production Deployment Checklist

- [ ] Get V2_SYNC_API_KEY from faddedsms.com
- [ ] Update V1_SYNC_API_KEY in production .env
- [ ] Run migrations: `php artisan migrate`
- [ ] Test connection: `php test-v1-connection.php`
- [ ] Test login with V1 user credentials
- [ ] Test balance sync
- [ ] Monitor logs for errors
- [ ] Set up monitoring/alerting
- [ ] Document any V1-specific users for testing
- [ ] Plan rollback strategy

---

## Support

If you encounter issues:

1. **Check V1 API Status**: Verify faddedsms.com is online and accessible
2. **Check Logs**: Review Laravel logs for detailed error messages
3. **Test Manually**: Use curl to test V1 endpoints directly
4. **Enable Debug**: Set `V1_SYNC_DEBUG=true` in .env

For more information, see the implementation guide provided or contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: October 9, 2025  
**Implemented on**: api.fadsms.com (V2 site)  
**Connects to**: faddedsms.com (V1 site)

