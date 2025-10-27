#!/usr/bin/env php
<?php
/**
 * Test V1 Sync Connection
 * 
 * This script tests the connection from V2 (api.fadsms.com) to V1 (faddedsms.com)
 * 
 * Usage: php test-v1-connection.php
 */

echo "========================================\n";
echo "V1 Sync Connection Test\n";
echo "========================================\n\n";

// Load environment variables from .env file
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            $value = trim($value, '"\'');
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}

$envFile = __DIR__ . '/.env';
$env = loadEnv($envFile);

$v1ApiUrl = $env['V1_API_URL'] ?? '';
$v1SyncKey = $env['V1_SYNC_API_KEY'] ?? '';

echo "V1 API URL: $v1ApiUrl\n";
echo "V1 Sync Key: " . (strlen($v1SyncKey) > 0 ? substr($v1SyncKey, 0, 10) . '...' : 'NOT SET') . "\n\n";

if (empty($v1ApiUrl) || empty($v1SyncKey)) {
    die("ERROR: V1_API_URL and V1_SYNC_API_KEY must be set in .env file\n");
}

if ($v1SyncKey === 'PLEASE_GET_THIS_FROM_FADDEDSMS_COM') {
    die("ERROR: Please update V1_SYNC_API_KEY in .env with the actual API key from faddedsms.com\n\n" .
        "To get the API key from V1 (faddedsms.com), check:\n" .
        "  - SSH to faddedsms.com and check .env file for V2_SYNC_API_KEY\n" .
        "  - Check v2-sync-config.txt file on faddedsms.com\n" .
        "  - Or contact the admin of faddedsms.com\n\n");
}

echo "========================================\n";
echo "Test 1: Verify API Endpoint\n";
echo "========================================\n";

$testEmail = 'test@example.com';
$url = $v1ApiUrl . '/verify-user';

echo "Testing: $url\n";
echo "Method: POST\n";
echo "Headers: X-V2-Sync-Key\n";
echo "Payload: {\"email\":\"$testEmail\"}\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-V2-Sync-Key: ' . $v1SyncKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode(['email' => $testEmail]),
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL ERROR: $error\n";
    die("\nConnection failed. Please check:\n" .
        "1. V1 site (faddedsms.com) is accessible\n" .
        "2. V1 has V2 Sync API endpoints configured\n" .
        "3. Firewall allows outbound HTTPS connections\n");
}

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

$result = json_decode($response, true);

if ($httpCode === 401 || (isset($result['message']) && str_contains($result['message'], 'Invalid sync API key'))) {
    echo "❌ AUTHENTICATION FAILED\n\n";
    echo "The API key is invalid or doesn't match.\n\n";
    echo "Please verify on V1 site (faddedsms.com):\n";
    echo "1. Check .env file for V2_SYNC_API_KEY\n";
    echo "2. Check v2-sync-config.txt file\n";
    echo "3. Ensure V2 Sync API is properly set up\n\n";
    echo "Then update V1_SYNC_API_KEY in this V2 site's .env file\n";
    die();
}

if ($httpCode === 200) {
    echo "✅ CONNECTION SUCCESSFUL\n";
    echo "V1 API is responding correctly!\n\n";
} else {
    echo "⚠️  Unexpected response code\n\n";
}

echo "========================================\n";
echo "Test 2: Test User Lookup\n";
echo "========================================\n";

$testEmail = 'admin@example.com'; // Use a real email if you know one
$url = $v1ApiUrl . '/get-user';

echo "Testing: $url\n";
echo "Looking up user: $testEmail\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-V2-Sync-Key: ' . $v1SyncKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode(['email' => $testEmail]),
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['status'])) {
    if ($result['status']) {
        echo "✅ USER FOUND ON V1\n";
        echo "User data is accessible!\n\n";
    } else {
        echo "ℹ️  USER NOT FOUND (This is normal if the email doesn't exist)\n\n";
    }
}

echo "========================================\n";
echo "Summary\n";
echo "========================================\n\n";

if ($httpCode === 200) {
    echo "✅ V1 Sync integration is working!\n\n";
    echo "Next steps:\n";
    echo "1. Users from V1 can now log into V2 with their credentials\n";
    echo "2. Balances will be synced from V1 on login\n";
    echo "3. All transactions on V2 will update V1 balance\n\n";
    echo "Try logging in with a V1 user's credentials to test!\n";
} else {
    echo "❌ V1 Sync integration needs attention\n\n";
    echo "Please:\n";
    echo "1. Get the correct V2_SYNC_API_KEY from faddedsms.com\n";
    echo "2. Update V1_SYNC_API_KEY in /var/www/api.fadsms.com/.env\n";
    echo "3. Run this test again\n";
}

echo "\n========================================\n";
echo "Configuration Check\n";
echo "========================================\n\n";

echo "Current V2 (api.fadsms.com) Configuration:\n";
echo "  V1_API_URL: $v1ApiUrl\n";
echo "  V1_SYNC_API_KEY: " . substr($v1SyncKey, 0, 20) . "...\n\n";

echo "Required on V1 (faddedsms.com):\n";
echo "  - V2 Sync API endpoints must be configured\n";
echo "  - V2_SYNC_API_KEY must match our V1_SYNC_API_KEY\n";
echo "  - Routes: /api/v2-sync/verify-user, /api/v2-sync/get-user, etc.\n\n";

echo "Done!\n";
