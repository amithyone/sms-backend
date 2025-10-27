# FaddedSMS API - Quick Start Guide

## 🚀 5-Minute Setup

Get started with FaddedSMS API in just 5 minutes!

---

## Step 1: Create API Key (1 minute)

### Via Web Dashboard (Coming Soon)
1. Log in to https://fadsms.com
2. Go to Settings → API Keys  
3. Click "Create New API Key"
4. Copy your key

### Via API (Right Now)
```bash
# Login first to get auth token
curl -X POST https://api.fadsms.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your@email.com",
    "password": "your_password"
  }'

# Create API key
curl -X POST https://api.fadsms.com/api/api-keys \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "name": "My First API Key",
    "permissions": ["sms", "vtu"]
  }'
```

**Save your API key!** You'll need it for all requests.

---

## Step 2: Test Connection (30 seconds)

```bash
# Replace with your actual API key
API_KEY="fad_your_api_key_here"

# Test 1: Get your balance
curl -H "X-API-Key: $API_KEY" \
     https://api.fadsms.com/api/v1/balance

# Expected response:
# {
#   "success": true,
#   "data": {
#     "balance": 5000.00,
#     "currency": "NGN"
#   }
# }
```

---

## Step 3: Make Your First Purchase (2 minutes)

### Purchase Airtime
```bash
curl -X POST https://api.fadsms.com/api/v1/airtime \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "network": "mtn",
    "phone": "08012345678",
    "amount": 100
  }'
```

### Purchase SMS Number
```bash
curl -X POST https://api.fadsms.com/api/v1/sms/order \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "country_code": "NG",
    "service": "whatsapp",
    "provider": "tiger_sms"
  }'

# Save the order_id and phone_number from response
```

### Get SMS Code
```bash
# Poll for SMS code (run multiple times)
curl -X POST https://api.fadsms.com/api/v1/sms/code \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "SMS_abc123xyz"
  }'
```

---

## Step 4: View Transactions (30 seconds)

```bash
# Get recent transactions
curl -H "X-API-Key: $API_KEY" \
     "https://api.fadsms.com/api/v1/transactions?limit=10"
```

---

## ✅ You're Done!

You now have a working API integration. Build anything:
- SMS verification service
- Airtime/data reseller
- Electricity bill payment
- White-label panel

---

## 🔥 Common Use Cases

### 1. Automate SMS Verification

```javascript
async function verifySms(service, countryCode) {
  // Step 1: Purchase number
  const order = await fetch('https://api.fadsms.com/api/v1/sms/order', {
    method: 'POST',
    headers: {
      'X-API-Key': API_KEY,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      country_code: countryCode,
      service: service,
      provider: 'tiger_sms'
    })
  }).then(r => r.json());
  
  const phoneNumber = order.data.phone_number;
  
  // Step 2: Use phone number for verification on target service
  console.log(`Use this number: ${phoneNumber}`);
  
  // Step 3: Poll for SMS code (every 2 seconds)
  for (let i = 0; i < 30; i++) {
    const code = await fetch('https://api.fadsms.com/api/v1/sms/code', {
      method: 'POST',
      headers: {
        'X-API-Key': API_KEY,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ order_id: order.data.order_id })
    }).then(r => r.json());
    
    if (code.data.sms_code) {
      return code.data.sms_code;
    }
    
    await new Promise(r => setTimeout(r, 2000));
  }
  
  throw new Error('SMS code not received');
}

// Usage
verifySms('whatsapp', 'NG').then(code => {
  console.log(`Verification code: ${code}`);
});
```

### 2. Bulk Airtime Reseller

```php
function purchaseBulkAirtime($recipients, $apiKey) {
    $results = [];
    
    foreach ($recipients as $recipient) {
        $ch = curl_init('https://api.fadsms.com/api/v1/airtime');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'network' => $recipient['network'],
            'phone' => $recipient['phone'],
            'amount' => $recipient['amount']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $results[] = json_decode($response, true);
    }
    
    return $results;
}

// Usage
$recipients = [
    ['network' => 'mtn', 'phone' => '08012345678', 'amount' => 100],
    ['network' => 'airtel', 'phone' => '08087654321', 'amount' => 200],
];

$results = purchaseBulkAirtime($recipients, 'fad_your_api_key');
```

---

## 🎯 Next Steps

1. **Read Full Documentation**: `/var/www/api.fadsms.com/API_DOCUMENTATION.md`
2. **Test Endpoints**: Use Postman or curl
3. **Build Integration**: Use code examples
4. **Monitor Usage**: Check API usage stats
5. **Go Live**: Start serving customers!

---

## 📚 Resources

- **Full API Docs**: `API_DOCUMENTATION.md`
- **Base URL**: `https://api.fadsms.com/api/v1`
- **Support**: support@fadsms.com

---

## 💡 Pro Tips

1. **Always check balance** before making purchases
2. **Poll SMS codes** every 2-3 seconds (not too fast)
3. **Store transaction references** for tracking
4. **Use try-catch** for error handling
5. **Implement retry logic** for network errors
6. **Cache API responses** when appropriate
7. **Monitor rate limits** to avoid throttling

---

**Happy building!** 🚀

If you need help, check the full documentation or contact support.
