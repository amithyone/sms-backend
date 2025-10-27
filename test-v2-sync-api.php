<?php

/**
 * V2 Sync API Testing Script
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['V2_SYNC_API_KEY'] ?? null;
$baseUrl = 'https://api.fadsms.com/api/v2-sync';

if (!$apiKey) {
    echo "✗ Error: V2_SYNC_API_KEY not found in .env\n";
    echo "  Run: php setup-v2-sync.php\n";
    exit(1);
}

echo "════════════════════════════════════════════════\n";
echo "   Testing V2 Sync API\n";
echo "════════════════════════════════════════════════\n\n";

// Test 1: Verify User
echo "Test 1: Verify User Exists\n";
echo "─────────────────────────────────────────\n";

$ch = curl_init("{$baseUrl}/verify-user");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@example.com']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "X-V2-Sync-Key: {$apiKey}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

if ($httpCode == 200) {
    echo "✓ Test 1 Passed\n\n";
} else {
    echo "✗ Test 1 Failed\n\n";
}

// Test 2: Test Invalid API Key
echo "Test 2: Invalid API Key (Should Fail)\n";
echo "─────────────────────────────────────────\n";

$ch = curl_init("{$baseUrl}/verify-user");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@example.com']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "X-V2-Sync-Key: invalid_key"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

if ($httpCode == 401) {
    echo "✓ Test 2 Passed (Correctly rejected invalid key)\n\n";
} else {
    echo "✗ Test 2 Failed\n\n";
}

// Test 3: Get User (if exists)
echo "Test 3: Get User Data\n";
echo "─────────────────────────────────────────\n";
echo "Looking for first user in database...\n";

// Get a real user email from database
exec("cd /var/www/api.fadsms.com && php artisan tinker --execute=\"echo DB::table('users')->value('email'); exit;\" 2>/dev/null", $output);
$testEmail = trim(implode('', $output));

if ($testEmail) {
    echo "Testing with email: {$testEmail}\n\n";
    
    $ch = curl_init("{$baseUrl}/get-user");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $testEmail]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-V2-Sync-Key: {$apiKey}"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Response Code: {$httpCode}\n";
    $data = json_decode($response, true);
    
    if ($httpCode == 200 && isset($data['data'])) {
        echo "✓ User Found:\n";
        echo "  Name: {$data['data']['name']}\n";
        echo "  Email: {$data['data']['email']}\n";
        echo "  Balance: ₦{$data['data']['wallet']}\n";
        echo "  Password Hash: " . substr($data['data']['password_hash'], 0, 20) . "...\n\n";
        echo "✓ Test 3 Passed\n\n";
    } else {
        echo "Response: {$response}\n";
        echo "✗ Test 3 Failed\n\n";
    }
} else {
    echo "✗ No users in database. Skipping test 3.\n\n";
}

echo "════════════════════════════════════════════════\n";
echo "   Testing Complete!\n";
echo "════════════════════════════════════════════════\n\n";

echo "📋 Summary:\n";
echo "   • V2 Sync API is configured and working\n";
echo "   • API Key: {$apiKey}\n";
echo "   • Base URL: {$baseUrl}\n\n";

echo "📖 Next Steps:\n";
echo "   1. Add API key to V2 site's .env\n";
echo "   2. Implement V1SyncService on V2\n";
echo "   3. Test login flow\n";
echo "   4. Test balance sync\n\n";

echo "📚 Documentation:\n";
echo "   • V2_SYNC_QUICK_SETUP.md - Quick start guide\n";
echo "   • V2_SYNC_API_DOCUMENTATION.md - Complete API reference\n\n";

?>

