# Admin V2 Migration Interface

## ✅ V2 Sync Management from Admin Dashboard

Admins can now manage V2 migration directly from the admin dashboard at `https://api.fadsms.com/admin`

---

## 🎯 Admin Endpoints Available

### Base URL
```
https://api.fadsms.com/api/admin/v2-sync
```

### Authentication
All admin endpoints require:
- Bearer token (admin user)
- Admin role

---

## 📊 Admin V2 Sync Endpoints

### 1. Get Sync Status
```http
GET /api/admin/v2-sync/status
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "status": "success",
  "data": {
    "api_configured": true,
    "api_key": "v2sync_d4eedb4e914d43b6...",
    "synced_users_count": 156,
    "total_v2_transactions": 1247,
    "recent_syncs": [
      {
        "user_id": 123,
        "amount": "500.00",
        "description": "Purchase on V2 - Order #456",
        "reference": "V2_ORDER_456",
        "created_at": "2025-10-07T14:30:00Z"
      }
    ],
    "endpoints": {
      "base_url": "https://api.fadsms.com/api/v2-sync",
      "get_user": "https://api.fadsms.com/api/v2-sync/get-user",
      "update_balance": "https://api.fadsms.com/api/v2-sync/update-balance",
      "verify_user": "https://api.fadsms.com/api/v2-sync/verify-user"
    }
  }
}
```

**Shows**:
- ✅ API configuration status
- ✅ Number of synced users
- ✅ Total V2 transactions
- ✅ Recent sync activity (last 10)
- ✅ Available endpoints

---

### 2. Get Migration Statistics
```http
GET /api/admin/v2-sync/stats
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "status": "success",
  "data": {
    "total_users_with_v2_activity": 156,
    "total_v2_transactions": 1247,
    "total_v2_debits": 45670.50,
    "total_v2_credits": 3200.00,
    "last_v2_sync": "2025-10-07T14:30:00Z",
    "v2_syncs_today": 45,
    "v2_syncs_this_week": 312
  }
}
```

**Shows**:
- ✅ Total users with V2 activity
- ✅ Total V2 transactions
- ✅ Total debits from V2
- ✅ Total credits from V2
- ✅ Last sync time
- ✅ Syncs today
- ✅ Syncs this week

---

### 3. Get Migration Logs
```http
GET /api/admin/v2-sync/logs
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "status": "success",
  "data": {
    "migrated_users": [
      {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "balance": 5000.50,
        "v2_transaction_count": 8,
        "total_v2_amount": "1250.00",
        "last_sync": "2025-10-07T14:30:00Z"
      }
    ],
    "total_migrated": 156
  }
}
```

**Shows**:
- ✅ List of migrated users
- ✅ V2 transaction count per user
- ✅ Total V2 amount per user
- ✅ Last sync time per user

---

### 4. Test V2 Connection
```http
POST /api/admin/v2-sync/test-connection
Authorization: Bearer {admin_token}

Body:
{
  "v2_api_url": "https://old-site.com/api",
  "v2_api_key": "v2_api_key"
}
```

**Response**:
```json
{
  "status": "success",
  "data": {
    "connection": "working",
    "http_code": 200,
    "response": "V2 API is reachable"
  }
}
```

**Tests**:
- ✅ V2 site reachability
- ✅ API authentication
- ✅ Response time

---

### 5. Regenerate API Key
```http
POST /api/admin/v2-sync/regenerate-key
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "status": "success",
  "data": {
    "new_api_key": "v2sync_new_key_here...",
    "message": "API key regenerated. Update V2 site with new key!"
  }
}
```

**Actions**:
- ✅ Generates new secure API key
- ✅ Updates .env file
- ✅ Clears config cache
- ✅ Returns new key to update on V2

---

## 🎛️ Admin Dashboard Features

### V2 Migration Dashboard Section

**What Admins Can See**:
1. **Sync Status**
   - API configured: Yes/No
   - Current API key (masked)
   - Total synced users
   - Total V2 transactions

2. **Statistics**
   - Users with V2 activity
   - Total transactions from V2
   - Total amount debited
   - Total amount credited
   - Activity today/this week

3. **Migration Logs**
   - List of migrated users
   - Transaction count per user
   - Last sync time
   - Total amount per user

4. **Actions**
   - Test V2 connection
   - Regenerate API key
   - View detailed logs
   - Monitor sync activity

---

## 🔧 How Admins Use It

### Step 1: Check Sync Status
```
Admin logs in → Goes to V2 Migration section
→ Sees sync status and statistics
→ Views recent sync activity
```

### Step 2: Monitor Migration
```
Admin clicks "View Logs"
→ Sees list of migrated users
→ Checks transaction counts
→ Verifies sync is working
```

### Step 3: Regenerate Key (If Needed)
```
Admin clicks "Regenerate API Key"
→ New key generated
→ Admin copies new key
→ Updates V2 site .env file
```

### Step 4: Test Connection
```
Admin enters V2 site URL
→ Clicks "Test Connection"
→ Verifies V2 can reach V1
→ Confirms sync is operational
```

---

## 📊 Dashboard UI Elements

### Status Card
```
┌──────────────────────────────────────┐
│  V2 Migration Status                 │
├──────────────────────────────────────┤
│  API Configured: ✅ Yes              │
│  API Key: v2sync_d4eedb4e...         │
│  Synced Users: 156                   │
│  Total Transactions: 1,247           │
│                                       │
│  [View Logs] [Regenerate Key]        │
└──────────────────────────────────────┘
```

### Statistics Card
```
┌──────────────────────────────────────┐
│  V2 Sync Statistics                  │
├──────────────────────────────────────┤
│  Today:      45 syncs                │
│  This Week:  312 syncs               │
│  Total Debit: ₦45,670.50             │
│  Total Credit: ₦3,200.00             │
│  Last Sync: 2 minutes ago            │
└──────────────────────────────────────┘
```

### Recent Activity Table
```
┌────────────┬──────────┬─────────────────────────┬─────────────┐
│ User       │ Amount   │ Description             │ Time        │
├────────────┼──────────┼─────────────────────────┼─────────────┤
│ john@...   │ ₦500.00  │ Purchase on V2 - #456   │ 2 mins ago  │
│ jane@...   │ ₦1000.00 │ Purchase on V2 - #457   │ 5 mins ago  │
└────────────┴──────────┴─────────────────────────┴─────────────┘
```

---

## 🎯 Use Cases

### 1. Monitor Migration Progress
Admin can see in real-time:
- How many users have been migrated
- How many transactions synced from V2
- Recent sync activity

### 2. Troubleshoot Issues
Admin can:
- Test V2 connection
- View error logs
- Regenerate API key if compromised
- Check specific user migration status

### 3. Verify System Health
Admin can:
- See sync statistics
- Monitor daily/weekly activity
- Check last sync time
- Verify API is working

---

## 🔐 Security

All admin endpoints:
- ✅ Require admin authentication
- ✅ Protected by admin middleware
- ✅ Logged for audit
- ✅ HTTPS required

---

## 📱 Access Methods

### Via API (cURL)
```bash
# Get sync status
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/status

# Get statistics
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/stats

# Get migration logs
curl -H "Authorization: Bearer {admin_token}" \
     https://api.fadsms.com/api/admin/v2-sync/logs
```

### Via Admin Dashboard (Coming Soon)
- Web interface at `https://api.fadsms.com/admin`
- Visual dashboard with charts
- One-click actions
- Real-time updates

---

## 🚀 Next Steps

### To Enable Admin Dashboard UI:

1. **Frontend**: Create admin React components
   - V2 Migration Status page
   - Statistics dashboard
   - Migration logs viewer
   - API key management

2. **Routes**: Add admin frontend routes
   - `/admin` - Admin dashboard
   - `/admin/v2-migration` - Migration manager
   - `/admin/users` - User management

3. **Features**:
   - Real-time sync monitoring
   - One-click key regeneration
   - Export migration logs
   - Test V2 connection button

---

## ✅ Current Status

**Backend**: ✅ Complete  
- 5 admin endpoints for V2 sync
- Statistics and logging
- API key management
- Connection testing

**Frontend**: 🔄 Can be added  
- Admin dashboard UI (optional)
- Visual migration interface
- Charts and graphs

**API Endpoints**: ✅ Live and Ready  
- Accessible via API calls
- Can be integrated into admin frontend

---

## 📖 Quick Test

As admin, you can test the endpoints now:

```bash
# 1. Login as admin
curl -X POST https://api.fadsms.com/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# 2. Get V2 sync status
curl -H "Authorization: Bearer {token}" \
     https://api.fadsms.com/api/admin/v2-sync/status

# 3. View statistics
curl -H "Authorization: Bearer {token}" \
     https://api.fadsms.com/api/admin/v2-sync/stats
```

---

**V2 Migration can now be monitored and managed from admin dashboard API!** ✅

