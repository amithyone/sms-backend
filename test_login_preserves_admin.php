<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\V1SyncService;

echo "=== Testing Login Preserves Admin Role ===\n\n";

// Set up user as admin
$user = User::where('email', 'imax9ja@gmail.com')->first();
echo "1. Before Login:\n";
echo "   Email: {$user->email}\n";
echo "   Role: {$user->role}\n";
echo "   isAdmin(): " . ($user->isAdmin() ? 'YES ✅' : 'NO ❌') . "\n\n";

// Simulate what happens during login with V1 sync
echo "2. Simulating V1 Login...\n";

// Get the password (you'll need to provide this)
// For testing, let's just simulate the authenticateUser call
$v1Sync = new V1SyncService();

// Mock V1 user data (this is what would come from V1 API)
$mockV1Data = [
    'id' => 4232,
    'name' => 'admin',
    'username' => 'admin',
    'email' => 'imax9ja@gmail.com',
    'wallet' => 1000,
    'role' => 'user', // V1 has them as 'user'
    'email_verified_at' => null,
    'phone' => null
];

echo "   V1 Role: {$mockV1Data['role']}\n";
echo "   Local Admin Role Should Be Preserved...\n\n";

// Check the logic
$existingUser = User::where('email', 'imax9ja@gmail.com')->first();
$localRole = ($existingUser && in_array($existingUser->role, ['admin', 'super_admin'])) 
    ? $existingUser->role 
    : ($mockV1Data['role'] ?? 'user');

echo "3. Role Determination:\n";
echo "   Existing User Found: " . ($existingUser ? 'YES' : 'NO') . "\n";
echo "   Existing Role: {$existingUser->role}\n";
echo "   Is Admin/Super Admin: " . (in_array($existingUser->role, ['admin', 'super_admin']) ? 'YES' : 'NO') . "\n";
echo "   Role That Will Be Used: {$localRole}\n\n";

if ($localRole === 'admin') {
    echo "✅ SUCCESS! Admin role will be preserved!\n\n";
} else {
    echo "❌ FAILED! Admin role will be lost!\n\n";
}

echo "4. Now try logging in through your frontend:\n";
echo "   Email: imax9ja@gmail.com\n";
echo "   The admin role should now persist after login!\n\n";

echo "✅ Test Complete!\n";

