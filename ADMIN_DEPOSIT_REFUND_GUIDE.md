# Admin Deposit & Refund Management Guide

## Overview
This guide covers the new admin features for managing deposits and processing transaction refunds on api.fadsms.com.

## Features Added

### 1. Deposit Management
Admins can now view pending deposits and approve/deny them with reasons.

### 2. Transaction Refund Management
Admins can manually refund successful transactions, returning money to user balances.

---

## API Endpoints

### Deposit Management Endpoints

#### 1. Get All Deposits (with filters)
```
GET /api/admin/deposits
```

**Query Parameters:**
- `search` - Search by reference or user name/email
- `status` - Filter by status: pending, completed, failed, cancelled
- `from_date` - Filter from date (YYYY-MM-DD)
- `to_date` - Filter to date (YYYY-MM-DD)
- `per_page` - Items per page (default: 20)

**Example:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/deposits?status=pending" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 4274,
        "amount": "1000.00",
        "reference": "PAYVIBE_1759755831_9953",
        "status": "pending",
        "created_at": "2025-10-10T10:30:00.000000Z",
        "user": {
          "id": 4274,
          "name": "John Doe",
          "email": "imax9ja@gmail.com"
        }
      }
    ],
    "total": 9
  }
}
```

---

#### 2. Get Pending Deposits Only
```
GET /api/admin/deposits/pending
```

**Example:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/deposits/pending" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

---

#### 3. Approve or Deny Deposit
```
PUT /api/admin/deposits/{id}/status
```

**Request Body:**
```json
{
  "status": "completed",  // or "failed", "cancelled"
  "admin_note": "Payment verified"  // optional
}
```

**Status Options:**
- `completed` - Approve deposit (credits user balance)
- `failed` - Deny deposit (payment failed)
- `cancelled` - Cancel deposit (admin cancelled)

**Example - Approve Deposit:**
```bash
curl -X PUT "https://api.fadsms.com/api/admin/deposits/1/status" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "admin_note": "Payment verified via bank statement"
  }'
```

**Example - Deny Deposit:**
```bash
curl -X PUT "https://api.fadsms.com/api/admin/deposits/1/status" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "failed",
    "admin_note": "Payment not received in bank account"
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "Deposit status updated successfully",
  "data": {
    "deposit": {
      "id": 1,
      "user_id": 4274,
      "amount": "1000.00",
      "status": "completed",
      "reference": "PAYVIBE_1759755831_9953",
      "metadata": {
        "admin_note": "Payment verified",
        "processed_at": "2025-10-10T12:00:00",
        "processed_by": 86,
        "processed_by_name": "Admin User"
      }
    }
  }
}
```

**What Happens on Approval:**
1. Deposit status changes to `completed`
2. User balance is credited with deposit amount
3. Transaction record is created with type `deposit`
4. Admin info (ID, note, timestamp) stored in metadata

---

### Transaction Refund Management Endpoints

#### 4. Get Refundable Transactions
```
GET /api/admin/transactions/refundable
```

**Query Parameters:**
- `search` - Search by description, reference, or user name/email
- `user_id` - Filter by user ID
- `from_date` - Filter from date (YYYY-MM-DD)
- `to_date` - Filter to date (YYYY-MM-DD)
- `per_page` - Items per page (default: 20)

**Example:**
```bash
curl -X GET "https://api.fadsms.com/api/admin/transactions/refundable?search=airtime" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 3,
        "user_id": 18102,
        "type": "service_purchase",
        "amount": "100.00",
        "balance_before": "1000.00",
        "balance_after": "900.00",
        "description": "Airtime purchase for 08148790554 (mtn)",
        "reference": "VTU_1ihVuaZHEb",
        "status": "success",
        "created_at": "2025-10-09T14:20:00.000000Z",
        "user": {
          "id": 18102,
          "name": "Test User",
          "email": "test@fadsms.com"
        }
      }
    ],
    "total": 50
  }
}
```

---

#### 5. Refund a Transaction
```
POST /api/admin/transactions/{id}/refund
```

**Request Body:**
```json
{
  "reason": "Service failed - network error",
  "amount": 100.00  // optional - for partial refunds
}
```

**Parameters:**
- `reason` (required) - Reason for refund (max 500 characters)
- `amount` (optional) - Partial refund amount (defaults to full transaction amount)

**Example - Full Refund:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/transactions/3/refund" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Service failed - SMS not received"
  }'
```

**Example - Partial Refund:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/transactions/5/refund" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Partial service delivery - 50% refund",
    "amount": 50.00
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "Transaction refunded successfully",
  "data": {
    "refund_amount": "100.00",
    "new_balance": "1000.00",
    "transaction": {
      "id": 3,
      "status": "success",
      "metadata": {
        "refunded": true,
        "refund_amount": "100.00",
        "refund_reason": "Service failed - SMS not received",
        "refunded_by": 86,
        "refunded_at": "2025-10-10T12:30:00"
      }
    }
  }
}
```

**What Happens on Refund:**
1. User balance is credited with refund amount
2. Original transaction metadata is updated with refund info
3. New transaction record created with type `refund`
4. Original transaction marked as refunded (cannot be refunded again)

---

## Error Responses

### Invalid Status
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "status": ["The selected status is invalid."]
  }
}
```

### Deposit Already Processed
```json
{
  "status": "error",
  "message": "Deposit status cannot be changed"
}
```

### Transaction Already Refunded
```json
{
  "status": "error",
  "message": "Transaction has already been refunded"
}
```

### Non-refundable Transaction
```json
{
  "status": "error",
  "message": "Only successful transactions can be refunded"
}
```

### Insufficient Permissions
```json
{
  "status": "error",
  "message": "Access denied. Admin privileges required."
}
```

---

## Database Changes

### Deposits Table
- Status values: `pending`, `completed`, `failed`, `cancelled`
- Admin processing info stored in `metadata` JSON field:
  - `admin_note` - Admin's note/reason
  - `processed_at` - Timestamp of processing
  - `processed_by` - Admin user ID
  - `processed_by_name` - Admin name

### Transactions Table
- Refunded transactions have `metadata.refunded = true`
- Refund metadata includes:
  - `refunded` - Boolean flag
  - `refund_amount` - Amount refunded
  - `refund_reason` - Reason for refund
  - `refunded_by` - Admin user ID
  - `refunded_at` - Timestamp
- New transaction created with type `refund` and reference `REF_{original_reference}`

---

## Testing the Features

### Test Deposit Approval

**Current Pending Deposits:**
```bash
mysql -u fadsms_user -p'Enter0text' fadsms_api -e \
  "SELECT id, user_id, amount, status, reference FROM deposits WHERE status = 'pending' LIMIT 5;"
```

**Approve Deposit ID 1:**
```bash
curl -X PUT "https://api.fadsms.com/api/admin/deposits/1/status" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "admin_note": "Test approval"
  }'
```

### Test Transaction Refund

**Find Refundable Transactions:**
```bash
mysql -u fadsms_user -p'Enter0text' fadsms_api -e \
  "SELECT id, user_id, type, amount, description, reference FROM transactions WHERE status = 'success' AND type = 'service_purchase' LIMIT 5;"
```

**Refund Transaction ID 3:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/transactions/3/refund" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Test refund"
  }'
```

---

## Frontend Integration Tips

### Deposit Management Page

**Features to implement:**
1. Table showing pending deposits
2. Modal/form for approve/deny with reason field
3. Filter by status and date range
4. Search by user email or reference

**Sample UI Flow:**
1. Display pending deposits in a table
2. Admin clicks "Approve" or "Deny" button
3. Modal appears asking for reason (optional for approve, recommended for deny)
4. On submit, call API endpoint
5. Show success message and refresh list

### Transaction Refund Page

**Features to implement:**
1. Table showing successful transactions (not yet refunded)
2. Modal/form for refund with reason field
3. Option for partial refund amount
4. Search and filter capabilities
5. Show refund history (transactions with type='refund')

**Sample UI Flow:**
1. Display refundable transactions in a table
2. Admin clicks "Refund" button
3. Modal appears with:
   - Original transaction details
   - Reason text field (required)
   - Amount field (optional, defaults to full amount)
4. On submit, call API endpoint
5. Show success message with new user balance
6. Mark transaction as refunded in table

---

## Security Notes

1. All endpoints require admin authentication via Sanctum token
2. User balance changes are wrapped in database transactions
3. Refunded transactions cannot be refunded again
4. Admin actions are logged in transaction metadata
5. Only deposits with status 'pending' can be modified

---

## Summary

✅ **Deposit Management:**
- View all deposits with filters
- View pending deposits specifically
- Approve deposits (credits user balance)
- Deny deposits with reason

✅ **Transaction Refunds:**
- View refundable transactions
- Full refunds (entire amount)
- Partial refunds (specified amount)
- Refund tracking (cannot refund twice)
- Admin audit trail in metadata

All features are production-ready and include proper error handling, validation, and security checks.

