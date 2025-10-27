# FaddedSMS Reseller API Documentation

## 🚀 Welcome to FaddedSMS API v1

Build your own SMS verification and VTU panel using our powerful API. Perfect for resellers, developers, and businesses.

**Base URL**: `https://api.fadsms.com/api/v1`

---

## 🔑 Authentication

All API requests require an API key. You can generate API keys from your dashboard.

### Include API Key in Requests

**Option 1: HTTP Header (Recommended)**
```bash
curl -H "X-API-Key: fad_your_api_key_here" https://api.fadsms.com/api/v1/balance
```

**Option 2: Query Parameter**
```bash
curl "https://api.fadsms.com/api/v1/balance?api_key=fad_your_api_key_here"
```

---

## 📋 API Endpoints

### Account & Balance

#### Get Account Info
```http
GET /v1/info
```

**Response**:
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 5000.50,
    "api_key_name": "Production Key",
    "permissions": ["sms", "vtu"],
    "rate_limits": {
      "per_minute": 60,
      "per_day": 10000
    }
  }
}
```

#### Get Balance
```http
GET /v1/balance
```

**Response**:
```json
{
  "success": true,
  "data": {
    "balance": 5000.50,
    "currency": "NGN"
  }
}
```

---

### SMS Services

#### Purchase SMS Number
```http
POST /v1/sms/order
```

**Request Body**:
```json
{
  "country_code": "NG",
  "service": "whatsapp",
  "provider": "tiger_sms"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "order_id": "SMS_abc123xyz",
    "phone_number": "+2348012345678",
    "country_code": "NG",
    "service": "whatsapp",
    "cost": 150.00,
    "balance": 4850.50,
    "expires_at": "2025-10-07T22:15:00Z",
    "status": "active"
  },
  "message": "SMS number purchased successfully"
}
```

#### Get SMS Code
```http
POST /v1/sms/code
```

**Request Body**:
```json
{
  "order_id": "SMS_abc123xyz"
}
```

**Response** (Code Received):
```json
{
  "success": true,
  "data": {
    "order_id": "SMS_abc123xyz",
    "phone_number": "+2348012345678",
    "sms_code": "123456",
    "status": "completed",
    "received_at": "2025-10-07T22:10:00Z"
  }
}
```

**Response** (Waiting):
```json
{
  "success": true,
  "data": {
    "order_id": "SMS_abc123xyz",
    "phone_number": "+2348012345678",
    "sms_code": null,
    "status": "waiting",
    "message": "No SMS code received yet"
  }
}
```

---

### VTU Services

#### Purchase Airtime
```http
POST /v1/airtime
```

**Request Body**:
```json
{
  "network": "mtn",
  "phone": "08012345678",
  "amount": 500
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "reference": "AIR_xyz789abc",
    "network": "mtn",
    "phone": "08012345678",
    "amount": 500.00,
    "balance": 4500.50,
    "status": "success"
  },
  "message": "Airtime purchased successfully"
}
```

#### Purchase Data Bundle
```http
POST /v1/data
```

**Request Body**:
```json
{
  "network": "mtn",
  "phone": "08012345678",
  "plan_id": "mtn-1gb-30days",
  "amount": 300
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "reference": "DATA_xyz789abc",
    "network": "mtn",
    "phone": "08012345678",
    "plan_id": "mtn-1gb-30days",
    "amount": 300.00,
    "balance": 4200.50,
    "status": "success"
  },
  "message": "Data bundle purchased successfully"
}
```

#### Purchase Electricity
```http
POST /v1/electricity
```

**Request Body**:
```json
{
  "disco": "ikeja-electric",
  "meter_number": "12345678901",
  "meter_type": "prepaid",
  "amount": 5000
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "reference": "ELEC_abc123xyz",
    "disco": "ikeja-electric",
    "meter_number": "12345678901",
    "meter_type": "prepaid",
    "amount": 5000.00,
    "token": "12345-67890-12345-67890",
    "customer_name": "JOHN DOE",
    "balance": 3200.50,
    "status": "success"
  },
  "message": "Electricity purchased successfully"
}
```

**Response** (Processing - Timeout):
```json
{
  "success": true,
  "processing": true,
  "data": {
    "reference": "ELEC_abc123xyz",
    "status": "processing",
    "amount": 5000.00,
    "balance": 3200.50,
    "message": "Request sent to provider, token will be delivered when ready"
  },
  "message": "Electricity purchase is processing. Check status in 5-10 minutes."
}
```

---

### Transactions

#### Get Transactions
```http
GET /v1/transactions?limit=50&page=1
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "type": "service_purchase",
      "amount": 500.00,
      "description": "Airtime: mtn (08012345678) via API",
      "reference": "AIR_xyz789abc",
      "status": "success",
      "created_at": "2025-10-07T22:00:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 156,
    "pages": 4
  }
}
```

#### Get Transaction by Reference
```http
GET /v1/transactions/{reference}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "type": "service_purchase",
    "amount": 500.00,
    "description": "Airtime: mtn (08012345678) via API",
    "reference": "AIR_xyz789abc",
    "status": "success",
    "metadata": {
      "category": "airtime",
      "network": "mtn",
      "phone": "08012345678",
      "api_purchase": true
    },
    "created_at": "2025-10-07T22:00:00Z"
  }
}
```

---

## ⚠️ Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "error": "API key required",
  "message": "Please provide API key in X-API-Key header or api_key parameter"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "error": "Insufficient permissions",
  "message": "This API key does not have 'sms' permission"
}
```

### 429 Rate Limit
```json
{
  "success": false,
  "error": "Rate limit exceeded",
  "message": "You have exceeded the API rate limit. Please try again later."
}
```

### 400 Bad Request
```json
{
  "success": false,
  "error": "Insufficient balance",
  "message": "Your account balance is insufficient for this purchase",
  "required": 500.00,
  "available": 350.00
}
```

### 422 Validation Error
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "phone": ["The phone field is required"],
    "amount": ["The amount must be at least 50"]
  }
}
```

---

## 🔐 API Key Management

Manage your API keys through authenticated endpoints:

### List API Keys
```http
GET /api/api-keys
Authorization: Bearer {your_auth_token}
```

### Create API Key
```http
POST /api/api-keys
Authorization: Bearer {your_auth_token}

Body:
{
  "name": "Production Key",
  "permissions": ["sms", "vtu"],
  "rate_limit_per_minute": 60,
  "rate_limit_per_day": 10000,
  "ip_whitelist": "1.2.3.4,5.6.7.8"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Production Key",
    "key": "fad_abc123xyz789...",
    "secret": "secret_xyz789abc...",
    "is_active": true,
    "permissions": ["sms", "vtu"],
    "rate_limit_per_minute": 60,
    "rate_limit_per_day": 10000,
    "created_at": "2025-10-07T22:00:00Z"
  },
  "message": "API key created successfully. Keep your key and secret safe!"
}
```

### Delete API Key
```http
DELETE /api/api-keys/{id}
Authorization: Bearer {your_auth_token}
```

---

## 📊 Rate Limits

Default rate limits per API key:
- **Per Minute**: 60 requests
- **Per Day**: 10,000 requests

Rate limits can be customized per API key.

---

## 🔒 Security Features

1. **API Key Authentication**: All requests require valid API key
2. **IP Whitelisting**: Restrict API keys to specific IP addresses
3. **Permissions**: Grant specific permissions (sms, vtu, etc.)
4. **Rate Limiting**: Prevent abuse with configurable limits
5. **Usage Logging**: All API calls are logged for audit
6. **Auto-expiration**: Set expiration dates for API keys

---

## 💡 Example Code

### PHP
```php
<?php

$apiKey = 'fad_your_api_key_here';
$baseUrl = 'https://api.fadsms.com/api/v1';

// Get balance
$ch = curl_init("{$baseUrl}/balance");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: {$apiKey}"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
echo "Balance: ₦" . $data['data']['balance'];

// Purchase airtime
$ch = curl_init("{$baseUrl}/airtime");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'network' => 'mtn',
    'phone' => '08012345678',
    'amount' => 500
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
print_r($data);
?>
```

### JavaScript
```javascript
const apiKey = 'fad_your_api_key_here';
const baseUrl = 'https://api.fadsms.com/api/v1';

// Get balance
async function getBalance() {
  const response = await fetch(`${baseUrl}/balance`, {
    headers: {
      'X-API-Key': apiKey
    }
  });
  const data = await response.json();
  console.log('Balance:', data.data.balance);
}

// Purchase SMS number
async function purchaseSms() {
  const response = await fetch(`${baseUrl}/sms/order`, {
    method: 'POST',
    headers: {
      'X-API-Key': apiKey,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      country_code: 'NG',
      service: 'whatsapp',
      provider: 'tiger_sms'
    })
  });
  const data = await response.json();
  console.log('Order:', data.data);
  return data.data.order_id;
}

// Poll for SMS code
async function pollForCode(orderId) {
  const maxAttempts = 30;
  for (let i = 0; i < maxAttempts; i++) {
    const response = await fetch(`${baseUrl}/sms/code`, {
      method: 'POST',
      headers: {
        'X-API-Key': apiKey,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ order_id: orderId })
    });
    const data = await response.json();
    
    if (data.data.sms_code) {
      console.log('SMS Code:', data.data.sms_code);
      return data.data.sms_code;
    }
    
    await new Promise(r => setTimeout(r, 2000)); // Wait 2 seconds
  }
  throw new Error('SMS code not received');
}
```

### Python
```python
import requests
import time

api_key = 'fad_your_api_key_here'
base_url = 'https://api.fadsms.com/api/v1'

headers = {
    'X-API-Key': api_key,
    'Content-Type': 'application/json'
}

# Get balance
response = requests.get(f'{base_url}/balance', headers=headers)
data = response.json()
print(f"Balance: ₦{data['data']['balance']}")

# Purchase airtime
payload = {
    'network': 'mtn',
    'phone': '08012345678',
    'amount': 500
}
response = requests.post(f'{base_url}/airtime', json=payload, headers=headers)
data = response.json()
print(data)

# Purchase SMS number
payload = {
    'country_code': 'NG',
    'service': 'whatsapp',
    'provider': 'tiger_sms'
}
response = requests.post(f'{base_url}/sms/order', json=payload, headers=headers)
order = response.json()['data']

# Poll for SMS code
for i in range(30):
    response = requests.post(
        f'{base_url}/sms/code',
        json={'order_id': order['order_id']},
        headers=headers
    )
    data = response.json()
    if data['data']['sms_code']:
        print(f"SMS Code: {data['data']['sms_code']}")
        break
    time.sleep(2)
```

---

## 🎯 Use Cases

### 1. SMS Verification Service
Build your own SMS verification platform:
- Purchase numbers via API
- Poll for codes automatically
- Provide to your customers

### 2. Bulk Airtime/Data Reseller
Create automated VTU system:
- Integrate with your website
- Auto-process orders
- Track all transactions

### 3. White-Label Panel
Build custom dashboard for your brand:
- Use our API as backend
- Create your own UI
- Full control over user experience

### 4. Mobile App Integration
Integrate services into mobile apps:
- iOS, Android, React Native
- RESTful API
- Real-time updates

---

## 📊 Pricing

API usage is charged from your account balance:
- **SMS Numbers**: Same as website pricing
- **Airtime**: Same as website pricing
- **Data**: Same as website pricing
- **Electricity**: Same as website pricing

**No additional API fees** - only service costs!

---

## 🛡️ Best Practices

### Security
1. **Never expose API keys** in client-side code
2. **Use IP whitelisting** for production keys
3. **Rotate keys regularly**
4. **Use separate keys** for development and production
5. **Monitor usage** for suspicious activity

### Performance
1. **Implement caching** on your end
2. **Use exponential backoff** for retries
3. **Handle rate limits** gracefully
4. **Poll SMS codes** every 2-5 seconds (not faster)
5. **Batch operations** when possible

### Reliability
1. **Handle errors** gracefully
2. **Check balance** before purchases
3. **Store references** for tracking
4. **Implement webhooks** for real-time updates (coming soon)
5. **Log all transactions** on your end

---

## 📈 API Usage Statistics

Get detailed usage stats from your dashboard:
```http
GET /api/api-keys/usage-stats?days=7
Authorization: Bearer {your_auth_token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "daily_stats": [
      {
        "date": "2025-10-07",
        "total_requests": 1523,
        "successful_requests": 1498,
        "failed_requests": 25,
        "avg_response_time": 234
      }
    ],
    "top_endpoints": [
      {
        "endpoint": "v1/sms/code",
        "requests": 856,
        "avg_response_time": 189
      }
    ],
    "period_days": 7
  }
}
```

---

## 🚀 Getting Started

### Step 1: Create API Key
1. Log in to your account at https://fadsms.com
2. Go to Settings → API Keys
3. Click "Create New API Key"
4. Copy your API key and secret

### Step 2: Fund Your Account
Ensure you have sufficient balance for purchases

### Step 3: Make Your First Request
```bash
curl -H "X-API-Key: fad_your_key" \
     https://api.fadsms.com/api/v1/balance
```

### Step 4: Integrate
Use the code examples above to integrate into your application

---

## 📞 Support

- **Documentation**: https://api.fadsms.com/docs
- **Email**: support@fadsms.com
- **API Status**: https://status.fadsms.com

---

## 🔄 Changelog

### v1.0.0 (October 7, 2025)
- ✅ Initial API release
- ✅ SMS number purchasing
- ✅ Airtime, Data, Electricity purchases
- ✅ Transaction history
- ✅ Rate limiting
- ✅ Usage tracking
- ✅ IP whitelisting

---

## ⚡ Quick Reference

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/v1/info` | GET | API Key | Get account info |
| `/v1/balance` | GET | API Key | Get balance |
| `/v1/sms/order` | POST | API Key | Buy SMS number |
| `/v1/sms/code` | POST | API Key | Get SMS code |
| `/v1/airtime` | POST | API Key | Buy airtime |
| `/v1/data` | POST | API Key | Buy data |
| `/v1/electricity` | POST | API Key | Buy electricity |
| `/v1/transactions` | GET | API Key | List transactions |
| `/api/api-keys` | GET | Bearer Token | List API keys |
| `/api/api-keys` | POST | Bearer Token | Create API key |

---

**Start building with FaddedSMS API today!** 🚀

