<?php

/**
 * Simple API Test Runner
 * Run this script to test all API endpoints
 */

echo "🚀 API Testing Script for VTU and SMS Services\n";
echo "==============================================\n\n";

// Configuration
$baseUrl = 'http://localhost:8000/api';
$testEmail = 'test@example.com';
$testPassword = 'password123';

// Test credentials (you can modify these)
$credentials = [
    'email' => $testEmail,
    'password' => $testPassword
];

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    $headers = array_merge($defaultHeaders, $headers);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    $decoded = json_decode($response, true);
    return [
        'data' => $decoded ?: $response,
        'http_code' => $httpCode,
        'raw' => $response
    ];
}

/**
 * Test endpoint and display result
 */
function testEndpoint($method, $endpoint, $description, $data = null, $headers = []) {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $result = makeRequest($method, $url, $data, $headers);
    
    $status = '❌';
    if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
        $status = '✅';
    } elseif ($result['http_code'] >= 400 && $result['http_code'] < 500) {
        $status = '⚠️'; // Expected error (like insufficient balance)
    }
    
    echo "{$status} {$description}\n";
    echo "   {$method} {$endpoint}\n";
    echo "   HTTP {$result['http_code']}\n";
    
    if (isset($result['data']['message'])) {
        echo "   Message: {$result['data']['message']}\n";
    }
    
    if (isset($result['data']['success'])) {
        echo "   Success: " . ($result['data']['success'] ? 'true' : 'false') . "\n";
    }
    
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    
    echo "\n";
    
    return $result;
}

// Start testing
echo "📡 Testing Public Endpoints...\n";
echo "==============================\n\n";

// Test basic connectivity
testEndpoint('GET', '/test', 'API Test Endpoint');
testEndpoint('GET', '/cors-test', 'CORS Test');
testEndpoint('GET', '/health/quick', 'Quick Health Check');

// Test VTU public endpoints
testEndpoint('GET', '/vtu/services', 'VTU Services List');
testEndpoint('GET', '/vtu/airtime/networks', 'Airtime Networks');
testEndpoint('GET', '/vtu/data/networks', 'Data Networks');
testEndpoint('GET', '/vtu/variations/data?network=mtn', 'MTN Data Bundles');
testEndpoint('GET', '/betting/providers', 'Betting Providers');
testEndpoint('GET', '/electricity/providers', 'Electricity Providers');

// Test SMS public endpoints
testEndpoint('GET', '/sms/providers', 'SMS Providers');
testEndpoint('GET', '/sms/countries', 'SMS Countries');
testEndpoint('GET', '/sms/services?country=187', 'SMS Services for USA');

echo "🔐 Testing Authentication...\n";
echo "============================\n\n";

// Test login
$loginResult = testEndpoint('POST', '/login', 'User Login', $credentials);

$authToken = null;
if (isset($loginResult['data']['data']['token'])) {
    $authToken = $loginResult['data']['data']['token'];
    echo "✅ Authentication successful - Token obtained\n\n";
} else {
    echo "❌ Authentication failed - Testing public endpoints only\n\n";
}

if ($authToken) {
    $authHeaders = ['Authorization: Bearer ' . $authToken];
    
    echo "💰 Testing VTU Protected Endpoints...\n";
    echo "=====================================\n\n";
    
    // Test phone validation
    testEndpoint('POST', '/vtu/validate/phone', 'Phone Validation', [
        'phone' => '08012345678',
        'network' => 'mtn'
    ], $authHeaders);
    
    // Test airtime purchase (will likely fail due to insufficient balance)
    testEndpoint('POST', '/vtu/airtime/purchase', 'Airtime Purchase', [
        'network' => 'mtn',
        'phone' => '08012345678',
        'amount' => 100
    ], $authHeaders);
    
    // Test data bundle purchase
    testEndpoint('POST', '/vtu/data/purchase', 'Data Bundle Purchase', [
        'network' => 'mtn',
        'phone' => '08012345678',
        'plan' => 'mtn_1GB',
        'plan_name' => '1GB Daily',
        'amount' => 300
    ], $authHeaders);
    
    // Test transaction endpoints
    testEndpoint('GET', '/vtu/transactions', 'User Transactions', null, $authHeaders);
    testEndpoint('GET', '/transactions', 'All Transactions', null, $authHeaders);
    
    echo "📱 Testing SMS Protected Endpoints...\n";
    echo "=====================================\n\n";
    
    // Test SMS order creation
    testEndpoint('POST', '/sms/order', 'SMS Order Creation', [
        'country' => '187',
        'service' => 'wa',
        'mode' => 'auto'
    ], $authHeaders);
    
    // Test SMS orders list
    testEndpoint('GET', '/sms/orders', 'SMS Orders List', null, $authHeaders);
    
    // Test SMS statistics
    testEndpoint('GET', '/sms/stats', 'SMS Statistics', null, $authHeaders);
    
    echo "💳 Testing Wallet Endpoints...\n";
    echo "==============================\n\n";
    
    // Test wallet endpoints
    testEndpoint('GET', '/wallet/deposits', 'Recent Deposits', null, $authHeaders);
    testEndpoint('POST', '/wallet/topup/initiate', 'Initiate Top-up', [
        'amount' => 1000,
        'payment_method' => 'payvibe'
    ], $authHeaders);
}

echo "🏥 Testing Health Check Endpoints...\n";
echo "====================================\n\n";

// Test comprehensive health check
testEndpoint('GET', '/health', 'Full Health Check');
testEndpoint('GET', '/health/endpoints', 'API Endpoints List');

echo "✅ API Testing Complete!\n";
echo "========================\n\n";

echo "📋 Summary:\n";
echo "- Tested public endpoints (VTU services, SMS providers, etc.)\n";
echo "- Tested authentication system\n";
echo "- Tested protected endpoints (purchases, orders, transactions)\n";
echo "- Tested health check endpoints\n\n";

echo "💡 Notes:\n";
echo "- Some purchase endpoints may fail due to insufficient balance (expected)\n";
echo "- Check the health endpoint for detailed service status\n";
echo "- All endpoints should return proper JSON responses\n";
echo "- Frontend can use these endpoints with proper error handling\n\n";

echo "🔧 Next Steps:\n";
echo "1. Check your .env file for proper API keys\n";
echo "2. Ensure database is properly configured\n";
echo "3. Test with real API credentials\n";
echo "4. Monitor the health endpoint for service status\n";

