# Admin Login Fix Summary

## ✅ Issues Fixed

### 1. AdminMiddleware Authentication Issue
**Problem:** The `AdminMiddleware` was using `auth()->check()` and `auth()->user()` which defaults to the `web` guard (session-based), but admin routes use `auth:sanctum` (token-based).

**Solution:** Updated `AdminMiddleware.php` to use `$request->user()` which properly works with Sanctum tokens.

**File Changed:** `/var/www/api.fadsms.com/app/Http/Middleware/AdminMiddleware.php`

```php
// Before (WRONG):
if (!auth()->check()) { ... }
if (!auth()->user()->isAdmin()) { ... }

// After (CORRECT):
$user = $request->user();
if (!$user) { ... }
if (!$user->isAdmin()) { ... }
```

### 2. User Role Update
**Problem:** User `imax9ja@gmail.com` had role set to `user` instead of `admin`.

**Solution:** Updated user role to `admin` in the database.

---

## 👥 Current Admin Users

| ID   | Name    | Email                  | Role  | Status |
|------|---------|------------------------|-------|--------|
| 86   | fadded  | admin@admin.com        | admin | active |
| 3011 | c4BOMB  | admin@fadded.com       | admin | active |
| 4274 | admin   | imax9ja@gmail.com      | admin | active |

---

## 🔐 How Admin Login Works

### Step 1: Login to Get Token
```bash
curl -X POST "https://api.fadsms.com/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "imax9ja@gmail.com",
    "password": "your_password"
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 4274,
      "name": "admin",
      "email": "imax9ja@gmail.com",
      "role": "admin",
      "balance": "1000.00"
    },
    "token": "106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217",
    "token_type": "Bearer"
  }
}
```

### Step 2: Use Token for Admin Endpoints
```bash
curl -X GET "https://api.fadsms.com/api/admin/dashboard" \
  -H "Authorization: Bearer 106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "stats": {
      "total_users": 14173,
      "active_users": 14173,
      "total_transactions": 5000,
      "pending_deposits": 9
    }
  }
}
```

---

## 🛣️ Admin API Routes

All admin routes require:
- `Authorization: Bearer {token}` header
- User must have `role` = `admin` or `super_admin`
- User must have `status` = `active`

### Dashboard & Stats
```
GET /api/admin/dashboard          - Admin dashboard data
GET /api/admin/statistics         - System statistics
```

### User Management
```
GET  /api/admin/users             - List all users (with filters)
GET  /api/admin/users/{id}        - Get specific user
PUT  /api/admin/users/{id}/status - Update user status
PUT  /api/admin/users/{id}/role   - Update user role (super_admin only)
POST /api/admin/users/{id}/balance - Adjust user balance
```

### Deposit Management
```
GET /api/admin/deposits            - All deposits (with filters)
GET /api/admin/deposits/pending    - Pending deposits only
PUT /api/admin/deposits/{id}/status - Approve/deny deposit
```

### Transaction Management
```
GET  /api/admin/transactions              - All transactions
GET  /api/admin/transactions/refundable   - Refundable transactions
POST /api/admin/transactions/{id}/refund  - Refund a transaction
```

### Order Management
```
GET /api/admin/orders/sms         - SMS orders
GET /api/admin/orders/vtu         - VTU orders
```

### Service Management
```
GET /api/admin/services           - List all services
PUT /api/admin/services/sms/{id}  - Update SMS service
PUT /api/admin/services/vtu/{id}  - Update VTU service
```

### Settings
```
GET  /api/admin/pricing           - Get pricing settings
POST /api/admin/pricing           - Update pricing settings
```

---

## 🧪 Testing Admin Access

### Test Token Generated for imax9ja@gmail.com:
```
Token: 106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217
```

### Quick Test Commands:

**Test Dashboard:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/dashboard" \
  -H "Authorization: Bearer 106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217" \
  -H "Accept: application/json"
```

**Test Pending Deposits:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/deposits/pending" \
  -H "Authorization: Bearer 106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217" \
  -H "Accept: application/json"
```

**Test Users List:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/users?per_page=10" \
  -H "Authorization: Bearer 106|REURecMnuUHgLPG2dBppjRCJi986dqcnGvjn0T5r6693a217" \
  -H "Accept: application/json"
```

---

## 🐛 Troubleshooting

### Error: "Authentication required"
- **Cause:** No token provided or invalid token
- **Solution:** Login first to get a valid token, then include it in `Authorization: Bearer {token}` header

### Error: "Access denied. Admin privileges required."
- **Cause:** User role is not `admin` or `super_admin`
- **Solution:** Update user role in database:
```sql
UPDATE users SET role = 'admin' WHERE email = 'user@example.com';
```

### Error: "Account is not active"
- **Cause:** User status is not `active`
- **Solution:** Update user status in database:
```sql
UPDATE users SET status = 'active' WHERE email = 'user@example.com';
```

---

## 🔍 How to Check User Admin Status

### Via Database:
```bash
mysql -u fadsms_user -p'Enter0text' fadsms_api -e \
  "SELECT id, name, email, role, status FROM users WHERE email = 'imax9ja@gmail.com';"
```

### Via Laravel Tinker:
```bash
cd /var/www/api.fadsms.com
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'imax9ja@gmail.com')->first();
echo $user->isAdmin() ? 'IS ADMIN' : 'NOT ADMIN';
```

---

## 📝 Notes

1. **Token Expiration:** Sanctum tokens don't expire by default unless configured
2. **Token Storage:** Store tokens securely in your frontend (localStorage, secure cookies, etc.)
3. **Multiple Tokens:** Users can have multiple active tokens (different devices/sessions)
4. **Token Revocation:** Logout endpoint revokes the current token only

---

## ✅ Summary

**What Was Fixed:**
1. ✅ AdminMiddleware now works with Sanctum tokens
2. ✅ User `imax9ja@gmail.com` role updated to `admin`
3. ✅ All admin routes now accessible with proper token

**Current Admin Users:**
- `admin@admin.com` (fadded)
- `admin@fadded.com` (c4BOMB)
- `imax9ja@gmail.com` (admin) ← **YOU**

**Test Status:** ✅ Admin login and access working correctly!

