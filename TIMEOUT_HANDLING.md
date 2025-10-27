# Timeout Handling for VTU.ng Electricity Purchases

## Problem
When VTU.ng API times out with errors like "Operation too slow. Less than 1 bytes/sec transferred the last 15 seconds", the service is usually still processing the request on their end. Previously, these were marked as "failed" transactions, but they often complete successfully later.

## Solution Implemented

### Backend Changes

#### 1. VtuNgService.php
- **Detection**: Added logic to detect timeout errors in exception handler
- **Keywords detected**: "timeout", "too slow", "less than", "transferred the last", "timed out", "connection timeout"
- **Response**: Returns special `processing` flag with status instead of marking as failed
- **Log level**: Changed from ERROR to WARNING for timeout scenarios

#### 2. VtuController.php - purchaseElectricity()
Added new handling for `processing` status:
- **Balance**: Still deducted (since request was sent to VTU.ng)
- **VTU Order Status**: Marked as `processing` instead of `failed`
- **Transaction Status**: Marked as `pending` instead of `failed`
- **Inbox Message**: Automatically created to notify user about processing status
- **Response**: Returns HTTP 200 with `success: true` and `processing: true`
- **Message**: "Request received and processing. Due to provider timeout, your electricity token will be delivered to your inbox within 5-10 minutes."

### Frontend Changes (ElectricityModal.tsx)

#### 1. Response Handling
- Detects `processing` flag from API response
- Shows appropriate success message for processing status
- Displays receipt with processing status

#### 2. Receipt Display
- **Icon**: Orange spinning refresh icon (instead of green checkmark)
- **Title**: "Request Processing" (instead of "Payment Successful!")
- **Color**: Orange theme (instead of green)
- **Token Section**: Shows processing message with animated spinner
- **Copy Button**: Disabled with "Processing..." text
- **Additional Info**: "Token will be delivered to your inbox shortly"

## User Experience Flow

### Successful Purchase (No Timeout)
1. User enters meter details and amount
2. Backend processes immediately
3. Token returned instantly
4. Green success receipt with token displayed

### Timeout Scenario (NEW)
1. User enters meter details and amount
2. Backend sends request to VTU.ng
3. VTU.ng times out but is still processing
4. Orange "Processing" receipt displayed
5. User balance deducted
6. Inbox message created
7. User told to check inbox in 5-10 minutes
8. When VTU.ng completes, token delivered via webhook/manual check

## Database Records

### Processing Transactions
- **transactions.status**: `pending` (not `failed`)
- **transactions.description**: Includes "- PROCESSING" suffix
- **transactions.metadata.needs_status_check**: `true`
- **vtu_orders.status**: `processing`

### Inbox Messages
- **Type**: `electricity`
- **Title**: "Electricity Purchase Processing"
- **Message**: Explains timeout and estimated completion time
- **Reference**: Included for tracking

## Benefits

1. **Better UX**: Users know their request is being processed, not failed
2. **Reduced Support**: Fewer "where's my token?" inquiries
3. **Accurate Records**: Transactions marked as pending, not failed
4. **Balance Tracking**: Correct balance deduction since request was sent
5. **Automatic Notification**: Inbox message keeps user informed

## Future Enhancements

1. **Status Check Endpoint**: Add API to manually check VTU.ng transaction status
2. **Automatic Retry**: Background job to check processing transactions after 10 minutes
3. **Webhook Handler**: Update transaction when VTU.ng sends delayed success callback
4. **Push Notifications**: Alert user when token arrives

## Testing

To test timeout handling:
1. Temporarily reduce HTTP timeout in VtuNgService
2. Or use VTU.ng test mode with slow responses
3. Verify orange "Processing" receipt appears
4. Check inbox for notification message
5. Verify transaction marked as `pending` not `failed`

## Error Keywords Detected

The system detects these error message patterns as timeouts:
- "timeout"
- "too slow"
- "less than"
- "transferred the last"
- "timed out"  
- "connection timeout"

All are case-insensitive matches.

## Automatic Status Checking (NEW)

### Background Job
A Laravel command runs **every 5 minutes** to automatically check the status of processing electricity transactions:

**Command**: `php artisan electricity:check-processing`

**What it does**:
1. Finds all electricity transactions with status = `processing`
2. Queries VTU.ng API for each transaction's current status
3. If completed, updates the database and sends token to user
4. If still processing, logs and waits for next check

**Schedule**: Every 5 minutes via Laravel Scheduler
**Cron Job**: `* * * * * cd /var/www/api.fadsms.com && php artisan schedule:run`

### What Gets Updated

When a processing transaction completes:
1. **VTU Order**: Status changed from `processing` to `completed`
2. **Transaction**: Status changed from `pending` to `success`
3. **Inbox Message**: New message created with token details
4. **SMS Notification**: Token sent via SMS to user
5. **Transaction Metadata**: Token, units, and customer name added

### Manual Checking

You can manually run the check anytime:
```bash
cd /var/www/api.fadsms.com
php artisan electricity:check-processing
```

### Monitoring

Check the logs to see the status checks:
```bash
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep "Electricity transaction"
```

## Complete Flow Diagram

### Timeout Scenario (Complete Flow)

```
User submits electricity purchase
        ↓
Backend sends request to VTU.ng
        ↓
VTU.ng times out (but is processing)
        ↓
Backend detects "Operation too slow" error
        ↓
Marks as PROCESSING (not failed)
        ↓
Deducts user balance
        ↓
Creates pending transaction record
        ↓
Creates inbox message: "Processing"
        ↓
Returns success=true, processing=true to frontend
        ↓
Frontend shows orange "Processing" receipt
        ↓
[Every 5 minutes]
Background job checks VTU.ng status
        ↓
Token ready? YES
        ↓
Updates transaction to SUCCESS
        ↓
Creates inbox message with token
        ↓
Sends SMS with token
        ↓
User receives token in inbox & SMS! ✓
```

## Testing the Complete System

### 1. Test Timeout Detection
The next time you get a timeout error from VTU.ng, the system should:
- Show orange "Processing" receipt in frontend
- Log: "VTU.ng electricity purchase timeout - likely processing"
- Not show "Electricity purchase failed" error

### 2. Test Background Job
After a timeout occurs:
```bash
# Wait 5 minutes or run manually
php artisan electricity:check-processing

# Check if token was retrieved and sent
# Look in inbox_messages table for the token
```

### 3. Monitor Logs
```bash
# Watch for timeout detection
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep "timeout - likely processing"

# Watch for successful updates
tail -f /var/www/api.fadsms.com/storage/logs/laravel.log | grep "Transaction.*completed"
```

## Database Queries for Monitoring

```sql
-- Find processing transactions
SELECT * FROM vtu_orders 
WHERE service_type = 'electricity' 
AND status = 'processing' 
ORDER BY created_at DESC;

-- Find transactions that need status check
SELECT * FROM transactions 
WHERE status = 'pending' 
AND JSON_EXTRACT(metadata, '$.needs_status_check') = true
ORDER BY created_at DESC;

-- Count processing vs completed
SELECT status, COUNT(*) 
FROM vtu_orders 
WHERE service_type = 'electricity' 
GROUP BY status;
```

## Troubleshooting

### If tokens are not being retrieved:

1. **Check cron is running**:
   ```bash
   crontab -l
   # Should show: * * * * * cd /var/www/api.fadsms.com && php artisan schedule:run
   ```

2. **Run command manually**:
   ```bash
   php artisan electricity:check-processing -v
   ```

3. **Check VTU.ng credentials**:
   ```bash
   php artisan tinker
   >>> app(\App\Services\VtuNgService::class)->getBalance()
   ```

4. **Check logs**:
   ```bash
   tail -50 /var/www/api.fadsms.com/storage/logs/laravel.log
   ```

## Success Metrics

With this system, you should see:
- ✅ Fewer "failed" transactions (only real failures)
- ✅ More "pending" → "success" transitions
- ✅ Tokens delivered even after timeouts
- ✅ Better user experience (no confusion)
- ✅ Fewer support tickets about missing tokens

