# V2 to V1 User Migration Guide

## 🎯 Purpose

This script migrates existing users from your V2 database to V1 by:
- Matching users by email address
- Syncing account balances
- Syncing password hashes
- Creating transaction logs

---

## ⚠️ Important Notes

**Before Running:**
1. ✅ Backup both V1 and V2 databases
2. ✅ Ensure V2 database is accessible
3. ✅ Test with a small batch first
4. ✅ Run during low-traffic period

**What Gets Synced:**
- ✅ Email (matching key)
- ✅ Password hash (for authentication)
- ✅ Account balance
- ✅ Name (if empty in V1)
- ✅ Phone (if empty in V1)

**What Happens:**
- If user exists in V1 (by email) → Updates balance & password
- If user doesn't exist in V1 → Creates new user with V2 data
- Transaction logs created for all balance changes

---

## 🚀 How to Run

### Step 1: Prepare V2 Database Information

You'll need:
- V2 Database Host (e.g., `localhost`, `192.168.1.100`)
- V2 Database Name (e.g., `old_fadsms_db`)
- V2 Database Username
- V2 Database Password

### Step 2: Run the Migration Script

```bash
cd /var/www/api.fadsms.com
php sync-v2-to-v1.php
```

### Step 3: Enter V2 Database Details

The script will prompt you for:
```
V2 Database Host: localhost
V2 Database Name: old_fadsms_db
V2 Database Username: root
V2 Database Password: ********
```

### Step 4: Review Preview

The script will show:
- Total users to sync
- First 5 users with their balances
- What actions will be taken

```
⚠️  SYNC PREVIEW
════════════════════════════════════════════════
This will sync 150 users from V2 to V1:
   • Match users by email
   • Update V1 balance with V2 balance
   • Update V1 password with V2 password hash
   • Create users if they don't exist in V1

First 5 users to sync:
   • john@example.com - Balance: ₦5,000.00
   • jane@example.com - Balance: ₦3,500.00
   ...
```

### Step 5: Confirm Sync

Type `yes` to proceed or `no` to cancel.

### Step 6: Wait for Completion

The script will process each user and show progress:
```
[1/150] Processing: john@example.com... ✅ Updated (Balance: ₦0 → ₦5,000)
[2/150] Processing: jane@example.com... ✅ Created (Balance: ₦3,500)
...
```

---

## 📊 What the Script Does

### For Existing Users (Email Match)
1. Finds user in V1 by email
2. Updates V1 balance with V2 balance
3. Updates V1 password hash with V2 password
4. Updates name/phone if empty in V1
5. Creates transaction log for balance change

### For New Users (No Email Match)
1. Creates new user in V1
2. Sets balance from V2
3. Sets password hash from V2
4. Copies name, phone from V2
5. Creates initial balance transaction log

### Transaction Logging
Every balance change is logged in the `transactions` table:
```
- User ID
- Amount changed
- Type (credit/debit)
- Method: 200 (V2 Sync)
- Status: completed
- Reference: V2_SYNC_timestamp_userid
- Metadata: V2 user ID, old/new balance, sync timestamp
```

---

## 📋 Example Output

```
════════════════════════════════════════════════════════════════
   V2 → V1 User Sync Script
════════════════════════════════════════════════════════════════

📋 Enter V2 Database Configuration:
────────────────────────────────────────
V2 Database Host: localhost
V2 Database Name: old_fadsms
V2 Database Username: root
V2 Database Password: ********

🔌 Connecting to V2 Database...
✅ Connected to V2 database

📊 Fetching users from V2 database...
✅ Found 150 users in V2 database

════════════════════════════════════════════════════════════════
   Starting Sync Process...
════════════════════════════════════════════════════════════════

[1/150] Processing: john@example.com... ✅ Updated (Balance: ₦0 → ₦5,000)
[2/150] Processing: jane@example.com... ✅ Created (Balance: ₦3,500)
[3/150] Processing: mike@example.com... ✅ Updated (Balance: ₦1,200 → ₦2,400)
...

════════════════════════════════════════════════════════════════
   Sync Complete!
════════════════════════════════════════════════════════════════

📊 Summary:
   Total Users:    150
   Updated:        75
   Created:        70
   Errors:         5

💰 Total Balance Synced: ₦456,789.50

✅ What was synced:
   • Email addresses matched
   • Balances updated/created
   • Password hashes synced
   • Transaction logs created

════════════════════════════════════════════════════════════════
🎉 V2 → V1 User Sync Completed Successfully!
════════════════════════════════════════════════════════════════
```

---

## 🔍 Verify the Sync

### 1. Check Admin Dashboard
```
Go to: https://api.fadsms.com/admin
Click: 🔄 V2 Migration
View: Migration Logs
```

You should see all synced users with their transaction counts.

### 2. Check Database
```sql
-- Count synced users
SELECT COUNT(*) FROM transactions 
WHERE metadata LIKE '%v2_migration%';

-- View synced balances
SELECT u.email, u.balance, t.amount, t.created_at
FROM users u
JOIN transactions t ON u.id = t.user_id
WHERE t.metadata LIKE '%v2_migration%'
ORDER BY t.created_at DESC;
```

### 3. Test User Login
Try logging in with a V2 user's credentials:
- Email from V2
- Password from V2
- Should work on V1 now

---

## ⚠️ Troubleshooting

### Error: "Failed to connect to V2 database"
**Solution:**
- Check V2 database credentials
- Ensure V2 database is accessible from V1 server
- Check firewall rules if V2 is on different server

### Error: "Duplicate entry for email"
**Solution:**
- User already exists in V1
- Script will update existing user instead of creating duplicate
- This is expected behavior

### Error: "Invalid password hash"
**Solution:**
- V2 password hashes should be bcrypt
- If V2 uses different hashing, users will need to reset password
- Consider adding password migration logic

### Some Users Not Synced
**Check:**
- Users have valid email addresses in V2
- Email format is valid
- V2 database connection didn't drop

---

## 🔄 Re-running the Script

You can safely re-run the script multiple times:
- Existing users will be updated with latest V2 data
- No duplicates will be created (email is unique)
- Useful for incremental syncs

**Use Case:**
Run once for initial migration, then run again after a week to catch any new V2 users.

---

## 📊 Database Schema Requirements

### V2 Database (Required Columns)
```sql
users table must have:
- id (integer)
- email (string, not null)
- password (string, hashed)
- balance (decimal)
- name (string, optional)
- phone (string, optional)
- created_at (timestamp, optional)
```

### V1 Database (Auto-Created)
```sql
transactions table:
- Automatically populated
- Contains migration history
- Tracks balance changes
```

---

## 🎯 Best Practices

### 1. Test First
Run with a small V2 database first:
```sql
-- On V2, create test database
CREATE DATABASE v2_test;
-- Copy 10 users for testing
INSERT INTO v2_test.users SELECT * FROM users LIMIT 10;
```

### 2. Backup
```bash
# Backup V1 database before sync
mysqldump -u root -p fadsms_v1 > backup_before_sync.sql

# Backup V2 database
mysqldump -u root -p fadsms_v2 > v2_backup.sql
```

### 3. Monitor
Watch the sync progress:
- Check for errors
- Verify balance totals
- Test login with synced users

### 4. Document
Keep record of:
- Sync date/time
- Number of users synced
- Total balance migrated
- Any errors encountered

---

## 📝 After Sync Checklist

✅ Verify user count in V1 matches V2  
✅ Test login with V2 credentials  
✅ Check balances are correct  
✅ Review transaction logs  
✅ Test V2 Sync API (for future real-time sync)  
✅ Update V2 site to use V1 API  
✅ Monitor for any issues  

---

## 🚀 Next Steps

After successful migration:

1. **Configure V2 Site**
   - Add V1 API credentials to V2 .env
   - Implement real-time sync for new transactions

2. **Test V2 → V1 Sync**
   - New user registration on V2 → Creates in V1
   - Balance changes on V2 → Updates in V1
   - Login on V2 → Authenticates with V1

3. **Monitor**
   - Check admin dashboard daily
   - Review sync statistics
   - Watch for any sync failures

---

## 📞 Support

If you encounter issues:
1. Check logs: `storage/logs/laravel.log`
2. Review admin dashboard: V2 Migration section
3. Re-run script with verbose output
4. Check database connectivity

---

Created: October 8, 2025  
Last Updated: October 8, 2025

---

**Ready to migrate?** Run: `php sync-v2-to-v1.php`
