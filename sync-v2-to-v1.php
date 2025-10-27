<?php
/**
 * V2 to V1 User Sync Script
 * This script syncs existing users from V2 database to V1
 * - Matches users by email
 * - Updates V1 balance with V2 balance
 * - Updates V1 password with V2 password hash
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   V2 → V1 User Sync Script\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// V2 Database Configuration
echo "📋 Enter V2 Database Configuration:\n";
echo "────────────────────────────────────────\n";

$v2Host = readline("V2 Database Host (e.g., localhost): ");
$v2Database = readline("V2 Database Name: ");
$v2Username = readline("V2 Database Username: ");
$v2Password = readline("V2 Database Password: ");

echo "\n";
echo "🔌 Connecting to V2 Database...\n";

try {
    // Connect to V2 database
    $v2Connection = new PDO(
        "mysql:host={$v2Host};dbname={$v2Database}",
        $v2Username,
        $v2Password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to V2 database\n\n";
} catch (PDOException $e) {
    echo "❌ Failed to connect to V2 database: " . $e->getMessage() . "\n";
    exit(1);
}

// Fetch users from V2
echo "📊 Fetching users from V2 database...\n";
$v2UsersQuery = $v2Connection->query("
    SELECT id, email, password, balance, name, phone, created_at 
    FROM users 
    WHERE email IS NOT NULL AND email != ''
    ORDER BY id ASC
");
$v2Users = $v2UsersQuery->fetchAll(PDO::FETCH_ASSOC);

echo "✅ Found " . count($v2Users) . " users in V2 database\n\n";

if (count($v2Users) === 0) {
    echo "No users to sync. Exiting.\n";
    exit(0);
}

// Confirmation
echo "════════════════════════════════════════════════════════════════\n";
echo "⚠️  SYNC PREVIEW\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "This will sync " . count($v2Users) . " users from V2 to V1:\n";
echo "   • Match users by email\n";
echo "   • Update V1 balance with V2 balance\n";
echo "   • Update V1 password with V2 password hash\n";
echo "   • Create users if they don't exist in V1\n";
echo "\n";
echo "First 5 users to sync:\n";
foreach (array_slice($v2Users, 0, 5) as $user) {
    echo "   • {$user['email']} - Balance: ₦" . number_format($user['balance'], 2) . "\n";
}
if (count($v2Users) > 5) {
    echo "   ... and " . (count($v2Users) - 5) . " more\n";
}
echo "\n";

$confirm = readline("Do you want to proceed? (yes/no): ");
if (strtolower(trim($confirm)) !== 'yes') {
    echo "❌ Sync cancelled.\n";
    exit(0);
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "   Starting Sync Process...\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$stats = [
    'total' => count($v2Users),
    'updated' => 0,
    'created' => 0,
    'skipped' => 0,
    'errors' => 0,
];

foreach ($v2Users as $index => $v2User) {
    $progress = $index + 1;
    $email = $v2User['email'];
    
    echo "[{$progress}/{$stats['total']}] Processing: {$email}... ";
    
    try {
        // Check if user exists in V1
        $v1User = User::where('email', $email)->first();
        
        if ($v1User) {
            // User exists - update balance and password
            $oldBalance = $v1User->balance;
            $v1User->balance = $v2User['balance'];
            $v1User->password = $v2User['password']; // Already hashed
            
            // Also sync name and phone if they're empty in V1
            if (empty($v1User->name) && !empty($v2User['name'])) {
                $v1User->name = $v2User['name'];
            }
            if (empty($v1User->phone) && !empty($v2User['phone'])) {
                $v1User->phone = $v2User['phone'];
            }
            
            $v1User->save();
            
            echo "✅ Updated (Balance: ₦{$oldBalance} → ₦{$v2User['balance']})\n";
            $stats['updated']++;
            
            // Create a transaction log for the balance update
            if ($oldBalance != $v2User['balance']) {
                DB::table('transactions')->insert([
                    'user_id' => $v1User->id,
                    'amount' => abs($v2User['balance'] - $oldBalance),
                    'type' => $v2User['balance'] > $oldBalance ? 'credit' : 'debit',
                    'method' => 200, // V2 Sync method
                    'status' => 'completed',
                    'description' => 'V2 to V1 balance sync',
                    'reference' => 'V2_SYNC_' . time() . '_' . $v1User->id,
                    'metadata' => json_encode([
                        'source' => 'v2_migration',
                        'v2_user_id' => $v2User['id'],
                        'old_balance' => $oldBalance,
                        'new_balance' => $v2User['balance'],
                        'synced_at' => now(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
        } else {
            // User doesn't exist - create new user
            $newUser = User::create([
                'name' => $v2User['name'] ?? 'V2 User',
                'email' => $v2User['email'],
                'password' => $v2User['password'], // Already hashed
                'phone' => $v2User['phone'] ?? null,
                'balance' => $v2User['balance'],
                'wallet_balance' => $v2User['balance'],
                'referral_code' => \Illuminate\Support\Str::random(10),
                'role' => 'user',
                'status' => 'active',
                'created_at' => $v2User['created_at'] ?? now(),
            ]);
            
            echo "✅ Created (Balance: ₦{$v2User['balance']})\n";
            $stats['created']++;
            
            // Create initial balance transaction
            if ($v2User['balance'] > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $newUser->id,
                    'amount' => $v2User['balance'],
                    'type' => 'credit',
                    'method' => 200, // V2 Sync method
                    'status' => 'completed',
                    'description' => 'Initial balance from V2 migration',
                    'reference' => 'V2_MIGRATION_' . time() . '_' . $newUser->id,
                    'metadata' => json_encode([
                        'source' => 'v2_migration',
                        'v2_user_id' => $v2User['id'],
                        'initial_balance' => $v2User['balance'],
                        'migrated_at' => now(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
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
echo "   Total Users:    {$stats['total']}\n";
echo "   Updated:        {$stats['updated']}\n";
echo "   Created:        {$stats['created']}\n";
echo "   Errors:         {$stats['errors']}\n";
echo "\n";

// Calculate total balance synced
$totalBalanceSynced = array_sum(array_column($v2Users, 'balance'));
echo "💰 Total Balance Synced: ₦" . number_format($totalBalanceSynced, 2) . "\n";
echo "\n";

// Show what was synced
echo "✅ What was synced:\n";
echo "   • Email addresses matched\n";
echo "   • Balances updated/created\n";
echo "   • Password hashes synced\n";
echo "   • Transaction logs created\n";
echo "\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "🎉 V2 → V1 User Sync Completed Successfully!\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "📋 Next Steps:\n";
echo "   1. Check admin dashboard for synced users\n";
echo "   2. Test login with V2 credentials\n";
echo "   3. Verify balances are correct\n";
echo "\n";
echo "Admin Dashboard: https://api.fadsms.com/admin → 🔄 V2 Migration\n";
echo "\n";
