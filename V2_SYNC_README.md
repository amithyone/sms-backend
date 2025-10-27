# V2 Sync API - Complete System

## 🎯 Purpose

This API allows your **old V2 site** to sync user data with **this new V1 site** (FaddedSMS), so users can:
- Login with their existing credentials
- Access their wallet balance
- Make purchases on V2 that deduct from V1 wallet
- Have a seamless experience across both sites

---

## ✅ What Has Been Implemented

### 1. API Controller (`V2SyncController.php`)
Complete API for V2 to communicate with V1:
- Get user data (for authentication)
- Verify user exists
- Update balance (debit/credit)
- Batch get users
- Create new user

### 2. Authentication
- Secure API key authentication
- Header-based: `X-V2-Sync-Key`
- Environment variable configuration

### 3. Routes (5 endpoints)
```
POST /api/v2-sync/get-user        - Get user data
POST /api/v2-sync/verify-user     - Check if user exists
POST /api/v2-sync/update-balance  - Update balance
POST /api/v2-sync/batch-users     - Get multiple users
POST /api/v2-sync/create-user     - Create new user
```

### 4. Setup & Testing Tools
- `setup-v2-sync.php` - Automated setup script
- `test-v2-sync-api.php` - API testing script

---

## 🚀 How It Works

### User Login on V2
```
1. User enters email/password on V2
2. V2 calls V1: GET /v2-sync/get-user
3. V1 returns user data + password hash
4. V2 verifies password using Hash::check()
5. User is logged into V2
```

### Purchase on V2
```
1. User makes purchase on V2
2. V2 calls V1: POST /v2-sync/update-balance (debit)
3. V1 checks balance and updates
4. V1 records transaction
5. V1 returns new balance
6. V2 completes order
```

---

## 📡 API Endpoints

### Base URL
```
https://api.fadsms.com/api/v2-sync
```

### Authentication
All requests require API key in header:
```
X-V2-Sync-Key: v2sync_your_api_key_here
```

### 1. Get User
```http
POST /api/v2-sync/get-user
Content-Type: application/json
X-V2-Sync-Key: your_api_key

{
  "email": "user@example.com"
}
```

**Response (200)**:
```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "08012345678",
    "wallet": 5000.50,
    "password_hash": "$2y$10$...",
    "created_at": "2025-01-15T10:00:00Z"
  },
  "message": "User data retrieved successfully"
}
```

### 2. Verify User
```http
POST /api/v2-sync/verify-user
```

**Response**:
```json
{
  "status": true,
  "exists": true
}
```

### 3. Update Balance
```http
POST /api/v2-sync/update-balance

{
  "email": "user@example.com",
  "amount": 100.00,
  "type": "debit",
  "description": "Purchase on V2 - Order #123",
  "reference": "V2_ORDER_123"
}
```

**Response**:
```json
{
  "status": true,
  "data": {
    "old_balance": 5000.50,
    "new_balance": 4900.50,
    "amount": 100.00,
    "type": "debit"
  },
  "message": "Balance updated successfully"
}
```

---

## 🔐 Security

- ✅ API key authentication
- ✅ HTTPS required
- ✅ Request validation
- ✅ Balance verification
- ✅ Transaction logging
- ✅ Unique reference enforcement

---

## 📚 Full Documentation

See individual documentation files for complete details:
- `V2_SYNC_QUICK_SETUP.md` - Quick start guide
- `V2_SYNC_API_DOCUMENTATION.md` - Complete API reference

---

## ✅ Status

**All systems operational and ready to use!**

Run `php test-v2-sync-api.php` to verify.
