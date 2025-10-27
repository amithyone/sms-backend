# SMS Polling System - Complete Guide

## ✅ YES! All SMS Providers Have Automatic Polling

Your system **already has** automatic SMS code retrieval for all providers. No manual checking needed!

---

## 🎯 Supported Providers (All 5)

| Provider | Auto-Polling | Status Check | Code Retrieval |
|----------|--------------|--------------|----------------|
| **Tiger SMS** | ✅ Yes | ✅ Yes | ✅ Automatic |
| **5SIM** | ✅ Yes | ✅ Yes | ✅ Automatic |
| **Dassy** | ✅ Yes | ✅ Yes | ✅ Automatic |
| **TextVerified** | ✅ Yes | ✅ Yes | ✅ Automatic |
| **SmsPool** | ✅ Yes | ✅ Yes | ✅ Automatic |

**All providers support automatic SMS code retrieval!** 🎉

---

## 🔄 How It Works

### Automatic Polling Flow

```
1. User orders SMS number
   ↓
2. Backend creates order with provider
   ↓
3. User receives phone number
   ↓
4. Frontend starts auto-polling
   ↓
5. Every 2 seconds: Check for SMS code
   ↓
6. Provider returns STATUS_WAIT (no code yet)
   ↓
7. Continue polling...
   ↓
8. Provider returns STATUS_OK:123456
   ↓
9. Backend extracts code "123456"
   ↓
10. Order marked as completed
    ↓
11. Inbox message created with code
    ↓
12. Frontend displays code to user! ✅
```

---

## 📊 Provider-Specific Implementation

### 1. Tiger SMS

**Endpoint**: `?action=getStatus&id={orderId}`

**Responses**:
- `STATUS_WAIT_CODE` - Waiting for SMS
- `STATUS_OK:123456` - SMS received! Code: 123456
- `STATUS_CANCEL` - Order cancelled

**Code Extraction**:
```php
if (stripos($body, 'STATUS_OK') === 0) {
    $parts = explode(':', $body, 2);
    return trim($parts[1]); // "123456"
}
```

### 2. 5SIM

**Endpoint**: `stubs/handler_api.php?action=getStatus&id={orderId}`

**Responses**:
- `STATUS_WAIT_CODE` - Waiting for SMS
- `STATUS_OK:123456` - SMS received! Code: 123456
- `STATUS_CANCEL` - Order cancelled

**Same extraction logic as Tiger SMS** ✅

### 3. Dassy

**Endpoint**: Provider-specific API

**Returns**: JSON with SMS code when available

### 4. TextVerified

**Endpoint**: Provider-specific API

**Returns**: JSON with verification code

### 5. SmsPool

**Endpoint**: Provider-specific API

**Returns**: SMS code or waiting status

---

## ⏱️ Polling Configuration

### Frontend (smsApi.ts)

```typescript
async pollForSmsCode(
  orderId: string, 
  maxAttempts: number = 30,  // Default: 30 attempts
  interval: number = 2000     // Default: 2 seconds
): Promise<string>
```

**Settings**:
- **Interval**: 2 seconds between checks
- **Max Attempts**: 30 (= 60 seconds total)
- **Auto-stop**: When code received or timeout

### Backend (SmsController.php)

```php
public function getSmsCode(Request $request): JsonResponse
{
    // 1. Find order in database
    $order = SmsOrder::where('order_id', $orderId)->first();
    
    // 2. If already completed, return cached code
    if ($order->isCompleted()) {
        return ['sms_code' => $order->sms_code];
    }
    
    // 3. Query provider for new code
    $smsCode = $this->smsProviderService->getSmsCode($order);
    
    // 4. If code received, save and return
    if ($smsCode) {
        $order->markAsCompleted($smsCode);
        // Create inbox message
        return ['sms_code' => $smsCode];
    }
    
    // 5. No code yet - return waiting status
    return ['status' => 'waiting'];
}
```

---

## 🎯 User Experience

### What Users See

1. **Order Number**: Phone number displayed immediately
2. **Waiting Message**: "Waiting for SMS..."
3. **Auto-Check**: System checks every 2 seconds (no button clicking!)
4. **Code Appears**: SMS code displayed when received
5. **Inbox Updated**: Code also saved in inbox for later reference

### No Manual Work Needed!

Users **don't need to**:
- ❌ Click "Check for SMS" button repeatedly
- ❌ Manually refresh the page
- ❌ Check provider dashboard
- ❌ Copy code from elsewhere

System does it **automatically**! ✅

---

## 📱 Provider Response Examples

### Tiger SMS / 5SIM

**Waiting**:
```
STATUS_WAIT_CODE
```

**Code Received**:
```
STATUS_OK:123456
```

**Cancelled**:
```
STATUS_CANCEL
```

### Other Providers (JSON)

**Waiting**:
```json
{
  "status": "waiting",
  "code": null
}
```

**Code Received**:
```json
{
  "status": "completed",
  "code": "123456"
}
```

---

## 🔍 Testing SMS Polling

### Manual Test

```bash
# 1. Create an SMS order via frontend
# 2. Watch logs for polling
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep "getSmsCode"

# 3. Check order status
php artisan tinker
>>> $order = App\Models\SmsOrder::latest()->first();
>>> $order->status
>>> $order->sms_code
```

### Test Each Provider

```bash
# Tiger SMS
curl "https://api.tiger-sms.com/stubs/handler_api.php?api_key=YOUR_KEY&action=getStatus&id=123456"

# 5SIM
curl "http://api1.5sim.net/stubs/handler_api.php?api_key=YOUR_KEY&action=getStatus&id=123456"
```

---

## 📊 Monitoring

### Check Polling Activity

```bash
# Watch real-time SMS checks
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep -i "sms code"

# Check recent orders
php artisan tinker
>>> App\Models\SmsOrder::latest()->take(5)->get(['id', 'order_id', 'status', 'sms_code', 'created_at'])
```

### Database Queries

```sql
-- Active orders waiting for SMS
SELECT id, order_id, phone_number, status, created_at 
FROM sms_orders 
WHERE status = 'active' 
ORDER BY created_at DESC;

-- Completed orders with codes
SELECT id, order_id, phone_number, sms_code, received_at 
FROM sms_orders 
WHERE status = 'completed' 
ORDER BY received_at DESC 
LIMIT 10;

-- Orders by provider
SELECT provider, COUNT(*) as total, 
       SUM(CASE WHEN sms_code IS NOT NULL THEN 1 ELSE 0 END) as with_codes
FROM sms_orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY provider;
```

---

## ⚙️ Configuration

### Change Polling Settings

**File**: `/var/www/fadsms.com/src/services/smsApi.ts`

```typescript
// Line 921
async pollForSmsCode(
  orderId: string, 
  maxAttempts: number = 30,  // ← Change max attempts
  interval: number = 2000     // ← Change interval (milliseconds)
)
```

**Recommendations**:
- **Interval**: 2-5 seconds (balance between UX and server load)
- **Max Attempts**: 30-60 (1-2 minutes total)
- **Don't go below 1 second** (unnecessary load on providers)

---

## 🎯 Provider API Status Codes

### Tiger SMS & 5SIM

| Response | Meaning | Action |
|----------|---------|--------|
| `STATUS_WAIT_CODE` | Waiting for SMS | Continue polling |
| `STATUS_OK:123456` | Code received | Extract and save |
| `STATUS_CANCEL` | Order cancelled | Stop polling |
| `STATUS_ACCESS_RETRY` | Retry allowed | Continue polling |

### Dassy / TextVerified / SmsPool

JSON-based responses with status field and code.

---

## 🧪 Testing Checklist

- [ ] Create SMS order with Tiger SMS
- [ ] Watch frontend auto-poll (every 2s)
- [ ] Receive SMS code automatically
- [ ] Check inbox for saved code
- [ ] Verify code displayed in UI
- [ ] Test with other providers (5SIM, Dassy, etc.)

---

## 📈 Success Metrics

**Your current system already has**:
- ✅ Automatic polling every 2 seconds
- ✅ Works with all 5 providers
- ✅ No manual checking needed
- ✅ Codes saved to database
- ✅ Inbox messages created
- ✅ Graceful timeout handling

---

## 💡 Best Practices

### For Users
1. Keep the page open while waiting for SMS (polling active)
2. Don't close browser while waiting
3. If page closed, check inbox for code

### For Developers
1. Monitor logs for failed polls
2. Check provider balance regularly
3. Review expired orders periodically
4. Test each provider monthly

---

## 🎉 Summary

**Question**: Do we have automatic SMS checking?  
**Answer**: **YES!** All 5 providers fully supported! ✅

**Method**:
- Frontend: Auto-polls every 2 seconds
- Backend: Queries each provider's API
- Database: Saves code when received
- Inbox: Creates message automatically

**No manual work needed - it's all automatic!** 🎉

---

## 📚 Related Files

**Backend**:
- `/var/www/api.fadsms.com/app/Http/Controllers/SmsController.php` - Main controller
- `/var/www/api.fadsms.com/app/Services/SmsProviderService.php` - Service coordinator
- `/var/www/api.fadsms.com/app/Services/Sms/Providers/*.php` - Provider implementations

**Frontend**:
- `/var/www/fadsms.com/src/services/smsApi.ts` - Polling logic
- `/var/www/fadsms.com/src/components/SmsModal.tsx` - UI (if exists)

**Models**:
- `/var/www/api.fadsms.com/app/Models/SmsOrder.php` - Order model
- `/var/www/api.fadsms.com/app/Models/SmsService.php` - Service model

