<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Importing ALL Users from Multi-line SQL ===\n\n";

try {
    // First, clear existing users (delete one by one to avoid foreign key issues)
    echo "1. Clearing existing users...\n";
    $existingUsers = \App\Models\User::count();
    
    // Delete users in chunks to avoid memory issues
    \App\Models\User::chunk(100, function($users) {
        foreach($users as $user) {
            $user->delete();
        }
    });
    
    echo "   ✓ Cleared {$existingUsers} existing users\n\n";
    
    // Read the SQL file
    $sqlFile = 'database/wolrdhome_sms.sql';
    $content = file_get_contents($sqlFile);
    
    if (!$content) {
        throw new Exception("Could not read SQL file: {$sqlFile}");
    }
    
    echo "2. SQL file loaded successfully\n";
    
    // Count total INSERT statements for users
    $totalInserts = substr_count($content, 'INSERT INTO `users`');
    echo "   ✓ Found {$totalInserts} total user INSERT statements\n\n";
    
    // Split content into lines and process each INSERT statement
    $lines = explode("\n", $content);
    $importedCount = 0;
    $skippedCount = 0;
    $currentInsert = '';
    $inInsert = false;
    
    echo "3. Processing user data...\n";
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            // Start of new INSERT statement
            $currentInsert = $line;
            $inInsert = true;
        } elseif ($inInsert && strpos($line, 'VALUES') !== false) {
            // Found VALUES line, combine with INSERT line
            $currentInsert .= ' ' . $line;
            
            // Now process the complete INSERT statement
            try {
                // Extract the VALUES part
                if (preg_match('/VALUES\s*\(([^)]+)\)/', $currentInsert, $matches)) {
                    $userData = $matches[1];
                    
                    // Parse the user data (remove quotes and split by comma)
                    $userData = str_replace("'", "", $userData);
                    $fields = explode(',', $userData);
                    
                    if (count($fields) < 15) {
                        $skippedCount++;
                        $inInsert = false;
                        continue;
                    }
                    
                    // Extract the fields we need
                    $email = trim($fields[2]);
                    $password = trim($fields[3]);
                    $wallet = floatval(trim($fields[4]));
                    $holdWallet = floatval(trim($fields[5]));
                    $username = trim($fields[1]);
                    
                    // Skip if email is empty or invalid
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skippedCount++;
                        $inInsert = false;
                        continue;
                    }
                    
                    // Calculate total wallet balance
                    $totalWallet = $wallet + $holdWallet;
                    
                    // Create user
                    $user = new \App\Models\User([
                        'name' => $username ?: 'User',
                        'email' => $email,
                        'password' => $password,
                        'username' => $username ?: null,
                        'balance' => $totalWallet,
                        'wallet_balance' => $totalWallet,
                        'status' => 'active', // All users are active
                        'role' => 'user', // Default role
                        'email_verified_at' => now(), // Mark as verified
                    ]);
                    
                    $user->save();
                    $importedCount++;
                    
                    if ($importedCount % 100 == 0) {
                        echo "   Processed {$importedCount} users...\n";
                    }
                }
                
            } catch (Exception $e) {
                $skippedCount++;
            }
            
            // Reset for next INSERT statement
            $inInsert = false;
            $currentInsert = '';
        } elseif ($inInsert) {
            // Continue building the INSERT statement
            $currentInsert .= ' ' . $line;
        }
    }
    
    echo "\n=== User Import Complete ===\n";
    echo "✓ Total INSERT statements found: {$totalInserts}\n";
    echo "✓ Successfully imported: {$importedCount} users\n";
    echo "✗ Skipped: {$skippedCount} users\n";
    echo "📊 Total users in database: " . \App\Models\User::count() . "\n";
    
    // Show sample of imported users
    echo "\nSample of imported users:\n";
    $sampleUsers = \App\Models\User::select('name', 'email', 'balance', 'status')->limit(5)->get();
    foreach ($sampleUsers as $user) {
        echo "  - {$user->name} ({$user->email}) - Balance: ₦{$user->balance} - Status: {$user->status}\n";
    }
    
    // Show some statistics
    echo "\nUser Statistics:\n";
    echo "  - Users with balance > 0: " . \App\Models\User::where('balance', '>', 0)->count() . "\n";
    echo "  - Users with balance = 0: " . \App\Models\User::where('balance', '=', 0)->count() . "\n";
    echo "  - Total wallet value: ₦" . \App\Models\User::sum('balance') . "\n";
    
} catch (Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
