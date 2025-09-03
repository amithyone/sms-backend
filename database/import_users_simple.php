<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Simple User Import ===\n\n";

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
    
    // Extract all user INSERT statements
    $pattern = '/INSERT INTO `users`[^;]+;/s';
    preg_match_all($pattern, $content, $matches);
    
    echo "3. Extracted " . count($matches[0]) . " INSERT statements\n\n";
    
    $importedCount = 0;
    $skippedCount = 0;
    
    // Process each INSERT statement
    foreach ($matches[0] as $index => $sqlStatement) {
        try {
            echo "Processing INSERT statement " . ($index + 1) . "...\n";
            
            // Transform the SQL to match our current table structure
            $transformedSql = str_replace(
                ['`wallet`', '`hold_wallet`', '`role_id`', '`disabled`', '`verify`'],
                ['`balance`', '`wallet_balance`', '`role`', '`status`', '`email_verified_at`'],
                $sqlStatement
            );
            
            // Also transform the VALUES to match our structure
            $transformedSql = preg_replace_callback(
                '/\(([^)]+)\)/',
                function($matches) {
                    $values = explode(',', $matches[1]);
                    
                    if (count($values) >= 15) {
                        // Extract wallet and hold_wallet values
                        $wallet = floatval(trim(str_replace("'", "", $values[4])));
                        $holdWallet = floatval(trim(str_replace("'", "", $values[5])));
                        $totalBalance = $wallet + $holdWallet;
                        
                        // Transform role_id to role string
                        $roleId = trim(str_replace("'", "", $values[6]));
                        $role = ($roleId == '5') ? 'admin' : 'user';
                        
                        // Transform disabled to status
                        $disabled = trim(str_replace("'", "", $values[12]));
                        $status = ($disabled == '0') ? 'active' : 'inactive';
                        
                        // Transform verify to email_verified_at
                        $verify = trim(str_replace("'", "", $values[13]));
                        $emailVerifiedAt = ($verify == '2') ? 'NOW()' : 'NULL';
                        
                        // Build new values array
                        $newValues = [
                            $values[0], // id
                            $values[1], // username
                            $values[2], // email
                            $values[3], // password
                            $totalBalance, // balance (wallet + hold_wallet)
                            $totalBalance, // wallet_balance
                            "'{$role}'", // role
                            $values[7], // api_key
                            $values[8], // api_percentage
                            $values[9], // webhook_url
                            $values[10], // code
                            "'{$status}'", // status
                            $emailVerifiedAt, // email_verified_at
                            $values[14], // created_at
                            $values[15]  // updated_at
                        ];
                        
                        return '(' . implode(',', $newValues) . ')';
                    }
                    
                    return $matches[0];
                },
                $transformedSql
            );
            
            // Execute the transformed SQL statement
            $result = \Illuminate\Support\Facades\DB::statement($transformedSql);
            
            if ($result) {
                $importedCount++;
                echo "   ✓ Successfully imported\n";
            } else {
                $skippedCount++;
                echo "   ✗ Failed to import\n";
            }
            
            if (($index + 1) % 10 == 0) {
                echo "   Processed " . ($index + 1) . " statements...\n";
            }
            
        } catch (Exception $e) {
            $skippedCount++;
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    echo "\n=== User Import Complete ===\n";
    echo "✓ Total INSERT statements found: {$totalInserts}\n";
    echo "✓ Successfully imported: {$importedCount} statements\n";
    echo "✗ Skipped: {$skippedCount} statements\n";
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
