<?php
/**
 * V1 API to V2 User Sync Script
 * This script syncs users from V1 (faddedsms.com) to V2 (api.fadsms.com)
 * using the V1 Sync API
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   V1 API → V2 User Sync Script\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// V1 API Configuration
$v1ApiUrl = 'https://faddedsms.com/api/v2-sync';
$v1ApiKey = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2';

echo "📋 Configuration:\n";
echo "   V1 API URL: {$v1ApiUrl}\n";
echo "   V2 Database: fadsms_api\n";
echo "\n";

// First, get a list of all users from V2 to know which emails to fetch from V1
echo "🔍 Fetching user list from V1...\n";

// Since we need a way to get all users, let's try a different approach
// We'll need to fetch users we want to sync
echo "⚠️  Note: This script will sync users as they login or as needed.\n";
echo "   For bulk migration, we need a batch endpoint on V1.\n";
echo "\n";

// Test V1 API connection
echo "🧪 Testing V1 API connection...\n";
try {
    $response = Http::timeout(10)
        ->withHeaders(['X-V2-Sync-Key' => $v1ApiKey])
        ->post($v1ApiUrl . '/verify-user', [
            'email' => 'test@example.com'
        ]);
    
    if ($response->successful()) {
        echo "✅ V1 API connection successful!\n";
        echo "   Response: " . $response->body() . "\n";
    } else {
        echo "❌ V1 API connection failed!\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Response: " . $response->body() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Failed to connect to V1 API: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   Testing User Sync\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// For testing, let's sync a few specific users
$testEmails = [
    // Add some test emails here
];

echo "Enter emails to sync (one per line, empty line to finish):\n";
while (true) {
    $email = trim(fgets(STDIN));
    if (empty($email)) break;
    $testEmails[] = $email;
}

if (empty($testEmails)) {
    echo "No emails provided. Exiting.\n";
    exit(0);
}

echo "\n📊 Will sync " . count($testEmails) . " users:\n";
foreach ($testEmails as $email) {
    echo "   • {$email}\n";
}
echo "\n";

$confirm = readline("Proceed? (yes/no): ");
if (strtolower(trim($confirm)) !== 'yes') {
    echo "❌ Sync cancelled.\n";
    exit(0);
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   Starting Sync...\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$stats = [
    'total' => count($testEmails),
    'synced' => 0,
    'created' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

foreach ($testEmails as $index => $email) {
    $progress = $index + 1;
    echo "[{$progress}/{$stats['total']}] Syncing: {$email}... ";
    
    try {
        // Get user data from V1
        $response = Http::timeout(10)
            ->withHeaders(['X-V2-Sync-Key' => $v1ApiKey])
            ->post($v1ApiUrl . '/get-user', [
                'email' => $email
            ]);
        
        if (!$response->successful()) {
            echo "❌ Failed to fetch from V1 (Status: {$response->status()})\n";
            $stats['errors']++;
            continue;
        }
        
        $result = $response->json();
        
        if (!$result['status'] || !isset($result['data'])) {
            echo "❌ User not found in V1\n";
            $stats['skipped']++;
            continue;
        }
        
        $v1UserData = $result['data'];
        
        // Check if user exists in V2
        $v2User = User::where('email', $email)->first();
        
        if ($v2User) {
            // Update existing user
            $oldBalance = $v2User->balance;
            $v2User->balance = $v1UserData['wallet'];
            $v2User->password = $v1UserData['password_hash'];
            
            if (empty($v2User->name)) {
                $v2User->name = $v1UserData['name'];
            }
            if (empty($v2User->phone)) {
                $v2User->phone = $v1UserData['phone'];
            }
            
            $v2User->save();
            
            echo "✅ Updated (Balance: ₦{$oldBalance} → ₦{$v1UserData['wallet']})\n";
            $stats['updated']++;
            
            // Log transaction
            if ($oldBalance != $v1UserData['wallet']) {
                DB::table('transactions')->insert([
                    'user_id' => $v2User->id,
                    'amount' => abs($v1UserData['wallet'] - $oldBalance),
                    'type' => $v1UserData['wallet'] > $oldBalance ? 'credit' : 'debit',
                    'method' => 200,
                    'status' => 'completed',
                    'description' => 'V1 to V2 API sync',
                    'reference' => 'V1_SYNC_' . time() . '_' . $v2User->id,
                    'metadata' => json_encode([
                        'source' => 'v1_api_sync',
                        'v1_user_id' => $v1UserData['id'],
                        'old_balance' => $oldBalance,
                        'new_balance' => $v1UserData['wallet'],
                        'synced_at' => now(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // Create new user
            $newUser = User::create([
                'name' => $v1UserData['name'] ?? 'V1 User',
                'email' => $v1UserData['email'],
                'password' => $v1UserData['password_hash'],
                'phone' => $v1UserData['phone'] ?? null,
                'balance' => $v1UserData['wallet'],
                'wallet_balance' => $v1UserData['wallet'],
                'referral_code' => \Illuminate\Support\Str::random(10),
                'role' => 'user',
                'status' => 'active',
                'created_at' => $v1UserData['created_at'] ?? now(),
            ]);
            
            echo "✅ Created (Balance: ₦{$v1UserData['wallet']})\n";
            $stats['created']++;
            
            // Log initial balance
            if ($v1UserData['wallet'] > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $newUser->id,
                    'amount' => $v1UserData['wallet'],
                    'type' => 'credit',
                    'method' => 200,
                    'status' => 'completed',
                    'description' => 'Initial balance from V1 migration',
                    'reference' => 'V1_MIGRATION_' . time() . '_' . $newUser->id,
                    'metadata' => json_encode([
                        'source' => 'v1_api_migration',
                        'v1_user_id' => $v1UserData['id'],
                        'initial_balance' => $v1UserData['wallet'],
                        'migrated_at' => now(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        $stats['synced']++;
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        $stats['errors']++;
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   Sync Complete!\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "📊 Summary:\n";
echo "   Total Processed: {$stats['total']}\n";
echo "   Successfully Synced: {$stats['synced']}\n";
echo "   Created: {$stats['created']}\n";
echo "   Updated: {$stats['updated']}\n";
echo "   Skipped: {$stats['skipped']}\n";
echo "   Errors: {$stats['errors']}\n";
echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🎉 V1 API → V2 Sync Completed!\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
