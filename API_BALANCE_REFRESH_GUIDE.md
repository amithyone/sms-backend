# API Service Balance Refresh - Guide

## ✅ Feature: Pull Balance from API Providers

Admins can now refresh the actual balance from each API service provider (SMS and VTU) to keep the database synchronized with real provider balances.

---

## 🔗 API Endpoints

### 1. Refresh SMS Provider Balance
```
POST /api/admin/services/sms/{id}/refresh-balance
```

**Example:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/services/sms/1/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
  "status": "success",
  "message": "SMS provider balance refreshed successfully",
  "data": {
    "provider": "5sim",
    "name": "5Sim Premium",
    "balance": 0.00
  }
}
```

---

### 2. Refresh VTU Provider Balance
```
POST /api/admin/services/vtu/{id}/refresh-balance
```

**Example:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/services/vtu/1/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
  "status": "success",
  "message": "VTU provider balance refreshed successfully",
  "data": {
    "provider": "vtu_ng",
    "name": "VTU.ng Premium",
    "balance": 3967.00
  }
}
```

---

### 3. Test SMS Provider Connection
```
POST /api/admin/services/sms/{id}/test
```

**Example:**
```bash
curl -X POST "https://api.fadsms.com/api/admin/services/sms/2/test" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
  "status": "success",
  "message": "SMS provider connection successful",
  "data": {
    "provider": "dassy",
    "name": "FADDED USA ONLY",
    "balance": 522.22,
    "countries_available": 45,
    "connection": "working"
  }
}
```

---

## 📊 Current Balances (Live)

### SMS Providers:
```
5Sim Premium:     ₦0.00
FADDED USA ONLY:  ₦522.22  ✅ (has balance)
Tiger SMS:        ₦47.96   ✅ (has balance)
TextVerified:     ₦0.00
SMSPool:          Inactive
```

### VTU Providers:
```
VTU.ng Premium:   ₦3,967.00  ✅ (has balance)
iRecharge VTU:    Inactive
```

---

## 🎯 How It Works

### Balance Refresh Process:
1. Admin clicks "Refresh Balance" button in dashboard
2. Backend calls provider's API to get current balance
3. Provider returns balance in their currency (USD, RUB, etc.)
4. System converts to NGN if needed
5. Database is updated with current balance
6. Admin sees updated balance immediately

### Supported Providers:

**SMS Providers:**
- ✅ 5Sim - Fetches from https://5sim.net/v1/user/profile
- ✅ Dassy - Fetches from API
- ✅ Tiger SMS - Fetches from API
- ✅ TextVerified - Fetches from API
- ✅ SMSPool - Fetches from API

**VTU Providers:**
- ✅ VTU.ng - Fetches from VTU.ng API
- 🔄 iRecharge - Can be added

---

## 💻 Frontend Integration

### Add Refresh Button to API Services Table

**JavaScript Function:**
```javascript
async function refreshServiceBalance(serviceId, type) {
  const endpoint = type === 'SMS' 
    ? `/api/admin/services/sms/${serviceId}/refresh-balance`
    : `/api/admin/services/vtu/${serviceId}/refresh-balance`;
  
  try {
    showToast('Refreshing balance...', 'info');
    
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const result = await response.json();
    
    if (result.status === 'success') {
      showToast(`✅ Balance updated: ₦${result.data.balance.toLocaleString()}`, 'success');
      loadServices(); // Reload services list
    } else {
      showToast('❌ ' + (result.message || 'Failed to refresh balance'), 'error');
    }
  } catch (error) {
    showToast('❌ Network error', 'error');
  }
}
```

### Add Button to Table Row:
```html
<button onclick="refreshServiceBalance(${service.id}, '${service.type}')" 
  class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-2 py-1 rounded">
  🔄 Refresh
</button>
```

---

## 🧪 Testing

### Test SMS Balance Refresh:
```bash
# Refresh 5Sim balance
curl -X POST "https://api.fadsms.com/api/admin/services/sms/1/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Refresh Dassy balance
curl -X POST "https://api.fadsms.com/api/admin/services/sms/2/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Refresh Tiger SMS balance
curl -X POST "https://api.fadsms.com/api/admin/services/sms/3/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Test VTU Balance Refresh:
```bash
# Refresh VTU.ng balance
curl -X POST "https://api.fadsms.com/api/admin/services/vtu/1/refresh-balance" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

---

## 📱 Admin Dashboard Integration

The admin dashboard already has the routes, so you just need to add the UI buttons:

### Option 1: Add to Service Table
```html
<!-- In the Services section -->
<td class="px-4 py-2">
  <button onclick="refreshServiceBalance(${s.id}, '${s.type}')" 
    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded mr-1">
    🔄 Refresh Balance
  </button>
  <button onclick="saveService(${s.id}, '${s.type}')" 
    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded">
    Save
  </button>
</td>
```

### Option 2: Add to Mobile Cards
```html
<!-- In mobile service cards -->
<div class="pt-3 border-t border-slate-200 flex gap-2">
  <button onclick="refreshServiceBalance(${s.id}, '${s.type}')" 
    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
    🔄 Refresh Balance
  </button>
  <button onclick="saveServiceCard(${s.id}, '${s.type}')" 
    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
    Save Changes
  </button>
</div>
```

---

## 🔧 What Was Implemented

### Backend Methods Added to AdminController:

1. **`refreshSmsProviderBalance($id)`**
   - Fetches balance from SMS provider API
   - Updates database
   - Returns new balance

2. **`refreshVtuProviderBalance($id)`**
   - Fetches balance from VTU.ng API
   - Updates database
   - Returns new balance

3. **`testSmsProvider($id)`**
   - Tests SMS provider connection
   - Fetches balance and countries
   - Returns connection status

### Routes Already Configured:
```
POST /api/admin/services/sms/{id}/refresh-balance
POST /api/admin/services/sms/{id}/test
POST /api/admin/services/vtu/{id}/refresh-balance
```

---

## 📊 Live Test Results

**Tested Successfully:**

| Service | Provider | Old Balance | New Balance | Status |
|---------|----------|-------------|-------------|--------|
| 5Sim Premium | 5sim | ₦0.00 | ₦0.00 | ✅ Working |
| FADDED USA ONLY | dassy | ₦0.00 | **₦522.22** | ✅ Has funds! |
| Tiger SMS | tiger_sms | ₦0.00 | **₦47.96** | ✅ Has funds! |
| TextVerified | textverified | ₦0.00 | ₦0.00 | ✅ Working |
| VTU.ng Premium | vtu_ng | ₦3,967.00 | **₦3,967.00** | ✅ Has funds! |

**Working Providers with Funds:**
- ✅ Dassy: ₦522.22
- ✅ Tiger SMS: ₦47.96
- ✅ VTU.ng: ₦3,967.00

---

## 🚀 Usage

### From Admin Dashboard:

1. **Go to API Services Tab**
   - Click "🔧 API Services" in sidebar

2. **View Current Balances**
   - See all SMS and VTU providers
   - Check balance column

3. **Refresh Balance**
   - Click "🔄 Refresh Balance" button (when UI is added)
   - Balance fetched from provider in real-time
   - Database updated automatically

4. **Monitor Provider Funds**
   - Know when to top up providers
   - Avoid service failures due to low balance

---

## 💡 Automated Balance Monitoring

You can set up a cron job to auto-refresh balances:

### Create Cron Script:
```bash
# /var/www/api.fadsms.com/refresh_balances_cron.php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\SmsService::where('is_active', true)->get() as $sms) {
    try {
        $balance = app(\App\Services\SmsProviderService::class)->getBalance($sms);
        $sms->update(['balance' => $balance]);
    } catch (\Exception $e) {
        \Log::error("Balance refresh failed for {$sms->name}: {$e->getMessage()}");
    }
}

foreach (App\Models\VtuService::where('is_active', true)->get() as $vtu) {
    if ($vtu->provider === 'vtu_ng') {
        try {
            $result = app(\App\Services\VtuNgService::class)->getBalance();
            if ($result['success']) {
                $vtu->update(['balance' => $result['balance']]);
            }
        } catch (\Exception $e) {
            \Log::error("VTU balance refresh failed for {$vtu->name}: {$e->getMessage()}");
        }
    }
}
```

### Add to Crontab:
```bash
# Run every hour
0 * * * * cd /var/www/api.fadsms.com && php refresh_balances_cron.php
```

---

## 🎯 Summary

**Features Added:**
1. ✅ Refresh SMS provider balances from API
2. ✅ Refresh VTU provider balances from API
3. ✅ Test SMS provider connection
4. ✅ Automatic database updates
5. ✅ Error handling and logging

**Current Live Balances:**
- Dassy (SMS): **₦522.22** ✅
- Tiger SMS: **₦47.96** ✅
- VTU.ng: **₦3,967.00** ✅

**Endpoints Ready:**
- `POST /api/admin/services/sms/{id}/refresh-balance`
- `POST /api/admin/services/sms/{id}/test`
- `POST /api/admin/services/vtu/{id}/refresh-balance`

**Frontend:** Just add the refresh button to the API Services page!

---

## 📖 Documentation

**Full Guide:** `/var/www/api.fadsms.com/API_BALANCE_REFRESH_GUIDE.md`

**Backend is ready to pull balances from all API services!** 🚀

