# V2 Sync - Admin Quick Guide

## ✅ Yes, Migration Can Be Triggered from Admin Dashboard!

Admins can now manage V2 migration directly via API endpoints at `https://api.fadsms.com/admin`

---

## 🎯 What Admin Can Do

### 1. Check Sync Status
```bash
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/status
```

**Shows:**
- ✅ API configured or not
- ✅ Current API key (masked)
- ✅ Number of synced users
- ✅ Total V2 transactions
- ✅ Recent sync activity

---

### 2. View Statistics
```bash
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/stats
```

**Shows:**
- ✅ Total users with V2 activity
- ✅ Total V2 transactions
- ✅ Total debits/credits
- ✅ Syncs today/this week
- ✅ Last sync time

---

### 3. View Migration Logs
```bash
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/logs
```

**Shows:**
- ✅ List of migrated users
- ✅ V2 transactions per user
- ✅ Total amount per user
- ✅ Last sync time per user

---

### 4. Regenerate API Key
```bash
curl -X POST \
     -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/regenerate-key
```

**Does:**
- ✅ Generates new secure API key
- ✅ Updates .env file
- ✅ Clears config cache
- ✅ Returns new key (update on V2!)

---

### 5. Test V2 Connection
```bash
curl -X POST \
     -H "Authorization: Bearer {admin_token}" \
     -H "Content-Type: application/json" \
     -d '{"v2_api_url":"https://old-site.com/api","v2_api_key":"v2_key"}' \
     https://api.fadsms.com/api/admin/v2-sync/test-connection
```

**Does:**
- ✅ Tests V2 site reachability
- ✅ Verifies API authentication
- ✅ Returns connection status

---

## 🔐 Admin Access Required

All endpoints require:
- Admin user login
- Bearer token authentication
- Admin role

---

## 📊 Admin Routes Available

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/admin/v2-sync/status` | View sync status |
| GET | `/api/admin/v2-sync/stats` | View statistics |
| GET | `/api/admin/v2-sync/logs` | View migration logs |
| POST | `/api/admin/v2-sync/regenerate-key` | Regenerate API key |
| POST | `/api/admin/v2-sync/test-connection` | Test V2 connection |

---

## 🚀 Quick Start for Admin

### Step 1: Login as Admin
```bash
TOKEN=$(curl -s -X POST https://api.fadsms.com/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@fadsms.com","password":"your_password"}' \
  | jq -r '.token')
```

### Step 2: Check Sync Status
```bash
curl -H "Authorization: Bearer $TOKEN" \
     https://api.fadsms.com/api/admin/v2-sync/status | jq
```

### Step 3: View Statistics
```bash
curl -H "Authorization: Bearer $TOKEN" \
     https://api.fadsms.com/api/admin/v2-sync/stats | jq
```

### Step 4: View Logs
```bash
curl -H "Authorization: Bearer $TOKEN" \
     https://api.fadsms.com/api/admin/v2-sync/logs | jq
```

---

## 💡 Use Cases

### Monitor Migration Progress
- See how many users migrated
- Track transaction volume from V2
- View recent sync activity

### Troubleshoot Issues
- Test V2 connection
- View migration logs
- Regenerate compromised API key
- Check specific user migration status

### System Health
- Monitor daily/weekly sync activity
- Verify last sync time
- Check API configuration

---

## 🎛️ Future: Admin Dashboard UI

Can be added to admin frontend:

- **V2 Migration Page** (`/admin/v2-migration`)
  - Visual status dashboard
  - Real-time statistics
  - Migration logs table
  - One-click key regeneration
  - Connection test button

- **Charts & Graphs**
  - Sync activity timeline
  - Transaction volume
  - User migration progress

---

## ✅ Current Implementation

**Backend**: ✅ Complete
- 5 admin endpoints
- Full statistics
- Migration logging
- API key management

**Access**: ✅ API Calls
- cURL commands
- Postman/Insomnia
- JavaScript fetch
- Can integrate into React admin UI

**Security**: ✅ Locked Down
- Admin authentication required
- Bearer token protection
- HTTPS enforced

---

## 📝 Summary

**Yes!** Admin can manage V2 migration from the dashboard via these 5 API endpoints:

1. ✅ View sync status
2. ✅ View statistics  
3. ✅ View migration logs
4. ✅ Regenerate API key
5. ✅ Test V2 connection

All endpoints are live at: `https://api.fadsms.com/api/admin/v2-sync/*`

**To add UI:** Create admin React components that call these endpoints.

---

**Documentation:**
- `V2_SYNC_README.md` - V2 implementation guide
- `ADMIN_V2_MIGRATION.md` - Admin interface details
- `V2_SYNC_ADMIN_QUICK_GUIDE.md` - This quick guide

---

✨ **Migration system is ready to use!**
