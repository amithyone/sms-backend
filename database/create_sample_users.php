<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Creating Sample Users ===\n\n";

try {
    // First, clear existing users
    echo "1. Clearing existing users...\n";
    $existingUsers = \App\Models\User::count();
    
    \App\Models\User::chunk(100, function($users) {
        foreach($users as $user) {
            $user->delete();
        }
    });
    
    echo "   ✓ Cleared {$existingUsers} existing users\n\n";
    
    echo "2. Creating sample users...\n";
    
    // Create sample users
    $users = [
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'username' => 'johndoe',
            'balance' => 1000.00,
            'wallet_balance' => 1000.00,
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ],
        [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
            'username' => 'janesmith',
            'balance' => 2500.00,
            'wallet_balance' => 2500.00,
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ],
        [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'username' => 'admin',
            'balance' => 5000.00,
            'wallet_balance' => 5000.00,
            'status' => 'active',
            'role' => 'admin',
            'email_verified_at' => now(),
        ],
        [
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
            'username' => 'bobwilson',
            'balance' => 750.00,
            'wallet_balance' => 750.00,
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ],
        [
            'name' => 'Alice Brown',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
            'username' => 'alicebrown',
            'balance' => 0.00,
            'wallet_balance' => 0.00,
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ],
    ];
    
    $importedCount = 0;
    foreach ($users as $userData) {
        try {
            $user = new \App\Models\User($userData);
            $user->save();
            $importedCount++;
            echo "   ✓ Created user: {$user->name} ({$user->email}) - Balance: ₦{$user->balance}\n";
        } catch (Exception $e) {
            echo "   ✗ Failed to create user: {$userData['email']} - {$e->getMessage()}\n";
        }
    }
    
    echo "\n=== Sample Users Created ===\n";
    echo "✓ Successfully created: {$importedCount} users\n";
    echo "📊 Total users in database: " . \App\Models\User::count() . "\n";
    
    // Show sample of created users
    echo "\nSample of created users:\n";
    $sampleUsers = \App\Models\User::select('name', 'email', 'balance', 'status', 'role')->limit(5)->get();
    foreach ($sampleUsers as $user) {
        echo "  - {$user->name} ({$user->email}) - Balance: ₦{$user->balance} - Status: {$user->status} - Role: {$user->role}\n";
    }
    
    // Show some statistics
    echo "\nUser Statistics:\n";
    echo "  - Users with balance > 0: " . \App\Models\User::where('balance', '>', 0)->count() . "\n";
    echo "  - Users with balance = 0: " . \App\Models\User::where('balance', '=', 0)->count() . "\n";
    echo "  - Total wallet value: ₦" . \App\Models\User::sum('balance') . "\n";
    echo "  - Admin users: " . \App\Models\User::where('role', 'admin')->count() . "\n";
    echo "  - Regular users: " . \App\Models\User::where('role', 'user')->count() . "\n";
    
} catch (Exception $e) {
    echo "Error during creation: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
