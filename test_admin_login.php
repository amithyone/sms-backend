<?php
/**
 * Test Admin Login and Access
 * 
 * This script tests:
 * 1. Admin user login
 * 2. Token generation
 * 3. Admin dashboard access with token
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Admin Login & Access ===\n\n";

// Test credentials
$testUsers = [
    [
        'email' => 'admin@admin.com',
        'password' => 'password', // Change this to actual password
        'expected_role' => 'admin'
    ],
];

foreach ($testUsers as $testUser) {
    echo "Testing user: {$testUser['email']}\n";
    echo str_repeat('-', 50) . "\n";
    
    // 1. Find the user
    $user = App\Models\User::where('email', $testUser['email'])->first();
    
    if (!$user) {
        echo "❌ User not found\n\n";
        continue;
    }
    
    echo "✅ User found:\n";
    echo "   - ID: {$user->id}\n";
    echo "   - Name: {$user->name}\n";
    echo "   - Email: {$user->email}\n";
    echo "   - Role: {$user->role}\n";
    echo "   - Status: {$user->status}\n";
    echo "   - isAdmin(): " . ($user->isAdmin() ? 'true' : 'false') . "\n";
    echo "   - isSuperAdmin(): " . ($user->isSuperAdmin() ? 'true' : 'false') . "\n\n";
    
    // 2. Check if user has admin role
    if (!$user->isAdmin()) {
        echo "❌ User is not an admin\n\n";
        continue;
    }
    
    echo "✅ User has admin privileges\n\n";
    
    // 3. Generate a token for testing
    $token = $user->createToken('test_admin_token')->plainTextToken;
    echo "✅ Token generated successfully\n";
    echo "   Token: " . substr($token, 0, 30) . "...\n\n";
    
    // 4. Test token validation
    echo "Testing token validation...\n";
    $tokenParts = explode('|', $token);
    if (count($tokenParts) === 2) {
        $tokenId = $tokenParts[0];
        $tokenHash = hash('sha256', $tokenParts[1]);
        
        $personalAccessToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($personalAccessToken) {
            echo "✅ Token is valid\n";
            echo "   Token belongs to user: {$personalAccessToken->tokenable->name}\n";
            echo "   Token user role: {$personalAccessToken->tokenable->role}\n";
            echo "   Token user isAdmin(): " . ($personalAccessToken->tokenable->isAdmin() ? 'true' : 'false') . "\n\n";
        } else {
            echo "❌ Token validation failed\n\n";
        }
    }
    
    // 5. Show how to use the token
    echo "📋 To test in your browser/Postman:\n";
    echo "   Endpoint: https://api.fadsms.com/api/admin/dashboard\n";
    echo "   Method: GET\n";
    echo "   Header: Authorization: Bearer {$token}\n\n";
    
    echo "📋 Test with curl:\n";
    echo "   curl -X GET \"https://api.fadsms.com/api/admin/dashboard\" \\\n";
    echo "     -H \"Authorization: Bearer {$token}\" \\\n";
    echo "     -H \"Accept: application/json\"\n\n";
    
    // Clean up test token
    $user->tokens()->where('name', 'test_admin_token')->delete();
    echo "✅ Test token cleaned up\n\n";
}

echo "=== Test Complete ===\n";

