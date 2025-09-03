<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Final User Import - Direct Creation ===\n\n";

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
    $inValues = false;
    
    echo "3. Processing user data...\n";
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            // Start of new INSERT statement
            $currentInsert = $line;
            $inInsert = true;
            $inValues = false;
        } elseif ($inInsert && strpos($line, 'VALUES') !== false) {
            // Found VALUES line
            $currentInsert .= ' ' . $line;
            $inValues = true;
        } elseif ($inValues && strpos($line, '(') === 0 && strpos($line, ')') !== false) {
            // Found a VALUES row - this is a user record
            try {
                // Extract the VALUES part (remove the opening and closing parentheses)
                $userData = substr($line, 1, -1); // Remove ( and )
                
                // Parse the user data (remove quotes and split by comma)
                $userData = str_replace("'", "", $userData);
                $fields = explode(',', $userData);
                
                if (count($fields) < 15) {
                    $skippedCount++;
                    continue;
                }
                
                // Extract the fields we need
                $email = trim($fields[2]); // email is at index 2
                $password = trim($fields[3]); // password is at index 3
                $wallet = floatval(trim($fields[4])); // wallet is at index 4
                $holdWallet = floatval(trim($fields[5])); // hold_wallet is at index 5
                $username = trim($fields[1]); // username is at index 1
                
                // Skip if email is empty or invalid
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skippedCount++;
                    continue;
                }
                
                // Calculate total wallet balance
                $totalWallet = $wallet + $holdWallet;
                
                // Transform role_id to role string
                $roleId = trim($fields[6]);
                $role = ($roleId == '5') ? 'admin' : 'user';
                
                // Transform disabled to status
                $disabled = trim($fields[12]);
                $status = ($disabled == '0') ? 'active' : 'inactive';
                
                // Transform verify to email_verified_at
                $verify = trim($fields[13]);
                $emailVerifiedAt = ($verify == '2') ? now() : null;
                
                // Create user using Eloquent
                $user = new \App\Models\User([
                    'name' => $username ?: 'User',
                    'email' => $email,
                    'password' => $password,
                    'username' => $username ?: null,
                    'balance' => $totalWallet,
                    'wallet_balance' => $totalWallet,
                    'status' => $status,
                    'role' => $role,
                    'email_verified_at' => $emailVerifiedAt,
                ]);
                
                $user->save();
                $importedCount++;
                
                if ($importedCount % 100 == 0) {
                    echo "   Processed {$importedCount} users...\n";
                }
                
            } catch (Exception $e) {
                $skippedCount++;
                continue;
            }
        } elseif ($inValues && strpos($line, ');') !== false) {
            // End of INSERT statement
            $inInsert = false;
            $inValues = false;
            $currentInsert = '';
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
